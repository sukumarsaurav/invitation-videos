<?php
/**
 * Trigger AWS Lambda Orchestrator
 * 
 * Cron job that invokes the video-orchestrator Lambda function.
 * The orchestrator handles fetching pending orders and triggering renders.
 * 
 * Usage: Run every 2 minutes via Hostinger cron
 */

// Ensure CLI only  
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Load configuration
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use Aws\Lambda\LambdaClient;

// Lock file to prevent overlapping executions
$lockFile = sys_get_temp_dir() . '/video_orchestrator.lock';
$lockTimeout = 600;  // 10 minutes max lock time

echo "[" . date('Y-m-d H:i:s') . "] Video Render Trigger starting...\n";

// Prevent multiple instances
if (file_exists($lockFile)) {
    $lockTime = (int) file_get_contents($lockFile);
    if (time() - $lockTime < $lockTimeout) {
        echo "Another instance is already running. Exiting.\n";
        exit(0);
    }
    echo "Stale lock file found, removing...\n";
}

file_put_contents($lockFile, time());

try {
    // Quick check for pending orders (avoid invoking Lambda unnecessarily)
    $pendingCount = Database::fetchOne(
        "SELECT COUNT(*) as count FROM orders WHERE order_status = 'queued'"
    );

    if (!$pendingCount || (int) $pendingCount['count'] === 0) {
        echo "No pending orders. Skipping Lambda invocation.\n";
        cleanup($lockFile);
        exit(0);
    }

    echo "Found {$pendingCount['count']} pending order(s). Invoking orchestrator...\n";

    // Get orchestrator function name
    $orchestratorFunction = defined('LAMBDA_ORCHESTRATOR_FUNCTION')
        ? LAMBDA_ORCHESTRATOR_FUNCTION
        : 'video-orchestrator';

    // Invoke the orchestrator Lambda
    $lambdaClient = new LambdaClient([
        'version' => 'latest',
        'region' => defined('AWS_DEFAULT_REGION') ? AWS_DEFAULT_REGION : 'us-east-1',
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    echo "Invoking Lambda: {$orchestratorFunction}\n";

    $result = $lambdaClient->invoke([
        'FunctionName' => $orchestratorFunction,
        'InvocationType' => 'Event',  // Async - don't wait for completion
        'Payload' => json_encode([
            'source' => 'cron',
            'timestamp' => time()
        ]),
    ]);

    $statusCode = $result['StatusCode'];

    if ($statusCode === 202) {
        echo "✅ Orchestrator invoked successfully (async).\n";
    } else {
        echo "Unexpected status code: {$statusCode}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Video Render Trigger error: " . $e->getMessage());
} finally {
    cleanup($lockFile);
}

echo "[" . date('Y-m-d H:i:s') . "] Video Render Trigger finished.\n";

function cleanup($lockFile)
{
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
