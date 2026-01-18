<?php
/**
 * Dress Colors API
 * 
 * Returns available colors for a dress design.
 * Used by frontend to dynamically load colors when user selects a dress.
 * 
 * GET /api/dress-colors.php?dress_id=1
 */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/DressDesignService.php';

use InvitationVideos\Services\DressDesignService;

// Get dress ID from query parameter
$dressId = isset($_GET['dress_id']) ? intval($_GET['dress_id']) : 0;

if (!$dressId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'dress_id is required',
        'colors' => []
    ]);
    exit;
}

try {
    $dressService = new DressDesignService();
    $colors = $dressService->getColorsForDress($dressId, true);

    // Format response for frontend
    $formattedColors = array_map(function ($color) {
        return [
            'id' => (int) $color['id'],
            'name' => $color['name'],
            'hex_code' => $color['hex_code'],
            'thumbnail_url' => $color['thumbnail_url']
        ];
    }, $colors);

    echo json_encode([
        'success' => true,
        'dress_id' => $dressId,
        'colors' => $formattedColors
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch colors',
        'colors' => []
    ]);
}
