<?php
/**
 * Wishlist API
 * 
 * Endpoints:
 * - POST /api/wishlist/add - Add template to wishlist
 * - POST /api/wishlist/remove - Remove template from wishlist
 * - GET /api/wishlist - Get user's wishlist
 * - GET /api/wishlist/check/{template_id} - Check if template is in wishlist
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// Helper function to send JSON response
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Check if user is logged in (except for check endpoint which returns false for guests)
if (!$userId && $action !== 'check') {
    jsonResponse(['success' => false, 'error' => 'Authentication required', 'requireLogin' => true], 401);
}

switch ($action) {
    case 'add':
        if ($method !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $templateId = intval($input['template_id'] ?? 0);

        if (!$templateId) {
            jsonResponse(['success' => false, 'error' => 'Invalid template ID'], 400);
        }

        // Check if template exists
        $template = Database::fetchOne("SELECT id FROM templates WHERE id = ? AND is_active = 1", [$templateId]);
        if (!$template) {
            jsonResponse(['success' => false, 'error' => 'Template not found'], 404);
        }

        // Check if already in wishlist
        $existing = Database::fetchOne(
            "SELECT id FROM wishlist WHERE user_id = ? AND template_id = ?",
            [$userId, $templateId]
        );

        if ($existing) {
            jsonResponse(['success' => true, 'message' => 'Already in wishlist', 'inWishlist' => true]);
        }

        // Add to wishlist
        try {
            Database::query(
                "INSERT INTO wishlist (user_id, template_id) VALUES (?, ?)",
                [$userId, $templateId]
            );
            jsonResponse(['success' => true, 'message' => 'Added to wishlist', 'inWishlist' => true]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to add to wishlist'], 500);
        }
        break;

    case 'remove':
        if ($method !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $templateId = intval($input['template_id'] ?? 0);

        if (!$templateId) {
            jsonResponse(['success' => false, 'error' => 'Invalid template ID'], 400);
        }

        // Remove from wishlist
        try {
            Database::query(
                "DELETE FROM wishlist WHERE user_id = ? AND template_id = ?",
                [$userId, $templateId]
            );
            jsonResponse(['success' => true, 'message' => 'Removed from wishlist', 'inWishlist' => false]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to remove from wishlist'], 500);
        }
        break;

    case 'list':
        if ($method !== 'GET') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        // Get wishlist with template details
        try {
            $wishlist = Database::fetchAll(
                "SELECT t.id, t.title, t.slug, t.thumbnail_url, t.price_usd, t.price_inr, 
                        t.discounted_price_usd, t.discounted_price_inr, t.duration_seconds,
                        t.is_premium, t.category, w.created_at as added_at
                 FROM wishlist w
                 INNER JOIN templates t ON w.template_id = t.id
                 WHERE w.user_id = ? AND t.is_active = 1
                 ORDER BY w.created_at DESC",
                [$userId]
            );

            jsonResponse([
                'success' => true,
                'wishlist' => $wishlist,
                'count' => count($wishlist)
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to get wishlist'], 500);
        }
        break;

    case 'check':
        if ($method !== 'GET') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $templateId = intval($_GET['template_id'] ?? 0);

        if (!$templateId) {
            jsonResponse(['success' => false, 'error' => 'Invalid template ID'], 400);
        }

        // Return false if not logged in
        if (!$userId) {
            jsonResponse(['success' => true, 'inWishlist' => false]);
        }

        // Check if in wishlist
        $existing = Database::fetchOne(
            "SELECT id FROM wishlist WHERE user_id = ? AND template_id = ?",
            [$userId, $templateId]
        );

        jsonResponse(['success' => true, 'inWishlist' => (bool) $existing]);
        break;

    case 'count':
        if ($method !== 'GET') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        if (!$userId) {
            jsonResponse(['success' => true, 'count' => 0]);
        }

        $result = Database::fetchOne(
            "SELECT COUNT(*) as count FROM wishlist w 
             INNER JOIN templates t ON w.template_id = t.id 
             WHERE w.user_id = ? AND t.is_active = 1",
            [$userId]
        );

        jsonResponse(['success' => true, 'count' => intval($result['count'] ?? 0)]);
        break;

    case 'ids':
        // Get list of template IDs in wishlist (for bulk check on gallery page)
        if ($method !== 'GET') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        if (!$userId) {
            jsonResponse(['success' => true, 'ids' => []]);
        }

        $items = Database::fetchAll(
            "SELECT template_id FROM wishlist WHERE user_id = ?",
            [$userId]
        );

        $ids = array_map(fn($item) => intval($item['template_id']), $items);
        jsonResponse(['success' => true, 'ids' => $ids]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
}
