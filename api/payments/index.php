<?php
/**
 * Payment API Endpoints
 * 
 * Handles payment intent creation for checkout
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Set JSON headers
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create-stripe-intent':
            createStripePaymentIntent($input);
            break;

        case 'create-razorpay-order':
            createRazorpayOrder($input);
            break;

        case 'verify-razorpay':
            verifyRazorpayPayment($input);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log("Payment API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Create Stripe Payment Intent
 */
function createStripePaymentIntent(array $input): void
{
    require_once __DIR__ . '/../../src/Payment/StripeService.php';

    $orderId = intval($input['order_id'] ?? 0);

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }

    // Get order
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE id = ? AND status = 'pending'",
        [$orderId]
    );

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found or already processed']);
        return;
    }

    // Create payment intent
    $stripeService = new StripeService();
    $result = $stripeService->createPaymentIntent(
        floatval($order['amount']),
        [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'template_id' => $order['template_id'],
        ]
    );

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
        return;
    }

    // Store payment intent ID
    Database::query(
        "UPDATE orders SET payment_id = ? WHERE id = ?",
        [$result['payment_intent_id'], $orderId]
    );

    echo json_encode([
        'client_secret' => $result['client_secret'],
        'payment_intent_id' => $result['payment_intent_id'],
    ]);
}

/**
 * Create Razorpay Order
 */
function createRazorpayOrder(array $input): void
{
    require_once __DIR__ . '/../../src/Payment/RazorpayService.php';

    $orderId = intval($input['order_id'] ?? 0);

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }

    // Get order
    $order = Database::fetchOne(
        "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = ? AND o.status = 'pending'",
        [$orderId]
    );

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found or already processed']);
        return;
    }

    // Create Razorpay order
    $razorpayService = new RazorpayService();
    $result = $razorpayService->createOrder(
        floatval($order['amount']),
        [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
        ]
    );

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
        return;
    }

    // Store Razorpay order ID
    Database::query(
        "UPDATE orders SET razorpay_order_id = ? WHERE id = ?",
        [$result['order_id'], $orderId]
    );

    // Get checkout options
    $checkoutOptions = $razorpayService->getCheckoutOptions(
        $result,
        [
            'name' => $order['customer_name'] ?? '',
            'email' => $order['customer_email'] ?? '',
            'phone' => $order['customer_phone'] ?? '',
        ]
    );

    echo json_encode([
        'razorpay_order_id' => $result['order_id'],
        'key_id' => RAZORPAY_KEY_ID,
        'amount' => $result['amount'],
        'checkout_options' => $checkoutOptions,
    ]);
}

/**
 * Verify Razorpay Payment
 */
function verifyRazorpayPayment(array $input): void
{
    require_once __DIR__ . '/../../src/Payment/RazorpayService.php';

    $orderId = intval($input['order_id'] ?? 0);
    $razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $input['razorpay_order_id'] ?? '';
    $razorpaySignature = $input['razorpay_signature'] ?? '';

    if (!$orderId || !$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $razorpayService = new RazorpayService();
    $isValid = $razorpayService->verifyPayment($razorpayPaymentId, $razorpayOrderId, $razorpaySignature);

    if (!$isValid) {
        http_response_code(400);
        echo json_encode(['error' => 'Payment verification failed']);
        return;
    }

    // Update order status
    Database::query(
        "UPDATE orders SET 
            status = 'paid',
            payment_id = ?,
            payment_gateway = 'razorpay',
            paid_at = NOW()
         WHERE id = ? AND status = 'pending'",
        [$razorpayPaymentId, $orderId]
    );

    // Increment template purchase count
    $order = Database::fetchOne("SELECT template_id FROM orders WHERE id = ?", [$orderId]);
    if ($order) {
        Database::query(
            "UPDATE templates SET purchase_count = purchase_count + 1 WHERE id = ?",
            [$order['template_id']]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'redirect' => '/order/' . $orderId . '/confirmation',
    ]);
}
