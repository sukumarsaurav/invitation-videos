<?php
/**
 * Update Order Status from Remotion
 * 
 * Updates order status during/after rendering.
 * 
 * POST /api/remotion/update-order.php
 * Headers: Authorization: Bearer <token>
 * Body: { "order_id": int, "status": string, "video_url"?: string }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_auth_helper.php';

$user = verifyRemotionToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = intval($input['order_id'] ?? 0);
$status = $input['status'] ?? '';
$videoUrl = $input['video_url'] ?? null;

if (!$orderId || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id and status required']);
    exit;
}

// Validate status
$validStatuses = ['queued', 'processing', 'completed', 'failed'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Build update query
$updates = ['order_status = ?'];
$params = [$status];

if ($status === 'completed') {
    $updates[] = 'completed_at = NOW()';

    if ($videoUrl) {
        $updates[] = 'output_video_url = ?';
        $updates[] = 'video_uploaded_at = NOW()';
        $updates[] = 'video_expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY)';
        $params[] = $videoUrl;
    }
}

if ($status === 'processing') {
    // Mark processing start time in notes
    $updates[] = "notes = CONCAT(COALESCE(notes, ''), '\n[Render started: ', NOW(), ']')";
}

$params[] = $orderId;

Database::query(
    "UPDATE orders SET " . implode(', ', $updates) . " WHERE id = ?",
    $params
);

// If completed with video, send notification email
if ($status === 'completed' && $videoUrl) {
    try {
        require_once __DIR__ . '/../../src/Services/EmailService.php';

        $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        $userData = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$order['user_id']]);

        if ($order && $userData) {
            $order['output_video_url'] = $videoUrl;
            \InvitationVideos\Services\EmailService::sendOrderCompletedEmail($order, $userData);
        }
    } catch (Exception $e) {
        error_log("Failed to send completion email for order #$orderId: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'message' => "Order #{$orderId} updated to {$status}",
]);
