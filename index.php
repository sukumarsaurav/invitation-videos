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
    // TODO: Handle support form submission (email or save to DB)
    $_SESSION['success'] = 'Thank you! We\'ll get back to you within 24 hours.';
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
