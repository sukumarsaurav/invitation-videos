<?php
/**
 * Fix Missing Uploads
 * 
 * This script copies uploads from draft_order_uploads to order_uploads
 * for orders that were created but didn't have their uploads transferred.
 * 
 * Run from command line: php admin/fix-missing-uploads.php
 * Or access via browser with admin auth.
 */

require_once __DIR__ . '/../config/database.php';

// If accessed via browser, require admin auth
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/auth.php';
    header('Content-Type: text/html; charset=utf-8');
    echo "<pre style='font-family: monospace; padding: 20px;'>";
}

echo "=== Fix Missing Uploads Script ===\n\n";

// First, let's see what's in draft_order_uploads
echo "1. Checking draft_order_uploads table:\n";
$draftUploads = Database::fetchAll("SELECT * FROM draft_order_uploads ORDER BY id");
echo "   Found " . count($draftUploads) . " records in draft_order_uploads\n";
foreach ($draftUploads as $u) {
    echo "   - ID: {$u['id']}, Draft: {$u['draft_id']}, Field: {$u['field_name']}, File: {$u['stored_filename']}\n";
}

echo "\n2. Checking order_uploads table:\n";
$orderUploads = Database::fetchAll("SELECT ou.*, o.order_number FROM order_uploads ou JOIN orders o ON ou.order_id = o.id ORDER BY ou.id");
echo "   Found " . count($orderUploads) . " records in order_uploads\n";
foreach ($orderUploads as $u) {
    echo "   - ID: {$u['id']}, Order: {$u['order_number']}, Field: {$u['field_name']}, File: {$u['stored_filename']}\n";
}

echo "\n3. Checking orders that might be missing uploads:\n";
$orders = Database::fetchAll("SELECT o.id, o.order_number, o.created_at FROM orders o ORDER BY o.id DESC LIMIT 20");
foreach ($orders as $order) {
    $uploadCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM order_uploads WHERE order_id = ?", [$order['id']]);
    $count = $uploadCount['cnt'] ?? 0;
    $status = $count > 0 ? "✓ Has $count uploads" : "✗ NO UPLOADS";
    echo "   Order #{$order['order_number']} (ID: {$order['id']}): $status\n";
}

// Check if there are orphaned draft uploads (drafts that were deleted but uploads remain)
echo "\n4. Checking for orphaned draft uploads:\n";
$orphanedUploads = Database::fetchAll(
    "SELECT dou.* FROM draft_order_uploads dou 
     LEFT JOIN draft_orders do ON dou.draft_id = do.id 
     WHERE do.id IS NULL"
);
echo "   Found " . count($orphanedUploads) . " orphaned uploads (draft deleted but upload record remains)\n";

// Manual fix option
if (isset($_GET['fix']) && isset($_GET['draft_id']) && isset($_GET['order_id'])) {
    $draftId = intval($_GET['draft_id']);
    $orderId = intval($_GET['order_id']);

    echo "\n5. Attempting to copy uploads from draft #{$draftId} to order #{$orderId}:\n";

    $uploads = Database::fetchAll("SELECT * FROM draft_order_uploads WHERE draft_id = ?", [$draftId]);

    if (empty($uploads)) {
        echo "   No uploads found for draft #{$draftId}\n";
    } else {
        foreach ($uploads as $upload) {
            try {
                Database::query(
                    "INSERT INTO order_uploads (order_id, field_name, file_type, original_filename, stored_filename, file_path, mime_type, file_size)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $upload['field_name'],
                        $upload['file_type'],
                        $upload['original_filename'],
                        $upload['stored_filename'],
                        $upload['file_path'],
                        $upload['mime_type'],
                        $upload['file_size']
                    ]
                );
                echo "   ✓ Copied: {$upload['stored_filename']}\n";
            } catch (Exception $e) {
                echo "   ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n=== Script Complete ===\n";

if (php_sapi_name() !== 'cli') {
    echo "\n\n<hr>";
    echo "<p><strong>To manually copy uploads:</strong></p>";
    echo "<p>Add <code>?fix=1&draft_id=X&order_id=Y</code> to the URL</p>";
    echo "</pre>";
}
