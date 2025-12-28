<?php
/**
 * Admin Settings Page
 * Manage application settings stored in the database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Helper function to get/set settings
function getSetting($key, $default = null)
{
    $result = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

function setSetting($key, $value, $type = 'string')
{
    $existing = Database::fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($existing) {
        Database::query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
    } else {
        Database::query(
            "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)",
            [$key, $value, $type]
        );
    }
}

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';

        if ($action === 'general') {
            setSetting('site_name', Security::sanitizeString($_POST['site_name'] ?? ''));
            setSetting('site_tagline', Security::sanitizeString($_POST['site_tagline'] ?? ''));
            setSetting('support_email', Security::sanitizeString($_POST['support_email'] ?? ''));
            setSetting('support_phone', Security::sanitizeString($_POST['support_phone'] ?? ''));
            setSetting('whatsapp_number', Security::sanitizeString($_POST['whatsapp_number'] ?? ''));
            $success = 'General settings updated successfully!';
        }

        if ($action === 'business') {
            setSetting('video_delivery_days', Security::sanitizeString($_POST['video_delivery_days'] ?? '2'));
            setSetting('video_download_expiry_days', Security::sanitizeString($_POST['video_download_expiry_days'] ?? '30'));
            setSetting('free_revisions', Security::sanitizeString($_POST['free_revisions'] ?? '2'));
            setSetting('min_order_for_promo', Security::sanitizeString($_POST['min_order_for_promo'] ?? '0'));
            $success = 'Business settings updated successfully!';
        }

        if ($action === 'notifications') {
            setSetting('email_on_new_order', isset($_POST['email_on_new_order']) ? '1' : '0', 'boolean');
            setSetting('email_on_payment', isset($_POST['email_on_payment']) ? '1' : '0', 'boolean');
            setSetting('email_on_support_ticket', isset($_POST['email_on_support_ticket']) ? '1' : '0', 'boolean');
            setSetting('admin_notification_email', Security::sanitizeString($_POST['admin_notification_email'] ?? ''));
            $success = 'Notification settings updated successfully!';
        }

        if ($action === 'social') {
            setSetting('facebook_url', Security::sanitizeString($_POST['facebook_url'] ?? ''));
            setSetting('instagram_url', Security::sanitizeString($_POST['instagram_url'] ?? ''));
            setSetting('twitter_url', Security::sanitizeString($_POST['twitter_url'] ?? ''));
            setSetting('youtube_url', Security::sanitizeString($_POST['youtube_url'] ?? ''));
            $success = 'Social media links updated successfully!';
        }
    } else {
        $error = 'Invalid form submission. Please try again.';
    }
}

// Get current settings
$settings = [
    // General
    'site_name' => getSetting('site_name', APP_NAME ?? 'InvitationVideos'),
    'site_tagline' => getSetting('site_tagline', 'Create stunning video invitations'),
    'support_email' => getSetting('support_email', 'support@invitationvideos.com'),
    'support_phone' => getSetting('support_phone', ''),
    'whatsapp_number' => getSetting('whatsapp_number', ''),

    // Business
    'video_delivery_days' => getSetting('video_delivery_days', '2'),
    'video_download_expiry_days' => getSetting('video_download_expiry_days', '30'),
    'free_revisions' => getSetting('free_revisions', '2'),
    'min_order_for_promo' => getSetting('min_order_for_promo', '0'),

    // Notifications
    'email_on_new_order' => getSetting('email_on_new_order', '1') === '1',
    'email_on_payment' => getSetting('email_on_payment', '1') === '1',
    'email_on_support_ticket' => getSetting('email_on_support_ticket', '1') === '1',
    'admin_notification_email' => getSetting('admin_notification_email', ''),

    // Social
    'facebook_url' => getSetting('facebook_url', ''),
    'instagram_url' => getSetting('instagram_url', ''),
    'twitter_url' => getSetting('twitter_url', ''),
    'youtube_url' => getSetting('youtube_url', ''),
];

$pageTitle = 'Settings';
$currentTab = $_GET['tab'] ?? 'general';
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
            <p class="text-slate-500 mt-1">Manage your application configuration</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= Security::escape($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= Security::escape($error) ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Tabs Navigation -->
        <div class="w-full lg:w-56 shrink-0">
            <nav class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <a href="?tab=general"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors <?= $currentTab === 'general' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">tune</span>
                    General
                </a>
                <a href="?tab=business"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-t border-slate-100 <?= $currentTab === 'business' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">business</span>
                    Business Rules
                </a>
                <a href="?tab=notifications"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-t border-slate-100 <?= $currentTab === 'notifications' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">notifications</span>
                    Notifications
                </a>
                <a href="?tab=social"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-t border-slate-100 <?= $currentTab === 'social' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">share</span>
                    Social Media
                </a>
            </nav>
        </div>

        <!-- Settings Content -->
        <div class="flex-1">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

                <?php if ($currentTab === 'general'): ?>
                    <!-- General Settings -->
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="general">

                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">General Settings</h2>
                            <p class="text-sm text-slate-500 mt-1">Basic information about your website</p>
                        </div>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Site Name</label>
                                <input type="text" name="site_name" value="<?= Security::escape($settings['site_name']) ?>"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Tagline</label>
                                <input type="text" name="site_tagline"
                                    value="<?= Security::escape($settings['site_tagline']) ?>"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Support Email</label>
                                    <input type="email" name="support_email"
                                        value="<?= Security::escape($settings['support_email']) ?>"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Support Phone</label>
                                    <input type="tel" name="support_phone"
                                        value="<?= Security::escape($settings['support_phone']) ?>"
                                        placeholder="+91 9876543210"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">WhatsApp Number</label>
                                <input type="tel" name="whatsapp_number"
                                    value="<?= Security::escape($settings['whatsapp_number']) ?>"
                                    placeholder="919876543210 (without + sign)"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="text-xs text-slate-500 mt-1">Used for the WhatsApp chat button</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                            <button type="submit"
                                class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($currentTab === 'business'): ?>
                    <!-- Business Rules -->
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="business">

                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">Business Rules</h2>
                            <p class="text-sm text-slate-500 mt-1">Configure delivery and order policies</p>
                        </div>

                        <div class="p-6 space-y-5">
                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Video Delivery Time
                                        (Days)</label>
                                    <input type="number" name="video_delivery_days" min="1" max="14"
                                        value="<?= Security::escape($settings['video_delivery_days']) ?>"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-slate-500 mt-1">Estimated days to deliver completed video</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Download Link Expiry
                                        (Days)</label>
                                    <input type="number" name="video_download_expiry_days" min="7" max="365"
                                        value="<?= Security::escape($settings['video_download_expiry_days']) ?>"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-slate-500 mt-1">How long customers can download their video</p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Free Revisions</label>
                                    <input type="number" name="free_revisions" min="0" max="10"
                                        value="<?= Security::escape($settings['free_revisions']) ?>"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-slate-500 mt-1">Number of free revision requests per order</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Min Order for Promo Codes
                                        ($)</label>
                                    <input type="number" name="min_order_for_promo" min="0" step="0.01"
                                        value="<?= Security::escape($settings['min_order_for_promo']) ?>"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-slate-500 mt-1">Minimum order value to apply promo codes</p>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                            <button type="submit"
                                class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($currentTab === 'notifications'): ?>
                    <!-- Notification Settings -->
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="notifications">

                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">Notification Settings</h2>
                            <p class="text-sm text-slate-500 mt-1">Configure email notifications for admin</p>
                        </div>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Admin Notification
                                    Email</label>
                                <input type="email" name="admin_notification_email"
                                    value="<?= Security::escape($settings['admin_notification_email']) ?>"
                                    placeholder="admin@example.com"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="text-xs text-slate-500 mt-1">Email address to receive admin notifications</p>
                            </div>

                            <div class="space-y-4">
                                <label class="block text-sm font-medium text-slate-700">Email Notifications</label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="email_on_new_order" value="1"
                                        <?= $settings['email_on_new_order'] ? 'checked' : '' ?>
                                        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20">
                                    <span class="text-sm text-slate-600">Notify on new orders</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="email_on_payment" value="1"
                                        <?= $settings['email_on_payment'] ? 'checked' : '' ?>
                                        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20">
                                    <span class="text-sm text-slate-600">Notify on successful payments</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="email_on_support_ticket" value="1"
                                        <?= $settings['email_on_support_ticket'] ? 'checked' : '' ?>
                                        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20">
                                    <span class="text-sm text-slate-600">Notify on new support tickets</span>
                                </label>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                            <button type="submit"
                                class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($currentTab === 'social'): ?>
                    <!-- Social Media -->
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="social">

                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">Social Media Links</h2>
                            <p class="text-sm text-slate-500 mt-1">Connect your social media accounts</p>
                        </div>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                        </svg>
                                        Facebook URL
                                    </span>
                                </label>
                                <input type="url" name="facebook_url"
                                    value="<?= Security::escape($settings['facebook_url']) ?>"
                                    placeholder="https://facebook.com/yourpage"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                        </svg>
                                        Instagram URL
                                    </span>
                                </label>
                                <input type="url" name="instagram_url"
                                    value="<?= Security::escape($settings['instagram_url']) ?>"
                                    placeholder="https://instagram.com/yourhandle"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-sky-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                        </svg>
                                        Twitter/X URL
                                    </span>
                                </label>
                                <input type="url" name="twitter_url"
                                    value="<?= Security::escape($settings['twitter_url']) ?>"
                                    placeholder="https://twitter.com/yourhandle"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                        </svg>
                                        YouTube URL
                                    </span>
                                </label>
                                <input type="url" name="youtube_url"
                                    value="<?= Security::escape($settings['youtube_url']) ?>"
                                    placeholder="https://youtube.com/@yourchannel"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                            <button type="submit"
                                class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>