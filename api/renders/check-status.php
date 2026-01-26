<?php
/**
 * Check Order Status - Frontend Polling Endpoint
 * 
 * Returns the current status of an order for JavaScript polling.
 * 
 * GET /api/renders/check-status.php?order_id=123
 * or
 * GET /api/renders/check-status.php?order_number=INV-20260124-001
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// Get order identifier
$orderId = $_GET['order_id'] ?? null;
$orderNumber = $_GET['order_number'] ?? null;

if (!$orderId && !$orderNumber) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'order_id or order_number is required'
    ]);
    exit;
}

// Build query based on provided identifier
if ($orderId) {
    $order = Database::fetchOne("
        SELECT 
            id,
            order_number,
            order_status,
            output_video_url,
            video_expires_at,
            created_at,
            completed_at
        FROM orders 
        WHERE id = ?
    ", [$orderId]);
} else {
    $order = Database::fetchOne("
        SELECT 
            id,
            order_number,
            order_status,
            output_video_url,
            video_expires_at,
            created_at,
            completed_at
        FROM orders 
        WHERE order_number = ?
    ", [$orderNumber]);
}

if (!$order) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Order not found'
    ]);
    exit;
}

// Build response
$response = [
    'success' => true,
    'order_id' => (int) $order['id'],
    'order_number' => $order['order_number'],
    'status' => $order['order_status'],
    'created_at' => $order['created_at']
];

// Add video URL if completed
if ($order['order_status'] === 'completed' && $order['output_video_url']) {
    $response['video_url'] = $order['output_video_url'];
    $response['video_full_url'] = 'https://' . $_SERVER['HTTP_HOST'] . $order['output_video_url'];
    $response['completed_at'] = $order['completed_at'];
    $response['expires_at'] = $order['video_expires_at'];

    // Check if video has expired
    if ($order['video_expires_at'] && strtotime($order['video_expires_at']) < time()) {
        $response['expired'] = true;
        $response['video_url'] = null;
        $response['video_full_url'] = null;
    }
}

// Add status message for frontend
$statusMessages = [
    'awaiting_payment' => 'Waiting for payment',
    'queued' => 'Your video is in the queue',
    'processing' => 'Your video is being created...',
    'completed' => 'Your video is ready!',
    'cancelled' => 'Order cancelled',
    'failed' => 'There was an error creating your video'
];

$response['status_message'] = $statusMessages[$order['order_status']] ?? $order['order_status'];

echo json_encode($response);
