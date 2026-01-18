<?php
/**
 * Dress Designs API
 * 
 * Returns available dress designs for a template.
 * Used by frontend on customize page.
 * 
 * GET /api/dress-designs.php?template_id=1
 */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/DressDesignService.php';

use InvitationVideos\Services\DressDesignService;

// Get template ID from query parameter
$templateId = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

try {
    $dressService = new DressDesignService();

    if ($templateId) {
        // Get designs assigned to this specific template
        $designs = $dressService->getDesignsForTemplate($templateId);
    } else {
        // Get all active designs (for admin or general use)
        $designs = $dressService->getAllDesigns(true);
    }

    // Format response for frontend
    $formattedDesigns = array_map(function ($design) use ($dressService) {
        // Get colors for this design
        $colors = $dressService->getColorsForDress($design['id'], true);

        return [
            'id' => (int) $design['id'],
            'name' => $design['name'],
            'slug' => $design['slug'],
            'description' => $design['description'],
            'thumbnail_url' => $design['thumbnail_url'],
            'category' => $design['category'],
            'gender' => $design['gender'],
            'color_count' => count($colors),
            'colors' => array_map(function ($color) {
                return [
                    'id' => (int) $color['id'],
                    'name' => $color['name'],
                    'hex_code' => $color['hex_code']
                ];
            }, $colors)
        ];
    }, $designs);

    echo json_encode([
        'success' => true,
        'template_id' => $templateId,
        'designs' => $formattedDesigns
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch dress designs',
        'designs' => []
    ]);
}
