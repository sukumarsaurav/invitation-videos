<?php
/**
 * Upload Rendered Video
 * 
 * Receives the rendered video from the Mac and stores it.
 * 
 * POST /api/remotion/upload-video.php
 * Headers: Authorization: Bearer <token>
 * Body: multipart/form-data with video file and order_id
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_auth_helper.php';

$user = verifyRemotionToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id required']);
    exit;
}

if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Video file required']);
    exit;
}

// Verify order exists
$order = Database::fetchOne("SELECT order_number FROM orders WHERE id = ?", [$orderId]);
if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Create upload directory
$uploadDir = __DIR__ . '/../../uploads/videos/' . $orderId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate filename
$extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION) ?: 'mp4';
$filename = 'video_' . $order['order_number'] . '_' . time() . '.' . $extension;
$filePath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($_FILES['video']['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save video']);
    exit;
}

// Generate public URL
$videoUrl = '/uploads/videos/' . $orderId . '/' . $filename;

// Update order
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

Database::query(
    "UPDATE orders SET 
        output_video_url = ?,
        video_uploaded_at = NOW(),
        video_expires_at = ?,
        order_status = 'completed',
        completed_at = NOW()
     WHERE id = ?",
    [$videoUrl, $expiresAt, $orderId]
);

// Send notification email (non-fatal - don't let email failure affect upload success)
try {
    $emailServicePath = __DIR__ . '/../../src/Services/EmailService.php';

    // Load vendor autoloader first
    require_once __DIR__ . '/../../vendor/autoload.php';

    // Only attempt email if the file exists and can be loaded
    if (file_exists($emailServicePath)) {
        require_once $emailServicePath;

        // Check if the class was loaded and PHPMailer is available
        $emailServiceExists = class_exists('InvitationVideos\Services\EmailService');
        $phpMailerExists = class_exists('PHPMailer\PHPMailer\PHPMailer');

        error_log("Email check for order #$orderId: EmailService=$emailServiceExists, PHPMailer=$phpMailerExists");

        if ($emailServiceExists && $phpMailerExists) {
            $orderData = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
            $userData = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$orderData['user_id']]);

            if ($orderData && $userData) {
                $result = \InvitationVideos\Services\EmailService::sendOrderCompletedEmail($orderData, $userData);
                error_log("Completion email for order #$orderId sent: " . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Email skipped for order #$orderId: Order or user data not found");
            }
        } else {
            error_log("Email skipped for order #$orderId: EmailService or PHPMailer not available");
        }
    } else {
        error_log("Email skipped for order #$orderId: EmailService.php not found at $emailServicePath");
    }
} catch (Throwable $e) {
    error_log("Failed to send completion email for order #$orderId: " . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'video_url' => $videoUrl,
    'expires_at' => $expiresAt,
    'message' => 'Video uploaded and order completed',
]);
