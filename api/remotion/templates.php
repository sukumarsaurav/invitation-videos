<?php
/**
 * Get Templates with Remotion Composition IDs
 * 
 * Returns templates for the Remotion Studio dashboard.
 * 
 * GET /api/remotion/templates.php
 * Headers: Authorization: Bearer <token>
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

$user = verifyRemotionToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$templates = Database::fetchAll("
    SELECT 
        id,
        title,
        slug,
        remotion_composition_id,
        category,
        subcategory,
        thumbnail_url,
        is_active
    FROM templates
    WHERE is_active = 1
    ORDER BY category, title
");

echo json_encode([
    'success' => true,
    'templates' => $templates,
]);
