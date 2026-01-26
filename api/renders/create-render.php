<?php
/**
 * Create Render Job - Queues video rendering for specific order
 * 
 * Called when an order is paid. This script:
 * 1. Validates the order exists and is paid
 * 2. Updates order_status to 'queued'
 * 3. Returns success (AWS Lambda Orchestrator will pick it up)
 * 
 * POST /api/renders/create-render.php
 * Body: { "order_id": 123 }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../remotion/_auth_helper.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id is required']);
    exit;
}

// Fetch order details
$order = Database::fetchOne("
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.order_status,
        o.template_id
    FROM orders o
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Check if order is paid
if ($order['status'] !== 'paid') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order is not paid']);
    exit;
}

// Check if already queued or processing
if (in_array($order['order_status'], ['queued', 'processing', 'completed'])) {
    // If it's already queued, just return success
    if ($order['order_status'] === 'queued') {
        echo json_encode([
            'success' => true,
            'message' => 'Order already queued',
            'order_id' => $orderId,
            'order_status' => 'queued'
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order already ' . $order['order_status']]);
    exit;
}

try {
    // Update order status to queued
    // The AWS Lambda Orchestrator polls for 'queued' orders and will pick this up
    Database::query("UPDATE orders SET order_status = 'queued' WHERE id = ?", [$orderId]);

    echo json_encode([
        'success' => true,
        'message' => 'Render job queued for AWS Lambda',
        'order_id' => $orderId,
        'order_number' => $order['order_number'],
        'order_status' => 'queued'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to queue order: ' . $e->getMessage()]);
}
