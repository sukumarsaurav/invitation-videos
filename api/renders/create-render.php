<?php
/**
 * Create Render Job - Dispatches video rendering to Google Cloud Tasks
 * 
 * Called when an order is paid. This script:
 * 1. Validates the order exists and is paid
 * 2. Updates order_status to 'queued'
 * 3. Dispatches a render task to Google Cloud Tasks
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

// Fetch order details with template asset info
$order = Database::fetch("
    SELECT 
        o.id,
        o.order_number,
        o.user_id,
        o.template_id,
        o.status,
        o.order_status,
        o.customization_data,
        t.title as template_title,
        t.remotion_composition_id,
        t.template_type,
        t.asset_base_url,
        t.default_music_url,
        t.background_asset,
        t.overlay_assets,
        t.duration_seconds,
        t.render_fps,
        t.render_width,
        t.render_height,
        u.email as user_email
    FROM orders o
    JOIN templates t ON o.template_id = t.id
    JOIN users u ON o.user_id = u.id
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
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order already ' . $order['order_status']]);
    exit;
}

// Check if template has Remotion composition
if (empty($order['remotion_composition_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Template does not have Remotion composition configured']);
    exit;
}

// Update order status to queued
Database::query("UPDATE orders SET order_status = 'queued' WHERE id = ?", [$orderId]);

// Prepare customization data
$customizationData = json_decode($order['customization_data'], true) ?? [];

// Get uploaded files for this order
$uploads = Database::fetchAll(
    "SELECT field_name, file_path FROM order_uploads WHERE order_id = ?",
    [$orderId]
);

// Merge uploads into customization data with full URLs
foreach ($uploads as $upload) {
    $filePath = $upload['file_path'];
    if (preg_match('#/uploads/(.+)$#', $filePath, $matches)) {
        $webPath = '/uploads/' . $matches[1];
    } elseif (strpos($filePath, '/uploads/') === 0) {
        $webPath = $filePath;
    } else {
        $webPath = '/uploads/' . basename($filePath);
    }
    $fullUrl = 'https://' . $_SERVER['HTTP_HOST'] . $webPath;
    $customizationData[$upload['field_name']] = $fullUrl;
}

// Add default music if not provided
if (empty($customizationData['music_url']) && !empty($order['default_music_url'])) {
    $customizationData['music_url'] = $order['default_music_url'];
}

// Add GCS background asset URL if not in customization data
if (!empty($order['asset_base_url']) && !empty($order['background_asset'])) {
    if (empty($customizationData['background_url'])) {
        $customizationData['background_url'] = rtrim($order['asset_base_url'], '/') . '/' . $order['background_asset'];
    }
}

// Parse overlay assets from JSON if available
if (!empty($order['overlay_assets'])) {
    $overlays = json_decode($order['overlay_assets'], true) ?: [];
    foreach ($overlays as $key => $overlayFile) {
        $overlayKey = is_numeric($key) ? "overlay_$key" : $key;
        if (empty($customizationData[$overlayKey])) {
            $customizationData[$overlayKey] = rtrim($order['asset_base_url'], '/') . '/' . $overlayFile;
        }
    }
}

// Prepare Cloud Tasks payload
$payload = [
    'order_id' => (int) $order['id'],
    'order_number' => $order['order_number'],
    'template_id' => $order['remotion_composition_id'],
    'input_props' => $customizationData,
    'template_settings' => [
        'type' => $order['template_type'] ?? 'video',
        'duration' => (int) ($order['duration_seconds'] ?? 30),
        'fps' => (int) ($order['render_fps'] ?? 30),
        'width' => (int) ($order['render_width'] ?? 1080),
        'height' => (int) ($order['render_height'] ?? 1920),
    ],
    'callback_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/renders/receive-video.php',
    'status_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/remotion/update-order.php',
    'secret_key' => getenv('RENDERER_SECRET_KEY') ?: 'rmtn_render_secret_key'
];

// Dispatch to Google Cloud Tasks
$dispatched = dispatchToCloudTasks($payload);

if ($dispatched) {
    echo json_encode([
        'success' => true,
        'message' => 'Render job queued',
        'order_id' => $orderId,
        'order_number' => $order['order_number'],
        'order_status' => 'queued'
    ]);
} else {
    // Revert status if dispatch failed
    Database::query("UPDATE orders SET order_status = 'awaiting_payment' WHERE id = ?", [$orderId]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to dispatch render job']);
}

/**
 * Dispatch task to Google Cloud Tasks
 */
function dispatchToCloudTasks(array $payload): bool
{
    $projectId = getenv('GCP_PROJECT_ID');
    $location = getenv('GCP_QUEUE_LOCATION') ?: 'us-central1';
    $queueName = getenv('GCP_QUEUE_NAME') ?: 'render-queue';
    $cloudRunUrl = getenv('CLOUD_RUN_URL');

    if (!$projectId || !$cloudRunUrl) {
        error_log('GCP_PROJECT_ID or CLOUD_RUN_URL not configured');
        return false;
    }

    // For now, use direct HTTP call to Cloud Run (simpler setup)
    // In production, you should use the Google Cloud Tasks PHP SDK
    // composer require google/cloud-tasks

    try {
        // Direct call to Cloud Run (works with --allow-unauthenticated)
        $ch = curl_init($cloudRunUrl . '/render');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // Quick timeout - Cloud Run will process async
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 200 or 202 means accepted
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        // If Cloud Run rejected, try Cloud Tasks queue
        return dispatchViaCloudTasksAPI($payload, $projectId, $location, $queueName, $cloudRunUrl);

    } catch (Exception $e) {
        error_log('Failed to dispatch render: ' . $e->getMessage());
        return false;
    }
}

/**
 * Dispatch via Google Cloud Tasks API (requires google/cloud-tasks package)
 */
function dispatchViaCloudTasksAPI(array $payload, string $projectId, string $location, string $queueName, string $cloudRunUrl): bool
{
    // Check if Cloud Tasks SDK is available
    if (!class_exists('Google\Cloud\Tasks\V2\CloudTasksClient')) {
        error_log('Google Cloud Tasks SDK not installed. Run: composer require google/cloud-tasks');
        return false;
    }

    try {
        $client = new \Google\Cloud\Tasks\V2\CloudTasksClient();

        $queuePath = $client->queueName($projectId, $location, $queueName);

        $httpRequest = new \Google\Cloud\Tasks\V2\HttpRequest();
        $httpRequest->setUrl($cloudRunUrl . '/render');
        $httpRequest->setHttpMethod(\Google\Cloud\Tasks\V2\HttpMethod::POST);
        $httpRequest->setBody(json_encode($payload));
        $httpRequest->setHeaders(['Content-Type' => 'application/json']);

        $task = new \Google\Cloud\Tasks\V2\Task();
        $task->setHttpRequest($httpRequest);

        $client->createTask($queuePath, $task);
        $client->close();

        return true;
    } catch (Exception $e) {
        error_log('Cloud Tasks API error: ' . $e->getMessage());
        return false;
    }
}
