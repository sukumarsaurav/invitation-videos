<?php
/**
 * Service Page: Wedding Invitation Video Maker
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Fetch relevant templates
$weddingTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE category = 'wedding' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
);

$pageTitle = 'Wedding Invitation Video Maker | Create Online in Minutes';
$metaDescription = 'Create stunning wedding invitation videos online. Choose from premium templates, customize with your photos and music, and download instant HD video specifically for WhatsApp.';

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
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-300 text-sm font-bold mb-6">
                    <span class="material-symbols-outlined text-base">favorite</span>
                    #1 Wedding Invitation Maker
                </div>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight mb-6">
                    Create Beautiful <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-rose-500 to-purple-600">Wedding Video
                        Invites</span>
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Skip the expensive designers. Create professional, movie-quality wedding invitation videos in
                    minutes. Perfect for WhatsApp, Instagram, and email.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="#templates"
                        class="w-full sm:w-auto px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all text-center">
                        Create Wedding Video
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
                        <span class="material-symbols-outlined text-6xl">movie</span>
                    </div>
                </div>
                <!-- Decorative elements -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-rose-500/10 rounded-full blur-3xl p-10"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500/10 rounded-full blur-3xl p-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Trending Wedding Templates
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Choose from our collection of premium,
                handcrafted designs. 100% customizable with your photos, music, and text.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($weddingTemplates as $template): ?>
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
            <a href="/templates?category=wedding"
                class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                View All Wedding Templates <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- Features / SEO Content -->
<section class="py-20 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-sm">
                <div
                    class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-300 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl">speed</span>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Instant Download</h3>
                <p class="text-slate-600 dark:text-slate-400">No waiting for designers. Edit your template and download
                    the HD video instantly.</p>
            </div>
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-sm">
                <div
                    class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl">music_note</span>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Your Choice of Music</h3>
                <p class="text-slate-600 dark:text-slate-400">Upload your favorite romantic song or choose from our
                    royalty-free library.</p>
            </div>
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-sm">
                <div
                    class="w-12 h-12 rounded-xl bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-300 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl">share</span>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">WhatsApp Friendly</h3>
                <p class="text-slate-600 dark:text-slate-400">Optimized for sharing on WhatsApp, Instagram Stories, and
                    Facebook.</p>
            </div>
        </div>
    </div>
</section>

<!-- Detailed SEO Content Support -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 prose prose-lg dark:prose-invert">
        <h2>Why Choose a Video Invitation for Your Wedding?</h2>
        <p>In the digital age, paper cards are becoming a thing of the past. A <strong>wedding invitation video</strong>
            is modern, eco-friendly, and creates a lasting impression. It allows you to tell your love story through
            photos, music, and beautiful animations that a static card simply can't match.</p>

        <h3>Benefits of using our Wedding Invitation Maker</h3>
        <ul>
            <li><strong>Save Time & Money:</strong> Traditional cards cost hundreds of dollars and take weeks to print.
                Our video invites start from free and are ready in minutes.</li>
            <li><strong>Easy Customization:</strong> You don't need video editing skills. Our easy-to-use editor lets
                you change text, colors, and photos with a few clicks.</li>
            <li><strong>Reach Everyone Instantly:</strong> Share your invitation via WhatsApp or social media to reach
                all your guests instantly, no matter where they are in the world.</li>
        </ul>

        <h2>How to Make a Wedding Invitation Video?</h2>
        <ol>
            <li><strong>Choose a Template:</strong> Browse our gallery of stunning wedding designs including
                traditional, modern, floral, and royal themes.</li>
            <li><strong>Personalize:</strong> Enter your event details (Bride & Groom names, Date, Venue). Upload your
                pre-wedding photos.</li>
            <li><strong>Add Music:</strong> Select a romantic track from our library or upload your own song.</li>
            <li><strong>Download & Share:</strong> Preview your video, and once you're happy, download it in Full HD
                quality to share with friends and family.</li>
        </ol>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>