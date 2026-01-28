<?php
/**
 * Mobile Bottom Tab Navigation Component
 * 
 * A persistent bottom tab bar for mobile screens (< 640px).
 * Shows 4 tabs: Home, Templates, Wishlist, Profile
 * 
 * Usage: Set $showMobileBottomTabs = false in pages that shouldn't show tabs.
 * 
 * Style: Dark theme matching header/footer colors.
 */

// Skip if explicitly disabled
if (!($showMobileBottomTabs ?? true)) {
    return;
}

// Determine current page for active state
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Tab configuration
$bottomTabs = [
    [
        'icon' => 'home',
        'label' => 'Home',
        'href' => '/',
        'active' => $currentPath === '/' || ($isHomePage ?? false),
    ],
    [
        'icon' => 'grid_view',
        'label' => 'Templates',
        'href' => '/templates',
        'active' => $currentPath === '/templates' || strpos($currentPath, '/template/') === 0,
    ],
    [
        'icon' => 'favorite',
        'label' => 'Wishlist',
        'href' => '/wishlist',
        'active' => $currentPath === '/wishlist',
    ],
    [
        'icon' => 'person',
        'label' => isset($_SESSION['user_id']) ? 'Profile' : 'Login',
        'href' => isset($_SESSION['user_id']) ? '/profile' : '/login',
        'active' => $currentPath === '/profile' || $currentPath === '/my-orders' || $currentPath === '/my-tickets',
    ],
];
?>

<!-- Mobile Bottom Tab Bar (visible only on small screens) -->
<nav id="mobile-bottom-tabs" class="fixed bottom-0 left-0 right-0 z-50 sm:hidden"
    style="background-color: var(--header-bg-color, #2c0914);">
    <div class="flex items-stretch">
        <?php foreach ($bottomTabs as $tab): ?>
            <a href="<?= $tab['href'] ?>"
                class="flex-1 flex flex-col items-center justify-center py-2 transition-colors <?= $tab['active'] ? 'text-primary' : '' ?>"
                style="color: <?= $tab['active'] ? 'var(--color-primary, #970747)' : 'var(--header-text-color, #b69b5b)' ?>;">
                <span class="material-symbols-outlined text-xl <?= $tab['active'] ? '' : 'opacity-80' ?>" <?= $tab['active'] ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>
                    <?= $tab['icon'] ?>
                </span>
                <span class="text-[10px] font-medium mt-0.5 <?= $tab['active'] ? 'font-bold' : 'opacity-80' ?>">
                    <?= $tab['label'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
    <!-- Safe area for devices with home indicator -->
    <div class="h-safe-area-inset-bottom" style="padding-bottom: env(safe-area-inset-bottom, 0px);"></div>
</nav>