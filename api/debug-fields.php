<?php
/**
 * Debug: Check template field presets for music
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2026') {
    die('Access denied');
}

header('Content-Type: application/json');

// Check template_field_presets for template 2 (Sacred Blossoms Wedding)
$templateFields = Database::fetchAll(
    "SELECT tfp.*, fp.name, fp.field_name, fp.field_type
     FROM template_field_presets tfp
     JOIN field_presets fp ON tfp.preset_id = fp.id
     WHERE tfp.template_id = 2
     ORDER BY tfp.step_number, tfp.display_order"
);

// Check step 3 assignments specifically
$step3Fields = Database::fetchAll(
    "SELECT tfp.*, fp.name, fp.field_name, fp.field_type
     FROM template_field_presets tfp
     JOIN field_presets fp ON tfp.preset_id = fp.id
     WHERE tfp.template_id = 2 AND tfp.step_number = 3"
);

// Check all music type fields
$allMusicFields = Database::fetchAll(
    "SELECT fp.*, tfp.template_id, tfp.step_number
     FROM field_presets fp
     LEFT JOIN template_field_presets tfp ON fp.id = tfp.preset_id
     WHERE fp.field_type = 'music'"
);

echo json_encode([
    'template_2_all_fields' => $templateFields,
    'template_2_step_3_fields' => $step3Fields,
    'all_music_fields_with_assignments' => $allMusicFields
], JSON_PRETTY_PRINT);
