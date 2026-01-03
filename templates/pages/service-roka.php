<?php
/**
 * Service Page: Roka Ceremony Invitation Video (Niche)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Reuse wedding templates but likely need specific ones. For now using wedding templates as fallback or filter if 'roka' category existed.
// Assuming 'wedding' is best fit but maybe filter by tag if tags existed.
$rokaTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE category = 'wedding' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
);

$pageTitle = 'Roka Ceremony Invitation Video Background & Templates';
$metaDescription = 'Design traditional Roka ceremony invitation videos. Celebrate the official beginning of your wedding journey with a beautiful digital invite card.';

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
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300 text-sm font-bold mb-6">
                    <span class="material-symbols-outlined text-base">ring_volume</span>
                    Indian Tradition
                </div>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight mb-6">
                    <span class="text-orange-600 dark:text-orange-500">Roka Ceremony</span> Invitation Videos
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    The Roka is the first official step towards your forever. Invite your close family and friends to
                    bless this union with a traditional video invitation.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="#templates"
                        class="w-full sm:w-auto px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all text-center">
                        Create Roka Invite
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-full">
                <div
                    class="relative aspect-[9/16] rounded-2xl overflow-hidden shadow-2xl border-4 border-white dark:border-slate-800 bg-slate-900">
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-800 text-slate-500">
                        <span class="material-symbols-outlined text-6xl">diamond</span>
                    </div>
                </div>
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/10 rounded-full blur-3xl p-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Traditional & Modern Styles
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Blending culture with digital elegance.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($rokaTemplates as $template): ?>
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
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Detailed SEO Content Support -->
<section class="py-20 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 prose prose-lg dark:prose-invert">
        <h2>Significance of Roka Ceremony</h2>
        <p>The <strong>Roka</strong> (or Rokka) is one of the most significant pre-wedding ceremonies in Indian
            weddings. It marks the union of both families and the official fixing of the marriage alliance. It is often
            a small, intimate gathering.</p>

        <h3>What to say in a Roka Invitation?</h3>
        <p>Unlike the formal wedding invite, a Roka invitation can be warmer and more personal. Key details involved:
        </p>
        <ul>
            <li><strong>Blessings sought by:</strong> Grandparents and Parents names.</li>
            <li><strong>Couple:</strong> Names of the bride and groom to be.</li>
            <li><strong>Date & Venue:</strong> Usually at home or a small banquet.</li>
        </ul>
        <p>Our video templates include placeholders for all these traditional elements, including Shlokas or religious
            symbols like Ganesha or Om.</p>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>