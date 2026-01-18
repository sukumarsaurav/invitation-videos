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
require_once __DIR__ . '/../../src/Services/EmailService.php';
require_once __DIR__ . '/../../src/Services/AIGenerationService.php';

use InvitationVideos\Services\EmailService;
use InvitationVideos\Services\AIGenerationService;

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
        // Payment Events
        case 'payment.captured':
            handlePaymentCaptured($event['payload']['payment']['entity']);
            break;

        case 'payment.authorized':
            handlePaymentAuthorized($event['payload']['payment']['entity']);
            break;

        case 'payment.failed':
            handlePaymentFailed($event['payload']['payment']['entity']);
            break;

        // Order Events
        case 'order.paid':
            handleOrderPaid($event['payload']['order']['entity'], $event['payload']['payment']['entity']);
            break;

        // Refund Events
        case 'refund.created':
        case 'refund.processed':
            handleRefundCreated($event['payload']['refund']['entity']);
            break;

        case 'refund.failed':
            error_log("Razorpay Webhook: Refund failed - " . json_encode($event['payload']['refund']['entity'] ?? []));
            break;

        // Dispute Events
        case 'payment.dispute.created':
        case 'payment.dispute.under_review':
        case 'payment.dispute.action_required':
            handleDisputeCreated($event['payload']['dispute']['entity'] ?? $event['payload']['payment']['entity']);
            break;

        case 'payment.dispute.won':
            handleDisputeWon($event['payload']['dispute']['entity'] ?? $event['payload']['payment']['entity']);
            break;

        case 'payment.dispute.lost':
        case 'payment.dispute.closed':
            handleDisputeLost($event['payload']['dispute']['entity'] ?? $event['payload']['payment']['entity']);
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
    require_once __DIR__ . '/../../src/Services/DraftOrderService.php';

    $razorpayOrderId = $payment['order_id'] ?? null;
    $razorpayPaymentId = $payment['id'] ?? null;
    $notes = $payment['notes'] ?? [];

    if (!$razorpayOrderId) {
        error_log("Razorpay Webhook: No order_id in payment");
        return;
    }

    // First, try to find existing order by Razorpay order ID
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE razorpay_order_id = ?",
        [$razorpayOrderId]
    );

    if (!$order) {
        // Try to find by notes/metadata (legacy orders)
        $orderId = $notes['order_id'] ?? null;
        if ($orderId) {
            $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        }
    }

    // If still no order, check if it's a draft order
    if (!$order) {
        $draftService = new \InvitationVideos\Services\DraftOrderService();
        $draft = $draftService->getDraftByRazorpayOrderId($razorpayOrderId);

        if (!$draft) {
            // Also check by draft_token in notes
            $draftToken = $notes['draft_token'] ?? null;
            if ($draftToken) {
                $draft = $draftService->getDraftByToken($draftToken);
            }
        }

        if ($draft) {
            // Convert draft to real order
            $orderId = $draftService->convertToOrder($draft, $razorpayPaymentId, 'razorpay');
            error_log("Razorpay Webhook: Converted draft #{$draft['id']} to order #{$orderId}");

            // Fetch the newly created order for email
            $order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        }
    }

    if ($order) {
        // If order exists but hasn't been marked as paid yet (for legacy orders)
        if ($order['payment_status'] !== 'paid') {
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

            // Increment template purchase count (only if not already done during draft conversion)
            // Draft conversion already increments, but legacy orders need this
            if (empty($notes['draft_token'])) {
                Database::query(
                    "UPDATE templates SET purchase_count = purchase_count + 1 WHERE id = ?",
                    [$order['template_id']]
                );
            }
        }

        error_log("Razorpay Webhook: Order #{$order['id']} marked as paid");

        // Queue AI caricature generation if dress was selected
        queueAiGenerationIfNeeded($order['id']);

        // Send payment confirmation email
        try {
            $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$order['user_id']]);
            $orderWithTemplate = Database::fetchOne(
                "SELECT o.*, t.title as template_title FROM orders o LEFT JOIN templates t ON o.template_id = t.id WHERE o.id = ?",
                [$order['id']]
            );
            $orderWithTemplate['paid_at'] = date('Y-m-d H:i:s');

            if ($user && $orderWithTemplate) {
                EmailService::sendPaymentReceivedEmail($orderWithTemplate, $user);
            }
        } catch (Exception $emailError) {
            error_log("Payment email failed for Order #{$order['id']}: " . $emailError->getMessage());
        }
    } else {
        error_log("Razorpay Webhook: Could not find order or draft for razorpay_order_id: $razorpayOrderId");
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

        error_log("Razorpay Webhook: Queued AI generation #{$queueId} for order #{$orderId}");

    } catch (Exception $e) {
        error_log("Razorpay Webhook: Failed to queue AI generation for order #{$orderId}: " . $e->getMessage());
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

/**
 * Handle dispute created/under review
 */
function handleDisputeCreated(array $dispute): void
{
    $paymentId = $dispute['payment_id'] ?? null;

    if (!$paymentId) {
        return;
    }

    $order = Database::fetchOne("SELECT * FROM orders WHERE payment_id = ?", [$paymentId]);

    if ($order) {
        Database::query(
            "UPDATE orders SET notes = CONCAT(COALESCE(notes, ''), '\nDispute opened: ', ?) WHERE id = ?",
            [date('Y-m-d H:i:s'), $order['id']]
        );

        error_log("Razorpay Webhook: Dispute opened for Order #{$order['id']}");
    }
}

/**
 * Handle dispute won
 */
function handleDisputeWon(array $dispute): void
{
    $paymentId = $dispute['payment_id'] ?? null;

    if (!$paymentId) {
        return;
    }

    $order = Database::fetchOne("SELECT * FROM orders WHERE payment_id = ?", [$paymentId]);

    if ($order) {
        Database::query(
            "UPDATE orders SET notes = CONCAT(COALESCE(notes, ''), '\nDispute won: ', ?) WHERE id = ?",
            [date('Y-m-d H:i:s'), $order['id']]
        );

        error_log("Razorpay Webhook: Dispute WON for Order #{$order['id']}");
    }
}

/**
 * Handle dispute lost
 */
function handleDisputeLost(array $dispute): void
{
    $paymentId = $dispute['payment_id'] ?? null;

    if (!$paymentId) {
        return;
    }

    $order = Database::fetchOne("SELECT * FROM orders WHERE payment_id = ?", [$paymentId]);

    if ($order) {
        // Mark order as disputed/refunded since we lost the dispute
        Database::query(
            "UPDATE orders SET 
                status = 'disputed',
                payment_status = 'disputed',
                order_status = 'cancelled',
                notes = CONCAT(COALESCE(notes, ''), '\nDispute LOST: ', ?)
             WHERE id = ?",
            [date('Y-m-d H:i:s'), $order['id']]
        );

        error_log("Razorpay Webhook: Dispute LOST for Order #{$order['id']}");
    }
}

