/**
 * Wishlist JavaScript Module
 * Handles wishlist add/remove operations with optimistic UI updates
 */

// Check if user is logged in (set by PHP)
const isLoggedIn = typeof window.isUserLoggedIn !== 'undefined' ? window.isUserLoggedIn : false;

// Toggle wishlist status
async function toggleWishlist(button) {
    const templateId = button.dataset.templateId;
    const inWishlist = button.dataset.inWishlist === 'true';

    // Redirect to login if not authenticated
    if (!isLoggedIn) {
        const currentUrl = window.location.pathname + window.location.search;
        window.location.href = '/login?redirect=' + encodeURIComponent(currentUrl);
        return;
    }

    // Optimistic UI update
    updateWishlistButtonUI(button, !inWishlist);

    try {
        const endpoint = inWishlist ? '/api/wishlist/remove' : '/api/wishlist/add';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ template_id: parseInt(templateId) })
        });

        const data = await response.json();

        if (data.requireLogin) {
            const currentUrl = window.location.pathname + window.location.search;
            window.location.href = '/login?redirect=' + encodeURIComponent(currentUrl);
            return;
        }

        if (data.success) {
            button.dataset.inWishlist = data.inWishlist ? 'true' : 'false';
            showWishlistToast(data.inWishlist ? 'Added to wishlist' : 'Removed from wishlist');
            updateWishlistCount();
        } else {
            // Revert on error
            updateWishlistButtonUI(button, inWishlist);
            showWishlistToast('Something went wrong', 'error');
        }
    } catch (error) {
        // Revert on error
        updateWishlistButtonUI(button, inWishlist);
        showWishlistToast('Something went wrong', 'error');
    }
}

// Update button UI state
function updateWishlistButtonUI(button, inWishlist) {
    const icon = button.querySelector('.wishlist-icon');
    if (!icon) return;

    if (inWishlist) {
        icon.classList.remove('text-slate-400');
        icon.classList.add('text-rose-500');
        icon.style.fontVariationSettings = '"FILL" 1';
    } else {
        icon.classList.remove('text-rose-500');
        icon.classList.add('text-slate-400');
        icon.style.fontVariationSettings = '"FILL" 0';
    }

    button.dataset.inWishlist = inWishlist ? 'true' : 'false';
}

// Update wishlist count in header
async function updateWishlistCount() {
    const badge = document.getElementById('wishlist-count-badge');
    if (!badge || !isLoggedIn) return;

    try {
        const response = await fetch('/api/wishlist/count');
        const data = await response.json();

        if (data.success) {
            if (data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    } catch (error) {
        // Silent fail
    }
}

// Load wishlist IDs for gallery page (bulk check)
async function loadWishlistIds() {
    if (!isLoggedIn) return [];

    try {
        const response = await fetch('/api/wishlist/ids');
        const data = await response.json();

        if (data.success) {
            return data.ids;
        }
    } catch (error) {
        // Silent fail
    }

    return [];
}

// Initialize wishlist buttons on page load
async function initWishlistButtons() {
    if (!isLoggedIn) return;

    const wishlistIds = await loadWishlistIds();

    document.querySelectorAll('.wishlist-btn').forEach(button => {
        const templateId = parseInt(button.dataset.templateId);
        if (wishlistIds.includes(templateId)) {
            updateWishlistButtonUI(button, true);
        }
    });
}

// Show toast notification
function showWishlistToast(message, type = 'success') {
    // Check if toast element exists, if not create it
    let toast = document.getElementById('wishlist-toast');

    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'wishlist-toast';
        toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-50 opacity-0 pointer-events-none transition-all duration-300 translate-y-4';
        toast.innerHTML = `
            <div class="flex items-center gap-3 px-4 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl shadow-xl">
                <span class="material-symbols-outlined text-lg" id="wishlist-toast-icon">check_circle</span>
                <span id="wishlist-toast-message" class="font-medium text-sm"></span>
            </div>
        `;
        document.body.appendChild(toast);
    }

    const icon = document.getElementById('wishlist-toast-icon');
    const text = document.getElementById('wishlist-toast-message');

    text.textContent = message;
    icon.textContent = type === 'error' ? 'error' : (type === 'success' ? 'check_circle' : 'favorite');

    toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
    toast.classList.add('opacity-100', 'translate-y-0');

    setTimeout(() => {
        toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
        toast.classList.remove('opacity-100', 'translate-y-0');
    }, 3000);
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    initWishlistButtons();
    updateWishlistCount();
});
