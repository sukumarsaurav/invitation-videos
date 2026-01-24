<?php
/**
 * Receive Video from Cloud Run - Boomerang Webhook
 * 
 * Cloud Run POSTs the rendered video back to this endpoint.
 * This script:
 * 1. Verifies the secret key
 * 2. Saves the video to /downloads/
 * 3. Updates the order status and video URL
 * 
 * POST /api/renders/receive-video.php
 * Headers: X-Renderer-Secret: <secret_key>
 * Body: multipart/form-data with 'video' file and 'order_id'
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Renderer-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Verify secret key
$expectedSecret = getenv('RENDERER_SECRET_KEY') ?: 'rmtn_render_secret_key';
$providedSecret = $_SERVER['HTTP_X_RENDERER_SECRET'] ?? $_POST['secret_key'] ?? '';

if ($providedSecret !== $expectedSecret) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get order ID
$orderId = $_POST['order_id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id is required']);
    exit;
}

// Check if video file was uploaded
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = isset($_FILES['video'])
        ? getUploadErrorMessage($_FILES['video']['error'])
        : 'No video file received';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $errorMessage]);
    exit;
}

// Verify order exists
$order = Database::fetchOne("SELECT id, order_number, order_status FROM orders WHERE id = ?", [$orderId]);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Create downloads directory if it doesn't exist
$downloadDir = __DIR__ . '/../../downloads';
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0755, true);
}

// Generate unique filename
$orderNumber = $order['order_number'];
$filename = "video_{$orderNumber}_" . time() . ".mp4";
$filePath = $downloadDir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['video']['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save video file']);
    exit;
}

// Generate web-accessible URL
$videoUrl = '/downloads/' . $filename;
$fullVideoUrl = 'https://' . $_SERVER['HTTP_HOST'] . $videoUrl;

// Calculate expiry date (7 days from now)
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

// Update order with video URL and status
Database::query("
    UPDATE orders 
    SET order_status = 'completed',
        output_video_url = ?,
        video_uploaded_at = NOW(),
        video_expires_at = ?,
        completed_at = NOW()
    WHERE id = ?
", [$videoUrl, $expiresAt, $orderId]);

// Send email notification to customer
try {
    require_once __DIR__ . '/../../src/Services/EmailService.php';

    // Get full order and user details for email
    $orderDetails = Database::fetchOne("
        SELECT o.*, t.title as template_title, u.email, u.name
        FROM orders o
        JOIN templates t ON o.template_id = t.id
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ", [$orderId]);

    if ($orderDetails) {
        $user = [
            'email' => $orderDetails['email'],
            'name' => $orderDetails['name'] ?? 'Customer'
        ];
        $order = array_merge($orderDetails, ['output_video_url' => $videoUrl]);

        \InvitationVideos\Services\EmailService::sendOrderCompletedEmail($order, $user);
        error_log("Email sent to {$user['email']} for order {$orderNumber}");
    }
} catch (Exception $e) {
    // Don't fail the upload if email fails
    error_log("Failed to send email for order {$orderNumber}: " . $e->getMessage());
}

// Log successful upload
error_log("Video received for order {$orderNumber}: {$filename}");

echo json_encode([
    'success' => true,
    'message' => 'Video received and saved',
    'order_id' => $orderId,
    'order_number' => $orderNumber,
    'video_url' => $fullVideoUrl,
    'expires_at' => $expiresAt
]);

/**
 * Get human-readable upload error message
 */
function getUploadErrorMessage(int $errorCode): string
{
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
    ];
    return $errors[$errorCode] ?? 'Unknown upload error';
}
