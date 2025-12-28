<?php
/**
 * Razorpay Webhook Handler
 * 
 * Endpoint: /api/webhooks/razorpay.php
 * 
 * Configure this URL in your Razorpay Dashboard:
 * https://dashboard.razorpay.com/app/website-app-settings/webhooks
 */

// Disable output buffering
ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Payment/RazorpayService.php';

// Set headers for webhook
header('Content-Type: application/json');

// Get the webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($payload) || empty($signature)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payload or signature']);
    exit;
}

try {
    $razorpayService = new RazorpayService();
    $result = $razorpayService->handleWebhook($payload, $signature);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Webhook verification failed');
    }

    $event = $result['event'];
    $eventType = $event['event'] ?? '';

    // Log the event
    error_log("Razorpay Webhook: Received event type: $eventType");

    switch ($eventType) {
        case 'payment.captured':
            handlePaymentCaptured($event['payload']['payment']['entity']);
            break;

        case 'payment.authorized':
            handlePaymentAuthorized($event['payload']['payment']['entity']);
            break;

        case 'payment.failed':
            handlePaymentFailed($event['payload']['payment']['entity']);
            break;

        case 'refund.created':
            handleRefundCreated($event['payload']['refund']['entity']);
            break;

        case 'order.paid':
            handleOrderPaid($event['payload']['order']['entity'], $event['payload']['payment']['entity']);
            break;

        default:
            error_log("Razorpay Webhook: Unhandled event type: $eventType");
    }

    // Return success
    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    error_log("Razorpay Webhook Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle payment captured (money received)
 */
function handlePaymentCaptured(array $payment): void
{
    $razorpayOrderId = $payment['order_id'] ?? null;
    $razorpayPaymentId = $payment['id'] ?? null;

    if (!$razorpayOrderId) {
        error_log("Razorpay Webhook: No order_id in payment");
        return;
    }

    // Find order by Razorpay order ID
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE razorpay_order_id = ?",
        [$razorpayOrderId]
    );

    if (!$order) {
        // Try to find by notes/metadata
        $notes = $payment['notes'] ?? [];
        $orderId = $notes['order_id'] ?? null;

        if ($orderId) {
            $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        }
    }

    if ($order) {
        Database::query(
            "UPDATE orders SET 
                status = 'paid',
                payment_status = 'paid',
                order_status = 'queued',
                payment_id = ?,
                payment_gateway = 'razorpay',
                paid_at = NOW()
             WHERE id = ? AND (status IN ('pending', 'processing') OR payment_status = 'pending')",
            [$razorpayPaymentId, $order['id']]
        );

        // Increment template purchase count
        Database::query(
            "UPDATE templates SET purchase_count = purchase_count + 1 WHERE id = ?",
            [$order['template_id']]
        );

        error_log("Razorpay Webhook: Order #{$order['id']} marked as paid");

        // TODO: Send confirmation email
        // TODO: Start video rendering
    }
}

/**
 * Handle payment authorized (needs capture for non-auto-capture)
 */
function handlePaymentAuthorized(array $payment): void
{
    $razorpayOrderId = $payment['order_id'] ?? null;

    if (!$razorpayOrderId) {
        return;
    }

    // For auto-capture enabled, this will be followed by payment.captured
    // For manual capture, you would capture the payment here

    error_log("Razorpay Webhook: Payment authorized for order: $razorpayOrderId");
}

/**
 * Handle payment failed
 */
function handlePaymentFailed(array $payment): void
{
    $razorpayOrderId = $payment['order_id'] ?? null;

    if (!$razorpayOrderId) {
        return;
    }

    $errorCode = $payment['error_code'] ?? 'unknown';
    $errorDescription = $payment['error_description'] ?? 'Payment failed';

    // Find order
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE razorpay_order_id = ?",
        [$razorpayOrderId]
    );

    if ($order) {
        Database::query(
            "UPDATE orders SET 
                status = 'failed',
                payment_status = 'failed',
                order_status = 'awaiting_payment',
                notes = ?
             WHERE id = ?",
            ["$errorCode: $errorDescription", $order['id']]
        );

        error_log("Razorpay Webhook: Order #{$order['id']} payment failed: $errorDescription");
    }
}

/**
 * Handle refund created
 */
function handleRefundCreated(array $refund): void
{
    $paymentId = $refund['payment_id'] ?? null;

    if (!$paymentId) {
        return;
    }

    // Find order by payment ID
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE payment_id = ?",
        [$paymentId]
    );

    if ($order) {
        $refundAmount = ($refund['amount'] ?? 0) / 100; // Convert paise to rupees

        Database::query(
            "UPDATE orders SET 
                status = 'refunded',
                payment_status = 'refunded',
                order_status = 'cancelled',
                discount_amount = ?
             WHERE id = ?",
            [$refundAmount, $order['id']]
        );

        error_log("Razorpay Webhook: Order #{$order['id']} refunded: ₹$refundAmount");
    }
}

/**
 * Handle order paid event
 */
function handleOrderPaid(array $razorpayOrder, array $payment): void
{
    // This is an alternative way to confirm payment
    // Usually handled by payment.captured, but included for completeness

    $razorpayOrderId = $razorpayOrder['id'] ?? null;

    if (!$razorpayOrderId) {
        return;
    }

    // Already handled by payment.captured in most cases
    error_log("Razorpay Webhook: Order paid event for: $razorpayOrderId");
}
