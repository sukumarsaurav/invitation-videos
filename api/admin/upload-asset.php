<?php
/**
 * API - Upload Template Asset (Video/Image) to S3
 * 
 * Uploads video or image files for use as slide backgrounds in templates.
 * Files are stored permanently in S3 for template rendering.
 */

require_once __DIR__ . '/../../admin/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

// Check for file upload
if (!isset($_FILES['asset_file']) || $_FILES['asset_file']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['asset_file']['error'] ?? 'No file';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error: ' . $errorCode]);
    exit;
}

$file = $_FILES['asset_file'];
$assetType = $_POST['asset_type'] ?? 'video'; // 'video' or 'image'

// Define allowed types and size limits
$allowedTypes = [
    'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
    'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
];

$maxSizes = [
    'video' => 50 * 1024 * 1024, // 50MB
    'image' => 10 * 1024 * 1024  // 10MB
];

// Validate asset type
if (!in_array($assetType, ['video', 'image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid asset type']);
    exit;
}

// Validate file type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, $allowedTypes[$assetType])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes[$assetType])
    ]);
    exit;
}

// Validate file size
if ($file['size'] > $maxSizes[$assetType]) {
    $maxMB = round($maxSizes[$assetType] / 1024 / 1024);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "File too large. Maximum size is {$maxMB}MB"]);
    exit;
}

try {
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^a-z0-9]/', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
    $filename = "templates/{$assetType}s/" . uniqid() . '_' . $safeName . '.' . $ext;

    // Upload to S3
    $s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region' => AWS_DEFAULT_REGION,
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    $bucket = 'invitation-video-assets-permanent';

    $result = $s3->putObject([
        'Bucket' => $bucket,
        'Key' => $filename,
        'SourceFile' => $file['tmp_name'],
        'ContentType' => $mimeType,
    ]);

    $s3Url = $result['ObjectURL'];

    error_log("[Asset Upload] Uploaded {$assetType}: {$s3Url}");

    echo json_encode([
        'success' => true,
        'url' => $s3Url,
        'type' => $assetType,
        'filename' => $filename,
        'size' => $file['size']
    ]);

} catch (Exception $e) {
    error_log("[Asset Upload] S3 Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()]);
}
