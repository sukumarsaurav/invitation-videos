<?php
/**
 * Invitation Videos - Razorpay Payment Service
 * 
 * Handles all Razorpay payment operations for Indian customers (INR)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';

use Razorpay\Api\Api;

class RazorpayService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    }

    /**
     * Create a Razorpay Order
     */
    public function createOrder(float $amount, array $metadata = []): array
    {
        try {
            $order = $this->api->order->create([
                'amount' => (int) ($amount * 100), // Convert to paise
                'currency' => 'INR',
                'receipt' => 'order_' . time(),
                'notes' => $metadata
            ]);

            return [
                'success' => true,
                'order_id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'key_id' => RAZORPAY_KEY_ID
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment signature
     */
    public function verifyPayment(string $paymentId, string $orderId, string $signature): bool
    {
        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ];

            $this->api->utility->verifyPaymentSignature($attributes);
            return true;

        } catch (\Exception $e) {
            error_log("Razorpay Verify Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment details
     */
    public function getPayment(string $paymentId): ?array
    {
        try {
            $payment = $this->api->payment->fetch($paymentId);

            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount / 100,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'order_id' => $payment->order_id,
                'notes' => $payment->notes ? (array) $payment->notes : []
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Capture authorized payment
     */
    public function capturePayment(string $paymentId, float $amount): array
    {
        try {
            $payment = $this->api->payment->fetch($paymentId);
            $result = $payment->capture([
                'amount' => (int) ($amount * 100)
            ]);

            return [
                'success' => true,
                'payment_id' => $result->id,
                'status' => $result->status
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process refund
     */
    public function refund(string $paymentId, ?float $amount = null): array
    {
        try {
            $refundData = [];
            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = $this->api->refund->create([
                'payment_id' => $paymentId,
                ...$refundData
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            // Verify webhook signature
            $expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_WEBHOOK_SECRET);

            if (!hash_equals($expectedSignature, $signature)) {
                return ['success' => false, 'error' => 'Invalid signature'];
            }

            $event = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'error' => 'Invalid JSON payload'];
            }

            return [
                'success' => true,
                'event' => $event
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Razorpay checkout options for frontend
     */
    public function getCheckoutOptions(array $order, array $user): array
    {
        return [
            'key' => RAZORPAY_KEY_ID,
            'amount' => $order['amount'] * 100,
            'currency' => 'INR',
            'name' => APP_NAME,
            'description' => 'Video Invitation - Order #' . $order['order_number'],
            'order_id' => $order['razorpay_order_id'],
            'prefill' => [
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'contact' => $user['phone'] ?? ''
            ],
            'theme' => [
                'color' => '#970747'
            ]
        ];
    }
}
