<?php
/**
 * Home / Landing Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Get featured templates (most purchased)
$featuredTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE is_active = 1 ORDER BY purchase_count DESC LIMIT 6"
);

// Get trending templates (recently popular)
$trendingTemplates = Database::fetchAll(
    "SELECT * FROM templates WHERE is_active = 1 ORDER BY created_at DESC, purchase_count DESC LIMIT 8"
);

// Get categories with counts
$categories = Database::fetchAll(
    "SELECT category, COUNT(*) as count FROM templates WHERE is_active = 1 GROUP BY category ORDER BY count DESC"
);

$pageTitle = 'Create Stunning Video Invitations | InvitationVideos';
$pageDescription = 'Create beautiful video invitations for weddings, birthdays, baby showers, and more. Choose from stunning templates and customize with your details.';
?>

<?php ob_start(); ?>

<!-- Hero Section - Compact -->
<section
    class="relative bg-gradient-to-br from-primary/10 via-purple-50 to-pink-50 dark:from-primary/20 dark:via-slate-900 dark:to-slate-900 py-10 sm:py-12 lg:py-14 overflow-hidden">
    <div class="absolute inset-0 bg-[url('/assets/images/grid.svg')] opacity-20"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-3xl mx-auto">
            <span
                class="inline-flex items-center gap-2 bg-primary/10 text-primary font-bold text-xs sm:text-sm px-3 sm:px-4 py-1 rounded-full mb-4">
                <span class="material-symbols-outlined text-base sm:text-lg">auto_awesome</span>
                <span>Create stunning invitations in minutes</span>
            </span>

            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 dark:text-white leading-tight mb-4">
                Beautiful <span class="text-primary">Video Invitations</span> for Every Occasion
            </h1>

            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mb-6 max-w-2xl mx-auto">
                Choose from our stunning templates, customize with your details, and share your personalized video
                invitation with loved ones.
            </p>

            <div class="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4">
                <a href="/templates"
                    class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-6 sm:px-8 py-3 rounded-xl shadow-lg shadow-primary/30 transition-all">
                    <span>Browse Templates</span>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
                <a href="#how-it-works"
                    class="inline-flex items-center justify-center gap-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-white font-bold px-6 sm:px-8 py-3 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 transition-all">
                    <span class="material-symbols-outlined">play_circle</span>
                    <span>Watch Demo</span>
                </a>
            </div>

            <!-- Trust Indicators -->
            <div class="flex items-center justify-center gap-6 mt-8">
                <div class="flex -space-x-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 border-2 border-white">
                    </div>
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 border-2 border-white">
                    </div>
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 border-2 border-white">
                    </div>
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 border-2 border-white">
                    </div>
                </div>
                <div class="text-left">
                    <p class="font-bold text-sm text-slate-900 dark:text-white">10,000+ Happy Customers</p>
                    <div class="flex items-center gap-0.5 text-yellow-500">
                        <span class="material-symbols-outlined text-sm">star</span>
                        <span class="material-symbols-outlined text-sm">star</span>
                        <span class="material-symbols-outlined text-sm">star</span>
                        <span class="material-symbols-outlined text-sm">star</span>
                        <span class="material-symbols-outlined text-sm">star</span>
                        <span class="text-slate-600 dark:text-slate-400 text-xs ml-1">4.9/5</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Browse Most Loved Categories -->
<section class="py-12 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">Browse Most Loved
                    Categories</h2>
                <p class="text-slate-600 dark:text-slate-400">Find the perfect template for your occasion</p>
            </div>
            <a href="/templates" class="hidden md:flex items-center gap-2 text-primary font-bold hover:underline">
                View All
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
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

            foreach ($categories as $index => $cat):
                $icon = $categoryIcons[$cat['category']] ?? 'category';
                $gradient = $categoryColors[$cat['category']] ?? 'from-slate-400 to-slate-500';
                $isTopCategory = $index < 3;
                ?>
                <a href="/templates?category=<?= $cat['category'] ?>"
                    class="group relative p-5 rounded-2xl bg-slate-50 dark:bg-slate-800 hover:shadow-xl transition-all border border-transparent hover:border-primary/20 <?= $isTopCategory ? 'ring-2 ring-primary/20' : '' ?>">
                    <?php if ($isTopCategory): ?>
                        <span
                            class="absolute -top-2 -right-2 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">local_fire_department</span>
                            Hot
                        </span>
                    <?php endif; ?>
                    <div
                        class="w-14 h-14 rounded-xl bg-gradient-to-br <?= $gradient ?> flex items-center justify-center text-white mb-3 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-2xl"><?= $icon ?></span>
                    </div>
                    <h3 class="font-bold text-slate-900 dark:text-white capitalize mb-1">
                        <?= str_replace('_', ' ', $cat['category']) ?></h3>
                    <p class="text-sm text-slate-500"><?= $cat['count'] ?> templates</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Trending Templates -->
<section class="py-12 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-2xl text-orange-500">trending_up</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Trending Templates</h2>
                </div>
                <p class="text-slate-600 dark:text-slate-400">Most popular picks this week</p>
            </div>
            <a href="/templates" class="hidden md:flex items-center gap-2 text-primary font-bold hover:underline">
                View All
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($trendingTemplates as $index => $template): ?>
                <div
                    class="group relative flex flex-col overflow-hidden rounded-xl bg-white dark:bg-slate-900 shadow-sm hover:shadow-xl transition-all">
                    <div class="relative aspect-[4/5] w-full overflow-hidden">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                            style="background-image: url('<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>');">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                        <?php if ($index < 3): ?>
                            <div class="absolute top-3 left-3">
                                <span
                                    class="bg-gradient-to-r from-orange-500 to-red-500 text-white text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">local_fire_department</span>
                                    #<?= $index + 1 ?> Trending
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="absolute bottom-3 left-3 right-3 text-white">
                            <h3 class="font-bold text-sm sm:text-base truncate"><?= Security::escape($template['title']) ?>
                            </h3>
                            <p class="text-xs opacity-80 capitalize"><?= str_replace('_', ' ', $template['category']) ?></p>
                        </div>

                        <div class="absolute top-3 right-3">
                            <span
                                class="bg-white/90 backdrop-blur-sm px-2 py-1 rounded-full text-xs font-bold text-slate-900">
                                $<?= number_format($template['price_usd'], 0) ?>
                            </span>
                        </div>
                    </div>

                    <a href="/template/<?= Security::escape($template['slug']) ?>"
                        class="p-3 flex items-center justify-center gap-2 bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-colors">
                        <span>Customize</span>
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-12 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">How It Works</h2>
            <p class="text-slate-600 dark:text-slate-400">Create your invitation in 3 easy steps</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6 rounded-2xl bg-slate-50 dark:bg-slate-800">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">grid_view</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">1. Choose Template</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Browse our collection and select the perfect
                    design for your event.</p>
            </div>

            <div class="text-center p-6 rounded-2xl bg-slate-50 dark:bg-slate-800">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">edit</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">2. Customize</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Add your details, photos, and music to personalize
                    your invitation.</p>
            </div>

            <div class="text-center p-6 rounded-2xl bg-slate-50 dark:bg-slate-800">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">share</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">3. Share</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Download your HD video and share it with friends
                    and family.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-12 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">Frequently Asked Questions
            </h2>
            <p class="text-slate-600 dark:text-slate-400">Everything you need to know about our video invitations</p>
        </div>

        <div class="space-y-4" x-data="{ openFaq: 1 }">
            <!-- FAQ Item 1 -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                <button @click="openFaq = openFaq === 1 ? null : 1"
                    class="w-full px-6 py-4 flex items-center justify-between text-left">
                    <h3 class="font-bold text-slate-900 dark:text-white">How do video invitations work?</h3>
                    <span class="material-symbols-outlined text-primary transition-transform"
                        :class="{ 'rotate-180': openFaq === 1 }">expand_more</span>
                </button>
                <div x-show="openFaq === 1" x-collapse class="px-6 pb-4">
                    <p class="text-slate-600 dark:text-slate-400">Simply choose a template, customize it with your event
                        details (names, date, venue, photos), and we'll generate a stunning HD video invitation. You can
                        then download it and share via WhatsApp, email, or social media.</p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                <button @click="openFaq = openFaq === 2 ? null : 2"
                    class="w-full px-6 py-4 flex items-center justify-between text-left">
                    <h3 class="font-bold text-slate-900 dark:text-white">How long does it take to create an invitation?
                    </h3>
                    <span class="material-symbols-outlined text-primary transition-transform"
                        :class="{ 'rotate-180': openFaq === 2 }">expand_more</span>
                </button>
                <div x-show="openFaq === 2" x-collapse class="px-6 pb-4">
                    <p class="text-slate-600 dark:text-slate-400">Most video invitations are ready within 24-48 hours.
                        Premium rush delivery is available for urgent orders. You'll receive your video via email and
                        can also download it from your account.</p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                <button @click="openFaq = openFaq === 3 ? null : 3"
                    class="w-full px-6 py-4 flex items-center justify-between text-left">
                    <h3 class="font-bold text-slate-900 dark:text-white">Can I make changes after ordering?</h3>
                    <span class="material-symbols-outlined text-primary transition-transform"
                        :class="{ 'rotate-180': openFaq === 3 }">expand_more</span>
                </button>
                <div x-show="openFaq === 3" x-collapse class="px-6 pb-4">
                    <p class="text-slate-600 dark:text-slate-400">Yes! We offer one free revision per order. If you need
                        to change names, dates, or other details, just contact our support team and we'll update your
                        video promptly.</p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                <button @click="openFaq = openFaq === 4 ? null : 4"
                    class="w-full px-6 py-4 flex items-center justify-between text-left">
                    <h3 class="font-bold text-slate-900 dark:text-white">What video formats do you provide?</h3>
                    <span class="material-symbols-outlined text-primary transition-transform"
                        :class="{ 'rotate-180': openFaq === 4 }">expand_more</span>
                </button>
                <div x-show="openFaq === 4" x-collapse class="px-6 pb-4">
                    <p class="text-slate-600 dark:text-slate-400">We provide videos in Full HD (1080p) MP4 format,
                        optimized for sharing on WhatsApp, Instagram, Facebook, and other platforms. The videos are also
                        perfect for displaying on screens at your event.</p>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                <button @click="openFaq = openFaq === 5 ? null : 5"
                    class="w-full px-6 py-4 flex items-center justify-between text-left">
                    <h3 class="font-bold text-slate-900 dark:text-white">Do you offer refunds?</h3>
                    <span class="material-symbols-outlined text-primary transition-transform"
                        :class="{ 'rotate-180': openFaq === 5 }">expand_more</span>
                </button>
                <div x-show="openFaq === 5" x-collapse class="px-6 pb-4">
                    <p class="text-slate-600 dark:text-slate-400">We offer a 100% satisfaction guarantee. If you're not
                        happy with your video after revisions, we'll provide a full refund. Your satisfaction is our
                        priority.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog/Tips Section for SEO -->
<section class="py-12 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">Tips & Inspiration</h2>
                <p class="text-slate-600 dark:text-slate-400">Ideas to make your invitations unforgettable</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Blog Post 1 -->
            <article
                class="group bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl transition-all">
                <div class="aspect-video bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-white/80">favorite</span>
                </div>
                <div class="p-5">
                    <span class="text-xs font-bold text-primary uppercase tracking-wide">Wedding Tips</span>
                    <h3
                        class="font-bold text-lg text-slate-900 dark:text-white mt-2 mb-2 group-hover:text-primary transition-colors">
                        10 Creative Ways to Send Your Wedding Invitations</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">Discover unique and memorable
                        ways to invite your guests to your special day. From video invites to custom QR codes...</p>
                    <a href="#"
                        class="inline-flex items-center gap-1 text-primary font-bold text-sm mt-3 hover:underline">
                        Read More <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </div>
            </article>

            <!-- Blog Post 2 -->
            <article
                class="group bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl transition-all">
                <div
                    class="aspect-video bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-white/80">cake</span>
                </div>
                <div class="p-5">
                    <span class="text-xs font-bold text-primary uppercase tracking-wide">Birthday Ideas</span>
                    <h3
                        class="font-bold text-lg text-slate-900 dark:text-white mt-2 mb-2 group-hover:text-primary transition-colors">
                        How to Plan a Surprise Birthday Party in 2024</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">From secret invitations to
                        perfect surprise timing. Learn the best strategies to throw an unforgettable surprise party...
                    </p>
                    <a href="#"
                        class="inline-flex items-center gap-1 text-primary font-bold text-sm mt-3 hover:underline">
                        Read More <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </div>
            </article>

            <!-- Blog Post 3 -->
            <article
                class="group bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl transition-all">
                <div class="aspect-video bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-white/80">child_care</span>
                </div>
                <div class="p-5">
                    <span class="text-xs font-bold text-primary uppercase tracking-wide">Baby Shower</span>
                    <h3
                        class="font-bold text-lg text-slate-900 dark:text-white mt-2 mb-2 group-hover:text-primary transition-colors">
                        Baby Shower Trends: What's Hot This Year</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">From gender reveal ideas to
                        themed decorations. Explore the latest trends in baby shower celebrations and invitations...</p>
                    <a href="#"
                        class="inline-flex items-center gap-1 text-primary font-bold text-sm mt-3 hover:underline">
                        Read More <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-primary to-purple-600">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-4">Ready to Create Your Invitation?</h2>
        <p class="text-base sm:text-lg text-white/80 mb-6">Join thousands of happy customers who have created stunning
            video invitations.</p>
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