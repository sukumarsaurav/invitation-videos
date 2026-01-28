<?php
/**
 * Video Render Processor (PHP-based Orchestrator)
 * 
 * Cron job that:
 * 1. Fetches pending orders from database
 * 2. Invokes AWS Remotion Lambda to render each video
 * 3. Updates order status on completion
 * 
 * This replaces the Node.js orchestrator with a pure PHP solution.
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
require_once __DIR__ . '/../config/database.php';

use Aws\Lambda\LambdaClient;
use Aws\S3\S3Client;

// Lock file to prevent overlapping executions
$lockFile = sys_get_temp_dir() . '/video_render_processor.lock';
$lockTimeout = 600;  // 10 minutes max lock time

echo "[" . date('Y-m-d H:i:s') . "] Video Render Processor starting...\n";

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
    // Fetch pending orders
    $orders = Database::fetchAll("
        SELECT 
            o.id,
            o.order_number,
            o.template_id,
            o.customization_data,
            t.remotion_composition_id,
            t.title as template_title,
            t.slug as template_slug,
            t.default_music_url,
            t.duration_seconds,
            t.asset_base_url,
            t.background_asset
        FROM orders o
        JOIN templates t ON o.template_id = t.id
        WHERE o.order_status = 'queued'
        ORDER BY o.created_at ASC
        LIMIT 5
    ");

    if (empty($orders)) {
        echo "No pending orders.\n";
        cleanup($lockFile);
        exit(0);
    }

    echo "Found " . count($orders) . " pending order(s).\n";

    // Initialize AWS clients
    $lambdaClient = new LambdaClient([
        'version' => 'latest',
        'region' => AWS_DEFAULT_REGION,
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    // Get Remotion function name from config or env
    $functionName = defined('REMOTION_FUNCTION_NAME')
        ? REMOTION_FUNCTION_NAME
        : getenv('REMOTION_FUNCTION_NAME');

    $serveUrl = defined('REMOTION_SERVE_URL')
        ? REMOTION_SERVE_URL
        : getenv('REMOTION_SERVE_URL');

    if (empty($functionName) || empty($serveUrl)) {
        throw new Exception("REMOTION_FUNCTION_NAME and REMOTION_SERVE_URL must be configured");
    }

    echo "Using Remotion function: {$functionName}\n";

    foreach ($orders as $order) {
        processOrder($order, $lambdaClient, $functionName, $serveUrl);
    }

    echo "Processing complete.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("Video Render Processor error: " . $e->getMessage());
} finally {
    cleanup($lockFile);
}

echo "[" . date('Y-m-d H:i:s') . "] Video Render Processor finished.\n";

/**
 * Process a single order
 */
