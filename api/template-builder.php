<?php
/**
 * Template Builder API
 * Handles saving/loading template designs with slides and field positions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

header('Content-Type: application/json');

// Check if admin (simple check - should use proper auth)
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Load template design
        $templateId = intval($_GET['template_id'] ?? 0);
        if (!$templateId) {
            throw new Exception('Template ID required');
        }

        $slides = Database::fetchAll(
            "SELECT * FROM template_slides WHERE template_id = ? ORDER BY slide_order",
            [$templateId]
        );

        $fields = Database::fetchAll(
            "SELECT * FROM template_fields WHERE template_id = ? ORDER BY display_order",
            [$templateId]
        );

        echo json_encode([
            'success' => true,
            'slides' => $slides,
            'fields' => $fields
        ]);

    } elseif ($method === 'POST') {
        // Check for file upload
        if (isset($_POST['action']) && $_POST['action'] === 'upload_background') {
            handleBackgroundUpload();
            exit;
        }

        // Handle JSON body
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid request body');
        }

        $action = $input['action'] ?? '';
        $templateId = intval($input['template_id'] ?? 0);

        if (!Security::validateCSRFToken($input['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        if ($action === 'save') {
            saveTemplateDesign($templateId, $input['slides'] ?? [], $input['fields'] ?? []);
        } else {
            throw new Exception('Unknown action');
        }

    } elseif ($method === 'DELETE') {
        $slideId = intval($_GET['slide_id'] ?? 0);
        if ($slideId) {
            Database::query("DELETE FROM template_slides WHERE id = ?", [$slideId]);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Slide ID required');
        }

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleBackgroundUpload()
{
    $templateId = intval($_POST['template_id'] ?? 0);
    $slideId = $_POST['slide_id'] ?? '';

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File too large (max 10MB)');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'slide_bg_' . $templateId . '_' . time() . '_' . uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/templates/slides/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        throw new Exception('Failed to save file');
    }

    $url = '/uploads/templates/slides/' . $filename;

    echo json_encode(['success' => true, 'url' => $url]);
}

function saveTemplateDesign($templateId, $slides, $fields)
{
    if (!$templateId) {
        throw new Exception('Template ID required');
    }

    $savedSlides = [];

    foreach ($slides as $index => $slide) {
        $slideData = [
            'template_id' => $templateId,
            'slide_order' => $index,
            'duration_ms' => intval($slide['duration_ms'] ?? 3000),
            'background_color' => Security::sanitizeString($slide['background_color'] ?? '#ffffff'),
            'background_image' => $slide['background_image'] ? Security::sanitizeString($slide['background_image']) : null,
            'transition_type' => $slide['transition_type'] ?? 'fade'
        ];

        if (is_numeric($slide['id'])) {
            // Update existing slide
            Database::query(
                "UPDATE template_slides SET 
                    slide_order = ?, duration_ms = ?, background_color = ?, 
                    background_image = ?, transition_type = ?
                WHERE id = ?",
                [
                    $slideData['slide_order'],
                    $slideData['duration_ms'],
                    $slideData['background_color'],
                    $slideData['background_image'],
                    $slideData['transition_type'],
                    $slide['id']
                ]
            );
            $slideData['id'] = $slide['id'];
        } else {
            // Insert new slide
            Database::query(
                "INSERT INTO template_slides 
                    (template_id, slide_order, duration_ms, background_color, background_image, transition_type)
                VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $slideData['template_id'],
                    $slideData['slide_order'],
                    $slideData['duration_ms'],
                    $slideData['background_color'],
                    $slideData['background_image'],
                    $slideData['transition_type']
                ]
            );
            $slideData['id'] = Database::lastInsertId();
        }

        $savedSlides[] = $slideData;
    }

    // Update field positions and slide assignments
    foreach ($fields as $field) {
        $fieldId = intval($field['id'] ?? 0);
        if (!$fieldId)
            continue;

        // Find the real slide ID if it was a new slide
        $slideId = null;
        if (!empty($field['slide_id'])) {
            if (is_numeric($field['slide_id'])) {
                $slideId = $field['slide_id'];
            } else {
                // Find by matching the temp ID to saved slides
                foreach ($slides as $i => $origSlide) {
                    if ($origSlide['id'] === $field['slide_id']) {
                        $slideId = $savedSlides[$i]['id'];
                        break;
                    }
                }
            }
        }

        Database::query(
            "UPDATE template_fields SET 
                slide_id = ?,
                position_x = ?,
                position_y = ?,
                font_family = ?,
                font_size = ?,
                font_weight = ?,
                font_color = ?,
                text_align = ?,
                animation_type = ?,
                animation_delay_ms = ?,
                animation_duration_ms = ?,
                sample_value = ?
            WHERE id = ?",
            [
                $slideId,
                intval($field['position_x'] ?? 50),
                intval($field['position_y'] ?? 50),
                Security::sanitizeString($field['font_family'] ?? 'Inter'),
                intval($field['font_size'] ?? 24),
                intval($field['font_weight'] ?? 400),
                Security::sanitizeString($field['font_color'] ?? '#000000'),
                $field['text_align'] ?? 'center',
                $field['animation_type'] ?? 'fadeIn',
                intval($field['animation_delay_ms'] ?? 0),
                intval($field['animation_duration_ms'] ?? 500),
                Security::sanitizeString($field['sample_value'] ?? ''),
                $fieldId
            ]
        );
    }

    echo json_encode([
        'success' => true,
        'slides' => $savedSlides
    ]);
}
