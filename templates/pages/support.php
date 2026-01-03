<?php
/**
 * Support Page - Help Center & Contact
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$pageTitle = 'Support';
$metaDescription = 'Get help with your video invitation order. Submit a support ticket, chat on WhatsApp, or browse FAQs. We respond within 2-4 hours.';
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// FAQ items
$faqs = [
    [
        'question' => 'How long does it take to receive my video?',
        'answer' => 'Most videos are delivered within 24-48 hours after payment. Complex customizations may take up to 72 hours.'
    ],
    [
        'question' => 'Can I make changes after ordering?',
        'answer' => 'Minor text changes are free within 24 hours of ordering. Major changes may incur additional fees.'
    ],
    [
        'question' => 'What file format will I receive?',
        'answer' => 'Videos are delivered in MP4 format (H.264), optimized for sharing on WhatsApp, Instagram, and other platforms.'
    ],
    [
        'question' => 'Can I get a refund?',
        'answer' => 'We offer full refunds if you cancel before we start working on your video. Once production begins, we offer free revisions instead.'
    ],
    [
        'question' => 'How do I share my video invitation?',
        'answer' => 'After download, you can share via WhatsApp, email, or any social media. We also provide a shareable link for each order.'
    ],
    [
        'question' => 'Can I use my own music?',
        'answer' => 'Yes! You can upload your own music or choose from our royalty-free music library.'
    ]
];
?>

<?php ob_start(); ?>

<div class="max-w-4xl mx-auto px-4 py-8 sm:py-12">
    <!-- Header -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary/10 text-primary mb-4">
            <span class="material-symbols-outlined text-4xl">support_agent</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">How can we help?</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-3 max-w-xl mx-auto">
            Find answers to common questions or get in touch with our support team
        </p>
    </div>

    <?php if ($success): ?>
        <div class="mb-8 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= Security::escape($success) ?>
        </div>
    <?php endif; ?>

    <!-- Quick Contact Options -->
    <div class="grid sm:grid-cols-3 gap-4 mb-12">
        <a href="mailto:support@invitationvideos.com"
            class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 hover:shadow-lg hover:border-primary/30 transition-all text-center group">
            <div
                class="w-12 h-12 mx-auto rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-2xl">mail</span>
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Email Us</h3>
            <p class="text-sm text-slate-500 mt-1">support@invitationvideos.com</p>
        </a>

        <a href="https://wa.me/919876543210" target="_blank"
            class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 hover:shadow-lg hover:border-green-300 transition-all text-center group">
            <div
                class="w-12 h-12 mx-auto rounded-xl bg-green-100 text-green-600 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">WhatsApp</h3>
            <p class="text-sm text-slate-500 mt-1">Chat with us instantly</p>
        </a>

        <div
            class="p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
            <div
                class="w-12 h-12 mx-auto rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-3">
                <span class="material-symbols-outlined text-2xl">schedule</span>
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Response Time</h3>
            <p class="text-sm text-slate-500 mt-1">Usually within 2-4 hours</p>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">help</span>
            Frequently Asked Questions
        </h2>

        <div class="space-y-4">
            <?php foreach ($faqs as $index => $faq): ?>
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <button onclick="toggleFaq(<?= $index ?>)"
                        class="w-full p-5 text-left flex items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <span
                            class="font-medium text-slate-900 dark:text-white"><?= Security::escape($faq['question']) ?></span>
                        <span id="faqIcon<?= $index ?>"
                            class="material-symbols-outlined text-slate-400 transition-transform">expand_more</span>
                    </button>
                    <div id="faqAnswer<?= $index ?>" class="hidden px-5 pb-5">
                        <p class="text-slate-600 dark:text-slate-400"><?= Security::escape($faq['answer']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contact Form -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">chat</span>
            Send us a message
        </h2>

        <form action="/support" method="POST" class="space-y-5">
            <?= Security::csrfField() ?>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Your Name
                    </label>
                    <input type="text" id="name" name="name" required
                        value="<?= isset($_SESSION['user_name']) ? Security::escape($_SESSION['user_name']) : '' ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required
                        value="<?= isset($_SESSION['user_email']) ? Security::escape($_SESSION['user_email']) : '' ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Subject
                </label>
                <select id="subject" name="subject" required
                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                    <option value="">Select a topic...</option>
                    <option value="order">Order Issue</option>
                    <option value="payment">Payment Problem</option>
                    <option value="revision">Request Revision</option>
                    <option value="refund">Refund Request</option>
                    <option value="technical">Technical Support</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label for="order_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Order Number <span class="text-slate-400">(optional)</span>
                </label>
                <input type="text" id="order_id" name="order_id" value="<?= Security::escape($_GET['order'] ?? '') ?>"
                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                    placeholder="e.g., VI-2024-00001">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Message
                </label>
                <textarea id="message" name="message" rows="5" required
                    class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"
                    placeholder="Describe your issue or question..."></textarea>
            </div>

            <button type="submit"
                class="w-full py-3 px-6 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">send</span>
                Send Message
            </button>
        </form>
    </div>
</div>

<script>
    function toggleFaq(index) {
        const answer = document.getElementById('faqAnswer' + index);
        const icon = document.getElementById('faqIcon' + index);

        if (answer.classList.contains('hidden')) {
            answer.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            answer.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>