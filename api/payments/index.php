<?php
/**
 * Payment API Endpoints
 * 
 * Handles payment intent creation for checkout
 */

// Disable display errors to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Custom error handler to return JSON
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

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
    require_once __DIR__ . '/../../src/Services/DraftOrderService.php';

    $isDraft = !empty($input['is_draft']);
    $draftToken = $input['draft_token'] ?? null;
    $orderId = intval($input['order_id'] ?? 0);

    $order = null;
    $draftId = null;

    if ($isDraft && $draftToken) {
        // Fetch from draft_orders
        $draftService = new \InvitationVideos\Services\DraftOrderService();
        $draft = $draftService->getDraftByToken($draftToken);

        if ($draft) {
            $draftId = $draft['id'];
            $order = [
                'id' => $draft['id'],
                'amount' => $draft['amount'],
                'template_id' => $draft['template_id'],
                'order_number' => 'DRAFT-' . strtoupper(substr($draftToken, 0, 8)),
            ];
        }
    } else {
        // Legacy: fetch from orders table
        $order = Database::fetchOne(
            "SELECT * FROM orders WHERE id = ? AND (status = 'pending' OR payment_status = 'pending')",
            [$orderId]
        );
    }

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found or already processed']);
        return;
    }

    // Create payment intent
    $stripeService = new StripeService();
    $metadata = [
        'template_id' => $order['template_id'],
    ];

    if ($isDraft && $draftToken) {
        $metadata['draft_token'] = $draftToken;
        $metadata['draft_id'] = $draftId;
    } else {
        $metadata['order_id'] = $order['id'];
        $metadata['order_number'] = $order['order_number'];
    }

    $result = $stripeService->createPaymentIntent(
        floatval($order['amount']),
        $metadata
    );

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
        return;
    }

    // Store payment intent ID
    if ($isDraft && $draftId) {
        $draftService->updatePaymentReference($draftId, 'stripe', $result['payment_intent_id']);
    } else {
        Database::query(
            "UPDATE orders SET payment_id = ? WHERE id = ?",
            [$result['payment_intent_id'], $orderId]
        );
    }

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
    require_once __DIR__ . '/../../src/Services/DraftOrderService.php';

    $isDraft = !empty($input['is_draft']);
    $draftToken = $input['draft_token'] ?? null;
    $orderId = intval($input['order_id'] ?? 0);

    $order = null;
    $draftId = null;
    $customerInfo = ['name' => '', 'email' => '', 'phone' => ''];

    if ($isDraft && $draftToken) {
        // Fetch from draft_orders
        $draftService = new \InvitationVideos\Services\DraftOrderService();
        $draft = $draftService->getDraftByToken($draftToken);

        if ($draft) {
            $draftId = $draft['id'];
            $order = [
                'id' => $draft['id'],
                'amount' => $draft['amount'],
                'template_id' => $draft['template_id'],
                'order_number' => 'DRAFT-' . strtoupper(substr($draftToken, 0, 8)),
                'razorpay_order_id' => null,
            ];

            // Get customer info if user_id exists
            if ($draft['user_id']) {
                $user = Database::fetchOne("SELECT name, email, phone FROM users WHERE id = ?", [$draft['user_id']]);
                if ($user) {
                    $customerInfo = ['name' => $user['name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? ''];
                }
            }
        }
    } else {
        // Legacy: fetch from orders table
        $order = Database::fetchOne(
            "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             WHERE o.id = ? AND (o.status = 'pending' OR o.payment_status = 'pending')",
            [$orderId]
        );

        if ($order) {
            $customerInfo = [
                'name' => $order['customer_name'] ?? '',
                'email' => $order['customer_email'] ?? '',
                'phone' => $order['customer_phone'] ?? ''
            ];
        }
    }

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found or already processed']);
        return;
    }

    // Create Razorpay order with metadata
    $razorpayService = new RazorpayService();
    $metadata = [
        'template_id' => $order['template_id'],
    ];

    if ($isDraft && $draftToken) {
        $metadata['draft_token'] = $draftToken;
        $metadata['draft_id'] = $draftId;
    } else {
        $metadata['order_id'] = $order['id'];
        $metadata['order_number'] = $order['order_number'] ?? 'ORD-' . $order['id'];
    }

    $result = $razorpayService->createOrder(
        floatval($order['amount']),
        $metadata
    );

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
        return;
    }

    // Store Razorpay order ID
    if ($isDraft && $draftId) {
        $draftService->updatePaymentReference($draftId, 'razorpay', $result['order_id']);
    } else {
        Database::query(
            "UPDATE orders SET razorpay_order_id = ? WHERE id = ?",
            [$result['order_id'], $orderId]
        );
    }

    // Get checkout options
    $razorpayService = new RazorpayService();
    $checkoutOptions = $razorpayService->getCheckoutOptions(
        array_merge($order, ['razorpay_order_id' => $result['order_id']]),
        $customerInfo
    );

    echo json_encode([
        'razorpay_order_id' => $result['order_id'],
        'key_id' => RAZORPAY_KEY_ID,
        'amount' => $result['amount'],
        'checkout_options' => $checkoutOptions,
        'is_draft' => $isDraft,
        'draft_token' => $draftToken,
    ]);
}


