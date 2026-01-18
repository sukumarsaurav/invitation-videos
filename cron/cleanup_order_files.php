<?php

/**
 * Cleanup Order Files Cron Job
 * 
 * Deletes order files 7 days after first download.
 * Also cleans up expired draft directories.
 * 
 * Cron schedule (daily at 3 AM):
 * 0 3 * * * /usr/bin/php /home/u277468165/domains/invitationvideos.com/public_html/cron/cleanup_order_files.php
 * 
 * Can also be run manually for testing:
 * php cron/cleanup_order_files.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    die('CLI access only');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Order Files Cleanup - " . date('Y-m-d H:i:s') . " ===\n\n";

$startTime = microtime(true);
$stats = [
    'orders_cleaned' => 0,
    'drafts_cleaned' => 0,
    'files_deleted' => 0,
    'bytes_freed' => 0,
    'errors' => 0,
];

// ============================================
// 1. Clean up expired order files
// ============================================
echo "Checking for expired order files...\n";

$expiredOrders = Database::fetchAll(
    "SELECT id, order_number, files_directory 
     FROM orders 
     WHERE files_expire_at IS NOT NULL 
     AND files_expire_at < NOW() 
     AND files_directory IS NOT NULL"
);

echo "Found " . count($expiredOrders) . " orders with expired files.\n";

foreach ($expiredOrders as $order) {
    $dir = UPLOAD_PATH . $order['files_directory'];

    echo "  - Order {$order['order_number']}: ";

    if (is_dir($dir)) {
        // Get size before deletion
        $size = getDirectorySize($dir);

        // Recursively delete directory
        if (deleteDirectoryRecursive($dir)) {
            $stats['orders_cleaned']++;
            $stats['bytes_freed'] += $size;
            echo "deleted (freed " . formatBytes($size) . ")\n";
        } else {
            $stats['errors']++;
            echo "FAILED to delete\n";
            error_log("Cleanup: Failed to delete order directory: {$dir}");
        }
    } else {
        echo "directory not found, marking as cleaned\n";
    }

    // Mark as cleaned in database
    Database::query(
        "UPDATE orders SET files_directory = NULL WHERE id = ?",
        [$order['id']]
    );
}

// ============================================
// 2. Clean up expired draft directories
// ============================================
echo "\nChecking for expired draft files...\n";

$expiredDrafts = Database::fetchAll(
    "SELECT id, draft_token, files_directory 
     FROM draft_orders 
     WHERE expires_at < NOW() 
     AND files_directory IS NOT NULL"
);

echo "Found " . count($expiredDrafts) . " drafts with expired files.\n";

foreach ($expiredDrafts as $draft) {
    $dir = UPLOAD_PATH . $draft['files_directory'];
    $tokenPrefix = substr($draft['draft_token'], 0, 8);

    echo "  - Draft {$tokenPrefix}...: ";

    if (is_dir($dir)) {
        $size = getDirectorySize($dir);

        if (deleteDirectoryRecursive($dir)) {
            $stats['drafts_cleaned']++;
            $stats['bytes_freed'] += $size;
            echo "deleted (freed " . formatBytes($size) . ")\n";
        } else {
            $stats['errors']++;
            echo "FAILED to delete\n";
            error_log("Cleanup: Failed to delete draft directory: {$dir}");
        }
    } else {
        echo "directory not found\n";
    }

    // Delete the expired draft record entirely
    Database::query(
        "DELETE FROM draft_order_uploads WHERE draft_id = ?",
        [$draft['id']]
    );
    Database::query(
        "DELETE FROM draft_orders WHERE id = ?",
        [$draft['id']]
    );
}

// ============================================
// 3. Clean up orphaned draft directories
// ============================================
echo "\nChecking for orphaned draft directories...\n";

$draftsDir = UPLOAD_PATH . 'drafts/';
if (is_dir($draftsDir)) {
    $directories = glob($draftsDir . '*', GLOB_ONLYDIR);

    foreach ($directories as $dir) {
        $dirName = basename($dir);

        // Check if any draft still references this directory
        $exists = Database::fetchOne(
            "SELECT id FROM draft_orders WHERE files_directory LIKE ?",
            ['%' . $dirName . '%']
        );

        if (!$exists) {
            // Check if directory is older than 2 days
            $mtime = filemtime($dir);
            if ($mtime < strtotime('-2 days')) {
                $size = getDirectorySize($dir);

                if (deleteDirectoryRecursive($dir)) {
                    $stats['drafts_cleaned']++;
                    $stats['bytes_freed'] += $size;
                    echo "  - Orphaned directory {$dirName}: deleted\n";
                }
            }
        }
    }
}

// ============================================
// Summary
// ============================================
$duration = round(microtime(true) - $startTime, 2);

echo "\n=== Cleanup Complete ===\n";
echo "Duration: {$duration}s\n";
echo "Orders cleaned: {$stats['orders_cleaned']}\n";
echo "Drafts cleaned: {$stats['drafts_cleaned']}\n";
echo "Space freed: " . formatBytes($stats['bytes_freed']) . "\n";
echo "Errors: {$stats['errors']}\n";

// Log summary
error_log("Cleanup complete: {$stats['orders_cleaned']} orders, {$stats['drafts_cleaned']} drafts, " .
    formatBytes($stats['bytes_freed']) . " freed, {$stats['errors']} errors");

// ============================================
// Helper Functions
// ============================================

function deleteDirectoryRecursive(string $dir): bool
{
    if (!is_dir($dir)) {
        return true;
    }

    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return rmdir($dir);
    } catch (Exception $e) {
        error_log("Cleanup error: " . $e->getMessage());
        return false;
    }
}

function getDirectorySize(string $dir): int
{
    $size = 0;

    if (!is_dir($dir)) {
        return $size;
    }

    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
    } catch (Exception $e) {
        // Ignore errors
    }

    return $size;
}

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
