<?php
/**
 * Fetch Queue of Pending Orders for Remotion Orchestrator
 * 
 * Called by: AWS Lambda Orchestrator
 * Method: GET
 * Headers: Authorization: Bearer <BACKEND_AUTH_TOKEN>
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

try {
    // Fetch queued orders
    // Join with templates to get all necessary render properties
    $orders = Database::fetchAll("
        SELECT 
            o.id,
            o.order_number,
            o.template_id,
            o.customization_data,
            t.remotion_composition_id,
            t.template_definition,
            t.title as template_title,
            t.slug as template_slug,
            t.asset_base_url,
            t.default_music_url as template_music,
            t.duration_seconds,
            t.render_fps,
            t.render_width,
            t.render_height,
            t.background_asset,
            t.overlay_assets,
            t.template_type
        FROM orders o
        JOIN templates t ON o.template_id = t.id
        WHERE o.order_status = 'queued'
        ORDER BY o.created_at ASC
        LIMIT 10
    ");

    $pendingOrders = [];

    foreach ($orders as $order) {
        // Parse customization data
        $customization = json_decode($order['customization_data'] ?? '{}', true) ?: [];

        // Add uploads to customization data (resolving full URLs)
        $uploads = Database::fetchAll(
            "SELECT field_name, file_path, s3_url FROM order_uploads WHERE order_id = ?",
            [$order['id']]
        );

        foreach ($uploads as $upload) {
            // Prefer S3 URL if available (faster for Lambda)
            if (!empty($upload['s3_url'])) {
                $webPath = $upload['s3_url'];
            } else {
                $webPath = $upload['file_path'];
                // Normalize path if needed (fallback to local server)
                if (!str_starts_with($webPath, 'http')) {
                    if (str_starts_with($webPath, '/uploads/')) {
                        $webPath = 'https://' . $_SERVER['HTTP_HOST'] . $webPath;
                    } else if (preg_match('#/uploads/(.+)$#', $webPath, $matches)) {
                        $webPath = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $matches[1];
                    }
                }
            }
            $customization[$upload['field_name']] = $webPath;
        }

        // Add music URL preference (from template)
        if (empty($customization['music_url'])) {
            $customization['music_url'] = $order['template_music'];
        }

        // Add background asset if implicit
        if (!empty($order['background_asset']) && empty($customization['background_url'])) {
            $baseUrl = rtrim($order['asset_base_url'], '/');
            $customization['background_url'] = $baseUrl . '/' . $order['background_asset'];
        }

        // Format for Orchestrator
        // If template_definition exists, use GenericTemplate
        // Otherwise, fall back to legacy composition (remotion_composition_id)
        $templateDefinition = null;
        if (!empty($order['template_definition'])) {
            $templateDefinition = json_decode($order['template_definition'], true);
        }

        $pendingOrders[] = [
            'id' => (int) $order['id'],
            'order_number' => $order['order_number'],
            'template_id' => (int) $order['template_id'],
            'remotion_composition_id' => $order['remotion_composition_id'],
            'template_definition' => $templateDefinition,
            'template_slug' => $order['template_slug'],
            'default_music_url' => $order['template_music'],
            'customization_data' => $customization,
            // Add extra meta for render settings if needed by Lambda
            'render_settings' => [
                'fps' => (int) $order['render_fps'],
                'durationInFrames' => (int) ($order['duration_seconds'] * $order['render_fps']),
                'width' => (int) $order['render_width'],
                'height' => (int) $order['render_height']
            ]
        ];

        // Debug: Log customization data for this order
        error_log("pending-orders.php: Order #{$order['order_number']} - customization keys: " . json_encode(array_keys($customization)));
        error_log("pending-orders.php: Order #{$order['order_number']} - template_definition present: " . (!empty($templateDefinition) ? 'YES (' . count($templateDefinition['slides'] ?? []) . ' slides)' : 'NO'));
    }

    echo json_encode([
        'success' => true,
        'count' => count($pendingOrders),
        'orders' => $pendingOrders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Failed to fetch pending orders: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
