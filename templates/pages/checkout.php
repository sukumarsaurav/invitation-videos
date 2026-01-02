<?php
/**
 * Checkout Page
 * 
 * Handles payment with Stripe (Global) or Razorpay (India)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$orderId = intval($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: /templates');
    exit;
}

// Get order details
$order = Database::fetchOne(
    "SELECT o.*, t.title as template_title, t.thumbnail_url 
     FROM orders o 
     JOIN templates t ON o.template_id = t.id 
     WHERE o.id = ?",
    [$orderId]
);

if (!$order || $order['status'] !== 'pending') {
    header('Location: /templates');
    exit;
}

// Get user info
$user = [];
if (!empty($_SESSION['user_id'])) {
    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]) ?? [];
}

// Determine payment gateway based on currency
$isIndian = ($order['currency'] === 'INR');
$gateway = $isIndian ? 'razorpay' : 'stripe';

$pageTitle = 'Checkout - ' . $order['order_number'];
?>

<?php ob_start(); ?>

<!-- Payment SDK Scripts (loaded only on checkout page) -->
<?php if ($isIndian && defined('RAZORPAY_KEY_ID') && RAZORPAY_KEY_ID): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php elseif (defined('STRIPE_PUBLIC_KEY') && STRIPE_PUBLIC_KEY): ?>
    <script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>

<div class="max-w-7xl mx-auto px-4 md:px-8 py-8">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- Checkout Form -->
        <div class="lg:col-span-7 flex flex-col gap-6">

            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-sm font-medium">
                <a class="text-slate-500 hover:text-primary transition-colors" href="/templates">Templates</a>
                <span class="material-symbols-outlined text-base text-slate-400">chevron_right</span>
                <span class="text-primary font-bold">Checkout</span>
            </nav>

            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Secure Checkout</h1>
                <p class="text-slate-500">Complete your payment to download your video invitation.</p>
            </div>

            <!-- Billing Info -->
            <section
                class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <span class="material-symbols-outlined text-primary text-2xl">receipt_long</span>
                    <h2 class="text-xl font-bold tracking-tight">Billing Information</h2>
                </div>

                <form id="checkout-form" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <input type="hidden" name="gateway" value="<?= $gateway ?>">

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Full Name</span>
                        <input type="text" name="name" required
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="John Doe" value="<?= Security::escape($user['name'] ?? '') ?>">
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Email Address</span>
                        <input type="email" name="email" required
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="john@example.com" value="<?= Security::escape($user['email'] ?? '') ?>">
                    </label>

                    <?php if (!$isIndian): ?>
                        <label class="flex flex-col gap-2 md:col-span-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Billing Address</span>
                            <input type="text" name="address"
                                class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="123 Main St, City, State, ZIP">
                        </label>
                    <?php endif; ?>
                </form>
            </section>

            <!-- Payment Method -->
            <section
                class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <span class="material-symbols-outlined text-primary text-2xl">credit_card</span>
                    <h2 class="text-xl font-bold tracking-tight">Payment Method</h2>
                </div>

                <?php if ($isIndian): ?>
                    <!-- Razorpay for India -->
                    <div class="text-center py-6">
                        <p class="text-slate-600 mb-4">You will be redirected to Razorpay's secure payment page</p>
                        <div class="flex justify-center gap-4 items-center">
                            <img src="/assets/images/razorpay-logo.png" alt="Razorpay" class="h-8">
                            <span class="text-slate-400">|</span>
                            <span class="text-sm text-slate-500">UPI • Cards • NetBanking • Wallets</span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Stripe for Global -->
                    <div id="card-element"
                        class="p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                        <!-- Stripe Elements will mount here -->
                    </div>
                    <div id="card-errors" class="text-red-500 text-sm mt-2"></div>
                <?php endif; ?>
            </section>

        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24 flex flex-col gap-6">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 shadow-lg">
                    <h3 class="text-lg font-bold mb-4">Order Summary</h3>

                    <div class="flex gap-4 mb-6">
                        <div class="w-24 h-16 shrink-0 rounded-lg bg-cover bg-center shadow-sm"
                            style="background-image: url('<?= Security::escape($order['thumbnail_url'] ?? '') ?>');">
                        </div>
                        <div class="flex flex-col justify-center">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
                                <?= Security::escape($order['template_title']) ?>
                            </h4>
                            <p class="text-xs text-slate-500 mt-1">Order
                                #<?= Security::escape($order['order_number']) ?></p>
                        </div>
                        <div class="ml-auto flex items-center">
                            <span class="font-bold text-slate-900 dark:text-white">
                                <?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="flex gap-2 mb-6">
                        <input type="text" id="promo-code" placeholder="Promo code"
                            class="flex-1 h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:ring-2 focus:ring-primary/20">
                        <button type="button" onclick="applyPromo()"
                            class="px-4 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold transition-colors">
                            Apply
                        </button>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-800 mb-4">

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                            <span>Subtotal</span>
                            <span><?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-green-600 font-medium">
                            <span>Discount</span>
                            <span id="discount-amount">-<?= $order['currency'] === 'INR' ? '₹' : '$' ?>0.00</span>
                        </div>
                        <hr class="border-slate-100 dark:border-slate-800 border-dashed">
                        <div
                            class="flex justify-between items-center text-lg font-bold text-slate-900 dark:text-white pt-2">
                            <span>Total</span>
                            <span
                                id="total-amount"><?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?></span>
                        </div>
                    </div>

                    <button type="button" id="pay-button" onclick="processPayment()"
                        class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 px-4 rounded-xl shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">lock</span>
                        Pay <?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?>
                    </button>

                    <div class="mt-4 flex flex-col items-center gap-2">
                        <div class="flex items-center justify-center gap-1 text-xs text-slate-400">
                            <span class="material-symbols-outlined text-sm">lock_clock</span>
                            <span>Payments are secure and encrypted</span>
                        </div>
                    </div>
                </div>

                <!-- Support Card -->
                <div class="bg-primary/5 rounded-xl border border-primary/10 p-4 flex items-start gap-3">
                    <div class="bg-primary/10 p-2 rounded-full shrink-0 text-primary">
                        <span class="material-symbols-outlined text-lg">support_agent</span>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white">Need help with your order?</h5>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Our support team is available 24/7 to
                            assist you.</p>
                        <a class="text-xs font-bold text-primary mt-2 inline-block hover:underline"
                            href="/support">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const orderId = <?= $orderId ?>;
    const gateway = '<?= $gateway ?>';
    const amount = <?= $order['amount'] ?>;
    const currency = '<?= $order['currency'] ?>';

    <?php if (!$isIndian): ?>
        // Stripe initialization
        const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#1e293b',
                    '::placeholder': { color: '#94a3b8' }
                }
            }
        });
        cardElement.mount('#card-element');

        cardElement.on('change', function (event) {
            document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
        });
    <?php endif; ?>

    async function processPayment() {
        const button = document.getElementById('pay-button');
        button.disabled = true;
        button.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Processing...';

        try {
            if (gateway === 'stripe') {
                await processStripePayment();
            } else {
                await processRazorpayPayment();
            }
        } catch (error) {
            alert('Payment failed: ' + error.message);
            button.disabled = false;
            button.innerHTML = '<span class="material-symbols-outlined">lock</span> Pay ' + (currency === 'INR' ? '₹' : '$') + amount.toFixed(2);
        }
    }

    async function processStripePayment() {
        // Create payment intent
        const response = await fetch('/api/create-payment-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        });

        const { client_secret, error } = await response.json();

        if (error) throw new Error(error);

        // Confirm payment
        const { error: stripeError, paymentIntent } = await stripe.confirmCardPayment(client_secret, {
            payment_method: { card: cardElement }
        });

        if (stripeError) throw new Error(stripeError.message);

        // Redirect to confirmation
        window.location.href = '/order/' + orderId + '/confirmation';
    }

    async function processRazorpayPayment() {
        // Create Razorpay order
        const response = await fetch('/api/create-razorpay-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        });

        const { razorpay_order_id, key_id, error } = await response.json();

        if (error) throw new Error(error);

        const options = {
            key: key_id,
            amount: amount * 100,
            currency: 'INR',
            name: 'VideoInvites',
            description: 'Video Invitation',
            order_id: razorpay_order_id,
            handler: function (response) {
                // Verify payment on server
                fetch('/api/verify-razorpay', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature
                    })
                }).then(() => {
                    window.location.href = '/order/' + orderId + '/confirmation';
                });
            },
            theme: { color: '#7f13ec' }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    }

    let currentDiscount = 0;
    let currentTotal = amount;

    async function applyPromo() {
        const code = document.getElementById('promo-code').value.trim();
        if (!code) {
            showPromoError('Please enter a promo code');
            return;
        }

        const button = event.target;
        const originalText = button.innerText;
        button.disabled = true;
        button.innerText = 'Applying...';

        try {
            const response = await fetch('/api/promo/validate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, order_id: orderId })
            });

            const result = await response.json();

            if (result.success) {
                currentDiscount = result.discount_amount;
                currentTotal = result.new_total;

                // Update UI
                document.getElementById('discount-amount').textContent = result.discount_display;
                document.getElementById('total-amount').textContent = result.new_total_display;

                // Update pay button
                const payBtn = document.getElementById('pay-button');
                payBtn.innerHTML = '<span class="material-symbols-outlined">lock</span> Pay ' + result.new_total_display;

                // Show success and disable input
                showPromoSuccess(result.message + ' (' + result.promo_description + ')');
                document.getElementById('promo-code').disabled = true;
                button.disabled = true;
                button.innerText = 'Applied';
                button.classList.remove('hover:bg-slate-200');
                button.classList.add('bg-green-100', 'text-green-700');
            } else {
                showPromoError(result.error);
                button.disabled = false;
                button.innerText = originalText;
            }
        } catch (error) {
            showPromoError('Failed to validate promo code');
            button.disabled = false;
            button.innerText = originalText;
        }
    }

    function showPromoError(message) {
        const container = document.getElementById('promo-code').parentElement;
        removePromoMessages();
        const errorDiv = document.createElement('div');
        errorDiv.id = 'promo-message';
        errorDiv.className = 'text-red-500 text-xs mt-2 flex items-center gap-1';
        errorDiv.innerHTML = '<span class="material-symbols-outlined text-sm">error</span>' + message;
        container.appendChild(errorDiv);
    }

    function showPromoSuccess(message) {
        const container = document.getElementById('promo-code').parentElement;
        removePromoMessages();
        const successDiv = document.createElement('div');
        successDiv.id = 'promo-message';
        successDiv.className = 'text-green-600 text-xs mt-2 flex items-center gap-1';
        successDiv.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span>' + message;
        container.appendChild(successDiv);
    }

    function removePromoMessages() {
        const existing = document.getElementById('promo-message');
        if (existing) existing.remove();
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>