<?php
/**
 * Home / Landing Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Get featured templates
$featuredTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE is_active = 1 ORDER BY purchase_count DESC LIMIT 6"
);

// Get categories with counts
$categories = Database::fetchAll(
    "SELECT category, COUNT(*) as count FROM templates WHERE is_active = 1 GROUP BY category"
);

$pageTitle = 'Create Stunning Video Invitations';
?>

<?php ob_start(); ?>

<!-- Hero Section -->
<section
    class="relative bg-gradient-to-br from-primary/10 via-purple-50 to-pink-50 dark:from-primary/20 dark:via-slate-900 dark:to-slate-900 py-12 sm:py-16 lg:py-20 overflow-hidden">
    <div class="absolute inset-0 bg-[url('/assets/images/grid.svg')] opacity-20"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <div class="text-center lg:text-left">
                <span
                    class="inline-flex items-center gap-2 bg-primary/10 text-primary font-bold text-xs sm:text-sm px-3 sm:px-4 py-1 rounded-full mb-4 sm:mb-6">
                    <span class="material-symbols-outlined text-base sm:text-lg">auto_awesome</span>
                    <span class="hidden sm:inline">Create stunning invitations in minutes</span>
                    <span class="sm:hidden">Stunning invitations</span>
                </span>

                <h1
                    class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white leading-tight mb-4 sm:mb-6">
                    Beautiful <span class="text-primary">Video Invitations</span> for Every Occasion
                </h1>

                <p
                    class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mb-6 sm:mb-8 max-w-lg mx-auto lg:mx-0">
                    Choose from our stunning templates, customize with your details, and share your personalized video
                    invitation with loved ones.
                </p>

                <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-3 sm:gap-4">
                    <a href="/templates"
                        class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-6 sm:px-8 py-3 sm:py-4 rounded-xl shadow-lg shadow-primary/30 transition-all">
                        <span>Browse Templates</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                    <a href="#how-it-works"
                        class="inline-flex items-center justify-center gap-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white font-bold px-6 sm:px-8 py-3 sm:py-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 transition-all">
                        <span class="material-symbols-outlined">play_circle</span>
                        <span>Watch Demo</span>
                    </a>
                </div>

                <div class="flex items-center justify-center lg:justify-start gap-4 sm:gap-6 mt-8 sm:mt-10">
                    <div class="flex -space-x-3">
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 border-2 border-white">
                        </div>
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 border-2 border-white">
                        </div>
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 border-2 border-white">
                        </div>
                        <div
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 border-2 border-white">
                        </div>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">10,000+ Happy Customers
                        </p>
                        <div class="flex items-center gap-0.5 text-yellow-500">
                            <span class="material-symbols-outlined text-sm sm:text-lg">star</span>
                            <span class="material-symbols-outlined text-sm sm:text-lg">star</span>
                            <span class="material-symbols-outlined text-sm sm:text-lg">star</span>
                            <span class="material-symbols-outlined text-sm sm:text-lg">star</span>
                            <span class="material-symbols-outlined text-sm sm:text-lg">star</span>
                            <span class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm ml-1">4.9/5</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative hidden lg:block">
                <div class="absolute -top-10 -right-10 w-72 h-72 bg-primary/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-10 -left-10 w-72 h-72 bg-pink-500/20 rounded-full blur-3xl"></div>

                <div
                    class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-3 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                    <div class="aspect-[9/16] rounded-xl bg-gradient-to-br from-purple-400 to-pink-500 overflow-hidden">
                        <div class="w-full h-full flex items-center justify-center text-white">
                            <div class="text-center p-8">
                                <span class="material-symbols-outlined text-6xl mb-4">celebration</span>
                                <h3 class="text-2xl font-bold">Your Event</h3>
                                <p class="opacity-80">Save the Date</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">Browse by Category</h2>
            <p class="text-slate-600 dark:text-slate-400">Find the perfect template for your occasion</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            $categoryIcons = [
                'wedding' => 'favorite',
                'birthday' => 'cake',
                'corporate' => 'business_center',
                'baby_shower' => 'child_care',
                'anniversary' => 'celebration'
            ];
            $categoryColors = [
                'wedding' => 'from-rose-400 to-pink-500',
                'birthday' => 'from-amber-400 to-orange-500',
                'corporate' => 'from-blue-400 to-indigo-500',
                'baby_shower' => 'from-teal-400 to-cyan-500',
                'anniversary' => 'from-purple-400 to-violet-500'
            ];

            foreach ($categories as $cat):
                $icon = $categoryIcons[$cat['category']] ?? 'category';
                $gradient = $categoryColors[$cat['category']] ?? 'from-slate-400 to-slate-500';
                ?>
                <a href="/templates?category=<?= $cat['category'] ?>"
                    class="group p-6 rounded-2xl bg-slate-50 dark:bg-slate-800 hover:shadow-xl transition-all border border-transparent hover:border-primary/20">
                    <div
                        class="w-16 h-16 rounded-xl bg-gradient-to-br <?= $gradient ?> flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl"><?= $icon ?></span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white capitalize mb-1"><?= $cat['category'] ?></h3>
                    <p class="text-sm text-slate-500"><?= $cat['count'] ?> templates</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Templates -->
<section class="py-16 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-end mb-12">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">Popular Templates</h2>
                <p class="text-slate-600 dark:text-slate-400">Most loved by our customers</p>
            </div>
            <a href="/templates" class="hidden md:flex items-center gap-2 text-primary font-bold hover:underline">
                View All
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <?php foreach ($featuredTemplates as $template): ?>
                <div
                    class="group relative flex flex-col overflow-hidden rounded-xl bg-white dark:bg-slate-900 shadow-sm hover:shadow-xl transition-all">
                    <div class="relative aspect-[9/16] w-full overflow-hidden">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                            style="background-image: url('<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>');">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                        <div class="absolute bottom-4 left-4 right-4 text-white">
                            <h3 class="font-bold text-lg"><?= Security::escape($template['title']) ?></h3>
                            <p class="text-sm opacity-80 capitalize"><?= $template['category'] ?></p>
                        </div>

                        <div class="absolute top-3 right-3">
                            <span
                                class="bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-bold text-slate-900">
                                $<?= number_format($template['price_usd'], 0) ?>
                            </span>
                        </div>
                    </div>

                    <a href="/template/<?= Security::escape($template['slug']) ?>"
                        class="p-4 flex items-center justify-center gap-2 bg-primary text-white font-bold hover:bg-primary/90 transition-colors">
                        <span>Customize</span>
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">How It Works</h2>
            <p class="text-slate-600 dark:text-slate-400">Create your invitation in 3 easy steps</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-4xl text-primary">grid_view</span>
                </div>
                <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-2">1. Choose Template</h3>
                <p class="text-slate-600 dark:text-slate-400">Browse our collection and select the perfect design for
                    your event.</p>
            </div>

            <div class="text-center">
                <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-4xl text-primary">edit</span>
                </div>
                <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-2">2. Customize</h3>
                <p class="text-slate-600 dark:text-slate-400">Add your details, photos, and music to personalize your
                    invitation.</p>
            </div>

            <div class="text-center">
                <div class="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-4xl text-primary">share</span>
                </div>
                <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-2">3. Share</h3>
                <p class="text-slate-600 dark:text-slate-400">Download your HD video and share it with friends and
                    family.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-primary to-purple-600">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to Create Your Invitation?</h2>
        <p class="text-lg text-white/80 mb-8">Join thousands of happy customers who have created stunning video
            invitations.</p>
        <a href="/templates"
            class="inline-flex items-center gap-2 bg-white text-primary font-bold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all">
            <span>Get Started Free</span>
            <span class="material-symbols-outlined">arrow_forward</span>
        </a>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>