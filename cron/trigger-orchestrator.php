<?php
/**
 * Trigger AWS Lambda Orchestrator
 * 
 * Cron job to process pending video render orders.
 * Calls the Node.js orchestrator which handles AWS Lambda rendering.
 * 
 * Usage: Run every 2 minutes via cron
 * Cron: 0,2,4,... * * * * php trigger-orchestrator.php >> /var/log/video-render.log 2>&1
 * 
 * For testing: php cron/trigger-orchestrator.php
 */

// Ensure CLI only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Configuration
$awsRendererPath = '/path/to/aws-renderer';  // Update this on server
$lockFile = sys_get_temp_dir() . '/video_orchestrator.lock';
$lockTimeout = 600;  // 10 minutes max lock time (videos can take a while)

echo "[" . date('Y-m-d H:i:s') . "] Video Render Orchestrator starting...\n";

// Check if aws-renderer path exists (for local dev, auto-detect)
if (!is_dir($awsRendererPath)) {
    // Try relative path for local development
    $localPath = dirname(__DIR__) . '/../aws-renderer';
    if (is_dir($localPath)) {
        $awsRendererPath = realpath($localPath);
        echo "Using local aws-renderer path: $awsRendererPath\n";
    } else {
        echo "ERROR: aws-renderer directory not found. Update \$awsRendererPath in this script.\n";
        exit(1);
    }
}

// Prevent multiple instances running simultaneously
if (file_exists($lockFile)) {
    $lockTime = (int) file_get_contents($lockFile);

    if (time() - $lockTime < $lockTimeout) {
        echo "Another instance is already running (locked for " . (time() - $lockTime) . "s). Exiting.\n";
        exit(0);
    }

    echo "Stale lock file found, removing...\n";
}

// Create lock file
file_put_contents($lockFile, time());

try {
    // Check if .env exists
    if (!file_exists("$awsRendererPath/.env")) {
        throw new Exception(".env file not found in aws-renderer. Copy .env.example to .env and configure.");
    }

    // Run the orchestrator
    $command = "cd " . escapeshellarg($awsRendererPath) . " && /bin/bash scripts/trigger.sh 2>&1";

    echo "Running: $command\n";
    echo "---\n";

    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    echo implode("\n", $output) . "\n";
    echo "---\n";

    if ($returnCode !== 0) {
        throw new Exception("Orchestrator exited with code $returnCode");
    }

    echo "Orchestrator completed successfully.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Video Render Orchestrator error: " . $e->getMessage());

} finally {
    // Remove lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Video Render Orchestrator finished.\n";
