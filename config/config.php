<?php
/**
 * VideoInvites - Application Configuration
 * 
 * This file loads environment variables and defines application constants.
 * Sensitive credentials should be stored in .env file (never commit to git!)
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if (!empty($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

/**
 * Helper function to get environment variable with default
 */
function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}

// =============================================================================
// Application Settings
// =============================================================================

define('APP_NAME', env('APP_NAME', 'InvitationVideos'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('APP_URL', env('APP_URL', 'https://invitationvideos.com'));

// =============================================================================
// Database Configuration
// =============================================================================

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_DATABASE', env('DB_DATABASE', 'videoinvites'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// Stripe Configuration (Global Payments - USD)
// =============================================================================

define('STRIPE_SECRET_KEY', env('STRIPE_SECRET_KEY', ''));
define('STRIPE_PUBLIC_KEY', env('STRIPE_PUBLIC_KEY', ''));
define('STRIPE_WEBHOOK_SECRET', env('STRIPE_WEBHOOK_SECRET', ''));

// =============================================================================
// Razorpay Configuration (India Payments - INR)
// =============================================================================

define('RAZORPAY_KEY_ID', env('RAZORPAY_KEY_ID', ''));
define('RAZORPAY_KEY_SECRET', env('RAZORPAY_KEY_SECRET', ''));
define('RAZORPAY_WEBHOOK_SECRET', env('RAZORPAY_WEBHOOK_SECRET', ''));

// =============================================================================
// Google OAuth Configuration
// =============================================================================

define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', APP_URL . '/auth/google/callback'));

// =============================================================================
// File Upload Settings
// =============================================================================

define('UPLOAD_MAX_SIZE', (int) env('UPLOAD_MAX_SIZE', 10 * 1024 * 1024)); // 10MB
define('UPLOAD_PATH', __DIR__ . '/../' . env('UPLOAD_PATH', 'uploads/'));

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/mp3', 'audio/wav']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime']);

// =============================================================================
// Security Settings
// =============================================================================

define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));
define('SESSION_NAME', env('SESSION_NAME', 'videoinvites_session'));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 8 * 60 * 60)); // 8 hours
define('PASSWORD_COST', 12); // bcrypt cost factor

// =============================================================================
// Email Configuration
// =============================================================================

define('MAIL_DRIVER', env('MAIL_DRIVER', 'smtp'));
define('MAIL_HOST', env('MAIL_HOST', 'smtp.mailtrap.io'));
define('MAIL_PORT', (int) env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@videoinvites.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', APP_NAME));

// =============================================================================
// Social Media Configuration (SEO & Footer)
// =============================================================================

define('SOCIAL_FACEBOOK', env('SOCIAL_FACEBOOK', '#'));
define('SOCIAL_INSTAGRAM', env('SOCIAL_INSTAGRAM', '#'));
define('SOCIAL_TWITTER', env('SOCIAL_TWITTER', '#'));
define('SOCIAL_YOUTUBE', env('SOCIAL_YOUTUBE', '#'));
define('SOCIAL_LINKEDIN', env('SOCIAL_LINKEDIN', '#'));

// =============================================================================
// Error Handling
// =============================================================================

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('UTC');
