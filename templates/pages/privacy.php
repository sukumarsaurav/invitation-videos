<?php
/**
 * Privacy Policy Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$pageTitle = 'Privacy Policy';
$metaDescription = 'Invitation Videos privacy policy - learn how we collect, use, and protect your personal information and uploaded content for video invitations.';
?>

<?php ob_start(); ?>

<div class="container-narrow section-padding">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-8">
        <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
        <span class="text-slate-400">/</span>
        <span class="font-medium text-slate-900">Privacy Policy</span>
    </nav>

    <div
        class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8 md:p-12">
        <h1 class="heading-section text-slate-900 mb-2">Privacy Policy</h1>
        <p class="text-slate-500 mb-8">Last updated: <?= date('F d, Y') ?></p>

        <div class="prose prose-slate max-w-none">
            <h2 class="text-xl font-bold mt-8 mb-4">1. Information We Collect</h2>
            <p class="text-slate-600 mb-4">
                We collect information you provide directly to us, such as when you create an account, place an order,
                or contact us for support. This may include:
            </p>
            <ul class="list-disc pl-6 text-slate-600 space-y-2 mb-6">
                <li>Name and email address</li>
                <li>Phone number (optional)</li>
                <li>Payment information (processed securely by our payment partners)</li>
                <li>Photos and content you upload for video customization</li>
                <li>Communication preferences</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">2. How We Use Your Information</h2>
            <p class="text-slate-600 mb-4">We use the information we collect to:</p>
            <ul class="list-disc pl-6 text-slate-600 space-y-2 mb-6">
                <li>Process and deliver your video invitation orders</li>
                <li>Send order confirmations and updates</li>
                <li>Respond to your questions and provide customer support</li>
                <li>Improve our services and develop new features</li>
                <li>Send promotional communications (with your consent)</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">3. Information Sharing</h2>
            <p class="text-slate-600 mb-6">
                We do not sell, trade, or rent your personal information to third parties. We may share your
                information with trusted service providers who assist us in operating our website and conducting
                our business, as long as they agree to keep this information confidential.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">4. Data Security</h2>
            <p class="text-slate-600 mb-6">
                We implement appropriate security measures to protect your personal information against unauthorized
                access, alteration, disclosure, or destruction. All payment transactions are processed through
                secure, encrypted connections (SSL/TLS).
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">5. Cookies</h2>
            <p class="text-slate-600 mb-6">
                We use cookies to enhance your experience on our website. Cookies help us remember your preferences,
                understand how you use our site, and improve our services. You can choose to disable cookies through
                your browser settings.
            </p>

            <h2 class="text-xl font-bold mt-8 mb-4">6. Your Rights</h2>
            <p class="text-slate-600 mb-4">You have the right to:</p>
            <ul class="list-disc pl-6 text-slate-600 space-y-2 mb-6">
                <li>Access your personal data</li>
                <li>Correct inaccurate data</li>
                <li>Request deletion of your data</li>
                <li>Opt-out of marketing communications</li>
                <li>Export your data</li>
            </ul>

            <h2 class="text-xl font-bold mt-8 mb-4">7. Contact Us</h2>
            <p class="text-slate-600 mb-6">
                If you have any questions about this Privacy Policy, please contact us at:
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