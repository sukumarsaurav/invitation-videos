<?php
/**
 * API endpoint to create a new template
 * POST /api/create-template.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

header('Content-Type: application/json');

// Start session with correct name if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$category = trim($input['category'] ?? 'other');

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Template name is required']);
    exit;
}

try {
    // Generate slug from name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');

    // Check if slug already exists and make unique if needed
    $existing = Database::fetchOne("SELECT COUNT(*) as cnt FROM templates WHERE slug = ?", [$slug]);
    if ($existing['cnt'] > 0) {
        $slug = $slug . '-' . time();
    }

    // Create template
    Database::query(
        "INSERT INTO templates (title, slug, description, category, is_active, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())",
        [$name, $slug, $description, $category]
    );
    $templateId = Database::lastInsertId();

    // Create initial slide for the template
    Database::query(
        "INSERT INTO template_slides (template_id, slide_order, duration_ms, background_color, transition_type)
        VALUES (?, 0, 3000, '#ffffff', 'fade')",
        [$templateId]
    );

    echo json_encode([
        'success' => true,
        'template_id' => $templateId,
        'slug' => $slug,
        'message' => 'Template created successfully',
        'redirect_url' => "/admin/template-builder.php?id={$templateId}"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create template: ' . $e->getMessage()]);
}
