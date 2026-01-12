<?php
/**
 * Categories Page - Two Column Layout
 * Left: Main categories with images
 * Right: Subcategories for selected category
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../config/homepage-config.php';

// Get active category from URL
$activeCategory = $_GET['active'] ?? null;

// Get all categories with subcategories from database
$allCategories = Database::fetchAll(
    "SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY display_order ASC, name ASC"
);

// Get subcategories for active category
$subcategories = [];
if ($activeCategory) {
    $parentCategory = Database::fetchOne(
        "SELECT id FROM categories WHERE slug = ? AND parent_id IS NULL",
        [$activeCategory]
    );
    if ($parentCategory) {
        $subcategories = Database::fetchAll(
            "SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY display_order ASC, name ASC",
            [$parentCategory['id']]
        );
    }
}

$pageTitle = 'Browse Categories';
$metaDescription = 'Browse all template categories for video invitations including weddings, birthdays, parties, and more.';
?>

<?php ob_start(); ?>

<!-- Categories Page - Four Column Layout (Mobile) -->
<div class="min-h-screen flex sm:hidden" style="padding-bottom: 60px;">
    <!-- Left Column: Main Categories (Narrower) -->
    <div class="w-20 flex-shrink-0 border-r border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 overflow-y-auto"
        style="height: calc(100vh - 60px);">
        <?php foreach ($homepageCategories as $index => $cat):
            $isActive = $activeCategory === $cat['slug'] || ($activeCategory === null && $index === 0);
            ?>
            <a href="/categories?active=<?= $cat['slug'] ?>"
                class="flex flex-col items-center gap-1 px-1 py-3 text-center transition-colors <?= $isActive ? 'bg-white dark:bg-slate-900 border-l-2 border-primary' : 'hover:bg-white dark:hover:bg-slate-800' ?>">
                <img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"
                    class="w-12 h-12 rounded-lg object-cover <?= $isActive ? 'ring-2 ring-primary' : '' ?>">
                <span
                    class="text-[10px] font-medium leading-tight <?= $isActive ? 'text-primary' : 'text-slate-600 dark:text-slate-300' ?>">
                    <?= $cat['name'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Right Section: Subcategories (3 Column Grid, Independent Scroll) -->
    <div class="flex-1 overflow-y-auto p-3 bg-white dark:bg-slate-900" style="height: calc(100vh - 60px);">
        <?php
        // Determine active category for display
        $displayCategory = $activeCategory ?? ($homepageCategories[0]['slug'] ?? 'wedding');
        $displayCategoryName = 'All';
        foreach ($homepageCategories as $cat) {
            if ($cat['slug'] === $displayCategory) {
                $displayCategoryName = $cat['name'];
                break;
            }
        }
        ?>

        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-3">
            <?= $displayCategoryName ?>
        </h2>

        <!-- View All Templates Link -->
        <a href="/templates?category=<?= $displayCategory ?>"
            class="flex items-center gap-2 p-3 rounded-xl bg-primary text-white font-medium mb-3 shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-xl">grid_view</span>
            <div class="flex-1">
                <span class="block text-sm font-bold">View All</span>
                <span class="text-[10px] text-white/70">Browse all templates</span>
            </div>
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </a>

        <?php if (!empty($subcategories)): ?>
            <!-- Subcategories 3-Column Grid -->
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Subcategories</h3>
            <div class="grid grid-cols-3 gap-2">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="/templates?category=<?= $sub['slug'] ?>"
                        class="flex flex-col items-center gap-1 p-2 rounded-lg bg-slate-50 dark:bg-slate-800 hover:bg-primary/5 transition-colors text-center">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white"
                            style="background-color: <?= $sub['color'] ?? '#7f13ec' ?>">
                            <span class="material-symbols-outlined text-xl">
                                <?= $sub['icon'] ?? 'category' ?>
                            </span>
                        </div>
                        <span class="text-[10px] font-medium text-slate-700 dark:text-slate-200 leading-tight">
                            <?= Security::escape($sub['name']) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Placeholder when no subcategories -->
            <div class="text-center py-6">
                <p class="text-slate-400 text-xs">Select a category to view subcategories</p>
                <p class="text-[10px] text-slate-300 mt-1">Subcategories can be added from admin</p>
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