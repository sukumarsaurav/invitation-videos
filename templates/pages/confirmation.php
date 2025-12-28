<?php
/**
 * Order Confirmation Page
 * 
 * Displayed after successful payment
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$orderId = intval($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: /templates');
    exit;
}

// Get order details with template info
$order = Database::fetchOne(
    "SELECT o.*, t.title as template_title, t.thumbnail_url, t.slug as template_slug
     FROM orders o 
     JOIN templates t ON o.template_id = t.id 
     WHERE o.id = ?",
    [$orderId]
);

if (!$order) {
    header('Location: /templates');
    exit;
}

$pageTitle = 'Order Confirmed - ' . $order['order_number'];
?>

<?php ob_start(); ?>

<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-lg w-full text-center">

        <!-- Success Animation -->
        <div class="mb-8">
            <div
                class="w-24 h-24 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center animate-bounce-in">
                <span class="material-symbols-outlined text-5xl text-green-600 dark:text-green-400">check_circle</span>
            </div>
        </div>

        <!-- Success Message -->
        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-3">
            Payment Successful!
        </h1>
        <p class="text-slate-600 dark:text-slate-400 mb-8">
            Thank you for your order. Your video invitation is being prepared.
        </p>

        <!-- Order Details Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 mb-8 text-left shadow-lg">

            <div class="flex gap-4 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="w-20 h-14 shrink-0 rounded-lg bg-cover bg-center shadow-sm"
                    style="background-image: url('<?= Security::escape($order['thumbnail_url'] ?? '') ?>');">
                </div>
                <div class="flex flex-col justify-center flex-1">
                    <h3 class="font-bold text-slate-900 dark:text-white">
                        <?= Security::escape($order['template_title']) ?>
                    </h3>
                    <p class="text-sm text-slate-500">Order #<?= Security::escape($order['order_number']) ?></p>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Status</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold 
                        <?php if ($order['status'] === 'paid'): ?>
                            bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                        <?php elseif ($order['status'] === 'processing'): ?>
                            bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                        <?php else: ?>
                            bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                        <?php endif; ?>">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Amount Paid</span>
                    <span class="font-bold text-slate-900 dark:text-white">
                        <?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Payment Method</span>
                    <span class="text-slate-700 dark:text-slate-300">
                        <?= ucfirst($order['payment_gateway'] ?? 'Card') ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Date</span>
                    <span class="text-slate-700 dark:text-slate-300">
                        <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- What's Next -->
        <div class="bg-primary/5 rounded-xl border border-primary/10 p-5 mb-8 text-left">
            <h4 class="font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">info</span>
                What happens next?
            </h4>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-green-500 text-base mt-0.5">check</span>
                    Your payment has been confirmed
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-primary text-base mt-0.5">schedule</span>
                    We're creating your personalized video (24-48 hours)
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-slate-400 text-base mt-0.5">mail</span>
                    You'll receive an email when your video is ready
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/my-orders"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-md shadow-primary/25 transition-all">
                <span class="material-symbols-outlined">receipt_long</span>
                View My Orders
            </a>
            <a href="/templates"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-all">
                <span class="material-symbols-outlined">explore</span>
                Browse More Templates
            </a>
        </div>

    </div>
</div>

<style>
    @keyframes bounce-in {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .animate-bounce-in {
        animation: bounce-in 0.5s ease-out;
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>