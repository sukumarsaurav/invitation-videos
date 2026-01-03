<?php
/**
 * Service Page: Baby Shower Invitation Video
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Fetch relevant templates
$babyShowerTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE category = 'baby_shower' AND is_active = 1 ORDER BY purchase_count DESC LIMIT 8"
);

$pageTitle = 'Baby Shower Video Invitation | Gender Reveal Invites';
$metaDescription = 'Design adorable baby shower invitation videos. Choose from pink, blue, or gender-neutral themes. Create cute animated invites for your special arrival.';

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
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-300 text-sm font-bold mb-6">
                    <span class="material-symbols-outlined text-base">child_care</span>
                    Cute & Adorable
                </div>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight mb-6">
                    Adorable <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-400 to-cyan-500">Baby
                        Shower Invites</span>
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Welcome your little one in style! Create heartwarming baby shower or gender reveal video invitations
                    to share the joy with loved ones.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    <a href="#templates"
                        class="w-full sm:w-auto px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all text-center">
                        Create Baby Shower Video
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
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-800 text-slate-500">
                        <span class="material-symbols-outlined text-6xl">stroller</span>
                    </div>
                </div>
                <!-- Decorative elements -->
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-teal-500/10 rounded-full blur-3xl p-10"></div>
                <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl p-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Templates Section -->
<section id="templates" class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Cute Themes for Boys & Girls
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Whether it's a boy, a girl, or a surprise,
                we have the perfect design for you.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($babyShowerTemplates as $template): ?>
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
            <a href="/templates?category=baby_shower"
                class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                View All Baby Shower Templates <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>
    </div>
</section>

<!-- Detailed SEO Content Support -->
<section class="py-20 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 prose prose-lg dark:prose-invert">
        <h2>Celebrate Your New Arrival with a Baby Shower Video</h2>
        <p>A baby shower is a heartwarming occasion. A <strong>baby shower video invitation</strong> sets the tone for a
            cute and cozy gathering. It's the modern way to invite friends and family to bless the mom-to-be.</p>

        <h3>Popular Baby Shower Themes</h3>
        <ul>
            <li><strong>It's a Boy:</strong> Blue themed templates with cars, teddy bears, and stars.</li>
            <li><strong>It's a Girl:</strong> Pink and pastel designs with flowers, butterflies, and fairies.</li>
            <li><strong>Gender Neutral:</strong> Yellow, green, or gold themes for when the gender is a surprise.</li>
            <li><strong>Gender Reveal:</strong> Suspenseful animations to reveal the big news!</li>
        </ul>

        <h2>Godh Bharai & Seemantham Video Invites</h2>
        <p>We also offer traditional Indian designs for <strong>Godh Bharai</strong> (North India) and
            <strong>Seemantham</strong> (South India) ceremonies. These templates feature traditional music, motifs, and
            culturally appropriate colors.</p>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>