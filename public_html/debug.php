<?php
/**
 * Server Debug File
 * Access: https://invitationvideos.com/debug.php
 * DELETE THIS FILE AFTER DEBUGGING!
 */

echo "<h1>InvitationVideos - Server Debug</h1>";
echo "<pre>";

echo "=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n\n";

echo "=== File Permissions ===\n";
echo "Current file: " . __FILE__ . "\n";
echo "Current dir: " . __DIR__ . "\n";
echo "Is writable: " . (is_writable(__DIR__) ? 'Yes' : 'No') . "\n\n";

echo "=== Check Critical Files ===\n";
$files = [
    '../config/config.php',
    '../config/database.php',
    '../.env',
    '../.env.example',
    '../vendor/autoload.php',
    'index.php',
    '.htaccess'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    echo "$file: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "\n";
}

echo "\n=== Apache Modules ===\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "mod_rewrite: " . (in_array('mod_rewrite', $modules) ? '✅ Enabled' : '❌ Disabled') . "\n";
} else {
    echo "Cannot check Apache modules (might be nginx or CGI mode)\n";
}

echo "\n=== .htaccess Test ===\n";
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "✅ .htaccess exists\n";
    echo "Content:\n" . htmlspecialchars(file_get_contents($htaccess)) . "\n";
} else {
    echo "❌ .htaccess NOT found\n";
}

echo "\n=== Environment ===\n";
echo "APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : 'Not defined') . "\n";

echo "\n=== Test Config Load ===\n";
try {
    if (file_exists(__DIR__ . '/../config/config.php')) {
        require_once __DIR__ . '/../config/config.php';
        echo "✅ Config loaded successfully\n";
        echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'Not defined') . "\n";
        echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'Not defined') . "\n";
    } else {
        echo "❌ Config file not found\n";
    }
} catch (Exception $e) {
    echo "❌ Config load error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p style='color:red;font-weight:bold;'>⚠️ DELETE this file after debugging!</p>";
