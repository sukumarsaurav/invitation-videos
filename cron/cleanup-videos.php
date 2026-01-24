<?php
/**
 * Video Cleanup Cron Job
 * 
 * Deletes videos older than 7 days to free up Hostinger storage.
 * 
 * Hostinger Cron Setup:
 * 1. Go to Hostinger Dashboard -> Advanced -> Cron Jobs
 * 2. Command: php /home/u277468165/domains/invitationvideos.com/public_html/cron/cleanup-videos.php
 * 3. Schedule: Run once a day (0 3 * * *)
 * 
 * This script:
 * 1. Scans the /downloads directory for MP4 files
 * 2. Deletes files older than the configured retention period
 * 3. Updates database to mark videos as expired
 * 4. Logs cleanup activity
 */

// Configuration
define('RETENTION_DAYS', 7);  // Delete videos older than 7 days
define('LOG_FILE', __DIR__ . '/cleanup.log');

// Only allow CLI execution or authenticated admin access
if (php_sapi_name() !== 'cli') {
    // For web access, require authentication
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../api/remotion/_auth_helper.php';

    $user = verifyRemotionToken();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Access denied']));
    }
    header('Content-Type: application/json');
}

// Log function
function logMessage(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] $message\n";
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);

    if (php_sapi_name() === 'cli') {
        echo $logLine;
    }
}

// Start cleanup
logMessage("=== Video Cleanup Started ===");

$downloadsDir = __DIR__ . '/../downloads';
$retentionSeconds = RETENTION_DAYS * 24 * 60 * 60;
$now = time();

// Check if downloads directory exists
if (!is_dir($downloadsDir)) {
    logMessage("Downloads directory does not exist: $downloadsDir");
    exit(0);
}

// Find all MP4 files
$files = glob($downloadsDir . '/*.mp4');
$totalFiles = count($files);
$deletedCount = 0;
$freedBytes = 0;

logMessage("Found $totalFiles video files to check");

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $fileAge = $now - filemtime($file);
    $fileAgeDays = round($fileAge / 86400, 1);

    if ($fileAge >= $retentionSeconds) {
        $fileSize = filesize($file);
        $filename = basename($file);

        // Try to extract order number from filename
        // Format: video_INV-20260124-001_1737747600.mp4
        if (preg_match('/video_(INV-[\d-]+)/', $filename, $matches)) {
            $orderNumber = $matches[1];

            // Update database to mark as expired
            try {
                require_once __DIR__ . '/../config/database.php';
                Database::query(
                    "UPDATE orders SET output_video_url = NULL WHERE order_number = ?",
                    [$orderNumber]
                );
                logMessage("Updated database for order: $orderNumber");
            } catch (Exception $e) {
                logMessage("Error updating database: " . $e->getMessage());
            }
        }

        // Delete the file
        if (unlink($file)) {
            $deletedCount++;
            $freedBytes += $fileSize;
            logMessage("Deleted: $filename (age: {$fileAgeDays} days, size: " . formatBytes($fileSize) . ")");
        } else {
            logMessage("Failed to delete: $filename");
        }
    }
}

// Summary
$freedMB = round($freedBytes / 1024 / 1024, 2);
logMessage("=== Cleanup Complete ===");
logMessage("Deleted: $deletedCount files");
logMessage("Freed: $freedMB MB");
logMessage("");

// Output JSON for web access
if (php_sapi_name() !== 'cli') {
    echo json_encode([
        'success' => true,
        'deleted_files' => $deletedCount,
        'freed_bytes' => $freedBytes,
        'freed_mb' => $freedMB,
        'total_scanned' => $totalFiles,
        'retention_days' => RETENTION_DAYS
    ]);
}

/**
 * Format bytes to human readable
 */
function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
