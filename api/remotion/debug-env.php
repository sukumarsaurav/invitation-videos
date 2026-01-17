<?php
/**
 * Debug Remotion Token - REMOVE AFTER DEBUGGING
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

$appSecret = getenv('APP_SECRET');
$remotionKey = getenv('REMOTION_API_KEY');

echo json_encode([
    'app_secret_set' => !empty($appSecret),
    'app_secret_length' => strlen($appSecret),
    'app_secret_first_10' => substr($appSecret, 0, 10) . '...',
    'remotion_key_set' => !empty($remotionKey),
    'remotion_key_match' => $remotionKey === 'rmtn_live_xK9mPqR7vL2nT5wZ8jF3hB6cY4dA1eG',
    'env_file_exists' => file_exists(__DIR__ . '/../../.env'),
]);
