<?php
/**
 * Auth Helper for Remotion API
 * 
 * Verifies the Authorization header token for Remotion API requests.
 */

/**
 * Verify the Remotion Studio authentication token
 * 
 * @return array|null User data if valid, null if invalid
 */
function verifyRemotionToken(): ?array
{
    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return null;
    }

    $token = $matches[1];

    try {
        // Decode token
        $decoded = base64_decode($token);
        $parts = explode('.', $decoded);

        if (count($parts) !== 2) {
            return null;
        }

        $payload = json_decode($parts[0], true);
        $signature = $parts[1];

        // Verify signature
        $appSecret = getenv('APP_SECRET') ?: 'default-secret-change-me';
        $expectedSignature = hash('sha256', $parts[0] . $appSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        // Verify user still exists and is admin
        require_once __DIR__ . '/../../config/database.php';

        $user = Database::fetchOne(
            "SELECT id, email, name, role FROM users WHERE id = ? AND role IN ('admin', 'editor')",
            [$payload['user_id']]
        );

        return $user ?: null;

    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage());
        return null;
    }
}
