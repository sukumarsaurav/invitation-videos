<?php
/**
 * Service Page: Save The Date Video Maker
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Fetch relevant templates (Save the date category usually is mapped to wedding or specific tag, adjusting query to wedding for now or specific if exists. Looking at index.php, category slug is 'save_the_date' in the array, let's try that first)
$saveTheDateTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE category = 'save_the_date' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
);

// Fallback to wedding if empty
if (empty($saveTheDateTemplates)) {
    $saveTheDateTemplates = Database::fetchAll(
        "SELECT * FROM templates WHERE category = 'wedding' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
    );
}

$pageTitle = 'Save the Date Video Maker | Animated Wedding Announcements';
$metaDescription = 'Create a Save the Date video that tells your love story. Share your engagement news with style using our romantic and elegant video templates.';

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
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-sm font-bold mb-6">
                    <span class="material-symbols-outlined text-base">event</span>
                    Perfect Announcement
                </div>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight mb-6">
                    Unique <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-indigo-600">Save
                        The Date</span> Videos
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Announce your big day before the formal card. Share your pre-wedding photos in a cinematic video
                    that gets everyone excited.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="#templates"
                        class="w-full sm:w-auto px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all text-center">
                        Create Video Now
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-full">
                <div
                    class="relative aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border-4 border-white dark:border-slate-800 bg-slate-900">
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-800 text-slate-500">
                        <span class="material-symbols-outlined text-6xl">calendar_month</span>
                    </div>
                </div>
                <!-- Decorative elements -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl p-10"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl p-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Trending Announcements</h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Short, sweet, and shareable videos to lock
                the date.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($saveTheDateTemplates as $template): ?>
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
    </div>
</section>

<!-- Detailed SEO Content Support -->
<section class="py-20 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 prose prose-lg dark:prose-invert">
        <h2>What is a Save the Date Video?</h2>
        <p>A <strong>Save the Date</strong> is a preliminary invitation sent months before the wedding. It informs
            guests of the date and location so they can make travel arrangements. A video version does this in a much
            more engaging way than a simple image or card.</p>

        <h3>When should you send a Save the Date?</h3>
        <p>Ideally, you should send it <strong>4 to 6 months</strong> before the wedding. If it's a destination wedding,
            send it 8 months in advance.</p>

        <h3>What to include?</h3>
        <ul>
            <li><strong>Couple's Names:</strong> Make it clear who is getting married!</li>
            <li><strong>Date:</strong> The most important part.</li>
            <li><strong>Location:</strong> City and Country is enough for now.</li>
            <li><strong>"Formal Invitation to Follow":</strong> Let them know more details are coming.</li>
            </ol>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>