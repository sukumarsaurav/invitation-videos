<?php
/**
 * VideoInvites - Stripe Payment Service
 * 
 * Handles all Stripe payment operations for global customers (USD)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';

class StripeService
{
    private \Stripe\StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    }

    /**
     * Create a Payment Intent for checkout
     */
    public function createPaymentIntent(float $amount, array $metadata = []): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => $metadata
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve Payment Intent status
     */
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
                'metadata' => $paymentIntent->metadata->toArray()
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return null;
        }
    }

    /**
     * Confirm payment was successful
     */
    public function confirmPayment(string $paymentIntentId): bool
    {
        $paymentIntent = $this->getPaymentIntent($paymentIntentId);
        return $paymentIntent && $paymentIntent['status'] === 'succeeded';
    }

    /**
     * Process refund
     */
    public function refund(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $refundData = [
                'payment_intent' => $paymentIntentId
            ];

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = $this->stripe->refunds->create($refundData);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook(string $payload, string $sigHeader): array
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                STRIPE_WEBHOOK_SECRET
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    return [
                        'type' => 'payment_succeeded',
                        'payment_id' => $paymentIntent->id,
                        'metadata' => $paymentIntent->metadata->toArray()
                    ];

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    return [
                        'type' => 'payment_failed',
                        'payment_id' => $paymentIntent->id,
                        'error' => $paymentIntent->last_payment_error?->message ?? 'Unknown error'
                    ];

                default:
                    return ['type' => 'unhandled', 'event_type' => $event->type];
            }

        } catch (\UnexpectedValueException $e) {
            return ['type' => 'error', 'error' => 'Invalid payload'];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return ['type' => 'error', 'error' => 'Invalid signature'];
        }
    }
}
