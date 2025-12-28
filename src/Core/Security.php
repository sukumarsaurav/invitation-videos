<?php
/**
 * VideoInvites - Security Utilities
 * 
 * XSS prevention, CSRF protection, input sanitization
 */

class Security
{

    /**
     * Escape output for HTML context (XSS prevention)
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape array of strings
     */
    public static function escapeArray(array $data): array
    {
        return array_map([self::class, 'escape'], $data);
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }

        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }

        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Regenerate CSRF token (call after successful form submission)
     */
    public static function regenerateCSRFToken(): string
    {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Get CSRF hidden input field HTML
     */
    public static function csrfField(): string
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }

    /**
     * Sanitize string input (remove dangerous characters)
     */
    public static function sanitizeString(string $input): string
    {
        $input = trim($input);
        $input = strip_tags($input);
        return $input;
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validate email format
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Hash password using bcrypt
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    }

    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate uploaded file
     */
    public static function validateUpload(array $file, array $allowedTypes, int $maxSize = UPLOAD_MAX_SIZE): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds limit (' . round($maxSize / 1024 / 1024, 1) . 'MB)';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type';
        }

        return $errors;
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        if (APP_ENV === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Rate limiting check (file-based for simplicity)
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        $rateLimitFile = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';

        $data = file_exists($rateLimitFile)
            ? json_decode(file_get_contents($rateLimitFile), true)
            : ['attempts' => 0, 'window_start' => time()];

        // Reset window if expired
        if (time() - $data['window_start'] > $windowSeconds) {
            $data = ['attempts' => 0, 'window_start' => time()];
        }

        // Check if rate limited
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        // Increment attempts
        $data['attempts']++;
        file_put_contents($rateLimitFile, json_encode($data));

        return true;
    }
}
