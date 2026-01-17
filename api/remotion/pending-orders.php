<?php
/**
 * Get Pending Orders for Remotion Rendering
 * 
 * Returns all orders that are paid but not yet rendered.
 * 
 * GET /api/remotion/pending-orders.php
 * Headers: Authorization: Bearer <token>
 * Returns: { "success": bool, "orders": array }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_auth_helper.php';

// Verify token
$user = verifyRemotionToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Fetch orders that need rendering
// Status: order_status = 'queued' means ready for rendering
$orders = Database::fetchAll("
    SELECT 
        o.id,
        o.order_number,
        o.user_id,
        o.template_id,
        o.status,
        o.order_status,
        o.customization_data,
        o.created_at,
        o.currency,
        o.amount,
        t.title as template_title,
        t.slug as template_slug,
        t.remotion_composition_id,
        t.default_music_url,
        u.email as user_email,
        u.name as user_name
    FROM orders o
    JOIN templates t ON o.template_id = t.id
    JOIN users u ON o.user_id = u.id
    WHERE o.order_status IN ('queued', 'processing')
    AND o.status = 'paid'
    ORDER BY o.created_at ASC
");

// Helper function to fix malformed URLs
function fixAssetUrl($value)
{
    if (!is_string($value))
        return $value;

    // Check if it looks like a URL with embedded server path
    // e.g., https://invitationvideos.com/home/u277468165/.../uploads/file.ext
    if (preg_match('#^https?://([^/]+)/home/[^/]+/.+/uploads/(.+)$#', $value, $matches)) {
        return 'https://' . $matches[1] . '/uploads/' . $matches[2];
    }

    // Fix paths with /config/../uploads/
    if (preg_match('#^https?://([^/]+).+/config/\.\./uploads/(.+)$#', $value, $matches)) {
        return 'https://' . $matches[1] . '/uploads/' . $matches[2];
    }

    return $value;
}

// Decode customization data and fetch uploaded files
foreach ($orders as &$order) {
    $order['customization_data'] = json_decode($order['customization_data'], true) ?? [];

    // Clean up any malformed URLs in customization_data
    foreach ($order['customization_data'] as $key => $value) {
        $order['customization_data'][$key] = fixAssetUrl($value);
    }

    // Get uploaded files for this order
    $uploads = Database::fetchAll(
        "SELECT field_name, file_path, file_type, original_filename 
         FROM order_uploads 
         WHERE order_id = ?",
        [$order['id']]
    );

    // Merge uploads into customization data
    foreach ($uploads as $upload) {
        // Extract the web-accessible path from the file_path
        // file_path may contain full server paths like /home/.../public_html/uploads/file.ext
        $filePath = $upload['file_path'];

        // Try to extract /uploads/... from the path
        if (preg_match('#/uploads/(.+)$#', $filePath, $matches)) {
            $webPath = '/uploads/' . $matches[1];
        } elseif (strpos($filePath, '/uploads/') === 0) {
            // Already a relative path
            $webPath = $filePath;
        } else {
            // Fallback: just use basename
            $webPath = '/uploads/' . basename($filePath);
        }

        $fullUrl = 'https://' . $_SERVER['HTTP_HOST'] . $webPath;
        $order['customization_data'][$upload['field_name']] = $fullUrl;
    }

    // Add default music if not provided
    if (empty($order['customization_data']['music_url']) && !empty($order['default_music_url'])) {
        $order['customization_data']['music_url'] = $order['default_music_url'];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($orders),
    'orders' => $orders,
]);
