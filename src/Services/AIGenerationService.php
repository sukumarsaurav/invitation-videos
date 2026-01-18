<?php
/**
 * AI Generation Service
 * 
 * Main orchestrator for AI image generation.
 * Handles queuing, processing, and managing AI generation jobs.
 */

namespace InvitationVideos\Services;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AI/AIProviderInterface.php';
require_once __DIR__ . '/AI/OpenAIImageService.php';

use InvitationVideos\Services\AI\AIProviderInterface;
use InvitationVideos\Services\AI\OpenAIImageService;

class AIGenerationService
{
    private AIProviderInterface $provider;

    public function __construct(?string $providerName = null)
    {
        $this->provider = $this->initializeProvider($providerName);
    }

    /**
     * Initialize the AI provider based on configuration
     */
    private function initializeProvider(?string $providerName): AIProviderInterface
    {
        // Get configured provider from settings if not specified
        if (!$providerName) {
            $providerName = $this->getConfiguredProvider();
        }

        // Currently only OpenAI is implemented
        // Add more providers here as they are implemented
        return match ($providerName) {
            'openai' => new OpenAIImageService(),
            // 'stability' => new StabilityAIService(),
            // 'replicate' => new ReplicateService(),
            // 'fal' => new FalAIService(),
            default => new OpenAIImageService()
        };
    }

