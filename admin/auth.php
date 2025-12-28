<?php
/**
 * Admin Authentication Middleware
 * 
 * Include this file at the top of all protected admin pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if admin is authenticated
 */
function isAdminAuthenticated(): bool
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get current admin user data
 */
function getAdminUser(): ?array
{
    if (!isAdminAuthenticated()) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'],
        'email' => $_SESSION['admin_email'] ?? '',
        'name' => $_SESSION['admin_name'] ?? 'Admin',
        'role' => $_SESSION['admin_role'] ?? 'admin',
        'logged_in_at' => $_SESSION['admin_logged_in_at'] ?? 0,
    ];
}

/**
 * Require admin authentication
 * Redirects to login page if not authenticated
 */
function requireAdminAuth(): void
{
    if (!isAdminAuthenticated()) {
        // Store the intended URL for redirect after login
        $_SESSION['admin_redirect_url'] = $_SERVER['REQUEST_URI'];

        header('Location: /admin/login.php');
        exit;
    }

    // Check session timeout (8 hours)
    $sessionTimeout = 8 * 60 * 60;
    if (isset($_SESSION['admin_logged_in_at']) && (time() - $_SESSION['admin_logged_in_at']) > $sessionTimeout) {
        // Session expired
        session_destroy();
        session_start();
        $_SESSION['admin_redirect_url'] = $_SERVER['REQUEST_URI'];

        header('Location: /admin/login.php?expired=1');
        exit;
    }
}

/**
 * Check if current admin has specific role
 */
function hasAdminRole(string $role): bool
{
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
}

/**
 * Check if current admin is super admin
 */
function isSuperAdmin(): bool
{
    return hasAdminRole('admin');
}

/**
 * Require specific admin role
 */
function requireAdminRole(string $role): void
{
    requireAdminAuth();

    if (!hasAdminRole($role)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this page.');
    }
}

// Auto-require authentication when this file is included
// Comment out the line below if you want to manually call requireAdminAuth()
requireAdminAuth();
