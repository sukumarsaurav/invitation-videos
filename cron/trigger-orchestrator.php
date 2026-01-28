<?php
/**
 * Trigger AWS Lambda Orchestrator
 * 
 * Cron job to trigger video rendering via AWS Lambda.
 * Invokes the Lambda orchestrator function which fetches pending orders 
 * from the PHP backend and renders them.
 * 
 * Usage: Run every 2 minutes via cron
 */

// Ensure CLI only  
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Load configuration
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

// Lock file to prevent overlapping executions
$lockFile = sys_get_temp_dir() . '/video_orchestrator.lock';
$lockTimeout = 600;  // 10 minutes max lock time

echo "[" . date('Y-m-d H:i:s') . "] Video Render Orchestrator starting...\n";

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
    // Check for pending orders first (avoid invoking Lambda unnecessarily)
    require_once __DIR__ . '/../config/database.php';

    $pendingCount = Database::fetchOne(
        "SELECT COUNT(*) as count FROM orders WHERE order_status = 'queued'"
    );

    if (!$pendingCount || (int) $pendingCount['count'] === 0) {
        echo "No pending orders. Skipping Lambda invocation.\n";
        if (file_exists($lockFile))
            unlink($lockFile);
        exit(0);
    }

    echo "Found {$pendingCount['count']} pending order(s). Invoking Lambda orchestrator...\n";

    // Invoke the Lambda orchestrator function
    $lambdaClient = new Aws\Lambda\LambdaClient([
        'version' => 'latest',
        'region' => AWS_DEFAULT_REGION,
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    // The orchestrator function name - should match what's deployed
    // Format: remotion-render-{version}-mem{memory}mb-disk{disk}mb-{timeout}sec
    $orchestratorFunctionName = defined('LAMBDA_ORCHESTRATOR_FUNCTION')
        ? LAMBDA_ORCHESTRATOR_FUNCTION
        : 'video-orchestrator';  // Fallback name if not defined

    echo "Invoking Lambda function: {$orchestratorFunctionName}\n";

    $result = $lambdaClient->invoke([
        'FunctionName' => $orchestratorFunctionName,
        'InvocationType' => 'Event',  // Async invocation
        'Payload' => json_encode([
            'source' => 'cron',
            'timestamp' => time()
        ]),
    ]);

    $statusCode = $result['StatusCode'];

    if ($statusCode === 202) {
        echo "Lambda invoked successfully (async). Status: 202 Accepted\n";
    } else {
        echo "Lambda invocation returned status: {$statusCode}\n";
        // For synchronous invocation, we could read the response
        if (isset($result['Payload'])) {
            $payload = json_decode($result['Payload']->getContents(), true);
            echo "Response: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
        }
    }

    echo "Orchestrator trigger completed successfully.\n";

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
