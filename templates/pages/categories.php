<?php
/**
 * Categories Page - Two Column Layout
 * Left: Main categories with images
 * Right: Subcategories for selected category (loaded from database)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../config/homepage-config.php';

// Get active category from URL
$activeCategory = $_GET['active'] ?? null;

// Load main categories and their subcategories from database
$mainCategoriesWithSubs = [];
try {
    // Get all categories with parent relationship
    $allCategories = Database::fetchAll(
        "SELECT c.*, p.slug as parent_slug 
         FROM categories c 
         LEFT JOIN categories p ON c.parent_id = p.id 
         WHERE c.is_active = 1 
         ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.display_order"
    ) ?? [];

    // Organize into main categories and subcategories
    foreach ($allCategories as $cat) {
        if ($cat['parent_id'] === null) {
            // Skip miscellaneous as it's handled differently
            if ($cat['slug'] === 'miscellaneous')
                continue;

            $mainCategoriesWithSubs[$cat['slug']] = [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'icon' => $cat['icon'],
                'color' => $cat['color'],
                'image' => $cat['image_url'] ?? '/assets/images/categories/' . $cat['slug'] . '.webp',
                'subcategories' => []
            ];
        } else if ($cat['parent_slug'] && isset($mainCategoriesWithSubs[$cat['parent_slug']])) {
            // Add subcategory
            $mainCategoriesWithSubs[$cat['parent_slug']]['subcategories'][] = [
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'icon' => $cat['icon'],
                'image' => '/assets/images/subcategories/' . $cat['slug'] . '.webp',
            ];
        }
    }

    // Add miscellaneous subcategories as standalone items at the end
    $miscCategory = Database::fetchOne("SELECT id FROM categories WHERE slug = 'miscellaneous' AND is_active = 1");
    if ($miscCategory) {
        $miscSubs = Database::fetchAll(
            "SELECT name, slug, icon FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY display_order",
            [$miscCategory['id']]
        ) ?? [];
        foreach ($miscSubs as $sub) {
            $mainCategoriesWithSubs[$sub['slug']] = [
                'id' => 0,
                'name' => $sub['name'],
                'slug' => $sub['slug'],
                'icon' => $sub['icon'],
                'color' => '#6b7280',
                'image' => '/assets/images/categories/' . $sub['slug'] . '.webp',
                'subcategories' => []
            ];
        }
    }
} catch (Exception $e) {
    // Fallback to homepage categories if database fails
    foreach ($homepageCategories as $cat) {
        $mainCategoriesWithSubs[$cat['slug']] = [
            'id' => 0,
            'name' => $cat['name'],
            'slug' => $cat['slug'],
            'icon' => '',
            'color' => '#7f13ec',
            'image' => $cat['image'],
            'subcategories' => []
        ];
    }
}

// If no active category, default to first one
$categoryKeys = array_keys($mainCategoriesWithSubs);
if (!$activeCategory || !isset($mainCategoriesWithSubs[$activeCategory])) {
    $activeCategory = $categoryKeys[0] ?? 'wedding';
}

$displayCategoryName = $mainCategoriesWithSubs[$activeCategory]['name'] ?? 'All';
$subcategories = $mainCategoriesWithSubs[$activeCategory]['subcategories'] ?? [];

$pageTitle = 'Browse Categories';
$metaDescription = 'Browse all template categories for video invitations including weddings, birthdays, parties, and more.';
?>

<?php ob_start(); ?>

<!-- Categories Page - Two Column Layout (Mobile) -->
<div class="min-h-screen flex sm:hidden" style="padding-top: 64px; padding-bottom: 60px;">
    <!-- Left Column: Main Categories - Fixed position with independent scroll -->
    <div class="w-20 flex-shrink-0 border-r border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 overflow-y-auto overscroll-contain"
        style="position: fixed; top: 64px; bottom: 60px; left: 0; width: 80px; -webkit-overflow-scrolling: touch;">
        <?php foreach ($mainCategoriesWithSubs as $slug => $cat):
            $isActive = $activeCategory === $slug;
            ?>
            <a href="/categories?active=<?= $slug ?>"
                class="flex flex-col items-center gap-1 px-1 py-3 text-center transition-colors <?= $isActive ? 'bg-white dark:bg-slate-900 border-l-2 border-primary' : 'hover:bg-white dark:hover:bg-slate-800' ?>">
                <img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"
                    class="w-12 h-12 rounded-lg object-cover <?= $isActive ? 'ring-2 ring-primary' : '' ?>"
                    onerror="this.src='/assets/images/placeholder-category.png'">
                <span
                    class="text-[10px] font-medium leading-tight <?= $isActive ? 'text-primary' : 'text-slate-600 dark:text-slate-300' ?>">
                    <?= $cat['name'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Right Section: Subcategories Grid - Scrolls with page, offset by sidebar width -->
    <div class="flex-1 p-3 bg-white dark:bg-slate-900" style="margin-left: 80px;">

        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-3">
            <?= $displayCategoryName ?>
        </h2>

        <!-- View All Templates Link -->
        <a href="/templates?category=<?= $activeCategory ?>"
            class="flex items-center gap-2 p-3 rounded-xl bg-primary text-white font-medium mb-4 shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-xl">grid_view</span>
            <div class="flex-1">
                <span class="block text-sm font-bold">View All</span>
                <span class="text-[10px] text-white/70">Browse all templates</span>
            </div>
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </a>

        <?php if (!empty($subcategories)): ?>
            <!-- Subcategories Grid -->
            <div class="grid grid-cols-3 gap-2">
                <?php foreach ($subcategories as $subcat): ?>
                    <a href="/templates?category=<?= $subcat['slug'] ?>"
                        class="flex flex-col items-center p-2 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <img src="<?= htmlspecialchars($subcat['image']) ?>" alt="<?= htmlspecialchars($subcat['name']) ?>"
                            class="w-14 h-14 rounded-lg object-cover mb-1"
                            onerror="this.src='/assets/images/placeholder-subcategory.png'">
                        <span
                            class="text-[10px] font-medium text-slate-700 dark:text-slate-300 text-center leading-tight line-clamp-2">
                            <?= htmlspecialchars($subcat['name']) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No subcategories message -->
            <div class="text-center py-6">
                <p class="text-slate-400 text-xs">No subcategories available</p>
                <p class="text-[10px] text-slate-300 mt-1">Click "View All" to browse templates</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Desktop: Redirect to templates -->
<div class="hidden sm:block">
    <div class="max-w-7xl mx-auto px-6 py-12 text-center">
        <h1 class="text-3xl font-bold mb-4">Browse Categories</h1>
        <p class="text-slate-600 mb-6">Use the main navigation to browse templates by category.</p>
        <a href="/templates"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90">
            <span class="material-symbols-outlined">grid_view</span>
            View All Templates
        </a>
    </div>
</div>

<!-- Mobile Bottom Tab Bar -->
<div class="fixed bottom-0 left-0 right-0 z-40 sm:hidden shadow-lg" style="background-color: #2c0914;">
    <div class="grid grid-cols-4 py-2">
        <!-- Home Tab -->
        <a href="/" class="flex flex-col items-center gap-0.5 py-1" style="color: #b69b5b;">
            <span class="material-symbols-outlined text-xl">home</span>
            <span class="text-[10px] font-medium">Home</span>
        </a>
        <!-- Category Tab (Active) -->
        <a href="/categories" class="flex flex-col items-center gap-0.5 py-1 text-primary">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">category</span>
            <span class="text-[10px] font-medium">Category</span>
        </a>
        <!-- Wishlist Tab -->
        <a href="/wishlist" class="flex flex-col items-center gap-0.5 py-1" style="color: #b69b5b;">
            <span class="material-symbols-outlined text-xl">favorite_border</span>
            <span class="text-[10px] font-medium">Wishlist</span>
        </a>
        <!-- Profile Tab -->
        <a href="<?= isset($_SESSION['user_id']) ? '/profile' : '/login' ?>"
            class="flex flex-col items-center gap-0.5 py-1" style="color: #b69b5b;">
            <span class="material-symbols-outlined text-xl">person</span>
            <span class="text-[10px] font-medium">Profile</span>
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>