<?php
/**
 * Remotion Auth Helper
 */

function verifyRemotionAuth(): bool
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    // Check Bearer token
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $expectedToken = getenv('BACKEND_AUTH_TOKEN') ?: 'your_secret_token_here'; // Fallback for dev
        return hash_equals($expectedToken, $token);
    }

    return false;
}

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
