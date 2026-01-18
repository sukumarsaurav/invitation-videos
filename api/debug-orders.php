<?php
/**
 * Debug: Check recent orders and their uploads
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2026') {
    die('Access denied');
}

header('Content-Type: application/json');

// Get recent orders
$recentOrders = Database::fetchAll(
    "SELECT o.id, o.order_number, o.status, o.order_status, o.customization_data, o.created_at,
            t.title as template_title
     FROM orders o 
     JOIN templates t ON o.template_id = t.id
     ORDER BY o.created_at DESC LIMIT 5"
);

// Get uploads for each order
foreach ($recentOrders as &$order) {
    $order['customization_data'] = json_decode($order['customization_data'], true);
    $order['uploads'] = Database::fetchAll(
        "SELECT field_name, file_type, original_filename, mime_type FROM order_uploads WHERE order_id = ?",
        [$order['id']]
    );
}

// Get recent draft orders
$recentDrafts = Database::fetchAll(
    "SELECT d.id, d.draft_token, d.customization_data, d.created_at,
            t.title as template_title
     FROM draft_orders d 
     JOIN templates t ON d.template_id = t.id
     WHERE d.expires_at > NOW()
     ORDER BY d.created_at DESC LIMIT 5"
);

foreach ($recentDrafts as &$draft) {
    $draft['customization_data'] = json_decode($draft['customization_data'], true);
    $draft['uploads'] = Database::fetchAll(
        "SELECT field_name, file_type, original_filename, mime_type FROM draft_order_uploads WHERE draft_id = ?",
        [$draft['id']]
    );
}

// Check session uploads folder
$uploadsDir = UPLOAD_PATH;
$recentFiles = [];
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    usort($files, function ($a, $b) use ($uploadsDir) {
        return filemtime($uploadsDir . $b) - filemtime($uploadsDir . $a);
    });
    foreach (array_slice($files, 0, 10) as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadsDir . $file;
            $recentFiles[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                'is_audio' => preg_match('/\.(mp3|wav|m4a|ogg)$/i', $file)
            ];
        }
    }
}

echo json_encode([
    'recent_orders' => $recentOrders,
    'recent_drafts' => $recentDrafts,
    'recent_uploads_folder' => $recentFiles
], JSON_PRETTY_PRINT);
