<?php
/**
 * Admin CMS Panel
 * Manage Hero Section, Theme Colors, and Category Images
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/../src/Core/ImageHelper.php';
require_once __DIR__ . '/auth.php';

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

// Ensure upload directories exist
$uploadDirs = [
    __DIR__ . '/../uploads/cms',
    __DIR__ . '/../uploads/cms/hero',
    __DIR__ . '/../uploads/cms/categories'
];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';

        // Hero Section Save
        if ($action === 'hero') {
            // Handle hero image upload (desktop)
            if (!empty($_FILES['hero_image_desktop']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/cms/hero/';
                $result = ImageHelper::processThumbnailUpload(
                    $_FILES['hero_image_desktop'],
                    $uploadDir,
                    'hero_desktop_',
                    1920,
                    800,
                    85
                );
                if ($result['success']) {
                    setSetting('hero_image_desktop', '/uploads/cms/hero/' . $result['url']);
                } else {
                    $error = 'Failed to upload desktop hero image: ' . $result['error'];
                }
            }

            // Handle hero image upload (mobile)
            if (!empty($_FILES['hero_image_mobile']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/cms/hero/';
                $result = ImageHelper::processThumbnailUpload(
                    $_FILES['hero_image_mobile'],
                    $uploadDir,
                    'hero_mobile_',
                    768,
                    600,
                    85
                );
                if ($result['success']) {
                    setSetting('hero_image_mobile', '/uploads/cms/hero/' . $result['url']);
                } else {
                    $error = 'Failed to upload mobile hero image: ' . $result['error'];
                }
            }

            // Save hero text
            setSetting('hero_title', $_POST['hero_title'] ?? '');
            setSetting('hero_subtitle', $_POST['hero_subtitle'] ?? '');

            if (!$error) {
                $success = 'Hero section updated successfully!';
            }
        }

        // Theme Colors Save
        if ($action === 'theme') {
            setSetting('theme_primary_color', $_POST['theme_primary_color'] ?? '#7f13ec');
            setSetting('theme_text_primary', $_POST['theme_text_primary'] ?? '#0f172a');
            setSetting('theme_text_secondary', $_POST['theme_text_secondary'] ?? '#64748b');
            setSetting('theme_bg_light', $_POST['theme_bg_light'] ?? '#f7f6f8');
            setSetting('theme_bg_dark', $_POST['theme_bg_dark'] ?? '#191022');
            // Nav colors (header/footer)
            setSetting('nav_bg_color', $_POST['nav_bg_color'] ?? '#ffffff');
            setSetting('nav_text_color', $_POST['nav_text_color'] ?? '#1e293b');
            setSetting('nav_hover_color', $_POST['nav_hover_color'] ?? '#7f13ec');
            $success = 'Theme colors updated successfully!';
        }

        // Category Images Save
        if ($action === 'category_image') {
            $categoryId = intval($_POST['category_id'] ?? 0);
            
            if ($categoryId > 0 && !empty($_FILES['category_image']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/cms/categories/';
                $result = ImageHelper::processThumbnailUpload(
                    $_FILES['category_image'],
                    $uploadDir,
                    'cat_' . $categoryId . '_',
                    200,
                    200,
                    90
                );
                if ($result['success']) {
                    Database::query(
                        "UPDATE categories SET image_url = ? WHERE id = ?",
                        ['/uploads/cms/categories/' . $result['url'], $categoryId]
                    );
                    $success = 'Category image uploaded successfully!';
                } else {
                    $error = 'Failed to upload category image: ' . $result['error'];
                }
            }
        }

        // Category Display Mode
        if ($action === 'category_display') {
            setSetting('category_display_mode', $_POST['category_display_mode'] ?? 'icon');
            $success = 'Category display mode updated!';
        }

        // Remove category image
        if ($action === 'remove_category_image') {
            $categoryId = intval($_POST['category_id'] ?? 0);
            if ($categoryId > 0) {
                Database::query("UPDATE categories SET image_url = NULL WHERE id = ?", [$categoryId]);
                $success = 'Category image removed!';
            }
        }

    } else {
        $error = 'Invalid form submission. Please try again.';
    }
}

// Get current settings
$settings = [
    'hero_image_desktop' => getSetting('hero_image_desktop', ''),
    'hero_image_mobile' => getSetting('hero_image_mobile', ''),
    'hero_title' => getSetting('hero_title', 'Create Beautiful <span class="text-primary">Invitation Videos</span>'),
    'hero_subtitle' => getSetting('hero_subtitle', 'Stunning video invitations for weddings, birthdays, and special events. Easy to customize, ready to share.'),
    'theme_primary_color' => getSetting('theme_primary_color', '#7f13ec'),
    'theme_text_primary' => getSetting('theme_text_primary', '#0f172a'),
    'theme_text_secondary' => getSetting('theme_text_secondary', '#64748b'),
    'theme_bg_light' => getSetting('theme_bg_light', '#f7f6f8'),
    'theme_bg_dark' => getSetting('theme_bg_dark', '#191022'),
    'nav_bg_color' => getSetting('nav_bg_color', '#ffffff'),
    'nav_text_color' => getSetting('nav_text_color', '#1e293b'),
    'nav_hover_color' => getSetting('nav_hover_color', '#7f13ec'),
    'category_display_mode' => getSetting('category_display_mode', 'icon'),
];

// Get categories
$categories = Database::fetchAll("SELECT * FROM categories ORDER BY display_order ASC, name ASC");

$pageTitle = 'CMS Management';
$currentTab = $_GET['tab'] ?? 'hero';
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">CMS Management</h1>
            <p class="text-slate-500 mt-1">Manage website content, theme colors, and category images</p>
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
                <a href="?tab=hero"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors <?= $currentTab === 'hero' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">image</span>
                    Hero Section
                </a>
                <a href="?tab=theme"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-t border-slate-100 <?= $currentTab === 'theme' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">palette</span>
                    Theme Colors
                </a>
                <a href="?tab=categories"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-t border-slate-100 <?= $currentTab === 'categories' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">category</span>
                    Category Images
                </a>
            </nav>
        </div>

        <!-- Content -->
        <div class="flex-1">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

                <?php if ($currentTab === 'hero'): ?>
                    <!-- Hero Section Settings -->
                    <form method="POST" enctype="multipart/form-data">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="hero">

                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">Hero Section</h2>
                            <p class="text-sm text-slate-500 mt-1">Customize the homepage hero banner</p>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Desktop Hero Image -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Desktop Hero Image (1920 x 800 recommended)
                                </label>
                                <div class="flex items-start gap-4">
                                    <?php if ($settings['hero_image_desktop']): ?>
                                        <div class="relative w-64 aspect-[2.4/1] rounded-lg overflow-hidden bg-slate-100">
                                            <img src="<?= Security::escape($settings['hero_image_desktop']) ?>"
                                                alt="Desktop Hero" class="w-full h-full object-cover">
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="w-64 aspect-[2.4/1] rounded-lg bg-slate-100 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-4xl text-slate-300">image</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <input type="file" name="hero_image_desktop" accept="image/*"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                        <p class="text-xs text-slate-400 mt-1">Will be compressed to WebP format</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Hero Image -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Mobile Hero Image (768 x 600 recommended)
                                </label>
                                <div class="flex items-start gap-4">
                                    <?php if ($settings['hero_image_mobile']): ?>
                                        <div class="relative w-32 aspect-[4/5] rounded-lg overflow-hidden bg-slate-100">
                                            <img src="<?= Security::escape($settings['hero_image_mobile']) ?>" alt="Mobile Hero"
                                                class="w-full h-full object-cover">
                                        </div>
                                    <?php else: ?>
                                        <div class="w-32 aspect-[4/5] rounded-lg bg-slate-100 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-4xl text-slate-300">image</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <input type="file" name="hero_image_mobile" accept="image/*"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                        <p class="text-xs text-slate-400 mt-1">Will be compressed to WebP format</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-200">

                            <!-- Hero Title -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Hero Title</label>
                                <input type="text" name="hero_title"
                                    value="<?= Security::escape($settings['hero_title']) ?>"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="text-xs text-slate-400 mt-1">HTML allowed. Use &lt;span class="text-primary"&gt;
                                    for accent color.</p>
                            </div>

                            <!-- Hero Subtitle -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Hero Subtitle</label>
                                <textarea name="hero_subtitle" rows="2"
                                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"><?= Security::escape($settings['hero_subtitle']) ?></textarea>
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

                <?php if ($currentTab === 'theme'): ?>
                    <!-- Theme Colors Settings -->
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="theme">

                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-bold text-slate-900">Theme Colors</h2>
                            <p class="text-sm text-slate-500 mt-1">Customize website colors</p>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Primary Color -->
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Primary Color</label>
                                    <p class="text-xs text-slate-400">Used for buttons, links, and accents</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="theme_primary_color"
                                        value="<?= Security::escape($settings['theme_primary_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['theme_primary_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <hr class="border-slate-200">

                            <!-- Text Colors -->
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Primary Text Color</label>
                                    <p class="text-xs text-slate-400">Main headings and important text</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="theme_text_primary"
                                        value="<?= Security::escape($settings['theme_text_primary']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['theme_text_primary']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Secondary Text
                                        Color</label>
                                    <p class="text-xs text-slate-400">Body text and descriptions</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="theme_text_secondary"
                                        value="<?= Security::escape($settings['theme_text_secondary']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['theme_text_secondary']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <hr class="border-slate-200">

                            <!-- Background Colors -->
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Light Mode
                                        Background</label>
                                    <p class="text-xs text-slate-400">Background for light theme</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="theme_bg_light"
                                        value="<?= Security::escape($settings['theme_bg_light']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['theme_bg_light']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Dark Mode
                                        Background</label>
                                    <p class="text-xs text-slate-400">Background for dark theme</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="theme_bg_dark"
                                        value="<?= Security::escape($settings['theme_bg_dark']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['theme_bg_dark']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <hr class="border-slate-200">

                            <!-- Header/Footer Navigation Colors -->
                            <h4 class="text-sm font-semibold text-slate-900">Header & Footer Colors</h4>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Background Color</label>
                                    <p class="text-xs text-slate-400">Header and footer background</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="nav_bg_color"
                                        value="<?= Security::escape($settings['nav_bg_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['nav_bg_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Text Color</label>
                                    <p class="text-xs text-slate-400">Links and text in header/footer</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="nav_text_color"
                                        value="<?= Security::escape($settings['nav_text_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['nav_text_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Hover/Active Color</label>
                                    <p class="text-xs text-slate-400">Links on hover and active state</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="nav_hover_color"
                                        value="<?= Security::escape($settings['nav_hover_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['nav_hover_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <!-- Color Preview -->
                            <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                                <h4 class="text-sm font-medium text-slate-700 mb-3">Preview</h4>
                                <div class="flex gap-4">
                                    <!-- Light Mode Preview -->
                                    <div class="flex-1 p-4 rounded-lg" id="light-preview"
                                        style="background: <?= Security::escape($settings['theme_bg_light']) ?>">
                                        <h5 class="font-bold mb-1"
                                            style="color: <?= Security::escape($settings['theme_text_primary']) ?>">Sample
                                            Heading</h5>
                                        <p class="text-sm mb-2"
                                            style="color: <?= Security::escape($settings['theme_text_secondary']) ?>">This
                                            is sample body text.</p>
                                        <button class="px-3 py-1 rounded text-white text-sm"
                                            style="background: <?= Security::escape($settings['theme_primary_color']) ?>">Button</button>
                                    </div>
                                    <!-- Dark Mode Preview -->
                                    <div class="flex-1 p-4 rounded-lg"
                                        style="background: <?= Security::escape($settings['theme_bg_dark']) ?>">
                                        <h5 class="font-bold mb-1 text-white">Sample Heading</h5>
                                        <p class="text-sm mb-2 text-slate-400">This is sample body text.</p>
                                        <button class="px-3 py-1 rounded text-white text-sm"
                                            style="background: <?= Security::escape($settings['theme_primary_color']) ?>">Button</button>
                                    </div>
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

                <?php if ($currentTab === 'categories'): ?>
                    <!-- Category Images Settings -->
                    <div class="p-6 border-b border-slate-200">
                        <h2 class="text-lg font-bold text-slate-900">Category Images</h2>
                        <p class="text-sm text-slate-500 mt-1">Upload images for categories (replaces icons on homepage)</p>
                    </div>

                    <!-- Display Mode Toggle -->
                    <div class="p-6 border-b border-slate-200 bg-slate-50">
                        <form method="POST" class="flex items-center justify-between">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="category_display">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Display Mode</label>
                                <p class="text-xs text-slate-400">Choose how categories appear on the homepage</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <select name="category_display_mode" onchange="this.form.submit()"
                                    class="px-4 py-2 rounded-lg border border-slate-300 text-sm">
                                    <option value="icon" <?= $settings['category_display_mode'] === 'icon' ? 'selected' : '' ?>
                                        >Icons Only</option>
                                    <option value="image" <?= $settings['category_display_mode'] === 'image' ? 'selected' : '' ?>>Images Only</option>
                                    <option value="both" <?= $settings['category_display_mode'] === 'both' ? 'selected' : '' ?>
                                        >Image with Icon Fallback</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- Categories List -->
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($categories as $category): ?>
                            <div class="p-4 flex items-center gap-4 hover:bg-slate-50">
                                <!-- Current Image/Icon -->
                                <div
                                    class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden shrink-0">
                                    <?php if ($category['image_url']): ?>
                                        <img src="<?= Security::escape($category['image_url']) ?>"
                                            alt="<?= Security::escape($category['name']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-2xl"
                                            style="color: <?= Security::escape($category['color']) ?>">
                                            <?= Security::escape($category['icon']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Category Info -->
                                <div class="flex-1">
                                    <h4 class="font-medium text-slate-900">
                                        <?= Security::escape($category['name']) ?>
                                    </h4>
                                    <p class="text-xs text-slate-400">
                                        <?= Security::escape($category['slug']) ?>
                                    </p>
                                </div>

                                <!-- Upload Form -->
                                <form method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="category_image">
                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                    <input type="file" name="category_image" accept="image/*" required
                                        class="text-sm text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                    <button type="submit"
                                        class="px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90">
                                        Upload
                                    </button>
                                </form>

                                <!-- Remove Image -->
                                <?php if ($category['image_url']): ?>
                                    <form method="POST" class="inline">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="action" value="remove_category_image">
                                        <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                        <button type="submit" onclick="return confirm('Remove this image?')"
                                            class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Remove image">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Script for live color preview -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const colorInputs = document.querySelectorAll('input[type="color"]');
        colorInputs.forEach(input => {
            input.addEventListener('input', function () {
                // Sync with text input
                this.nextElementSibling.value = this.value;
            });
        });
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>