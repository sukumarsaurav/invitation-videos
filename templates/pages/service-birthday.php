<?php
/**
 * Service Page: Birthday Video Invitation Maker
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Fetch relevant templates
$birthdayTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE category = 'birthday' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
);

$pageTitle = 'Birthday Video Invitation Maker | Kids & Adults Birthday Videos';
$metaDescription = 'Create fun and exciting birthday invitation videos online. Perfect for kids first birthday or adult parties. Customize with photos, music, and share on WhatsApp.';

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
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300 text-sm font-bold mb-6">
                    <span class="material-symbols-outlined text-base">cake</span>
                    For All Ages
                </div>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight mb-6">
                    Fun & Exciting <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">Birthday
                        Video Invites</span>
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Make your party unforgettable from the start! Create amazing birthday invitation videos for kids and
                    adults in minutes.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="#templates"
                        class="w-full sm:w-auto px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all text-center">
                        Create Birthday Video
                    </a>
                    <a href="#how-it-works"
                        class="w-full sm:w-auto px-8 py-4 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all text-center flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">play_circle</span>
                        See How It Works
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-full">
                <div
                    class="relative aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border-4 border-white dark:border-slate-800 bg-slate-900">
                    <!-- Placeholder for hero video/image -->
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-800 text-slate-500">
                        <span class="material-symbols-outlined text-6xl">cake</span>
                    </div>
                </div>
                <!-- Decorative elements -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl p-10"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-orange-500/10 rounded-full blur-3xl p-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Popular Birthday Themes</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">From 1st birthday milestones to sweet 16s
                and adult parties, we have it all.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($birthdayTemplates as $template): ?>
                <a href="/template/<?= Security::escape($template['slug']) ?>"
                    class="group block bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30">
                    <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                        <img src="<?= Security::escape($template['thumbnail_url']) ?>"
                            alt="<?= Security::escape($template['title']) ?>"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                            loading="lazy">
                    </div>
                    <div class="p-4">
                        <h3
                            class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors truncate">
                            <?= Security::escape($template['title']) ?>
                        </h3>
                        <p
                            class="text-sm <?= $template['price_usd'] > 0 ? 'text-primary font-bold' : 'text-green-600 font-bold' ?>">
                            <?= $template['price_usd'] > 0 ? '$' . number_format($template['price_usd'], 2) : 'Free' ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="/templates?category=birthday"
                class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                View All Birthday Templates <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- Detailed SEO Content Support -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 prose prose-lg dark:prose-invert">
        <h2>Make Your Birthday Party Special with Video Invites</h2>
        <p>A birthday comes only once a year, so make the invitation special! Whether you are planning a <strong>1st
                birthday party</strong> for your baby, a surprise party for a friend, or a milestone 50th birthday
            celebration, a video invitation sets the perfect mood.</p>

        <h3>Why Create a Digital Birthday Invitation?</h3>
        <ul>
            <li><strong>Exciting & Fun:</strong> Unlike boring text messages, video invites use animation and music to
                get people excited about your party.</li>
            <li><strong>Eco-Friendly:</strong> Save paper and help the environment by going digital.</li>
            <li><strong>Easy to Share:</strong> Send it via WhatsApp, Facebook Messenger, or Instagram DMs. Everyone
                uses them!</li>
        </ul>

        <h2>Types of Birthday Video Intros We Offer</h2>
        <ul>
            <li><strong>Kids Themes:</strong> Superheroes, Princesses, Jungle Safari, Unicorn, and Cocomelon styles.
            </li>
            <li><strong>Milestone Birthdays:</strong> Elegant designs for 18th, 21st, 30th, 40th, and 50th birthdays.
            </li>
            <li><strong>Surprise Party:</strong> Secret agent themes and suspenseful music for surprise events.</li>
        </ul>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>