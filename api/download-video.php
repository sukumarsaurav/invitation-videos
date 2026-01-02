<?php
/**
 * Secure Video Download API
 * 
 * This endpoint verifies user ownership before serving video files.
 * Prevents unauthorized access to other users' videos by proxying downloads.
 * 
 * Usage: GET /api/download-video.php?order_id=123
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Require authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get order ID and preview mode from query params
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';

if ($orderId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Fetch order and verify ownership
    $order = Database::fetchOne(
        "SELECT id, user_id, order_number, output_video_url, video_expires_at, order_status 
         FROM orders 
         WHERE id = ?",
        [$orderId]
    );

    // Order not found
    if (!$order) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    // SECURITY CHECK: Verify the order belongs to the logged-in user
    if ((int) $order['user_id'] !== (int) $userId) {
        // Log this attempt for security monitoring
        error_log("Unauthorized video download attempt - User: {$userId}, Order: {$orderId}, Owner: {$order['user_id']}");

        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Check if video exists
    if (empty($order['output_video_url'])) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Video not available']);
        exit;
    }

    // Check if video has expired
    if (!empty($order['video_expires_at'])) {
        $expiryTime = strtotime($order['video_expires_at']);
        if ($expiryTime <= time()) {
            http_response_code(410);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Video download has expired']);
            exit;
        }
    }

    $videoUrl = $order['output_video_url'];

    // Determine if this is a local file or remote URL
    $isLocalFile = false;
    $localPath = null;

    // Check if it's a local path (starts with / or is relative to uploads)
    if (strpos($videoUrl, 'http') !== 0) {
        // It's a local file path
        $isLocalFile = true;

        // Resolve the full path
        if (strpos($videoUrl, '/') === 0) {
            // Absolute path from web root
            $localPath = __DIR__ . '/..' . $videoUrl;
        } else {
            // Relative path
            $localPath = __DIR__ . '/../' . $videoUrl;
        }

        $localPath = realpath($localPath);

        // Security: Ensure the file is within allowed directories
        $allowedDirs = [
            realpath(__DIR__ . '/../uploads'),
            realpath(__DIR__ . '/../storage'),
            realpath(__DIR__ . '/../public/videos')
        ];

        $isAllowed = false;
        foreach ($allowedDirs as $allowedDir) {
            if ($allowedDir && strpos($localPath, $allowedDir) === 0) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed || !$localPath || !file_exists($localPath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Video file not found']);
            exit;
        }
    }

    // Generate a filename for download
    $filename = 'invitation_video_' . $order['order_number'] . '.mp4';

    // Serve the file
    if ($isLocalFile) {
        // Serve local file
        $fileSize = filesize($localPath);
        $mimeType = mime_content_type($localPath) ?: 'video/mp4';

        header('Content-Type: ' . $mimeType);
        if ($isPreview) {
            // For preview, display inline in browser
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            // For download, force download dialog
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Stream the file
        readfile($localPath);
        exit;
    } else {
        // For remote URLs (S3, GCS, CDN, etc.), fetch and proxy
        $ch = curl_init($videoUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 300, // 5 minutes for large files
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_NOBODY => true, // First, just get headers
        ]);

        // Get file info first
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if ($httpCode !== 200) {
            curl_close($ch);
            http_response_code(502);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to fetch video']);
            exit;
        }

        // Now stream the actual content
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
            echo $data;
            return strlen($data);
        });

        // Set headers for download/preview
        header('Content-Type: ' . ($contentType ?: 'video/mp4'));
        if ($isPreview) {
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        if ($contentLength > 0) {
            header('Content-Length: ' . $contentLength);
        }
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Stream the video
        curl_exec($ch);
        curl_close($ch);
        exit;
    }

} catch (Exception $e) {
    error_log("Video download error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Download failed']);
    exit;
}
