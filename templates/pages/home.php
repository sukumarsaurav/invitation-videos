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

// Get latest blog posts
$blogPosts = Database::fetchAll(
    "SELECT id, title, slug, excerpt, featured_image, category, published_at 
     FROM blog_posts WHERE status = 'published' 
     ORDER BY published_at DESC LIMIT 3"
);

$pageTitle = 'Create Stunning Video Invitations | InvitationVideos';
$pageDescription = 'Create beautiful video invitations for weddings, birthdays, baby showers, and more. Choose from stunning templates and customize with your details.';
?>

<?php ob_start(); ?>

<!-- All Categories Section -->
<section class="py-10 sm:py-12 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-start sm:items-center justify-between mb-8 flex-col sm:flex-row gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">All Categories</h1>
                <p class="text-slate-600 dark:text-slate-400">Browse our comprehensive collection of video templates for
                    any event.</p>
            </div>
            <a href="/templates"
                class="flex items-center gap-2 text-primary font-bold hover:underline whitespace-nowrap">
                View Full Catalog
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php
            $allCategories = [
                ['slug' => 'wedding', 'name' => 'Wedding', 'icon' => 'favorite', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50 dark:bg-rose-900/20'],
                ['slug' => 'birthday', 'name' => 'Birthday', 'icon' => 'cake', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-900/20'],
                ['slug' => 'baby_shower', 'name' => 'Baby Shower', 'icon' => 'child_care', 'color' => 'text-teal-500', 'bg' => 'bg-teal-50 dark:bg-teal-900/20'],
                ['slug' => 'save_the_date', 'name' => 'Save the Date', 'icon' => 'event', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                ['slug' => 'parties', 'name' => 'Parties', 'icon' => 'celebration', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                ['slug' => 'corporate', 'name' => 'Corporate', 'icon' => 'business_center', 'color' => 'text-slate-600', 'bg' => 'bg-slate-100 dark:bg-slate-800'],
                ['slug' => 'holidays', 'name' => 'Holidays', 'icon' => 'redeem', 'color' => 'text-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/20'],
                ['slug' => 'anniversary', 'name' => 'Anniversary', 'icon' => 'favorite_border', 'color' => 'text-pink-500', 'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                ['slug' => 'graduation', 'name' => 'Graduation', 'icon' => 'school', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                ['slug' => 'housewarming', 'name' => 'Housewarming', 'icon' => 'home', 'color' => 'text-cyan-500', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                ['slug' => 'religious', 'name' => 'Religious', 'icon' => 'church', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/20'],
                ['slug' => 'farewell', 'name' => 'Farewell', 'icon' => 'waving_hand', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
            ];

            foreach ($allCategories as $cat): ?>
                <a href="/templates?category=<?= $cat['slug'] ?>"
                    class="group flex flex-col items-center p-5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:shadow-lg hover:border-primary/30 transition-all">
                    <div
                        class="w-14 h-14 rounded-xl <?= $cat['bg'] ?> flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-2xl <?= $cat['color'] ?>"><?= $cat['icon'] ?></span>
                    </div>
                    <span
                        class="font-semibold text-sm text-slate-900 dark:text-white text-center"><?= $cat['name'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Popular Templates -->
<section class="py-12 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-start sm:items-center justify-between mb-8 flex-col sm:flex-row gap-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">Popular Templates</h2>
                <p class="text-slate-600 dark:text-slate-400">Discover trending designs for your next event.</p>
            </div>
            <a href="/templates"
                class="flex items-center gap-2 text-primary font-bold hover:underline whitespace-nowrap">
                View All Templates
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php
            $categoryBadgeColors = [
                'wedding' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
                'birthday' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'baby_shower' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
                'corporate' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                'anniversary' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-300',
                'parties' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
                'graduation' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300',
                'religious' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
            ];

            foreach ($trendingTemplates as $template):
                $badgeColor = $categoryBadgeColors[$template['category']] ?? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                ?>
                <a href="/template/<?= Security::escape($template['slug']) ?>"
                    class="group block bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30">
                    <!-- Image -->
                    <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                        <img src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                             alt="<?= Security::escape($template['title']) ?>"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                             width="300" height="375" loading="lazy" decoding="async">

                        <!-- Category Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $badgeColor ?>">
                                <?= ucfirst(str_replace('_', ' ', $template['category'])) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
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
<?php if (!empty($blogPosts)): ?>
    <section class="py-12 bg-white dark:bg-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-2">Tips & Inspiration</h2>
                    <p class="text-slate-600 dark:text-slate-400">Ideas to make your invitations unforgettable</p>
                </div>
                <a href="/blog" class="text-primary font-bold hover:underline flex items-center gap-1">
                    View All <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php
                $colors = [
                    ['from-rose-400', 'to-pink-500', 'favorite'],
                    ['from-amber-400', 'to-orange-500', 'cake'],
                    ['from-teal-400', 'to-cyan-500', 'child_care']
                ];
                foreach ($blogPosts as $i => $post):
                    $color = $colors[$i % count($colors)];
                    ?>
                    <article
                        class="group bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl transition-all">
                        <a href="/blog/<?= Security::escape($post['slug']) ?>" class="block">
                            <div class="aspect-video <?= $post['featured_image'] ? 'bg-slate-100' : "bg-gradient-to-br {$color[0]} {$color[1]}" ?> flex items-center justify-center relative overflow-hidden">
                                <?php if ($post['featured_image']): ?>
                                    <img src="<?= Security::escape($post['featured_image']) ?>"
                                         alt="<?= Security::escape($post['title']) ?>"
                                         class="absolute inset-0 w-full h-full object-cover"
                                         width="400" height="225" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-5xl text-white/80"><?= $color[2] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="p-5">
                                <?php if ($post['category']): ?>
                                    <span
                                        class="text-xs font-bold text-primary uppercase tracking-wide"><?= Security::escape($post['category']) ?></span>
                                <?php endif; ?>
                                <h3
                                    class="font-bold text-lg text-slate-900 dark:text-white mt-2 mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                    <?= Security::escape($post['title']) ?>
                                </h3>
                                <?php if ($post['excerpt']): ?>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                                        <?= Security::escape($post['excerpt']) ?></p>
                                <?php endif; ?>
                                <span
                                    class="inline-flex items-center gap-1 text-primary font-bold text-sm mt-3 group-hover:underline">
                                    Read More <span class="material-symbols-outlined text-base">arrow_forward</span>
                                </span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

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