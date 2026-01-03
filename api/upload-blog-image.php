<?php
/**
 * API - Blog Image Upload
 * 
 * Handles image uploads for blog posts with automatic compression and WebP conversion.
 * Returns JSON response with uploaded image URL and compression statistics.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/../src/Core/ImageHelper.php';

// Start session for authentication check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (must be admin)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check for file upload
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL => 'File partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
    ];
    $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMsg = $uploadErrors[$errorCode] ?? 'Unknown upload error';

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Define upload directory
$uploadDir = __DIR__ . '/../uploads/blog/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit;
    }
}

// Process the upload with compression and WebP conversion
$result = ImageHelper::processThumbnailUpload(
    $_FILES['image'],
    $uploadDir,
    'blog_',
    1200,  // Max width for blog images
    800    // Max height for blog images
);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
    exit;
}

// Build the public URL
$filename = basename($result['url']);
$publicUrl = '/uploads/blog/' . $filename;

// Return success response
echo json_encode([
    'success' => true,
    'url' => $publicUrl,
    'filename' => $filename,
    'compression' => [
        'original_size' => $result['compression_stats']['original_size'],
        'compressed_size' => $result['compression_stats']['compressed_size'],
        'savings' => $result['compression_stats']['compression_ratio'],
        'format' => $result['compression_stats']['format']
    ]
]);