    /**
     * Get the configured AI provider from settings
     */
    private function getConfiguredProvider(): string
    {
        $setting = \Database::fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'ai_image_provider'"
        );
        return $setting['setting_value'] ?? 'openai';
    }

    /**
     * Check if AI generation is enabled and configured
     */
    public function isEnabled(): bool
    {
        $enabled = \Database::fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'ai_generation_enabled'"
        );

        return ($enabled['setting_value'] ?? '0') === '1' && $this->provider->isConfigured();
    }

    /**
     * Queue an AI generation job for an order
     * 
     * @param int $orderId The order ID
     * @param int $dressId Selected dress design ID
     * @param int|null $colorId Selected color ID (optional)
     * @param string $originalImageUrl URL of the user's uploaded photo
     * @return int Queue item ID
     */
    public function queueGeneration(int $orderId, int $dressId, ?int $colorId, string $originalImageUrl): int
    {
        $prompt = $this->getPromptForDressColor($dressId, $colorId);

        \Database::query(
            "INSERT INTO ai_generation_queue 
             (order_id, original_image_url, dress_id, color_id, prompt_used, status, ai_provider, cost_cents) 
             VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)",
            [
                $orderId,
                $originalImageUrl,
                $dressId,
                $colorId,
                $prompt,
                $this->provider->getProviderName(),
                $this->provider->getEstimatedCostCents()
            ]
        );

        return (int) \Database::lastInsertId();
    }

    /**
     * Process a specific queue item
     * 
     * @param int $queueId Queue item ID
     * @return bool True if generation succeeded
     */
    public function processQueueItem(int $queueId): bool
    {
        // Get the queue item
        $item = \Database::fetchOne(
            "SELECT * FROM ai_generation_queue WHERE id = ? AND status IN ('pending', 'failed')",
            [$queueId]
        );

        if (!$item) {
            error_log("AIGenerationService: Queue item {$queueId} not found or not processable");
            return false;
        }

        // Check max attempts
        if ($item['attempts'] >= $item['max_attempts']) {
            error_log("AIGenerationService: Queue item {$queueId} exceeded max attempts");
            return false;
        }

        // Mark as processing
        \Database::query(
            "UPDATE ai_generation_queue 
             SET status = 'processing', processing_started_at = NOW(), attempts = attempts + 1 
             WHERE id = ?",
            [$queueId]
        );

        try {
            error_log("AIGenerationService: Processing queue item {$queueId}, attempt " . ($item['attempts'] + 1));

            // Call the AI provider
            $result = $this->provider->generateImage(
                $item['prompt_used'],
                $item['original_image_url']
            );

            if ($result['success']) {
                // Download and store the image locally
                $localUrl = $this->storeGeneratedImage($result['image_url'], $item['order_id']);

                // Update queue item as completed
                \Database::query(
                    "UPDATE ai_generation_queue 
                     SET status = 'completed', generated_image_url = ?, ai_job_id = ?, completed_at = NOW() 
                     WHERE id = ?",
                    [$localUrl, $result['job_id'], $queueId]
                );

                // Update the order's customization data with the generated image
                $this->updateOrderWithGeneratedImage($item['order_id'], $localUrl);

                error_log("AIGenerationService: Queue item {$queueId} completed successfully");
                return true;

            } else {
                // Mark as failed
                \Database::query(
                    "UPDATE ai_generation_queue SET status = 'failed', error_message = ? WHERE id = ?",
                    [$result['error'], $queueId]
                );

                error_log("AIGenerationService: Queue item {$queueId} failed: " . $result['error']);
                return false;
            }

        } catch (\Exception $e) {
            // Mark as failed with exception message
            \Database::query(
                "UPDATE ai_generation_queue SET status = 'failed', error_message = ? WHERE id = ?",
                ['Exception: ' . $e->getMessage(), $queueId]
            );

            error_log("AIGenerationService: Queue item {$queueId} exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process all pending queue items (for cron job)
     * 
     * @param int $limit Maximum items to process in one run
     * @return array ['processed' => int, 'succeeded' => int, 'failed' => int]
     */
    public function processQueue(int $limit = 5): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'failed' => 0];

        // Get pending items, ordered by creation time
        $items = \Database::fetchAll(
            "SELECT id FROM ai_generation_queue 
             WHERE status IN ('pending', 'failed') 
             AND attempts < max_attempts
             ORDER BY created_at ASC
             LIMIT ?",
            [$limit]
        );

        foreach ($items as $item) {
            $stats['processed']++;

            if ($this->processQueueItem($item['id'])) {
                $stats['succeeded']++;
            } else {
                $stats['failed']++;
            }

            // Brief delay between API calls to avoid rate limiting
            if ($stats['processed'] < count($items)) {
                sleep(2);
            }
        }

        return $stats;
    }

    /**
     * Get the prompt for a dress+color combination
     */
    private function getPromptForDressColor(int $dressId, ?int $colorId): string
    {
        // First try to get prompt for specific dress+color
        $prompt = \Database::fetchOne(
            "SELECT prompt_text FROM dress_ai_prompts 
             WHERE dress_id = ? AND color_id = ?",
            [$dressId, $colorId]
        );

        if ($prompt && !empty($prompt['prompt_text'])) {
            return $prompt['prompt_text'];
        }

        // Fallback to dress default prompt (color_id IS NULL)
        $prompt = \Database::fetchOne(
            "SELECT prompt_text FROM dress_ai_prompts 
             WHERE dress_id = ? AND color_id IS NULL",
            [$dressId]
        );

        if ($prompt && !empty($prompt['prompt_text'])) {
            return $prompt['prompt_text'];
        }

        // Fallback to a generic prompt with dress name
        $dress = \Database::fetchOne(
            "SELECT name, description FROM dress_designs WHERE id = ?",
            [$dressId]
        );

        $dressName = $dress['name'] ?? 'elegant traditional attire';

        // Get color name if available
        $colorName = '';
        if ($colorId) {
            $color = \Database::fetchOne(
                "SELECT name FROM dress_colors WHERE id = ?",
                [$colorId]
            );
            $colorName = $color['name'] ?? '';
        }

        // Build a generic prompt
        $prompt = "The couple wearing {$dressName}";
        if ($colorName) {
            $prompt .= " in {$colorName} color";
        }
        $prompt .= ". Traditional Indian wedding style with ornate details and jewelry.";

        return $prompt;
    }

    /**
     * Download and store the generated image locally
     */
    private function storeGeneratedImage(string $remoteUrl, int $orderId): string
    {
        $generatedDir = UPLOAD_PATH . 'generated/';

        // Create directory if it doesn't exist
        if (!is_dir($generatedDir)) {
            mkdir($generatedDir, 0755, true);
        }

        // Download the image
        $imageData = @file_get_contents($remoteUrl);

        if ($imageData === false) {
            throw new \Exception("Failed to download generated image from: {$remoteUrl}");
        }

        // Determine file extension from content
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        $extension = match ($mimeType) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png'
        };

        $filename = 'ai_' . $orderId . '_' . time() . '.' . $extension;
        $localPath = $generatedDir . $filename;

        // Save the file
        if (file_put_contents($localPath, $imageData) === false) {
            throw new \Exception("Failed to save generated image to: {$localPath}");
        }

        // Return the web-accessible URL
        return '/uploads/generated/' . $filename;
    }

    /**
     * Update order's customization data with the generated image URL
     */
    private function updateOrderWithGeneratedImage(int $orderId, string $imageUrl): void
    {
        $order = \Database::fetchOne(
            "SELECT customization_data FROM orders WHERE id = ?",
            [$orderId]
        );

        if (!$order) {
            error_log("AIGenerationService: Order {$orderId} not found for image update");
            return;
        }

        $data = json_decode($order['customization_data'], true) ?? [];
        $data['ai_generated_image'] = $imageUrl;

        \Database::query(
            "UPDATE orders SET customization_data = ? WHERE id = ?",
            [json_encode($data), $orderId]
        );

        error_log("AIGenerationService: Updated order {$orderId} with generated image: {$imageUrl}");
    }

    /**
     * Get queue status for an order
     */
    public function getQueueStatusForOrder(int $orderId): ?array
    {
        return \Database::fetchOne(
            "SELECT id, status, generated_image_url, error_message, attempts, created_at, completed_at 
             FROM ai_generation_queue 
             WHERE order_id = ? 
             ORDER BY created_at DESC 
             LIMIT 1",
            [$orderId]
        );
    }

    /**
     * Get all queue items (for admin)
     */
    public function getQueueItems(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT q.*, o.order_number, d.name as dress_name, c.name as color_name
                FROM ai_generation_queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN dress_designs d ON q.dress_id = d.id
                LEFT JOIN dress_colors c ON q.color_id = c.id";

        $params = [];

        if ($status) {
            $sql .= " WHERE q.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY q.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return \Database::fetchAll($sql, $params);
    }

    /**
     * Retry a failed queue item
     */
    public function retryQueueItem(int $queueId): bool
    {
        // Reset status to pending
        \Database::query(
            "UPDATE ai_generation_queue SET status = 'pending', error_message = NULL WHERE id = ? AND status = 'failed'",
            [$queueId]
        );

        return $this->processQueueItem($queueId);
    }

    /**
     * Get the current provider
     */
    public function getProvider(): AIProviderInterface
    {
        return $this->provider;
    }
}
