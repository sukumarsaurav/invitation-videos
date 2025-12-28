<?php
/**
 * Google OAuth Service
 * 
 * Handles Google Sign-In authentication flow
 */

require_once __DIR__ . '/../../config/config.php';

class GoogleAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function __construct()
    {
        $this->clientId = GOOGLE_CLIENT_ID;
        $this->clientSecret = GOOGLE_CLIENT_SECRET;
        $this->redirectUri = GOOGLE_REDIRECT_URI;
    }

    /**
     * Generate Google OAuth authorization URL
     */
    public function getAuthUrl(): string
    {
        // Generate state token for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'state' => $state,
            'prompt' => 'select_account'
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): ?array
    {
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $response = $this->httpPost(self::TOKEN_URL, $data);

        if (!$response || isset($response['error'])) {
            return null;
        }

        return $response;
    }

    /**
     * Get user info from Google
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $url = self::USERINFO_URL;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Validate OAuth state token
     */
    public function validateState(string $state): bool
    {
        $storedState = $_SESSION['google_oauth_state'] ?? '';
        unset($_SESSION['google_oauth_state']);

        return hash_equals($storedState, $state);
    }

    /**
     * HTTP POST request
     */
    private function httpPost(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
