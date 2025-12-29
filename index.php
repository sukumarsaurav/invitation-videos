<?php
/**
 * InvitationVideos - Application Entry Point
 * 
 * All requests are routed through this file
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Load core classes
require_once __DIR__ . '/src/Core/Security.php';
require_once __DIR__ . '/src/Core/Router.php';

// Set security headers
Security::setSecurityHeaders();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Initialize router
$router = new Router();

// Set base path (empty for root domain deployment)
$router->setBasePath('');

// ===================
// PUBLIC ROUTES
// ===================

// Home page
$router->get('/', function () {
    include __DIR__ . '/templates/pages/home.php';
});

// My Orders (user order history)
$router->get('/my-orders', function () {
    include __DIR__ . '/templates/pages/my-orders.php';
});

// My Support Tickets
$router->get('/my-tickets', function () {
    include __DIR__ . '/templates/pages/my-tickets.php';
});

$router->post('/my-tickets', function () {
    include __DIR__ . '/templates/pages/my-tickets.php';
});

// Template gallery
$router->get('/templates', function () {
    include __DIR__ . '/templates/pages/gallery.php';
});

// Template detail & customization (supports both slug and ID for backward compatibility)
$router->get('/template/{slug}', function ($slug) {
    $_GET['template_slug'] = $slug;
    include __DIR__ . '/templates/pages/customize.php';
});

// Handle template customization form POST
$router->post('/template/{slug}', function ($slug) {
    $_GET['template_slug'] = $slug;
    include __DIR__ . '/templates/pages/customize.php';
});

// Checkout page
$router->get('/checkout/{orderId}', function ($orderId) {
    $_GET['order_id'] = $orderId;
    include __DIR__ . '/templates/pages/checkout.php';
});

// Order confirmation
$router->get('/order/{orderId}/confirmation', function ($orderId) {
    $_GET['order_id'] = $orderId;
    include __DIR__ . '/templates/pages/confirmation.php';
});

// Blog listing
$router->get('/blog', function () {
    include __DIR__ . '/templates/pages/blog.php';
});

// Single blog post
$router->get('/blog/{slug}', function ($slug) {
    $_GET['slug'] = $slug;
    include __DIR__ . '/templates/pages/blog-post.php';
});

// ===================
// STATIC PAGES
// ===================

// Privacy Policy
$router->get('/privacy', function () {
    include __DIR__ . '/templates/pages/privacy.php';
});

// Terms of Service
$router->get('/terms', function () {
    include __DIR__ . '/templates/pages/terms.php';
});

// Refund Policy
$router->get('/refund', function () {
    include __DIR__ . '/templates/pages/refund.php';
});

// FAQ
$router->get('/faq', function () {
    include __DIR__ . '/templates/pages/faq.php';
});

// Contact Us
$router->get('/contact', function () {
    include __DIR__ . '/templates/pages/contact.php';
});

$router->post('/contact', function () {
    include __DIR__ . '/templates/pages/contact.php';
});

// ===================
// API ROUTES
// ===================

// Create payment intent (Stripe)
$router->post('/api/create-payment-intent', function () {
    $_GET['action'] = 'create-stripe-intent';
    require_once __DIR__ . '/api/payments/index.php';
});

// Create order (Razorpay)
$router->post('/api/create-razorpay-order', function () {
    $_GET['action'] = 'create-razorpay-order';
    require_once __DIR__ . '/api/payments/index.php';
});

// Verify Razorpay payment
$router->post('/api/verify-razorpay', function () {
    $_GET['action'] = 'verify-razorpay';
    require_once __DIR__ . '/api/payments/index.php';
});

// Stripe webhook
$router->post('/api/webhook/stripe', function () {
    require_once __DIR__ . '/api/webhooks/stripe.php';
});

// Razorpay webhook
$router->post('/api/webhook/razorpay', function () {
    require_once __DIR__ . '/api/webhooks/razorpay.php';
});

// Promo code validation
$router->post('/api/promo/validate', function () {
    require_once __DIR__ . '/api/promo/validate.php';
});

// Get template fields (for dynamic forms) - supports both ID and slug
$router->get('/api/template/{identifier}/fields', function ($identifier) {
    require_once __DIR__ . '/src/Controllers/TemplateController.php';
    $controller = new TemplateController();
    $controller->getFields($identifier);
});

// Submit customization form
$router->post('/api/template/{id}/customize', function ($id) {
    require_once __DIR__ . '/src/Controllers/TemplateController.php';
    $controller = new TemplateController();
    $controller->submitCustomization($id);
});

// ===================
// AUTH ROUTES
// ===================

// Support page
$router->get('/support', function () {
    include __DIR__ . '/templates/pages/support.php';
});

$router->post('/support', function () {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/src/Core/Security.php';

    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid form submission. Please try again.';
        header('Location: /support');
        exit;
    }

    // Get form data
    $name = Security::sanitizeString($_POST['name'] ?? '');
    $email = Security::sanitizeString($_POST['email'] ?? '');
    $subject = Security::sanitizeString($_POST['subject'] ?? '');
    $orderNumber = Security::sanitizeString($_POST['order_id'] ?? '');
    $message = Security::sanitizeString($_POST['message'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: /support');
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: /support');
        exit;
    }

    // Find or create user
    $user = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if (!$user) {
        // Create a guest user for non-registered users
        Database::query(
            "INSERT INTO users (email, name, role, status) VALUES (?, ?, 'customer', 'active')",
            [$email, $name]
        );
        $userId = Database::lastInsertId();
    } else {
        $userId = $user['id'];
    }

    // Find order if order number provided
    $orderId = null;
    if (!empty($orderNumber)) {
        $order = Database::fetchOne("SELECT id FROM orders WHERE order_number = ?", [$orderNumber]);
        if ($order) {
            $orderId = $order['id'];
        }
    }

    // Generate ticket number (ST-YYYYMMDD-XXXX)
    $today = date('Ymd');
    $lastTicket = Database::fetchOne(
        "SELECT ticket_number FROM support_tickets WHERE ticket_number LIKE ? ORDER BY id DESC LIMIT 1",
        ["ST-{$today}-%"]
    );

    if ($lastTicket) {
        $lastNumber = intval(substr($lastTicket['ticket_number'], -4));
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '0001';
    }
    $ticketNumber = "ST-{$today}-{$newNumber}";

    // Map subject to proper label
    $subjectLabels = [
        'order' => 'Order Issue',
        'payment' => 'Payment Problem',
        'revision' => 'Request Revision',
        'refund' => 'Refund Request',
        'technical' => 'Technical Support',
        'other' => 'Other'
    ];
    $subjectLabel = $subjectLabels[$subject] ?? $subject;

    // Create support ticket
    Database::query(
        "INSERT INTO support_tickets (ticket_number, user_id, order_id, subject, message, priority, status) 
         VALUES (?, ?, ?, ?, ?, 'medium', 'open')",
        [$ticketNumber, $userId, $orderId, $subjectLabel, $message]
    );

    $_SESSION['success'] = "Thank you! Your ticket #{$ticketNumber} has been created. We'll get back to you within 24 hours.";
    header('Location: /support');
    exit;
});

// Profile page
$router->get('/profile', function () {
    include __DIR__ . '/templates/pages/profile.php';
});

$router->post('/profile', function () {
    include __DIR__ . '/templates/pages/profile.php';
});

$router->get('/login', function () {
    include __DIR__ . '/templates/pages/login.php';
});

$router->post('/login', function () {
    require_once __DIR__ . '/src/Auth/AuthController.php';
    $auth = new AuthController();
    $auth->login();
});

$router->get('/register', function () {
    include __DIR__ . '/templates/pages/register.php';
});

$router->post('/register', function () {
    require_once __DIR__ . '/src/Auth/AuthController.php';
    $auth = new AuthController();
    $auth->register();
});

$router->get('/logout', function () {
    require_once __DIR__ . '/src/Auth/AuthController.php';
    $auth = new AuthController();
    $auth->logout();
});

// Google OAuth
$router->get('/auth/google', function () {
    require_once __DIR__ . '/src/Auth/GoogleAuthService.php';

    // Capture redirect URL from query param if present
    if (!empty($_GET['redirect'])) {
        $_SESSION['redirect_url'] = $_GET['redirect'];
    }

    $google = new GoogleAuthService();
    header('Location: ' . $google->getAuthUrl());
    exit;
});

$router->get('/auth/google/callback', function () {
    require_once __DIR__ . '/src/Auth/google-callback.php';
});

// ===================
// ADMIN ROUTES
// ===================

$router->get('/admin', function () {
    header('Location: /admin/dashboard.php');
    exit;
});

// ===================
// DISPATCH REQUEST
// ===================

$router->dispatch();
