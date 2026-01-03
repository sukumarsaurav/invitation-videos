<?php
/**
 * FAQ Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$pageTitle = 'Frequently Asked Questions';
$metaDescription = 'Find answers to common questions about video invitations - how they work, delivery times, payment options, revisions, and more.';

// FAQ data
$faqs = [
    'General' => [
        [
            'question' => 'What is ' . (APP_NAME ?? 'InvitationVideos') . '?',
            'answer' => 'We are a video invitation creation service that helps you create beautiful, personalized video invitations for all your special occasions - weddings, birthdays, anniversaries, baby showers, and more.'
        ],
        [
            'question' => 'How does it work?',
            'answer' => 'Simply choose a template, customize it with your details (names, dates, photos), complete payment, and we\'ll create your personalized video invitation within 24-48 hours. You\'ll receive a download link via email.'
        ],
        [
            'question' => 'What occasions do you cover?',
            'answer' => 'We offer templates for weddings, birthdays, anniversaries, baby showers, corporate events, festivals (Holi, Diwali), and many more celebrations.'
        ],
    ],
    'Orders & Delivery' => [
        [
            'question' => 'How long does it take to receive my video?',
            'answer' => 'Standard delivery is 24-48 hours after payment and submission of all required details. Express delivery options may be available for select templates.'
        ],
        [
            'question' => 'In what format will I receive my video?',
            'answer' => 'You\'ll receive your video in MP4 format (Full HD 1080p), optimized for sharing on WhatsApp, Instagram, Facebook, and other social media platforms.'
        ],
        [
            'question' => 'How will I receive my video?',
            'answer' => 'Once your video is ready, you\'ll receive an email with a download link. You can also access it from your "My Orders" page after logging in.'
        ],
        [
            'question' => 'Can I make changes after placing an order?',
            'answer' => 'Yes! We offer free revisions for text corrections, photo replacements, and minor adjustments. Just submit a revision request through your order page.'
        ],
    ],
    'Payments' => [
        [
            'question' => 'What payment methods do you accept?',
            'answer' => 'We accept all major credit/debit cards (Visa, Mastercard), UPI, net banking, and wallet payments. International customers can pay via Stripe (credit cards).'
        ],
        [
            'question' => 'Is my payment secure?',
            'answer' => 'Absolutely! All payments are processed through Stripe and Razorpay - industry-leading payment gateways with bank-level security and SSL encryption.'
        ],
        [
            'question' => 'Do you offer refunds?',
            'answer' => 'Yes, we offer full refunds before work begins and partial refunds during production. Please see our <a href="/refund" class="text-primary hover:underline">Refund Policy</a> for details.'
        ],
    ],
    'Technical' => [
        [
            'question' => 'What photo formats do you accept?',
            'answer' => 'We accept JPG, JPEG, PNG, and WebP formats. For best results, use high-resolution photos (at least 1080px wide).'
        ],
        [
            'question' => 'Can I use my own music?',
            'answer' => 'Currently, you can choose from our curated music library. We\'re working on adding custom music upload functionality.'
        ],
        [
            'question' => 'How many photos can I add?',
            'answer' => 'The number of photos depends on the template you choose. Most templates support 5-15 photos. The exact number is shown on each template page.'
        ],
    ],
];
?>

<?php ob_start(); ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-8">
        <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
        <span class="text-slate-400">/</span>
        <span class="font-medium text-slate-900 dark:text-white">FAQ</span>
    </nav>

    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
            Frequently Asked Questions
        </h1>
        <p class="text-lg text-slate-600 dark:text-slate-400">
            Find answers to common questions about our video invitation service
        </p>
    </div>

    <!-- Quick Contact -->
    <div
        class="bg-gradient-to-r from-primary/10 to-purple-100 dark:from-primary/20 dark:to-purple-900/20 rounded-2xl p-6 mb-10 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="font-bold text-slate-900 dark:text-white">Can't find what you're looking for?</h3>
            <p class="text-slate-600 dark:text-slate-400">Our support team is here to help</p>
        </div>
        <a href="/support"
            class="flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined">support_agent</span>
            Contact Support
        </a>
    </div>

    <!-- FAQ Sections -->
    <div class="space-y-8">
        <?php foreach ($faqs as $category => $questions): ?>
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
                <h2
                    class="text-lg font-bold px-6 py-4 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <?= Security::escape($category) ?>
                </h2>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($questions as $index => $faq): ?>
                        <details class="group" <?= $index === 0 ? 'open' : '' ?>>
                            <summary
                                class="flex items-center justify-between gap-4 p-6 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <span class="font-medium text-slate-900 dark:text-white">
                                    <?= Security::escape($faq['question']) ?>
                                </span>
                                <span
                                    class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">
                                    expand_more
                                </span>
                            </summary>
                            <div class="px-6 pb-6 text-slate-600 dark:text-slate-400">
                                <?= $faq['answer'] ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>