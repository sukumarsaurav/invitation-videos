<?php
/**
 * Auth Debugger
 * Upload this to /api/remotion/debug-auth.php to check server config
 */
header('Content-Type: application/json');

// Try to load env if possible (depending on how your app loads it)
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once __DIR__ . '/../../config.php';
}

$envToken = getenv('BACKEND_AUTH_TOKEN');
$serverToken = $_SERVER['BACKEND_AUTH_TOKEN'] ?? null;

// Check headers
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? 'MISSING';

echo json_encode([
    'debug_status' => 'running',
    'environment_check' => [
        'BACKEND_AUTH_TOKEN_env' => $envToken ? 'SET (Length: ' . strlen($envToken) . ')' : 'MISSING/EMPTY',
        'BACKEND_AUTH_TOKEN_server' => $serverToken ? 'SET' : 'MISSING',
        'current_value_preview' => $envToken ? substr($envToken, 0, 5) . '...' : 'N/A'
    ],
    'request_check' => [
        'Authorization_Header' => $authHeader
    ],
    'expected_configure_value' => '4dd79...'
]);
