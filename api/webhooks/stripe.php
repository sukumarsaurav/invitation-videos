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
require_once __DIR__ . '/../../src/Services/AIGenerationService.php';

use InvitationVideos\Services\AIGenerationService;

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
    require_once __DIR__ . '/../../src/Services/DraftOrderService.php';

    $metadata = $paymentIntent['metadata'] ?? [];
    $orderId = $metadata['order_id'] ?? null;
    $draftToken = $metadata['draft_token'] ?? null;
    $draftId = $metadata['draft_id'] ?? null;
    $paymentId = $paymentIntent['id'];

    $order = null;

    // Check if this is a draft order first
    if ($draftToken) {
        $draftService = new \InvitationVideos\Services\DraftOrderService();
        $draft = $draftService->getDraftByToken($draftToken);

        if (!$draft && $draftId) {
            // Fall back to payment intent stored in draft
            $draft = $draftService->getDraftByStripePaymentIntent($paymentId);
        }

        if ($draft) {
            // Convert draft to real order
            $orderId = $draftService->convertToOrder($draft, $paymentId, 'stripe');
            error_log("Stripe Webhook: Converted draft to order #$orderId");

            // Fetch the newly created order
            $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        }
    }

    // Legacy flow: look up by order_id in metadata
    if (!$order && $orderId) {
        $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);

        if ($order && $order['payment_status'] !== 'paid') {
            // Update order status (both old and new columns)
            Database::query(
                "UPDATE orders SET 
                    status = 'paid',
                    payment_status = 'paid',
                    order_status = 'queued',
                    payment_id = ?,
                    payment_gateway = 'stripe',
                    paid_at = NOW()
                 WHERE id = ? AND (status = 'pending' OR payment_status = 'pending')",
                [$paymentId, $orderId]
            );

            // Increment template purchase count
            Database::query(
                "UPDATE templates SET purchase_count = purchase_count + 1 WHERE id = ?",
                [$order['template_id']]
            );
        }
    }

    if ($order) {
        error_log("Stripe Webhook: Order #{$order['id']} marked as paid");

        // Queue AI caricature generation if dress was selected
        queueAiGenerationIfNeeded($order['id']);

        // TODO: Send confirmation email to customer
        // TODO: Start video rendering process
    } else {
        error_log("Stripe Webhook: No order or draft found for payment intent: $paymentId");
    }
}

/**
 * Queue AI generation if order has dress/color selected
 */
function queueAiGenerationIfNeeded(int $orderId): void
{
    try {
        $aiService = new AIGenerationService();

        // Check if AI generation is enabled
        if (!$aiService->isEnabled()) {
            return;
        }

        // Get order with customization data
        $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            return;
        }

        // Parse customization data to check for dress selection
        $customizationData = json_decode($order['customization_data'] ?? '{}', true);
        $dressId = $customizationData['ai_dress_id'] ?? null;
        $colorId = $customizationData['ai_color_id'] ?? null;

        if (!$dressId) {
            // No dress selected, skip AI generation
            return;
        }

        // Get a reference image from uploads if available
        $originalImageUrl = null;
        $uploads = Database::fetchAll(
            "SELECT file_path FROM order_uploads WHERE order_id = ? AND (field_name LIKE '%photo%' OR field_name LIKE '%image%') LIMIT 1",
            [$orderId]
        );
        if (!empty($uploads)) {
            $originalImageUrl = $uploads[0]['file_path'];
        }

        // Queue the AI generation
        $queueId = $aiService->queueGeneration($orderId, $dressId, $colorId, $originalImageUrl ?? '');

        error_log("Stripe Webhook: Queued AI generation #{$queueId} for order #{$orderId}");

    } catch (Exception $e) {
        error_log("Stripe Webhook: Failed to queue AI generation for order #{$orderId}: " . $e->getMessage());
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
            payment_status = 'failed',
            order_status = 'awaiting_payment',
            notes = ?
         WHERE id = ? AND (status = 'pending' OR payment_status = 'pending')",
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
                payment_status = 'refunded',
                order_status = 'cancelled',
                discount_amount = ?
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
