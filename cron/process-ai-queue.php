<?php
/**
 * AI Queue Processor
 * 
 * Cron job to process pending AI generation queue items.
 * 
 * Usage: Run every minute via cron
 * * * * * * php /path/to/cron/process-ai-queue.php >> /var/log/ai-queue.log 2>&1
 */

// Ensure CLI only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/AIGenerationService.php';

use InvitationVideos\Services\AIGenerationService;

// Configuration
$maxItemsPerRun = 5;  // Process up to 5 items per cron run
$lockFile = sys_get_temp_dir() . '/ai_queue_processor.lock';
$lockTimeout = 300;   // 5 minutes max lock time

echo "[" . date('Y-m-d H:i:s') . "] AI Queue Processor starting...\n";

// Prevent multiple instances running simultaneously
if (file_exists($lockFile)) {
    $lockTime = (int) file_get_contents($lockFile);

    if (time() - $lockTime < $lockTimeout) {
        echo "Another instance is already running. Exiting.\n";
        exit(0);
    }

    echo "Stale lock file found, removing...\n";
}

// Create lock file
file_put_contents($lockFile, time());

try {
    // Check if AI generation is enabled
    $aiService = new AIGenerationService();

    if (!$aiService->isEnabled()) {
        echo "AI generation is disabled or not configured. Exiting.\n";
        unlink($lockFile);
        exit(0);
    }

    // Process the queue
    $stats = $aiService->processQueue($maxItemsPerRun);

    echo "Processing complete:\n";
    echo "  - Processed: {$stats['processed']}\n";
    echo "  - Succeeded: {$stats['succeeded']}\n";
    echo "  - Failed: {$stats['failed']}\n";

    // If nothing was processed, check queue status
    if ($stats['processed'] === 0) {
        $pendingCount = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM ai_generation_queue WHERE status = 'pending'"
        );
        $failedCount = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM ai_generation_queue WHERE status = 'failed' AND attempts < max_attempts"
        );

        echo "Queue status: " . ($pendingCount['cnt'] ?? 0) . " pending, " . ($failedCount['cnt'] ?? 0) . " retryable\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("AI Queue Processor error: " . $e->getMessage());

} finally {
    // Remove lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] AI Queue Processor finished.\n";
