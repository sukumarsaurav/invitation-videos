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
        $videoUrl = $input['video_url'] ?? null;
        if (!$videoUrl) {
            throw new Exception("Video URL required for completed status");
        }

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
        ", [$videoUrl, $expiresAt, $orderId]);

        // Send email
        sendCompletionEmail($orderId, $videoUrl);
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