/**
 * Verify Razorpay Payment
 */
function verifyRazorpayPayment(array $input): void
{
    require_once __DIR__ . '/../../src/Payment/RazorpayService.php';
    require_once __DIR__ . '/../../src/Services/DraftOrderService.php';

    $orderId = intval($input['order_id'] ?? 0);
    $razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $input['razorpay_order_id'] ?? '';
    $razorpaySignature = $input['razorpay_signature'] ?? '';
    $isDraft = !empty($input['is_draft']);
    $draftToken = $input['draft_token'] ?? null;

    // Debug logging
    error_log("verifyRazorpayPayment: Called with input: " . json_encode([
        'order_id' => $orderId,
        'is_draft' => $isDraft,
        'draft_token' => $draftToken,
        'razorpay_order_id' => $razorpayOrderId
    ]));

    if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    $razorpayService = new RazorpayService();
    $isValid = $razorpayService->verifyPayment($razorpayPaymentId, $razorpayOrderId, $razorpaySignature);

    if (!$isValid) {
        error_log("verifyRazorpayPayment: Signature verification FAILED");
        http_response_code(400);
        echo json_encode(['error' => 'Payment verification failed']);
        return;
    }

    error_log("verifyRazorpayPayment: Signature verification PASSED");

    $realOrderId = null;

    // Handle draft orders - convert to real order
    if ($isDraft && $draftToken) {
        error_log("verifyRazorpayPayment: Looking up draft with token: {$draftToken}");
        $draftService = new \InvitationVideos\Services\DraftOrderService();

        // Use lenient lookup (no expiry check) for payment verification
        $draft = $draftService->getDraftByTokenForVerification($draftToken);

        if (!$draft) {
            error_log("verifyRazorpayPayment: Draft NOT found by token, trying by razorpay_order_id: {$razorpayOrderId}");
            // Try to find by razorpay_order_id
            $draft = $draftService->getDraftByRazorpayOrderId($razorpayOrderId);
        }

        if ($draft) {
            error_log("verifyRazorpayPayment: Found draft ID #{$draft['id']}, converting to order...");
            // Convert draft to real order
            $realOrderId = $draftService->convertToOrder($draft, $razorpayPaymentId, 'razorpay');
            error_log("Razorpay Verify: Converted draft #{$draft['id']} to order #{$realOrderId}");
        } else {
            // Draft not found - it may have been already converted by webhook
            // Check if an order already exists with this payment
            error_log("verifyRazorpayPayment: Draft NOT found, checking if order already exists...");

            $existingOrder = Database::fetchOne(
                "SELECT id FROM orders WHERE payment_id = ? OR razorpay_order_id = ?",
                [$razorpayPaymentId, $razorpayOrderId]
            );

            if ($existingOrder) {
                error_log("verifyRazorpayPayment: Order already exists! ID: {$existingOrder['id']} (webhook already processed)");
                $realOrderId = $existingOrder['id'];
            } else {
                error_log("verifyRazorpayPayment: No order found either. Token: {$draftToken}, RazorpayOrderId: {$razorpayOrderId}");
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                return;
            }
        }
    } else {
        // Legacy: Update existing order in orders table
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID required for legacy orders']);
            return;
        }

        Database::query(
            "UPDATE orders SET 
                status = 'paid',
                payment_status = 'paid',
                order_status = 'queued',
                payment_id = ?,
                payment_gateway = 'razorpay',
                paid_at = NOW()
             WHERE id = ? AND (status = 'pending' OR payment_status = 'pending')",
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

        $realOrderId = $orderId;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'order_id' => $realOrderId,
        'redirect' => '/order/' . $realOrderId . '/confirmation',
    ]);
}