function processOrder($order, $lambdaClient, $functionName, $serveUrl)
{
    $orderId = $order['id'];
    $orderNumber = $order['order_number'];

    echo "\n--- Processing Order #{$orderNumber} ---\n";

    try {
        // Mark as processing
        Database::query(
            "UPDATE orders SET order_status = 'processing' WHERE id = ?",
            [$orderId]
        );

        // Get customization data
        $customData = json_decode($order['customization_data'] ?? '{}', true) ?: [];

        // Add uploads from order_uploads table
        $uploads = Database::fetchAll(
            "SELECT field_name, file_path, s3_url FROM order_uploads WHERE order_id = ?",
            [$orderId]
        );

        foreach ($uploads as $upload) {
            // Prefer S3 URL
            $url = !empty($upload['s3_url']) ? $upload['s3_url'] : $upload['file_path'];
            if (!str_starts_with($url, 'http')) {
                $url = 'https://invitationvideos.com' . $url;
            }
            $customData[$upload['field_name']] = $url;
        }

        // Add default music if not set
        if (empty($customData['music_url']) && !empty($order['default_music_url'])) {
            $customData['music_url'] = $order['default_music_url'];
        }

        // Add background asset if needed
        if (!empty($order['background_asset']) && empty($customData['background_url'])) {
            $baseUrl = rtrim($order['asset_base_url'] ?? '', '/');
            $customData['background_url'] = $baseUrl . '/' . $order['background_asset'];
        }

        // Determine composition ID
        $compositionId = $order['remotion_composition_id'] ?: 'FirstTemplate';

        echo "Composition: {$compositionId}\n";
        echo "Props: " . json_encode($customData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        // Map props for the composition
        $inputProps = mapPropsForComposition($compositionId, $customData, $order);

        // Invoke Remotion Lambda via renderMediaOnLambda
        // We need to call the Lambda function with the render parameters
        $renderPayload = [
            'type' => 'start',
            'serveUrl' => $serveUrl,
            'composition' => $compositionId,
            'codec' => 'h264',
            'inputProps' => $inputProps,
            'outName' => "orders/{$orderNumber}/video.mp4",
            'jpegQuality' => 80,
            'privacy' => 'public',
        ];

        echo "Invoking Remotion Lambda...\n";

        $result = $lambdaClient->invoke([
            'FunctionName' => $functionName,
            'InvocationType' => 'RequestResponse',
            'Payload' => json_encode($renderPayload),
        ]);

        $response = json_decode($result['Payload']->getContents(), true);

        if (isset($response['outputFile'])) {
            $videoUrl = $response['outputFile'];

            // Update order as completed
            Database::query(
                "UPDATE orders SET order_status = 'completed', output_video_url = ? WHERE id = ?",
                [$videoUrl, $orderId]
            );

            echo "✅ Completed! Video: {$videoUrl}\n";

            // Send completion email
            sendCompletionEmail($orderId, $videoUrl);

        } elseif (isset($response['renderId'])) {
            // Render started but not complete - need to poll
            $renderId = $response['renderId'];
            $bucketName = $response['bucketName'];

            echo "Render started: {$renderId}. Polling for completion...\n";

            $videoUrl = pollForCompletion($lambdaClient, $functionName, $renderId, $bucketName);

            if ($videoUrl) {
                Database::query(
                    "UPDATE orders SET order_status = 'completed', output_video_url = ? WHERE id = ?",
                    [$videoUrl, $orderId]
                );
                echo "✅ Completed! Video: {$videoUrl}\n";
                sendCompletionEmail($orderId, $videoUrl);
            } else {
                throw new Exception("Polling timed out");
            }

        } else {
            throw new Exception("Unexpected response: " . json_encode($response));
        }

    } catch (Exception $e) {
        echo "❌ Failed: " . $e->getMessage() . "\n";
        error_log("Render failed for order #{$orderNumber}: " . $e->getMessage());

        Database::query(
            "UPDATE orders SET order_status = 'failed' WHERE id = ?",
            [$orderId]
        );
    }
}

/**
 * Map customization data to composition-specific props
 */
function mapPropsForComposition($compositionId, $customData, $order)
{
    // FirstTemplate props
    if ($compositionId === 'FirstTemplate') {
        return [
            'groomName' => $customData['groom_name'] ?? '',
            'brideName' => $customData['bride_name'] ?? '',
            'title' => $customData['title'] ?? "You're Invited",
            'subtitle' => $customData['subtitle'] ?? '',
            'eventName' => $customData['event_name'] ?? $customData['eventName'] ?? '',
            'eventDate' => $customData['wedding_date'] ?? $customData['event_date'] ?? '',
            'eventTime' => $customData['event_time'] ?? '',
            'eventVenue' => $customData['venue_name'] ?? '',
            'venueAddress' => $customData['venue_address'] ?? '',
            'couplePhotoUrl' => $customData['couple_photo'] ?? '',
            'musicUrl' => $customData['music_url'] ?? $order['default_music_url'] ?? '',
            'backgroundVideoUrl' => $customData['background_url'] ?? '',
            'primaryColor' => $customData['primary_color'] ?? '#FFD700',
            'secondaryColor' => $customData['secondary_color'] ?? '#FFFFFF',
        ];
    }

    // DiyaDelight and similar templates
    if (str_contains($compositionId, 'Diya') || str_contains($compositionId, 'Diwali')) {
        return [
            'hostName' => $customData['host_name'] ?? '',
            'eventDate' => $customData['event_date'] ?? '',
            'eventTime' => $customData['event_time'] ?? '',
            'eventVenue' => $customData['venue_name'] ?? '',
            'venueAddress' => $customData['venue_address'] ?? '',
            'message' => $customData['message'] ?? '',
            'musicUrl' => $customData['music_url'] ?? $order['default_music_url'] ?? '',
        ];
    }

    // Default generic mapping
    return [
        'groomName' => $customData['groom_name'] ?? '',
        'brideName' => $customData['bride_name'] ?? '',
        'eventDate' => $customData['event_date'] ?? $customData['wedding_date'] ?? '',
        'eventTime' => $customData['event_time'] ?? '',
        'eventVenue' => $customData['venue_name'] ?? $customData['venue'] ?? '',
        'musicUrl' => $customData['music_url'] ?? $order['default_music_url'] ?? '',
        'couplePhotoUrl' => $customData['couple_photo'] ?? '',
        'backgroundVideoUrl' => $customData['background_url'] ?? '',
    ];
}

/**
 * Poll for render completion
 */
function pollForCompletion($lambdaClient, $functionName, $renderId, $bucketName, $maxAttempts = 60)
{
    for ($i = 0; $i < $maxAttempts; $i++) {
        sleep(5);

        $result = $lambdaClient->invoke([
            'FunctionName' => $functionName,
            'Payload' => json_encode([
                'type' => 'progress',
                'renderId' => $renderId,
                'bucketName' => $bucketName,
            ]),
        ]);

        $progress = json_decode($result['Payload']->getContents(), true);

        if (!empty($progress['done'])) {
            return $progress['outputFile'] ?? null;
        }

        if (!empty($progress['fatalErrorEncountered'])) {
            throw new Exception($progress['errors'][0]['message'] ?? 'Render failed');
        }

        $pct = round(($progress['overallProgress'] ?? 0) * 100);
        echo "Progress: {$pct}%\n";
    }

    return null;
}

/**
 * Send completion email
 */
function sendCompletionEmail($orderId, $videoUrl)
{
    try {
        require_once __DIR__ . '/../src/Services/EmailService.php';

        $order = Database::fetchOne("
            SELECT o.*, t.title as template_title, u.email, u.name
            FROM orders o
            JOIN templates t ON o.template_id = t.id
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ", [$orderId]);

        if ($order) {
            $user = ['email' => $order['email'], 'name' => $order['name']];
            $orderData = array_merge($order, ['output_video_url' => $videoUrl]);
            \InvitationVideos\Services\EmailService::sendOrderCompletedEmail($orderData, $user);
            echo "Email sent to: {$order['email']}\n";
        }
    } catch (Exception $e) {
        error_log("Failed to send completion email for order #{$orderId}: " . $e->getMessage());
    }
}

/**
 * Cleanup lock file
 */
function cleanup($lockFile)
{
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
