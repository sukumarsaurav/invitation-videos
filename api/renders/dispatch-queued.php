<?php
/**
 * Manually Dispatch Render for Queued Order
 * 
 * Admin endpoint to re-dispatch a render job for an order that is stuck in "queued" status.
 * 
 * POST /api/renders/dispatch-queued.php
 * Body: { "order_id": 123 } or ?order_id=123
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../remotion/_auth_helper.php';

// Get order ID from query or body
$orderId = $_GET['order_id'] ?? null;
if (!$orderId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? null;
}

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id is required']);
    exit;
}

$orderId = (int) $orderId;

// Verify order exists and is in a renderable state
$order = Database::fetchOne("
    SELECT 
        o.id,
        o.order_number,
        o.status,
        o.order_status,
        o.customization_data,
        t.remotion_composition_id,
        t.title as template_title,
        t.default_music_url,
        t.duration_seconds,
        t.render_fps,
        t.render_width,
        t.render_height
    FROM orders o
    JOIN templates t ON o.template_id = t.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Check status
if ($order['status'] !== 'paid') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order is not paid (status: ' . $order['status'] . ')']);
    exit;
}

if (empty($order['remotion_composition_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Template does not have Remotion composition configured - manual processing required']);
    exit;
}

// Get Cloud Run URL
$cloudRunUrl = getenv('CLOUD_RUN_URL');
if (empty($cloudRunUrl)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'CLOUD_RUN_URL not configured']);
    exit;
}

// Get uploaded files
$uploads = Database::fetchAll(
    "SELECT field_name, file_path FROM order_uploads WHERE order_id = ?",
    [$orderId]
);

// Build customization data with asset URLs
$customizationData = json_decode($order['customization_data'] ?? '{}', true) ?: [];

foreach ($uploads as $upload) {
    $filePath = $upload['file_path'];

    // file_path should be web-relative (e.g., /uploads/orders/ORD-123/photo.jpg)
    // For legacy absolute paths, extract the web portion
    if (strpos($filePath, '/uploads/') === 0) {
        $webPath = $filePath;  // Already standardized
    } elseif (preg_match('#/uploads/(.+)$#', $filePath, $matches)) {
        $webPath = '/uploads/' . $matches[1];  // Extract from absolute
    } else {
        $webPath = '/uploads/' . basename($filePath);  // Fallback
    }

    $customizationData[$upload['field_name']] = 'https://invitationvideos.com' . $webPath;
}

// Add default music if needed
if (empty($customizationData['music_url']) && !empty($order['default_music_url'])) {
    $customizationData['music_url'] = $order['default_music_url'];
}

// Build render payload
$payload = [
    'order_id' => (int) $order['id'],
    'order_number' => $order['order_number'],
    'template_id' => $order['remotion_composition_id'],
    'input_props' => $customizationData,
    'template_settings' => [
        'duration' => (int) ($order['duration_seconds'] ?? 30),
        'fps' => (int) ($order['render_fps'] ?? 30),
        'width' => (int) ($order['render_width'] ?? 1080),
        'height' => (int) ($order['render_height'] ?? 1920),
    ],
    'callback_url' => 'https://invitationvideos.com/api/renders/receive-video.php',
    'status_url' => 'https://invitationvideos.com/api/remotion/update-order.php',
    'secret_key' => getenv('RENDERER_SECRET_KEY') ?: 'rmtn_render_secret_key'
];

// Update status to queued (in case it was stuck)
Database::query("UPDATE orders SET order_status = 'queued' WHERE id = ?", [$orderId]);

// Dispatch to Cloud Run
$ch = curl_init($cloudRunUrl . '/render');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    error_log("dispatch-queued: Successfully dispatched render for order #{$orderId} (HTTP {$httpCode})");
    echo json_encode([
        'success' => true,
        'message' => 'Render job dispatched successfully',
        'order_id' => $orderId,
        'order_number' => $order['order_number'],
        'template' => $order['template_title'],
        'composition_id' => $order['remotion_composition_id'],
        'cloud_run_response' => json_decode($result, true)
    ]);
} else {
    error_log("dispatch-queued: Failed to dispatch render for order #{$orderId} (HTTP {$httpCode}): {$result}");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to dispatch to Cloud Run',
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'response' => $result,
        'cloud_run_url' => $cloudRunUrl . '/render'
    ]);
}
