<?php
/**
 * Google OAuth Callback Handler
 * 
 * Processes Google Sign-In callback and creates/logs in user
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Auth/GoogleAuthService.php';
require_once __DIR__ . '/../../src/Services/GeoLocationService.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$googleAuth = new GoogleAuthService();

// Check for errors from Google
if (isset($_GET['error'])) {
    $_SESSION['error'] = 'Google sign-in was cancelled';
    header('Location: /login');
    exit;
}

// Validate authorization code
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    $_SESSION['error'] = 'Invalid Google response';
    header('Location: /login');
    exit;
}

// Validate state token (CSRF protection)
if (!$googleAuth->validateState($_GET['state'])) {
    $_SESSION['error'] = 'Invalid security token';
    header('Location: /login');
    exit;
}

// Exchange code for access token
$tokenData = $googleAuth->getAccessToken($_GET['code']);
if (!$tokenData) {
    $_SESSION['error'] = 'Failed to authenticate with Google';
    header('Location: /login');
    exit;
}

// Get user info from Google
$userInfo = $googleAuth->getUserInfo($tokenData['access_token']);
if (!$userInfo || !isset($userInfo['email'])) {
    $_SESSION['error'] = 'Failed to get user information from Google';
    header('Location: /login');
    exit;
}

$email = $userInfo['email'];
$name = $userInfo['name'] ?? '';
$googleId = $userInfo['sub'];
$picture = $userInfo['picture'] ?? '';

// Check if user exists
$user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

if ($user) {
    // Existing user - update Google ID if not set
    if (empty($user['google_id'])) {
        Database::query(
            "UPDATE users SET google_id = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?",
            [$googleId, $picture, $user['id']]
        );
    }
} else {
    // Create new user - detect country from IP
    $geoData = GeoLocationService::getCountryFromIP(GeoLocationService::getClientIP());

    Database::query(
        "INSERT INTO users (name, email, google_id, avatar_url, country_code, role, status, email_verified_at) 
         VALUES (?, ?, ?, ?, ?, 'customer', 'active', NOW())",
        [$name, $email, $googleId, $picture, $geoData['country_code']]
    );

    $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
}

// Log the user in
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_avatar'] = $user['avatar_url'] ?? '';
$_SESSION['user_logged_in_at'] = time();

// Update last login
Database::query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

// Determine redirect based on role
if ($user['role'] === 'admin') {
    $redirect = '/admin/dashboard.php';
} else {
    $redirect = $_SESSION['redirect_url'] ?? '/';
}
unset($_SESSION['redirect_url']);

$_SESSION['success'] = 'Signed in with Google successfully!';
header('Location: ' . $redirect);
exit;

