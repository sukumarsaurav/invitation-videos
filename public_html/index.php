<?php
/**
 * VideoInvites - Application Entry Point
 * 
 * All requests are routed through this file
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Load core classes
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/../src/Core/Router.php';

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
    include __DIR__ . '/../templates/pages/home.php';
});

// Template gallery
$router->get('/templates', function () {
    include __DIR__ . '/../templates/pages/gallery.php';
});

// Template detail & customization
$router->get('/template/{id}', function ($id) {
    $_GET['template_id'] = $id;
    include __DIR__ . '/../templates/pages/customize.php';
});

// Checkout page
$router->get('/checkout/{orderId}', function ($orderId) {
    $_GET['order_id'] = $orderId;
    include __DIR__ . '/../templates/pages/checkout.php';
});

// Order confirmation
$router->get('/order/{orderId}/confirmation', function ($orderId) {
    $_GET['order_id'] = $orderId;
    include __DIR__ . '/../templates/pages/confirmation.php';
});

// ===================
// API ROUTES
// ===================

// Create payment intent (Stripe)
$router->post('/api/create-payment-intent', function () {
    require_once __DIR__ . '/api/create-payment-intent.php';
});

// Create order (Razorpay)
$router->post('/api/create-razorpay-order', function () {
    require_once __DIR__ . '/api/create-razorpay-order.php';
});

// Stripe webhook
$router->post('/api/webhook/stripe', function () {
    require_once __DIR__ . '/api/webhook-stripe.php';
});

// Razorpay webhook
$router->post('/api/webhook/razorpay', function () {
    require_once __DIR__ . '/api/webhook-razorpay.php';
});

// Get template fields (for dynamic forms)
$router->get('/api/template/{id}/fields', function ($id) {
    require_once __DIR__ . '/../src/Controllers/TemplateController.php';
    $controller = new TemplateController();
    $controller->getFields($id);
});

// Submit customization form
$router->post('/api/template/{id}/customize', function ($id) {
    require_once __DIR__ . '/../src/Controllers/TemplateController.php';
    $controller = new TemplateController();
    $controller->submitCustomization($id);
});

// ===================
// AUTH ROUTES
// ===================

$router->get('/login', function () {
    include __DIR__ . '/../templates/pages/login.php';
});

$router->post('/login', function () {
    require_once __DIR__ . '/../src/Auth/AuthController.php';
    $auth = new AuthController();
    $auth->login();
});

$router->get('/register', function () {
    include __DIR__ . '/../templates/pages/register.php';
});

$router->post('/register', function () {
    require_once __DIR__ . '/../src/Auth/AuthController.php';
    $auth = new AuthController();
    $auth->register();
});

$router->get('/logout', function () {
    require_once __DIR__ . '/../src/Auth/AuthController.php';
    $auth = new AuthController();
    $auth->logout();
});

// ===================
// DISPATCH REQUEST
// ===================

$router->dispatch();
