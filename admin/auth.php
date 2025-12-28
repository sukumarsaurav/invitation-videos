<?php
/**
 * Admin Authentication Middleware
 * 
 * Include this file at the top of all protected admin pages
 * Uses the main user authentication system
 */

require_once __DIR__ . '/../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * Check if user is authenticated
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 */
function isAdmin(): bool
{
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current admin user data
 */
function getAdminUser(): ?array
{
    if (!isAdmin()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? 'Admin',
        'role' => $_SESSION['user_role'] ?? 'admin',
        'avatar' => $_SESSION['user_avatar'] ?? '',
        'logged_in_at' => $_SESSION['user_logged_in_at'] ?? 0,
    ];
}

/**
 * Require admin authentication
 * Redirects to main login page if not authenticated or not admin
 */
function requireAdminAuth(): void
{
    if (!isAuthenticated()) {
        // Store the intended URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login');
        exit;
    }

    if (!isAdmin()) {
        // User is logged in but not an admin
        http_response_code(403);
        die('<h1>Access Denied</h1><p>You do not have admin privileges.</p><p><a href="/">Go to Home</a></p>');
    }

    // Check session timeout (8 hours)
    $sessionTimeout = 8 * 60 * 60;
    if (isset($_SESSION['user_logged_in_at']) && (time() - $_SESSION['user_logged_in_at']) > $sessionTimeout) {
        // Session expired
        session_destroy();
        session_start();
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = 'Your session has expired. Please log in again.';
        header('Location: /login');
        exit;
    }
}

/**
 * Check if current user has specific role
 */
function hasRole(string $role): bool
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if current user is admin or editor
 */
function canManageContent(): bool
{
    return hasRole('admin') || hasRole('editor');
}

// Auto-require admin authentication when this file is included
requireAdminAuth();
