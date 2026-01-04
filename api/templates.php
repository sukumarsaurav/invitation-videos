<?php
/**
 * API: Get Templates (Paginated)
 * 
 * GET /api/templates.php
 * 
 * Query Parameters:
 * - category: Filter by category (optional)
 * - tradition: Filter by cultural tradition (optional)
 * - sort: popular|newest|price_low|price_high (default: popular)
 * - page: Page number (default: 1)
 * - limit: Templates per page (default: 12)
 * 
 * Returns JSON:
 * {
 *   "success": true,
 *   "templates": [...],
 *   "pagination": { "page": 1, "limit": 12, "total": 50, "hasMore": true }
 * }
 */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=60'); // Cache for 1 minute

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/../src/Core/ImageHelper.php';

// Get parameters
$category = $_GET['category'] ?? null;
$tradition = $_GET['tradition'] ?? null;
$sort = $_GET['sort'] ?? 'popular';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(24, max(6, intval($_GET['limit'] ?? 12))); // Min 6, max 24

// Build query
$sql = "SELECT * FROM templates WHERE is_active = 1";
$countSql = "SELECT COUNT(*) as total FROM templates WHERE is_active = 1";
$params = [];

if ($category) {
    $sql .= " AND category = ?";
    $countSql .= " AND category = ?";
    $params[] = $category;
}

if ($tradition) {
    $sql .= " AND cultural_tradition = ?";
    $countSql .= " AND cultural_tradition = ?";
    $params[] = $tradition;
}

// Sort
switch ($sort) {
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'price_low':
        $sql .= " ORDER BY price_usd ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price_usd DESC";
        break;
    default:
        $sql .= " ORDER BY purchase_count DESC";
}

// Get total count
$totalResult = Database::fetchOne($countSql, $params);
$total = intval($totalResult['total'] ?? 0);

// Add pagination
$offset = ($page - 1) * $limit;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Fetch templates
$templates = Database::fetchAll($sql, $params);

// Format templates for JSON response
$formattedTemplates = array_map(function ($template) {
    // Get responsive srcset URLs
    $thumbnailUrl = $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg';
    $pathInfo = pathinfo($thumbnailUrl);
    $basePath = rtrim($pathInfo['dirname'], '/') . '/' . $pathInfo['filename'];

    $srcset = [];
    $widths = [200, 300, 400];
    foreach ($widths as $width) {
        $variantPath = $basePath . '-' . $width . 'w.webp';
        $srcset[$width] = $variantPath;
    }

    return [
        'id' => intval($template['id']),
        'title' => $template['title'],
        'slug' => $template['slug'],
        'category' => $template['category'],
        'thumbnail_url' => $thumbnailUrl,
        'srcset' => $srcset,
        'price_usd' => floatval($template['price_usd']),
        'price_inr' => floatval($template['price_inr']),
        'discounted_price_usd' => $template['discounted_price_usd'] ? floatval($template['discounted_price_usd']) : null,
        'is_premium' => (bool) $template['is_premium'],
        'duration_seconds' => intval($template['duration_seconds']),
        'aspect_ratio' => $template['aspect_ratio'] ?? '9:16',
    ];
}, $templates);

// Calculate pagination info
$hasMore = ($page * $limit) < $total;
$totalPages = ceil($total / $limit);

// Return response
echo json_encode([
    'success' => true,
    'templates' => $formattedTemplates,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'totalPages' => $totalPages,
        'hasMore' => $hasMore,
    ],
], JSON_UNESCAPED_SLASHES);
