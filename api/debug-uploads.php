<?php
/**
 * Debug: Check music field configuration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2026') {
    die('Access denied');
}

header('Content-Type: application/json');

// Get field presets with music type
$musicFields = Database::fetchAll(
    "SELECT fp.*, tfp.template_id
     FROM field_presets fp
     LEFT JOIN template_field_presets tfp ON fp.id = tfp.preset_id
     WHERE fp.field_type = 'music'"
);

// Check draft_order_uploads for any music
$draftMusicUploads = Database::fetchAll(
    "SELECT * FROM draft_order_uploads WHERE file_type = 'music' ORDER BY created_at DESC LIMIT 10"
);

// Check order_uploads for any music
$orderMusicUploads = Database::fetchAll(
    "SELECT * FROM order_uploads WHERE file_type = 'music' ORDER BY created_at DESC LIMIT 10"
);

echo json_encode([
    'music_fields' => $musicFields,
    'draft_music_uploads' => $draftMusicUploads,
    'order_music_uploads' => $orderMusicUploads
], JSON_PRETTY_PRINT);
