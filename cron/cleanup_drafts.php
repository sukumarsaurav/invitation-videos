#!/usr/bin/env php
<?php
/**
 * Clean up expired draft orders
 * 
 * Run this script daily via cron:
 * 0 3 * * * php /path/to/cron/cleanup_drafts.php
 * 
 * This will delete draft orders that have passed their expiry date,
 * along with any associated uploaded files.
 */

// Error handling
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Load dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/DraftOrderService.php';

use InvitationVideos\Services\DraftOrderService;

// Log function
function logMessage(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
    error_log("DraftCleanup: $message");
}

try {
    logMessage("Starting draft order cleanup...");

    $draftService = new DraftOrderService();
    $deletedCount = $draftService->cleanupExpired();

    logMessage("Cleanup complete. Deleted $deletedCount expired draft(s).");

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

exit(0);
