<?php
/**
 * Stripe Webhook Handler
 * 
 * Endpoint: /api/webhooks/stripe.php
 * 
 * Configure this URL in your Stripe Dashboard:
 * https://dashboard.stripe.com/webhooks
 */

// Disable output buffering
ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Payment/StripeService.php';

// Set headers for webhook
header('Content-Type: application/json');

// Get the webhook payload
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sigHeader)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payload or signature']);
    exit;
}

try {
    $stripeService = new StripeService();
    $result = $stripeService->handleWebhook($payload, $sigHeader);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Webhook processing failed');
    }

    $event = $result['event'];
    $eventType = $event['type'] ?? '';

    // Log the event
    error_log("Stripe Webhook: Received event type: $eventType");

    switch ($eventType) {
        case 'payment_intent.succeeded':
            handlePaymentSuccess($event['data']['object']);
            break;

        case 'payment_intent.payment_failed':
            handlePaymentFailed($event['data']['object']);
            break;

        case 'charge.refunded':
            handleRefund($event['data']['object']);
            break;

        case 'charge.dispute.created':
            handleDispute($event['data']['object']);
            break;

        default:
            // Log unhandled events for debugging
            error_log("Stripe Webhook: Unhandled event type: $eventType");
    }

    // Return success
    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (Exception $e) {
    error_log("Stripe Webhook Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle successful payment
 */
function handlePaymentSuccess(array $paymentIntent): void
{
    $orderId = $paymentIntent['metadata']['order_id'] ?? null;

    if (!$orderId) {
        error_log("Stripe Webhook: No order_id in payment intent metadata");
        return;
    }

    // Update order status
    Database::query(
        "UPDATE orders SET 
            status = 'paid',
            payment_id = ?,
            payment_gateway = 'stripe',
            paid_at = NOW()
         WHERE id = ? AND status = 'pending'",
        [$paymentIntent['id'], $orderId]
    );

    // Get order details
    $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);

    if ($order) {
        // Increment template purchase count
        Database::query(
            "UPDATE templates SET purchase_count = purchase_count + 1 WHERE id = ?",
            [$order['template_id']]
        );

        // TODO: Send confirmation email to customer
        // TODO: Start video rendering process

        error_log("Stripe Webhook: Order #$orderId marked as paid");
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailed(array $paymentIntent): void
{
    $orderId = $paymentIntent['metadata']['order_id'] ?? null;

    if (!$orderId) {
        return;
    }

    $failureMessage = $paymentIntent['last_payment_error']['message'] ?? 'Payment failed';

    Database::query(
        "UPDATE orders SET 
            status = 'failed',
            payment_notes = ?
         WHERE id = ? AND status = 'pending'",
        [$failureMessage, $orderId]
    );

    error_log("Stripe Webhook: Order #$orderId payment failed: $failureMessage");
}

/**
 * Handle refund
 */
function handleRefund(array $charge): void
{
    $paymentIntentId = $charge['payment_intent'] ?? null;

    if (!$paymentIntentId) {
        return;
    }

    // Find order by payment ID
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE payment_id = ?",
        [$paymentIntentId]
    );

    if ($order) {
        $refundAmount = $charge['amount_refunded'] / 100;

        Database::query(
            "UPDATE orders SET 
                status = 'refunded',
                refund_amount = ?,
                refunded_at = NOW()
             WHERE id = ?",
            [$refundAmount, $order['id']]
        );

        error_log("Stripe Webhook: Order #{$order['id']} refunded: $refundAmount");
    }
}

/**
 * Handle dispute/chargeback
 */
function handleDispute(array $dispute): void
{
    $chargeId = $dispute['charge'] ?? null;

    if (!$chargeId) {
        return;
    }

    // Log dispute for admin review
    error_log("Stripe Webhook: Dispute created for charge: $chargeId");

    // TODO: Create support ticket for admin to review
    // TODO: Send notification to admin
}
