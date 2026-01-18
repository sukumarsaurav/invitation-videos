<?php
/**
 * Debug: Check PHP error logs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2026') {
    die('Access denied');
}

header('Content-Type: text/plain');

// Show PHP upload limits
echo "=== PHP Upload Configuration ===\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "\n";

// Show UPLOAD_PATH
echo "=== Upload Path Configuration ===\n";
echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "Directory exists: " . (is_dir(UPLOAD_PATH) ? 'yes' : 'no') . "\n";
echo "Directory writable: " . (is_writable(UPLOAD_PATH) ? 'yes' : 'no') . "\n";
echo "\n";

// Try to read the last part of error log
$logPaths = [
    '/home/u277468165/domains/invitationvideos.com/public_html/error_log',
    '/home/u277468165/logs/error.log',
    ini_get('error_log'),
];

echo "=== Recent Error Log (customize.php related) ===\n";
foreach ($logPaths as $logPath) {
    if (!empty($logPath) && file_exists($logPath) && is_readable($logPath)) {
        echo "Found log at: {$logPath}\n";

        // Read last 200 lines and filter for customize.php
        $lines = file($logPath);
        $lastLines = array_slice($lines, -500);
        $relevant = array_filter($lastLines, function ($line) {
            return strpos($line, 'customize.php') !== false;
        });

        echo "Last 50 relevant entries:\n";
        foreach (array_slice($relevant, -50) as $line) {
            echo $line;
        }
        break;
    }
}

echo "\n=== Done ===\n";
