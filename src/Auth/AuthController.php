<?php
/**
 * Auth Controller
 * 
 * Handles user authentication (login, register, logout)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Core/Security.php';
require_once __DIR__ . '/../Services/EmailService.php';

use VideoInvites\Services\EmailService;

class AuthController
{
    /**
     * Handle user login
     */
    public function login(): void
    {
        // Validate CSRF
        if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /login');
            exit;
        }

        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Please enter email and password';
            header('Location: /login');
            exit;
        }

        // Rate limiting
        $rateLimitKey = 'login_' . md5($email);
        if (!Security::checkRateLimit($rateLimitKey, 5, 900)) {
            $_SESSION['error'] = 'Too many login attempts. Please try again in 15 minutes.';
            header('Location: /login');
            exit;
        }

        // Find user
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );

        if ($user && Security::verifyPassword($password, $user['password_hash'])) {
            // Login successful
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_logged_in_at'] = time();

            // Update last login
            Database::query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            // Redirect to intended URL or home
            $redirect = $_SESSION['redirect_url'] ?? '/';
            unset($_SESSION['redirect_url']);

            header('Location: ' . $redirect);
            exit;
        }

        $_SESSION['error'] = 'Invalid email or password';
        header('Location: /login');
        exit;
    }

    /**
     * Handle user registration
     */
    public function register(): void
    {
        // Validate CSRF
        if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /register');
            exit;
        }

        $name = Security::sanitizeString($_POST['name'] ?? '');
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        }

        if (!Security::isValidEmail($email)) {
            $errors[] = 'Valid email is required';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }

        // Check if email exists
        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'Email already registered';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['name' => $name, 'email' => $email];
            header('Location: /register');
            exit;
        }

        // Create user
        $passwordHash = Security::hashPassword($password);

        Database::query(
            "INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'customer', 'active')",
            [$name, $email, $passwordHash]
        );

        $userId = Database::lastInsertId();

        // Auto-login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_logged_in_at'] = time();

        $_SESSION['success'] = 'Account created successfully!';

        // Send welcome email
        try {
            EmailService::sendWelcomeEmail($email, $name);
        } catch (Exception $e) {
            error_log('Welcome email failed: ' . $e->getMessage());
        }

        // Redirect to intended URL or home
        $redirect = $_SESSION['redirect_url'] ?? '/';
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Handle user logout
     */
    public function logout(): void
    {
        // Clear user session data
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_logged_in_at']);

        session_regenerate_id(true);

        header('Location: /?logged_out=1');
        exit;
    }
}
