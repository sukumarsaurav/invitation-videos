<?php
/**
 * Auth Helper for Remotion API
 * 
 * Verifies the Authorization header token for Remotion API requests.
 */

// Load environment variables FIRST
require_once __DIR__ . '/../../config/config.php';

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

    // ============================================
    // METHOD 1: Direct API Key Auth (Simplest)
    // Header: Authorization: ApiKey <key>
    // ============================================
    if (preg_match('/^ApiKey\s+(.+)$/i', $authHeader, $matches)) {
        $apiKey = $matches[1];
        $expectedApiKey = getenv('REMOTION_API_KEY');

        if (!empty($expectedApiKey) && hash_equals($expectedApiKey, $apiKey)) {
            // API key is valid - return first admin user
            require_once __DIR__ . '/../../config/database.php';
            $user = Database::fetchOne(
                "SELECT id, email, name, role FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1"
            );
            return $user ?: null;
        }
        return null;
    }

    // ============================================
    // METHOD 2: Bearer Token Auth (Original)
    // Header: Authorization: Bearer <token>
    // ============================================
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

        // DEBUG - remove after fixing
        error_log("DEBUG Token Verification:");
        error_log("  Payload: " . $parts[0]);
        error_log("  APP_SECRET length: " . strlen($appSecret));
        error_log("  Expected sig: " . $expectedSignature);
        error_log("  Got sig: " . $signature);
        error_log("  Match: " . (hash_equals($expectedSignature, $signature) ? 'YES' : 'NO'));

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
