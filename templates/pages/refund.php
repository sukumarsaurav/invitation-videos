<?php
/**
 * Refund Policy Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$pageTitle = 'Refund Policy';
$metaDescription = 'VideoInvites refund policy - full refunds before work begins, partial refunds during production, and free revisions after delivery.';
?>

<?php ob_start(); ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-8">
        <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
        <span class="text-slate-400">/</span>
        <span class="font-medium text-slate-900 dark:text-white">Refund Policy</span>
    </nav>

    <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-8 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-2">Refund Policy</h1>
        <p class="text-slate-500 mb-8">Last updated: <?= date('F d, Y') ?></p>

        <!-- Quick Summary -->
        <div class="bg-primary/5 border border-primary/20 rounded-xl p-6 mb-8">
            <h3 class="font-bold text-primary mb-2">Quick Summary</h3>
            <p class="text-slate-600 dark:text-slate-400">
                We offer full refunds before work begins, partial refunds during production, and free revisions
                after delivery. Your satisfaction is our priority!
            </p>
        </div>

        <div class="prose prose-slate dark:prose-invert max-w-none">
            <h2 class="text-xl font-bold mt-8 mb-4">1. Full Refund (100%)</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-4">You are eligible for a full refund if:</p>
            <ul class="list-disc pl-6 text-slate-600 dark:text-slate-400 space-y-2 mb-6">
                <li>You cancel your order before work has begun</li>
                <li>We are unable to deliver your order within the promised timeframe (and you don't wish to wait)</li>
                <li>Technical issues on our end prevent delivery</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">2. Partial Refund (50%)</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-4">A partial refund may be issued if:</p>
            <ul class="list-disc pl-6 text-slate-600 dark:text-slate-400 space-y-2 mb-6">
                <li>You cancel after work has begun but before final delivery</li>
                <li>You provided incomplete information that prevents completion</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">3. No Refund</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-4">Refunds are not available if:</p>
            <ul class="list-disc pl-6 text-slate-600 dark:text-slate-400 space-y-2 mb-6">
                <li>The final video has been delivered and downloaded</li>
                <li>You simply changed your mind after delivery</li>
                <li>The video was created according to your specifications but you expected something different</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">4. Free Revisions</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                Before requesting a refund, please consider our revision policy. We offer free revisions for:
            </p>
            <ul class="list-disc pl-6 text-slate-600 dark:text-slate-400 space-y-2 mb-6">
                <li>Text corrections (spelling, dates, names)</li>
                <li>Photo replacements</li>
                <li>Music changes (from our library)</li>
                <li>Minor adjustments to meet your requirements</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">5. How to Request a Refund</h2>
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 mb-6">
                <ol class="list-decimal pl-6 text-slate-600 dark:text-slate-400 space-y-3">
                    <li>Go to <a href="/support" class="text-primary hover:underline">Support</a></li>
                    <li>Select "Refund Request" as the subject</li>
                    <li>Provide your order number and reason for refund</li>
                    <li>Our team will review and respond within 24-48 hours</li>
                </ol>
            </div>

            <h2 class="text-xl font-bold mt-8 mb-4">6. Refund Processing Time</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                Once approved, refunds are processed within 5-7 business days. The time for the refund to appear
                in your account depends on your payment method and bank.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">7. Contact Us</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                Have questions about our refund policy? Contact us at:
                <br><br>
                <a href="mailto:support@invitationvideos.com"
                    class="text-primary hover:underline">support@invitationvideos.com</a>
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>