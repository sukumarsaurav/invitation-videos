<?php
/**
 * Auto-Fix .env Environment Configuration
 * 
 * Upload this to /api/remotion/fix-env.php and run it once.
 * It will append the missing BACKEND_AUTH_TOKEN to your .env file.
 */

header('Content-Type: application/json');

$envPath = __DIR__ . '/../../.env';
$tokenKey = "BACKEND_AUTH_TOKEN";
$tokenValue = "4dd79ce4c28b969c1f5014421f4d8a68ba03bbdafc2cfa40306c6e75873ec47c";

if (!file_exists($envPath)) {
    echo json_encode(['success' => false, 'error' => '.env file not found at ' . realpath(__DIR__ . '/../../')]);
    exit;
}

// Read current content
$content = file_get_contents($envPath);

// Check if already exists
if (strpos($content, $tokenKey) !== false) {
    echo json_encode([
        'success' => true, 
        'message' => 'Token already exists in .env', 
        'action' => 'none_needed'
    ]);
    exit;
}

// Append token
$newContent = $content . "\n\n# AWS Remotion Integration\n$tokenKey=$tokenValue\n";

// Write back
if (file_put_contents($envPath, $newContent)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully added BACKEND_AUTH_TOKEN to .env', 
        'action' => 'updated',
        'path' => realpath($envPath)
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to write to .env file. Check permissions.',
        'action' => 'failed'
    ]);
}
