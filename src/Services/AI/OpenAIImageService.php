<?php
/**
 * OpenAI Image Service
 * 
 * Implementation of AIProviderInterface using OpenAI DALL-E API.
 * Supports DALL-E 3 and DALL-E 2 models.
 */

namespace InvitationVideos\Services\AI;

require_once __DIR__ . '/../../../config/database.php';

class OpenAIImageService implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.openai.com/v1/images/generations';

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Load configuration from database settings
     */
    private function loadConfig(): void
    {
        // Try to get API key from settings first
        $settings = \Database::fetchAll(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('ai_openai_api_key', 'ai_openai_model')"
        );

        $config = [];
        foreach ($settings as $setting) {
            $config[$setting['setting_key']] = $setting['setting_value'];
        }

        // Fallback to environment variable if not in database
        $this->apiKey = $config['ai_openai_api_key'] ?? getenv('OPENAI_API_KEY') ?: '';
        $this->model = $config['ai_openai_model'] ?? 'dall-e-3';
    }

    /**
     * Generate an image using OpenAI DALL-E
     * 
     * @param string $prompt The generation prompt
     * @param string|null $referenceImageUrl Not used by DALL-E (no image-to-image in generations endpoint)
     * @param array $options [
     *     'model' => 'dall-e-3' | 'dall-e-2',
     *     'size' => '1024x1024' | '1024x1792' | '1792x1024',
     *     'quality' => 'standard' | 'hd',
     *     'style' => 'vivid' | 'natural'
     * ]
     */
    public function generateImage(string $prompt, ?string $referenceImageUrl = null, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'image_url' => null,
                'job_id' => null,
                'error' => 'OpenAI API key is not configured'
            ];
        }

        // Build the enhanced prompt for caricature style
        $enhancedPrompt = $this->buildCaricaturePrompt($prompt);

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'prompt' => $enhancedPrompt,
            'n' => 1,
            'size' => $options['size'] ?? '1024x1024',
            'response_format' => 'url'
        ];

        // DALL-E 3 specific options
        if (($payload['model'] === 'dall-e-3')) {
            $payload['quality'] = $options['quality'] ?? 'standard';
            $payload['style'] = $options['style'] ?? 'vivid';
        }

        try {
            $response = $this->callApi($payload);

            if (isset($response['data'][0]['url'])) {
                return [
                    'success' => true,
                    'image_url' => $response['data'][0]['url'],
                    'job_id' => 'openai_' . uniqid(),
                    'error' => null
                ];
            }

            // Handle error response
            $errorMessage = $response['error']['message'] ?? 'Unknown error from OpenAI';

            return [
                'success' => false,
                'image_url' => null,
                'job_id' => null,
                'error' => $errorMessage
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'image_url' => null,
                'job_id' => null,
                'error' => 'API call failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build an enhanced prompt optimized for caricature-style wedding illustrations
     */
    private function buildCaricaturePrompt(string $basePrompt): string
    {
        $prefix = "Create a beautiful, high-quality cartoon caricature illustration of an Indian couple. ";
        $style = "Style: Warm, colorful, Pixar-like 3D cartoon with soft lighting and festive mood. ";
        $suffix = " The illustration should be suitable for a wedding invitation video. Professional quality, celebratory atmosphere, intricate details on clothing and jewelry.";

        return $prefix . $style . $basePrompt . $suffix;
    }

    /**
     * Make the API call to OpenAI
     */
    private function callApi(array $payload): array
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 120, // 2 minute timeout for image generation
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("cURL error: {$curlError}");
        }

        $decoded = json_decode($response, true);

        if ($decoded === null) {
            throw new \Exception("Invalid JSON response from OpenAI");
        }

        // Log for debugging (remove in production)
        if ($httpCode !== 200) {
            error_log("OpenAI API error (HTTP {$httpCode}): " . $response);
        }

        return $decoded;
    }

    /**
     * Check job status - OpenAI DALL-E is synchronous, so always returns completed
     */
    public function checkJobStatus(string $jobId): array
    {
        // OpenAI image generation is synchronous - no job status to check
        return [
            'status' => 'completed',
            'image_url' => null,
            'error' => null
        ];
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'openai';
    }

    /**
     * Check if configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && strlen($this->apiKey) > 10;
    }

    /**
     * Get estimated cost - DALL-E 3 standard is ~$0.04, HD is ~$0.08
     */
    public function getEstimatedCostCents(): int
    {
        return $this->model === 'dall-e-3' ? 8 : 4;
    }

    /**
     * Get the current model being used
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
