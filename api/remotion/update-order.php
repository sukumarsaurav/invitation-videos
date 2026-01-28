<?php
/**
 * Update Order API
 * 
 * Called by: AWS Lambda Orchestrator
 * Method: POST
 * Body: { "order_id": 123, "status": "completed", "video_url": "..." }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_auth_helper.php';

// Verify authentication
if (!verifyRemotionAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? null;
$status = $input['status'] ?? null;

if (!$orderId || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    if ($status === 'processing') {
        Database::query("UPDATE orders SET order_status = 'processing' WHERE id = ?", [$orderId]);
    } elseif ($status === 'completed') {
        $s3VideoUrl = $input['video_url'] ?? null;
        if (!$s3VideoUrl) {
            throw new Exception("Video URL required for completed status");
        }

        // Download from S3 and save to Hostinger
        $orderNumber = $order['order_number'];
        $localVideoPath = downloadAndSaveVideo($s3VideoUrl, $orderNumber);

        if (!$localVideoPath) {
            throw new Exception("Failed to download and save video from S3");
        }

        // Generate Hostinger URL
        $hostingerUrl = APP_URL . '/uploads/orders/' . $orderNumber . '/video.mp4';

        // Calculate expiry (7 days)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        Database::query("
            UPDATE orders 
            SET order_status = 'completed',
                output_video_url = ?,
                video_uploaded_at = NOW(),
                video_expires_at = ?,
                completed_at = NOW()
            WHERE id = ?
        ", [$hostingerUrl, $expiresAt, $orderId]);

        error_log("[Update Order] Video saved to Hostinger: $hostingerUrl (S3 source: $s3VideoUrl)");

        // Send email with Hostinger URL
        sendCompletionEmail($orderId, $hostingerUrl);
    } elseif ($status === 'failed') {
        $error = $input['error'] ?? 'Unknown error';
        error_log("Render failed for order #{$order['order_number']}: $error");

        // Mark as failed but maybe don't expose full error to user yet?
        // Or keep as processing/queued to retry?
        // For now, mark as failed so admin can intervene.
        Database::query("UPDATE orders SET order_status = 'failed' WHERE id = ?", [$orderId]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Update order failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Send completion email
 */
function sendCompletionEmail($orderId, $videoUrl)
{
    try {
        error_log("[sendCompletionEmail] Starting for order ID: $orderId, video URL: $videoUrl");

        require_once __DIR__ . '/../../src/Services/EmailService.php';

        $orderDetails = Database::fetchOne("
            SELECT o.*, t.title as template_title, u.email, u.name
            FROM orders o
            JOIN templates t ON o.template_id = t.id
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ", [$orderId]);

        if ($orderDetails) {
            error_log("[sendCompletionEmail] Found order details, user email: " . $orderDetails['email']);

            $user = ['email' => $orderDetails['email'], 'name' => $orderDetails['name']];
            $order = array_merge($orderDetails, ['output_video_url' => $videoUrl]);

            $result = \InvitationVideos\Services\EmailService::sendOrderCompletedEmail($order, $user);
            error_log("[sendCompletionEmail] Email send result: " . ($result ? 'SUCCESS' : 'FAILED'));
        } else {
            error_log("[sendCompletionEmail] Order not found for ID: $orderId");
        }
    } catch (Exception $e) {
        error_log("[sendCompletionEmail] Exception: " . $e->getMessage());
    }
}

/**
 * Download video from S3 and save to Hostinger
 * 
 * @param string $s3Url The S3 URL of the rendered video
 * @param string $orderNumber The order number (e.g., ORD-ABC123)
 * @return string|false Local path on success, false on failure
 */
function downloadAndSaveVideo($s3Url, $orderNumber)
{
    try {
        // Create directory: uploads/orders/{order_number}/
        $uploadDir = __DIR__ . '/../../uploads/orders/' . $orderNumber;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("[downloadAndSaveVideo] Failed to create directory: $uploadDir");
                return false;
            }
        }

        $videoPath = $uploadDir . '/video.mp4';

        error_log("[downloadAndSaveVideo] Downloading from S3: $s3Url");

        // Download video from S3 using cURL for better error handling
        $ch = curl_init($s3Url);
        $fp = fopen($videoPath, 'wb');

        if (!$fp) {
            error_log("[downloadAndSaveVideo] Failed to open file for writing: $videoPath");
            return false;
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);  // 5 minute timeout for large videos
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if (!$success || $httpCode !== 200) {
            error_log("[downloadAndSaveVideo] Download failed. HTTP: $httpCode, Error: $error");
            // Clean up failed download
            if (file_exists($videoPath)) {
                unlink($videoPath);
            }
            return false;
        }

        // Verify file was downloaded
        $fileSize = filesize($videoPath);
        if ($fileSize < 1000) {  // Video should be at least 1KB
            error_log("[downloadAndSaveVideo] Downloaded file too small: $fileSize bytes");
            unlink($videoPath);
            return false;
        }

        error_log("[downloadAndSaveVideo] Success! Saved to: $videoPath ($fileSize bytes)");
        return $videoPath;

    } catch (Exception $e) {
        error_log("[downloadAndSaveVideo] Exception: " . $e->getMessage());
        return false;
    }
}
