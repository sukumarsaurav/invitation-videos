<?php
/**
 * Wishlist Page - Shows user's saved templates
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Core/ImageHelper.php';

// Require login
if (empty($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/wishlist';
    header('Location: /login?redirect=' . urlencode('/wishlist'));
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch wishlist items
$wishlistItems = Database::fetchAll(
    "SELECT t.id, t.title, t.slug, t.thumbnail_url, t.price_usd, t.price_inr, 
            t.discounted_price_usd, t.discounted_price_inr, t.duration_seconds,
            t.is_premium, t.category, w.created_at as added_at
     FROM wishlist w
     INNER JOIN templates t ON w.template_id = t.id
     WHERE w.user_id = ? AND t.is_active = 1
     ORDER BY w.created_at DESC",
    [$userId]
);

$pageTitle = 'My Wishlist';
$metaDescription = 'View your saved video invitation templates.';

// For mobile header: show back arrow
$showBackButton = true;
?>

<?php ob_start(); ?>

<div class="container-section py-6 sm:py-10">

    <!-- Breadcrumb -->
    <nav class="hidden sm:flex items-center gap-2 text-sm mb-6">
        <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
        <span class="text-slate-400">/</span>
        <span class="font-medium text-slate-900">Wishlist</span>
    </nav>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <h1 class="heading-hero text-slate-900 tracking-tight">
                My Wishlist
            </h1>
            <p class="text-slate-500 mt-1">
                <span id="wishlist-count">
                    <?= count($wishlistItems) ?>
                </span>
                <?= count($wishlistItems) === 1 ? 'template' : 'templates' ?> saved
            </p>
        </div>
    </div>

    <?php if (!empty($wishlistItems)): ?>
        <!-- Wishlist Grid -->
        <div id="wishlist-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
            <?php foreach ($wishlistItems as $index => $template):
                $isAboveFold = $index < 4;
                ?>
                <div class="wishlist-item group" data-template-id="<?= $template['id'] ?>">
                    <a href="/template/<?= Security::escape($template['slug']) ?>" class="block">
                        <!-- Image Card -->
                        <div
                            class="relative aspect-[4/5] overflow-hidden bg-slate-100 rounded-2xl shadow-sm hover:shadow-xl transition-all border border-slate-100 group-hover:border-primary/30">
                            <?= ImageHelper::responsiveThumbnail(
                                $template['thumbnail_url'] ?? '/assets/images/placeholder.jpg',
                                $template['title'],
                                $isAboveFold,
                                $isAboveFold,
                                'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'
                            ) ?>

                            <!-- Badges -->
                            <?php if ($template['is_premium']): ?>
                                <span
                                    class="absolute top-2 left-2 px-2 py-1 rounded-md bg-white/90 text-xs font-bold text-slate-900 backdrop-blur-sm">Premium</span>
                            <?php elseif ($template['price_usd'] == 0): ?>
                                <span
                                    class="absolute top-2 left-2 px-2 py-1 rounded-md bg-green-500/90 text-xs font-bold text-white backdrop-blur-sm">Free</span>
                            <?php endif; ?>

                            <!-- Remove from Wishlist Button -->
                            <button type="button"
                                class="wishlist-remove-btn absolute top-2 right-2 size-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-rose-50 transition-all z-10"
                                data-template-id="<?= $template['id'] ?>"
                                onclick="event.preventDefault(); event.stopPropagation(); removeFromWishlist(<?= $template['id'] ?>, this);"
                                title="Remove from wishlist">
                                <span class="material-symbols-outlined text-lg text-rose-500"
                                    style="font-variation-settings: 'FILL' 1;">favorite</span>
                            </button>
                        </div>
                    </a>

                    <!-- Title & Price (Outside Card) -->
                    <div class="pt-3 px-1">
                        <a href="/template/<?= Security::escape($template['slug']) ?>" class="block">
                            <h3
                                class="font-bold text-sm text-slate-900 truncate group-hover:text-primary transition-colors">
                                <?= Security::escape($template['title']) ?>
                            </h3>
                        </a>
                        <p class="template-price text-sm font-semibold mt-0.5 <?= $template['price_usd'] == 0 ? 'text-green-600' : 'text-slate-700' ?>"
                            data-usd="<?= $template['price_usd'] ?>" data-inr="<?= $template['price_inr'] ?? 0 ?>">
                            <?= $template['price_usd'] == 0 ? 'Free' : '₹' . number_format($template['price_inr'] ?? 0, 0) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="size-24 mx-auto mb-6 rounded-full bg-slate-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-5xl text-slate-300">favorite</span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Your wishlist is empty</h3>
            <p class="text-slate-500 mb-6 max-w-md mx-auto">
                Save templates you love by clicking the heart icon. They'll appear here for easy access.
            </p>
            <a href="/templates"
                class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all">
                <span>Browse Templates</span>
                <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Toast Notification -->
<div id="toast"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 opacity-0 pointer-events-none transition-all duration-300 translate-y-4">
    <div
        class="flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-xl">
        <span class="material-symbols-outlined text-lg" id="toast-icon">check_circle</span>
        <span id="toast-message" class="font-medium text-sm"></span>
    </div>
</div>

<script>
    // Currency detection (timezone-based) - INR is default
    const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const isIndianUser = userTimezone.includes('Kolkata') || userTimezone.includes('Calcutta') || userTimezone.includes('Asia/');
    const userCurrency = isIndianUser ? 'INR' : 'USD';

    // Update prices based on detected currency
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.template-price').forEach(el => {
            const usd = parseFloat(el.dataset.usd) || 0;
            const inr = parseFloat(el.dataset.inr) || 0;
            if (usd === 0) return; // Skip free items

            if (userCurrency === 'USD' && usd > 0) {
                el.textContent = '$' + Math.round(usd);
            }
        });
    });

    // Remove from wishlist
    async function removeFromWishlist(templateId, button) {
        const item = button.closest('.wishlist-item');

        // Optimistic UI update - fade out
        item.style.transition = 'opacity 0.3s, transform 0.3s';
        item.style.opacity = '0';
        item.style.transform = 'scale(0.9)';

        try {
            const response = await fetch('/api/wishlist/remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ template_id: templateId })
            });

            const data = await response.json();

            if (data.success) {
                // Remove item from DOM after animation
                setTimeout(() => {
                    item.remove();
                    updateWishlistCount();
                    showToast('Removed from wishlist');
                }, 300);
            } else {
                // Revert on error
                item.style.opacity = '1';
                item.style.transform = 'scale(1)';
                showToast('Failed to remove', 'error');
            }
        } catch (error) {
            // Revert on error
            item.style.opacity = '1';
            item.style.transform = 'scale(1)';
            showToast('Failed to remove', 'error');
        }
    }

    // Update wishlist count
    function updateWishlistCount() {
        const grid = document.getElementById('wishlist-grid');
        const countEl = document.getElementById('wishlist-count');

        if (grid && countEl) {
            const count = grid.querySelectorAll('.wishlist-item').length;
            countEl.textContent = count;

            // Show empty state if no items left
            if (count === 0) {
                location.reload();
            }
        }
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const icon = document.getElementById('toast-icon');
        const text = document.getElementById('toast-message');

        text.textContent = message;
        icon.textContent = type === 'error' ? 'error' : 'check_circle';

        toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
        toast.classList.add('opacity-100', 'translate-y-0');

        setTimeout(() => {
            toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
            toast.classList.remove('opacity-100', 'translate-y-0');
        }, 3000);
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>