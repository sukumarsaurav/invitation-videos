<?php
// api/debug-image.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2026') {
    die('Access denied');
}

$orderId = $_GET['order_id'] ?? null;

header('Content-Type: application/json');

$query = "SELECT * FROM order_uploads";
$params = [];

if ($orderId) {
    $query .= " WHERE order_id = ?";
    $params[] = $orderId;
} else {
    $query .= " ORDER BY id DESC LIMIT 20";
}

$uploads = Database::fetchAll($query, $params);

// Also get the order to check files_directory
$orders = [];
if (!empty($uploads)) {
    $orderIds = array_unique(array_column($uploads, 'order_id'));
    if (!empty($orderIds)) {
        $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
        $orders = Database::fetchAll(
            "SELECT id, order_number, files_directory FROM orders WHERE id IN ($placeholders)",
            $orderIds
        );
    }
}

echo json_encode([
    'uploads' => $uploads,
    'orders' => $orders
], JSON_PRETTY_PRINT);
