<?php
/**
 * Debug: Check order uploads
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2026') {
    die('Access denied');
}

header('Content-Type: application/json');

// Get recent orders with their uploads
$orders = Database::fetchAll(
    "SELECT o.id, o.order_number, o.customization_data 
     FROM orders o 
     ORDER BY o.created_at DESC 
     LIMIT 5"
);

$result = [];
foreach ($orders as $order) {
    $uploads = Database::fetchAll(
        "SELECT field_name, file_type, original_filename, file_path 
         FROM order_uploads 
         WHERE order_id = ?",
        [$order['id']]
    );

    $result[] = [
        'order_id' => $order['id'],
        'order_number' => $order['order_number'],
        'uploads' => $uploads,
        'customization_data' => json_decode($order['customization_data'], true)
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
