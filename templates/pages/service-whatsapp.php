<?php
/**
 * Service Page: WhatsApp Wedding Invitation Video (Niche)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Reuse wedding templates but emphasize mobile/WhatsApp aspect
$whatsappTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE category = 'wedding' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
);

$pageTitle = 'WhatsApp Wedding Invitation Video | Mobile Friendly Invites';
$metaDescription = 'Create wedding invitation videos perfectly sized for WhatsApp Status and Chat. Fast loading, vertical (portrait) format, and crystal clear HD quality.';

ob_start();
?>

<!-- Hero Section -->
<section class="relative pt-20 pb-24 lg:pt-32 lg:pb-40 overflow-hidden">
    <div class="absolute inset-0 bg-slate-50 dark:bg-slate-900">
        <div class="absolute inset-0 bg-[url('/assets/images/pattern.svg')] opacity-[0.03]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="text-center lg:text-left">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-300 text-sm font-bold mb-6">
                    <!-- WhatsApp Icon-ish -->
                    <span class="material-symbols-outlined text-base">chat</span>
                    Optimized for WhatsApp
                </div>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight mb-6">
                    <span class="text-green-600 dark:text-green-500">WhatsApp</span> Wedding Invitation Videos
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    The easiest way to invite your guests. Create vertical full-screen videos that look perfect on
                    mobile phones and WhatsApp Status.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="#templates"
                        class="w-full sm:w-auto px-8 py-4 bg-green-600 text-white font-bold rounded-xl shadow-lg shadow-green-600/30 hover:bg-green-700 hover:scale-105 transition-all text-center">
                        Create WhatsApp Invite
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-full">
                <div
                    class="relative aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border-4 border-white dark:border-slate-800 bg-slate-900">
                    <!-- Phone mock visual implied -->
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-800 text-slate-500">
                        <span class="material-symbols-outlined text-6xl">smartphone</span>
                    </div>
                </div>
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-green-500/10 rounded-full blur-3xl p-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Best Sellers for WhatsApp
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Vertical (9:16) designs that fill the phone
                screen beautifully.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($whatsappTemplates as $template): ?>
                <a href="/template/<?= Security::escape($template['slug']) ?>"
                    class="group block bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30">
                    <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                        <img src="<?= Security::escape($template['thumbnail_url']) ?>"
                            alt="<?= Security::escape($template['title']) ?>"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                            loading="lazy">
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Detailed SEO Content Support -->
<section class="py-20 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 prose prose-lg dark:prose-invert">
        <h2>Why send invitations via WhatsApp?</h2>
        <p>In India and many parts of the world, WhatsApp is the primary mode of communication. Sending a
            <strong>WhatsApp wedding card</strong> is:</p>
        <ul>
            <li><strong>Instant:</strong> Delivered in seconds.</li>
            <li><strong>Interactive:</strong> Guests can reply immediately.</li>
            <li><strong>Cost Effective:</strong> No printing or postage costs.</li>
        </ul>

        <h3>Tips for WhatsApp Invitations</h3>
        <p>Keep the video under 30 seconds for easy viewing. Ensure the text is large enough to read on small screens.
            Our templates are designed specifically with these mobile-first principles in mind.</p>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>