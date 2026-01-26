<?php
/**
 * Auth Debugger (v2)
 */
header('Content-Type: application/json');

// FIX: Correct path to config.php (it's in config/ folder)
$configPath = __DIR__ . '/../../config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo json_encode(['error' => 'Config file not found at ' . $configPath]);
    exit;
}

$envToken = getenv('BACKEND_AUTH_TOKEN');

// Polyfill check
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
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

// Check raw server vars for clues
$rawAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'Not Found in $_SERVER';

echo json_encode([
    'status' => 'debug_v2',
    'env_loading' => [
        'BACKEND_AUTH_TOKEN' => $envToken ? 'LOADED' : 'MISSING',
        'token_length' => strlen($envToken ?? ''),
        'first_chars' => $envToken ? substr($envToken, 0, 5) . '...' : 'N/A'
    ],
    'header_check' => [
        'Authorization_Found' => !empty($authHeader),
        'Raw_Server_Auth' => $rawAuth,
        'Headers_Count' => count($headers)
    ],
    'server_info' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]
]);
