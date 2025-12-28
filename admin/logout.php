<?php
/**
 * Admin Logout
 */

session_start();

// Clear all admin session data
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_logged_in_at']);

// Regenerate session ID for security
session_regenerate_id(true);

// Redirect to login with success message
header('Location: /admin/login.php?logout=1');
exit;
