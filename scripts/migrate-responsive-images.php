<?php
/**
 * Migration Script: Generate Responsive Thumbnails
 * 
 * This script generates responsive srcset variants (315w, 472w, 630w)
 * for all existing template thumbnails that don't already have them.
 * 
 * Run once via CLI: php scripts/migrate-responsive-images.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/ImageHelper.php';

echo "=== Responsive Thumbnail Migration ===\n\n";

// Get all templates with thumbnails
$templates = Database::fetchAll("SELECT id, title, thumbnail_url FROM templates WHERE thumbnail_url IS NOT NULL AND thumbnail_url != ''");

echo "Found " . count($templates) . " template(s) to process.\n\n";

$uploadDir = __DIR__ . '/../uploads/templates/';
$success = 0;
$skipped = 0;
$failed = 0;

foreach ($templates as $template) {
    $thumbnailUrl = $template['thumbnail_url'];
    $pathInfo = pathinfo($thumbnailUrl);
    $baseFilename = $pathInfo['filename'];

    // Check if 315w variant already exists
    $smallVariant = $uploadDir . $baseFilename . '-315w.webp';
    if (file_exists($smallVariant)) {
        echo "[SKIP] {$template['title']} - variants already exist\n";
        $skipped++;
        continue;
    }

    // Get the source file path
    $sourcePath = __DIR__ . '/..' . $thumbnailUrl;

    if (!file_exists($sourcePath)) {
        echo "[FAIL] {$template['title']} - source file not found: {$thumbnailUrl}\n";
        $failed++;
        continue;
    }

    // Generate responsive variants
    $result = ImageHelper::generateResponsiveThumbnails(
        $sourcePath,
        $uploadDir,
        $baseFilename,
        [315, 472, 630],
        70
    );

    if ($result['success']) {
        $variants = count($result['variants']);
        echo "[OK]   {$template['title']} - generated {$variants} variant(s)\n";
        $success++;
    } else {
        echo "[FAIL] {$template['title']} - {$result['error']}\n";
        $failed++;
    }
}

echo "\n=== Summary ===\n";
echo "Success: {$success}\n";
echo "Skipped: {$skipped}\n";
echo "Failed:  {$failed}\n";
echo "Total:   " . count($templates) . "\n";
