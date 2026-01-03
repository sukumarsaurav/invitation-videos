<?php
/**
 * Terms of Service Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$pageTitle = 'Terms of Service';
$metaDescription = 'VideoInvites terms of service - user responsibilities, intellectual property, order processing, payments, and liability information.';
?>

<?php ob_start(); ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-8">
        <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
        <span class="text-slate-400">/</span>
        <span class="font-medium text-slate-900 dark:text-white">Terms of Service</span>
    </nav>

    <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-8 md:p-12">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-2">Terms of Service</h1>
        <p class="text-slate-500 mb-8">Last updated: <?= date('F d, Y') ?></p>

        <div class="prose prose-slate dark:prose-invert max-w-none">
            <h2 class="text-xl font-bold mt-8 mb-4">1. Acceptance of Terms</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                By accessing and using <?= APP_NAME ?? 'InvitationVideos' ?> ("Service"), you accept and agree to be
                bound by
                these Terms of Service. If you do not agree to these terms, please do not use our Service.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">2. Description of Service</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                <?= APP_NAME ?? 'InvitationVideos' ?> provides customizable video invitation templates for various
                occasions
                including weddings, birthdays, anniversaries, and other celebrations. We create personalized video
                invitations based on the information and media you provide.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">3. User Responsibilities</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-4">You agree to:</p>
            <ul class="list-disc pl-6 text-slate-600 dark:text-slate-400 space-y-2 mb-6">
                <li>Provide accurate and complete information when placing orders</li>
                <li>Upload only content you have the right to use (photos, text, etc.)</li>
                <li>Not use the Service for any unlawful purpose</li>
                <li>Not submit offensive, defamatory, or inappropriate content</li>
                <li>Maintain the confidentiality of your account credentials</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">4. Intellectual Property</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                All video templates, designs, graphics, and other content provided by
                <?= APP_NAME ?? 'InvitationVideos' ?>
                are our intellectual property. You receive a license to use the final customized video for personal,
                non-commercial purposes only.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">5. Order Processing & Delivery</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-4">
                We strive to deliver your customized video within the timeframe specified at checkout. Delivery times
                may vary
                based on order complexity and volume.
            </p>
            <ul class="list-disc pl-6 text-slate-600 dark:text-slate-400 space-y-2 mb-6">
                <li>Standard delivery: 24-48 hours</li>
                <li>Express delivery: 12-24 hours (if available)</li>
                <li>Revisions: As per the package selected</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">6. Payment Terms</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                All payments are processed securely through our payment partners (Stripe, Razorpay). Prices are
                displayed
                in your local currency where available. Payment is required in full before work begins on your order.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">7. Modifications to Service</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                We reserve the right to modify, suspend, or discontinue any aspect of the Service at any time without
                prior notice. We may also update these Terms of Service from time to time.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">8. Limitation of Liability</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                <?= APP_NAME ?? 'InvitationVideos' ?> shall not be liable for any indirect, incidental, special, or
                consequential damages arising from your use of the Service. Our maximum liability is limited to the
                amount paid for the specific order in question.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">9. Contact</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">
                For any questions regarding these Terms of Service, please contact us at:
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