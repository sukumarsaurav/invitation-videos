<?php
/**
 * Home / Landing Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Core/ImageHelper.php';

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

// Fetch CMS homepage sections
$homepageSections = [];
try {
    $homepageSections = Database::fetchAll(
        "SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY display_order ASC"
    ) ?? [];
} catch (Exception $e) {
    // Table may not exist yet
}

// Fetch CMS settings for hero and categories
$heroImageDesktop = '';
$heroImageMobile = '';
$heroTitle = '';
$heroSubtitle = '';
$heroButtonText = '';
$heroButtonLink = '';
$categoryDisplayMode = 'icon';

try {
    $cmsRows = Database::fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('hero_image_desktop', 'hero_image_mobile', 'hero_title', 'hero_subtitle', 'hero_button_text', 'hero_button_link', 'category_display_mode')");
    if ($cmsRows) {
        foreach ($cmsRows as $row) {
            switch ($row['setting_key']) {
                case 'hero_image_desktop':
                    $heroImageDesktop = $row['setting_value'] ?? '';
                    break;
                case 'hero_image_mobile':
                    $heroImageMobile = $row['setting_value'] ?? '';
                    break;
                case 'hero_title':
                    $heroTitle = $row['setting_value'] ?? '';
                    break;
                case 'hero_subtitle':
                    $heroSubtitle = $row['setting_value'] ?? '';
                    break;
                case 'hero_button_text':
                    $heroButtonText = $row['setting_value'] ?? '';
                    break;
                case 'hero_button_link':
                    $heroButtonLink = $row['setting_value'] ?? '';
                    break;
                case 'category_display_mode':
                    $categoryDisplayMode = $row['setting_value'] ?? 'icon';
                    break;
            }
        }
    }
} catch (Exception $e) {
    // Settings table may not exist yet, use defaults
}

$pageTitle = 'Create Stunning Video Invitations | Free Templates';
$metaDescription = 'Create beautiful video invitations for weddings, birthdays, baby showers, and more. Browse stunning templates, customize with your details, and share via WhatsApp.';
$isHomePage = true;  // For floating help button display
?>

<?php ob_start(); ?>

<!-- Hero Section -->
<section
    class="min-h-[80vh] flex items-center justify-center relative overflow-hidden<?= empty($heroImageDesktop) ? ' bg-gradient-to-br from-primary/5 via-purple-500/5 to-rose-500/5 dark:from-primary/10 dark:via-purple-500/10 dark:to-rose-500/10' : '' ?>">
    <?php if (!empty($heroImageDesktop)): ?>
        <!-- Hero Background Image -->
        <div class="absolute inset-0 z-0">
            <picture>
                <?php if (!empty($heroImageMobile)): ?>
                    <source media="(max-width: 768px)" srcset="<?= htmlspecialchars($heroImageMobile) ?>">
                <?php endif; ?>
                <img src="<?= htmlspecialchars($heroImageDesktop) ?>" alt="Hero Background"
                    class="w-full h-full object-cover" loading="eager">
            </picture>
            <!-- Only show overlay if there's hero text -->
            <?php if (!empty(trim(strip_tags($heroTitle))) || !empty(trim($heroSubtitle))): ?>
                <div class="absolute inset-0 bg-black/40"></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 text-center relative z-10 py-16">
        <?php
        // Only show hero text if it's not empty (after stripping HTML tags for title check)
        $hasTitleText = !empty(trim(strip_tags($heroTitle)));
        $hasSubtitleText = !empty(trim($heroSubtitle));
        ?>
        <?php if ($hasTitleText): ?>
            <h1
                class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold <?= !empty($heroImageDesktop) ? 'text-white' : 'text-slate-900 dark:text-white' ?> mb-3">
                <?= $heroTitle ?>
            </h1>
        <?php endif; ?>
        <?php if ($hasSubtitleText): ?>
            <p
                class="<?= !empty($heroImageDesktop) ? 'text-white/90' : 'text-slate-600 dark:text-slate-400' ?> text-sm sm:text-base md:text-lg max-w-2xl mx-auto mb-6">
                <?= htmlspecialchars($heroSubtitle) ?>
            </p>
        <?php endif; ?>
        <?php if (($hasTitleText || $hasSubtitleText) && !empty($heroButtonText)): ?>
            <a href="<?= htmlspecialchars($heroButtonLink ?: '/templates') ?>"
                class="inline-flex items-center gap-2 bg-primary text-white font-bold px-6 py-3 sm:px-8 sm:py-4 rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all text-sm sm:text-base">
                <span><?= htmlspecialchars($heroButtonText) ?></span>
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- All Categories Section -->
<section class="py-8 sm:py-12 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-start sm:items-center justify-between mb-6 flex-col sm:flex-row gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-1">All Categories</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm">Browse templates for any event</p>
            </div>
            <a href="/templates"
                class="hidden sm:flex items-center gap-2 text-primary font-bold hover:underline whitespace-nowrap">
                View Full Catalog
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <?php
        // Fetch categories from database with image_url support
        $dbCategories = [];
        try {
            // Try with image_url first (if migration was run)
            $dbCategories = Database::fetchAll("SELECT id, name, slug, icon, color, image_url FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC LIMIT 12") ?? [];
        } catch (Exception $e) {
            // If image_url column doesn't exist, try without it
            try {
                $dbCategories = Database::fetchAll("SELECT id, name, slug, icon, color FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC LIMIT 12") ?? [];
            } catch (Exception $e2) {
                // Fallback to hardcoded if table doesn't exist
            }
        }

        // Fallback hardcoded categories if database is empty
        if (empty($dbCategories)) {
            $allCategories = [
                ['slug' => 'wedding', 'name' => 'Wedding', 'icon' => 'favorite', 'color' => '#f43f5e', 'bg' => 'bg-rose-50 dark:bg-rose-900/20', 'image_url' => null],
                ['slug' => 'birthday', 'name' => 'Birthday', 'icon' => 'cake', 'color' => '#f59e0b', 'bg' => 'bg-amber-50 dark:bg-amber-900/20', 'image_url' => null],
                ['slug' => 'baby_shower', 'name' => 'Baby Shower', 'icon' => 'child_care', 'color' => '#14b8a6', 'bg' => 'bg-teal-50 dark:bg-teal-900/20', 'image_url' => null],
                ['slug' => 'save_the_date', 'name' => 'Save Date', 'icon' => 'event', 'color' => '#3b82f6', 'bg' => 'bg-blue-50 dark:bg-blue-900/20', 'image_url' => null],
                ['slug' => 'parties', 'name' => 'Parties', 'icon' => 'celebration', 'color' => '#f97316', 'bg' => 'bg-orange-50 dark:bg-orange-900/20', 'image_url' => null],
                ['slug' => 'corporate', 'name' => 'Corporate', 'icon' => 'business_center', 'color' => '#64748b', 'bg' => 'bg-slate-100 dark:bg-slate-800', 'image_url' => null],
                ['slug' => 'holidays', 'name' => 'Holidays', 'icon' => 'redeem', 'color' => '#ef4444', 'bg' => 'bg-red-50 dark:bg-red-900/20', 'image_url' => null],
                ['slug' => 'anniversary', 'name' => 'Anniversary', 'icon' => 'favorite_border', 'color' => '#ec4899', 'bg' => 'bg-pink-50 dark:bg-pink-900/20', 'image_url' => null],
                ['slug' => 'graduation', 'name' => 'Graduation', 'icon' => 'school', 'color' => '#6366f1', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'image_url' => null],
                ['slug' => 'housewarming', 'name' => 'Housewarming', 'icon' => 'home', 'color' => '#06b6d4', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/20', 'image_url' => null],
                ['slug' => 'religious', 'name' => 'Religious', 'icon' => 'church', 'color' => '#ca8a04', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'image_url' => null],
                ['slug' => 'farewell', 'name' => 'Farewell', 'icon' => 'waving_hand', 'color' => '#a855f7', 'bg' => 'bg-purple-50 dark:bg-purple-900/20', 'image_url' => null],
            ];
        } else {
            // Map database categories to display format
            $allCategories = array_map(function ($cat) {
                $color = $cat['color'] ?? '#7f13ec';
                return [
                    'slug' => $cat['slug'],
                    'name' => $cat['name'],
                    'icon' => $cat['icon'] ?? 'category',
                    'color' => $color,
                    'bg' => 'bg-slate-100 dark:bg-slate-800',
                    'image_url' => $cat['image_url'] ?? null,
                ];
            }, $dbCategories);
        }

        // Check display mode from CMS settings
        $showImages = ($categoryDisplayMode === 'image' || $categoryDisplayMode === 'both');

        // Split categories for mobile two-row layout
        $row1Categories = array_slice($allCategories, 0, 6);
        $row2Categories = array_slice($allCategories, 6, 6);
        ?>

        <!-- Mobile: Two-row synchronized horizontal scrolling (< 768px) -->
        <div class="sm:hidden category-scroll-container overflow-x-auto -mx-4 px-4"
            style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
            <div class="flex flex-col gap-4" style="min-width: max-content;">
                <!-- Row 1 -->
                <div class="flex gap-4">
                    <?php foreach ($row1Categories as $cat): ?>
                        <a href="/templates?category=<?= $cat['slug'] ?>"
                            class="flex flex-col items-center w-14 flex-shrink-0">
                            <div
                                class="w-14 h-14 rounded-xl <?= $cat['bg'] ?> flex items-center justify-center mb-1 overflow-hidden">
                                <?php if ($showImages && !empty($cat['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($cat['image_url']) ?>"
                                        alt="<?= htmlspecialchars($cat['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-2xl"
                                        style="color: <?= htmlspecialchars($cat['color']) ?>"><?= $cat['icon'] ?></span>
                                <?php endif; ?>
                            </div>
                            <span
                                class="text-[10px] font-medium text-slate-700 dark:text-slate-300 text-center leading-tight"><?= $cat['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <!-- Row 2 -->
                <div class="flex gap-4">
                    <?php foreach ($row2Categories as $cat): ?>
                        <a href="/templates?category=<?= $cat['slug'] ?>"
                            class="flex flex-col items-center w-14 flex-shrink-0">
                            <div
                                class="w-14 h-14 rounded-xl <?= $cat['bg'] ?> flex items-center justify-center mb-1 overflow-hidden">
                                <?php if ($showImages && !empty($cat['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($cat['image_url']) ?>"
                                        alt="<?= htmlspecialchars($cat['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-2xl"
                                        style="color: <?= htmlspecialchars($cat['color']) ?>"><?= $cat['icon'] ?></span>
                                <?php endif; ?>
                            </div>
                            <span
                                class="text-[10px] font-medium text-slate-700 dark:text-slate-300 text-center leading-tight"><?= $cat['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Desktop: Grid layout (>= 768px) -->
        <div class="hidden sm:grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($allCategories as $cat): ?>
                <a href="/templates?category=<?= $cat['slug'] ?>"
                    class="group flex flex-col items-center p-5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:shadow-lg hover:border-primary/30 transition-all">
                    <div
                        class="w-14 h-14 rounded-xl <?= $cat['bg'] ?> flex items-center justify-center mb-3 group-hover:scale-110 transition-transform overflow-hidden">
                        <?php if ($showImages && !empty($cat['image_url'])): ?>
                            <img src="<?= htmlspecialchars($cat['image_url']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"
                                class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-2xl"
                                style="color: <?= htmlspecialchars($cat['color']) ?>"><?= $cat['icon'] ?></span>
                        <?php endif; ?>
                    </div>
                    <span
                        class="font-semibold text-sm text-slate-900 dark:text-white text-center"><?= $cat['name'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
// =====================================================
// Dynamic Homepage Sections from CMS
// =====================================================
if (!empty($homepageSections)):
    foreach ($homepageSections as $sectionIndex => $section):
        // Build the template query based on section filters
        $sectionQuery = "SELECT * FROM templates WHERE is_active = 1";
        $sectionParams = [];

        if (!empty($section['category_slug'])) {
            $sectionQuery .= " AND category = ?";
            $sectionParams[] = $section['category_slug'];
        }
        if (!empty($section['subcategory'])) {
            $sectionQuery .= " AND subcategory = ?";
            $sectionParams[] = $section['subcategory'];
        }

        $sectionQuery .= " ORDER BY purchase_count DESC, created_at DESC LIMIT ?";
        $sectionParams[] = intval($section['template_count'] ?? 4);

        $sectionTemplates = Database::fetchAll($sectionQuery, $sectionParams) ?? [];

        // Skip if no templates found
        if (empty($sectionTemplates))
            continue;

        // Read SVG content if exists
        $svgContent = '';
        if (!empty($section['banner_svg_url'])) {
            $svgPath = __DIR__ . '/../../' . ltrim($section['banner_svg_url'], '/');
            if (file_exists($svgPath)) {
                $svgContent = file_get_contents($svgPath);
            }
        }

        // Parse positioning data
        $svgPos = json_decode($section['svg_position'] ?? '{}', true) ?: [];
        $imgPos = json_decode($section['image_position'] ?? '{}', true) ?: [];
        $bannerHeights = json_decode($section['banner_heights'] ?? '{}', true) ?: ['xs' => '80px', 'sm' => '100px', 'md' => '120px', 'lg' => '140px', 'xl' => '160px'];
        $svgAnimation = $section['svg_animation'] ?? 'none';
        $imgAnimation = $section['image_animation'] ?? 'none';
        $imageOverflow = $section['image_overflow'] ?? 1;
        ?>

        <!-- CMS Section: <?= Security::escape($section['section_title']) ?> -->
        <section class="relative overflow-hidden sm:overflow-visible">
            <!-- Header Banner -->
            <div class="section-banner relative" style="background-color: <?= Security::escape($section['banner_bg_color']) ?>;
                        --height-xs: <?= Security::escape($bannerHeights['xs'] ?? '80px') ?>;
                        --height-sm: <?= Security::escape($bannerHeights['sm'] ?? '100px') ?>;
                        --height-md: <?= Security::escape($bannerHeights['md'] ?? '120px') ?>;
                        --height-lg: <?= Security::escape($bannerHeights['lg'] ?? '140px') ?>;
                        --height-xl: <?= Security::escape($bannerHeights['xl'] ?? '160px') ?>;">

                <!-- SVG Pattern Background -->
                <?php if ($svgContent): ?>
                    <div class="section-element section-svg <?= $svgAnimation !== 'none' ? 'animate-on-scroll ' . $svgAnimation : '' ?>"
                        data-positions='<?= Security::escape(json_encode($svgPos)) ?>'>
                        <?= $svgContent ?>
                    </div>
                <?php endif; ?>

                <!-- Category Image (Now visible on all screens) -->
                <?php if (!empty($section['banner_image_url'])): ?>
                    <div class="section-element section-image <?= $imgAnimation !== 'none' ? 'animate-on-scroll ' . $imgAnimation : '' ?> <?= $imageOverflow ? 'allow-overflow' : '' ?>"
                        data-positions='<?= Security::escape(json_encode($imgPos)) ?>'>
                        <img src="<?= Security::escape($section['banner_image_url']) ?>"
                            alt="<?= Security::escape($section['section_title']) ?>">
                    </div>
                <?php endif; ?>

                <!-- Title Layer -->
                <div class="section-title-layer max-w-7xl mx-auto px-4 sm:px-6">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-light italic"
                        style="color: <?= Security::escape($section['title_color']) ?>; font-family: 'Georgia', serif;">
                        <?= Security::escape($section['section_title']) ?>
                    </h2>
                </div>
            </div>
            <?php
            // Parse visible counts for carousel
            $visibleCounts = json_decode($section['visible_counts'] ?? '{}', true) ?: ['xs' => 2, 'sm' => 3, 'md' => 4, 'lg' => 4, 'xl' => 4];
            ?>

            <!-- Template Carousel Container -->
            <div class="py-6 sm:py-8" style="background-color: <?= Security::escape($section['grid_bg_color']) ?>">
                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <div class="section-carousel relative"
                        data-visible-counts='<?= Security::escape(json_encode($visibleCounts)) ?>'>
                        <!-- Prev Arrow (Desktop) -->
                        <button class="carousel-prev" aria-label="Previous templates">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>

                        <!-- Carousel Track -->
                        <div class="carousel-track">
                            <?php foreach ($sectionTemplates as $tplIndex => $template): ?>
                                <div class="carousel-item">
                                    <a href="/template/<?= Security::escape($template['slug']) ?>"
                                        class="group block bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200/50">
                                        <!-- Image -->
                                        <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                                            <?= ImageHelper::responsiveThumbnail(
                                                $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg',
                                                $template['title'],
                                                $sectionIndex === 0 && $tplIndex < 2,
                                                $sectionIndex === 0 && $tplIndex < 2,
                                                'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'
                                            ) ?>
                                        </div>

                                        <!-- Content -->
                                        <div class="p-4">
                                            <h3
                                                class="font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors truncate text-sm sm:text-base">
                                                <?= Security::escape($template['title']) ?>
                                            </h3>
                                            <p
                                                class="text-sm <?= $template['price_usd'] > 0 ? 'text-slate-700 dark:text-slate-300' : 'text-green-600 font-bold' ?>">
                                                <?= $template['price_usd'] > 0 ? '₹' . number_format($template['price_inr'], 0) : 'Free' ?>
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Next Arrow (Desktop) -->
                        <button class="carousel-next" aria-label="Next templates">
                            <span class="material-symbols-outlined">chevron_forward</span>
                        </button>

                        <!-- Dot Indicators -->
                        <div class="carousel-dots"></div>
                    </div>
                </div>
            </div>
        </section>

        <?php
    endforeach;
endif;

// Fallback: Show Popular Templates if no CMS sections exist
if (empty($homepageSections)):
    ?>
    <!-- Popular Templates (Fallback) -->
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

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
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

                foreach ($trendingTemplates as $index => $template):
                    $badgeColor = $categoryBadgeColors[$template['category']] ?? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                    $isAboveFold = $index < 2;
                    ?>
                    <a href="/template/<?= Security::escape($template['slug']) ?>"
                        class="group block bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-slate-200 dark:border-slate-700 hover:border-primary/30">
                        <div class="relative aspect-[4/5] overflow-hidden bg-slate-100">
                            <?= ImageHelper::responsiveThumbnail(
                                $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg',
                                $template['title'],
                                $isAboveFold,
                                $isAboveFold,
                                'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'
                            ) ?>
                            <div class="absolute top-3 left-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $badgeColor ?>">
                                    <?= ucfirst(str_replace('_', ' ', $template['category'])) ?>
                                </span>
                            </div>
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
<?php endif; ?>

<!-- How It Works -->
<section id="how-it-works" class="py-12" style="background-color: var(--footer-bg-color, #ffffff);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold mb-3" style="color: var(--footer-text-color, #0f172a);">How It
                Works</h2>
            <p style="color: var(--footer-text-color, #64748b); opacity: 0.8;">Create your invitation in 3 easy steps
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
                class="text-center p-6 rounded-2xl bg-white/10 dark:bg-black/10 backdrop-blur-sm border border-white/20 dark:border-black/20">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">grid_view</span>
                </div>
                <h3 class="font-bold text-lg mb-2" style="color: var(--footer-text-color, #0f172a);">1. Choose Template
                </h3>
                <p class="text-sm" style="color: var(--footer-text-color, #64748b); opacity: 0.8;">Browse our collection
                    and select the perfect
                    design for your event.</p>
            </div>

            <div
                class="text-center p-6 rounded-2xl bg-white/10 dark:bg-black/10 backdrop-blur-sm border border-white/20 dark:border-black/20">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">edit</span>
                </div>
                <h3 class="font-bold text-lg mb-2" style="color: var(--footer-text-color, #0f172a);">2. Customize</h3>
                <p class="text-sm" style="color: var(--footer-text-color, #64748b); opacity: 0.8;">Add your details,
                    photos, and music to personalize
                    your invitation.</p>
            </div>

            <div
                class="text-center p-6 rounded-2xl bg-white/10 dark:bg-black/10 backdrop-blur-sm border border-white/20 dark:border-black/20">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-3xl text-primary">share</span>
                </div>
                <h3 class="font-bold text-lg mb-2" style="color: var(--footer-text-color, #0f172a);">3. Share</h3>
                <p class="text-sm" style="color: var(--footer-text-color, #64748b); opacity: 0.8;">Download your HD
                    video and share it with friends
                    and family.</p>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Invitation Videos (SEO Content) -->
<section class="py-12 bg-gradient-to-br from-primary/5 to-purple-500/5 dark:from-primary/10 dark:to-purple-500/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">Why Choose Invitation Videos?
            </h2>
            <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">Discover why thousands of customers trust us
                to create their perfect video invitations for weddings, birthdays, and special occasions.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <!-- Feature 1 -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-primary">high_quality</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">Full HD Quality Videos</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">All our video invitations are rendered in stunning
                    1080p Full HD quality. Your invitation will look crystal clear on any device - from smartphones to
                    large TV screens at your event venue.</p>
            </div>

            <!-- Feature 2 -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-emerald-500">schedule</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">Quick Turnaround Time</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Receive your customized video invitation within
                    24-48 hours. Need it faster? Our rush delivery option ensures you get your video within hours for
                    those last-minute celebrations.</p>
            </div>

            <!-- Feature 3 -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-amber-500">brush</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">Professional Design Templates</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Choose from our extensive library of
                    professionally designed templates crafted by expert motion graphics designers. From elegant wedding
                    invitations to fun birthday animations.</p>
            </div>

            <!-- Feature 4 -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-rose-500">share</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">Easy Sharing on WhatsApp</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Our videos are optimized for WhatsApp, Instagram,
                    and Facebook sharing. The perfect file size ensures your invitation reaches all your guests without
                    compression or quality loss.</p>
            </div>

            <!-- Feature 5 -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-blue-500">support_agent</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">Dedicated Customer Support</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Our friendly support team is available to help you
                    with any questions or revisions. We offer one free revision per order to ensure your invitation is
                    exactly how you envisioned.</p>
            </div>

            <!-- Feature 6 -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-xl bg-teal-500/10 flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-2xl text-teal-500">verified</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-2">100% Satisfaction Guarantee</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">We stand behind our work with a complete
                    satisfaction guarantee. If you're not happy with your video after revisions, we'll provide a full
                    refund - no questions asked.</p>
            </div>
        </div>

        <!-- Additional SEO Content -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 shadow-sm border border-slate-200 dark:border-slate-700">
            <h3 class="font-bold text-xl text-slate-900 dark:text-white mb-4">The Perfect Video Invitation for Every
                Occasion</h3>
            <div class="prose prose-slate dark:prose-invert max-w-none">
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    At Invitation Videos, we believe that every celebration deserves a memorable beginning. Our video
                    invitations transform the traditional way of inviting guests into an immersive, emotional
                    experience. Whether you're planning an intimate wedding ceremony, a grand birthday bash, a
                    heartwarming baby shower, or a corporate event, our professionally crafted video invitations set the
                    perfect tone for your special day.
                </p>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Our collection includes templates for Indian weddings featuring traditional elements like mandaps,
                    mehendi designs, and festive colors. We also offer contemporary minimalist designs for modern
                    celebrations, elegant save-the-date videos, and vibrant party invitations that capture the
                    excitement of your upcoming event.
                </p>
                <p class="text-slate-600 dark:text-slate-400">
                    The process is simple: browse our template gallery, select the design that speaks to you, enter your
                    event details including names, dates, venue information, and photos, and we'll create a stunning
                    animated video that you can share with all your loved ones. Your video invitation becomes a keepsake
                    that guests will remember long after your event concludes.
                </p>
            </div>
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

<!-- Testimonials Section -->
<section class="py-12 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">What Our Customers Say</h2>
            <p class="text-slate-600 dark:text-slate-400">Trusted by thousands of happy couples and families</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Testimonial 1 -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
                <div class="flex items-center gap-1 mb-4">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <span class="material-symbols-outlined text-amber-400 text-lg"
                            style="font-variation-settings: 'FILL' 1;">star</span>
                    <?php endfor; ?>
                </div>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    "The video invitation for our wedding was absolutely stunning! Our guests were amazed and kept
                    asking where we got it made. Highly recommend!"
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="text-primary font-bold">P</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">Priya & Rahul</p>
                        <p class="text-xs text-slate-500">Wedding Invitation</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
                <div class="flex items-center gap-1 mb-4">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <span class="material-symbols-outlined text-amber-400 text-lg"
                            style="font-variation-settings: 'FILL' 1;">star</span>
                    <?php endfor; ?>
                </div>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    "Created a beautiful birthday invitation for my daughter's 5th birthday. The animation quality was
                    professional and delivery was super fast!"
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-rose-500/10 flex items-center justify-center">
                        <span class="text-rose-500 font-bold">A</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">Anjali Sharma</p>
                        <p class="text-xs text-slate-500">Birthday Invitation</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all">
                <div class="flex items-center gap-1 mb-4">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <span class="material-symbols-outlined text-amber-400 text-lg"
                            style="font-variation-settings: 'FILL' 1;">star</span>
                    <?php endfor; ?>
                </div>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    "Easy to customize and the WhatsApp sharing was seamless. Everyone loved the video quality. Will
                    definitely use again for our next event!"
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center">
                        <span class="text-emerald-500 font-bold">R</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white text-sm">Rajesh Kumar</p>
                        <p class="text-xs text-slate-500">Engagement Ceremony</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-12 bg-slate-50 dark:bg-slate-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-3">Frequently Asked Questions
            </h2>
            <p class="text-slate-600 dark:text-slate-400">Everything you need to know about our video invitations</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" x-data="{ openFaq: 1 }">
            <!-- Left Column -->
            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                    <button @click="openFaq = openFaq === 1 ? null : 1"
                        class="w-full px-6 py-4 flex items-center justify-between text-left">
                        <h3 class="font-bold text-slate-900 dark:text-white">How do video invitations work?</h3>
                        <span class="material-symbols-outlined text-primary transition-transform"
                            :class="{ 'rotate-180': openFaq === 1 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 1" x-collapse class="px-6 pb-4">
                        <p class="text-slate-600 dark:text-slate-400">Simply choose a template, customize it with your
                            event
                            details (names, date, venue, photos), and we'll generate a stunning HD video invitation. You
                            can
                            then download it and share via WhatsApp, email, or social media.</p>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                    <button @click="openFaq = openFaq === 2 ? null : 2"
                        class="w-full px-6 py-4 flex items-center justify-between text-left">
                        <h3 class="font-bold text-slate-900 dark:text-white">How long does it take to create an
                            invitation?
                        </h3>
                        <span class="material-symbols-outlined text-primary transition-transform"
                            :class="{ 'rotate-180': openFaq === 2 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 2" x-collapse class="px-6 pb-4">
                        <p class="text-slate-600 dark:text-slate-400">Most video invitations are ready within 24-48
                            hours.
                            Premium rush delivery is available for urgent orders. You'll receive your video via email
                            and
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
                        <p class="text-slate-600 dark:text-slate-400">Yes! We offer one free revision per order. If you
                            need
                            to change names, dates, or other details, just contact our support team and we'll update
                            your
                            video promptly.</p>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
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
                            optimized for sharing on WhatsApp, Instagram, Facebook, and other platforms. The videos are
                            also
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
                        <p class="text-slate-600 dark:text-slate-400">We offer a 100% satisfaction guarantee. If you're
                            not
                            happy with your video after revisions, we'll provide a full refund. Your satisfaction is our
                            priority.</p>
                    </div>
                </div>

                <!-- FAQ Item 6 -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm overflow-hidden">
                    <button @click="openFaq = openFaq === 6 ? null : 6"
                        class="w-full px-6 py-4 flex items-center justify-between text-left">
                        <h3 class="font-bold text-slate-900 dark:text-white">How can I share my invitation?</h3>
                        <span class="material-symbols-outlined text-primary transition-transform"
                            :class="{ 'rotate-180': openFaq === 6 }">expand_more</span>
                    </button>
                    <div x-show="openFaq === 6" x-collapse class="px-6 pb-4">
                        <p class="text-slate-600 dark:text-slate-400">Once your video is ready, you can download it and
                            share
                            directly via WhatsApp, email, Facebook, Instagram, or any messaging app. The file is
                            optimized
                            for quick sharing without losing quality.</p>
                    </div>
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
                            <div
                                class="aspect-video <?= $post['featured_image'] ? 'bg-slate-100' : "bg-gradient-to-br {$color[0]} {$color[1]}" ?> flex items-center justify-center relative overflow-hidden">
                                <?php if ($post['featured_image']): ?>
                                    <img src="<?= Security::escape($post['featured_image']) ?>"
                                        alt="<?= Security::escape($post['title']) ?>"
                                        class="absolute inset-0 w-full h-full object-cover" width="400" height="225" loading="lazy"
                                        decoding="async">
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
                                        <?= Security::escape($post['excerpt']) ?>
                                    </p>
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>