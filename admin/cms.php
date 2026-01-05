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
    __DIR__ . '/../uploads/cms/categories',
    __DIR__ . '/../uploads/cms/sections'
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
            setSetting('hero_button_text', $_POST['hero_button_text'] ?? '');
            setSetting('hero_button_link', $_POST['hero_button_link'] ?? '');

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
            // Header colors
            setSetting('header_bg_color', $_POST['header_bg_color'] ?? '#ffffff');
            setSetting('header_text_color', $_POST['header_text_color'] ?? '#1e293b');
            setSetting('header_hover_color', $_POST['header_hover_color'] ?? '#7f13ec');
            // Footer colors
            setSetting('footer_bg_color', $_POST['footer_bg_color'] ?? '#ffffff');
            setSetting('footer_text_color', $_POST['footer_text_color'] ?? '#1e293b');
            setSetting('footer_hover_color', $_POST['footer_hover_color'] ?? '#7f13ec');
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

        // Homepage Section - Save/Update
        if ($action === 'save_section') {
            $sectionId = intval($_POST['section_id'] ?? 0);
            $sectionTitle = trim($_POST['section_title'] ?? '');
            $categorySlug = $_POST['category_slug'] ?? null;
            $subcategory = trim($_POST['subcategory'] ?? '') ?: null;
            $bannerBgColor = $_POST['banner_bg_color'] ?? '#a11045';
            $titleColor = $_POST['title_color'] ?? '#d4a853';
            $gridBgColor = $_POST['grid_bg_color'] ?? '#f5f0e8';
            $templateCount = max(3, min(6, intval($_POST['template_count'] ?? 4)));
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            // Visual positioning data
            $svgPosition = $_POST['svg_position'] ?? null;
            $imagePosition = $_POST['image_position'] ?? null;
            $bannerHeights = $_POST['banner_heights'] ?? null;
            $svgAnimation = $_POST['svg_animation'] ?? 'none';
            $imageAnimation = $_POST['image_animation'] ?? 'none';
            $imageOverflow = isset($_POST['image_overflow']) && $_POST['image_overflow'] === '1' ? 1 : 0;

            if (empty($sectionTitle)) {
                $error = 'Section title is required.';
            } else {
                // Check max 8 sections
                $existingCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM homepage_sections")['cnt'] ?? 0;
                if ($sectionId === 0 && $existingCount >= 8) {
                    $error = 'Maximum 8 homepage sections allowed.';
                } else {
                    // Handle banner image upload
                    $bannerImageUrl = $_POST['existing_banner_image'] ?? null;
                    if (!empty($_FILES['banner_image']['name'])) {
                        $uploadDir = __DIR__ . '/../uploads/cms/sections/';
                        $result = ImageHelper::processThumbnailUpload(
                            $_FILES['banner_image'],
                            $uploadDir,
                            'section_img_',
                            600,
                            400,
                            90
                        );
                        if ($result['success']) {
                            $bannerImageUrl = '/uploads/cms/sections/' . $result['url'];
                        } else {
                            $error = 'Failed to upload banner image: ' . $result['error'];
                        }
                    }

                    // Handle SVG upload
                    $bannerSvgUrl = $_POST['existing_banner_svg'] ?? null;
                    if (!empty($_FILES['banner_svg']['name'])) {
                        $svgFile = $_FILES['banner_svg'];
                        if ($svgFile['type'] === 'image/svg+xml' || pathinfo($svgFile['name'], PATHINFO_EXTENSION) === 'svg') {
                            $svgName = 'section_svg_' . time() . '_' . uniqid() . '.svg';
                            $svgPath = __DIR__ . '/../uploads/cms/sections/' . $svgName;
                            if (move_uploaded_file($svgFile['tmp_name'], $svgPath)) {
                                $bannerSvgUrl = '/uploads/cms/sections/' . $svgName;
                            } else {
                                $error = 'Failed to upload SVG file.';
                            }
                        } else {
                            $error = 'Invalid SVG file format.';
                        }
                    }

                    if (!$error) {
                        if ($sectionId > 0) {
                            // Update existing
                            Database::query(
                                "UPDATE homepage_sections SET 
                                    section_title = ?, category_slug = ?, subcategory = ?,
                                    banner_bg_color = ?, banner_svg_url = ?, banner_image_url = ?,
                                    title_color = ?, grid_bg_color = ?, template_count = ?, is_active = ?,
                                    svg_position = ?, image_position = ?, banner_heights = ?,
                                    svg_animation = ?, image_animation = ?, image_overflow = ?,
                                    updated_at = NOW()
                                WHERE id = ?",
                                [$sectionTitle, $categorySlug, $subcategory, $bannerBgColor, $bannerSvgUrl, 
                                 $bannerImageUrl, $titleColor, $gridBgColor, $templateCount, $isActive,
                                 $svgPosition, $imagePosition, $bannerHeights, $svgAnimation, $imageAnimation, $imageOverflow, $sectionId]
                            );
                            $success = 'Section updated successfully!';
                        } else {
                            // Get next display order
                            $maxOrder = Database::fetchOne("SELECT MAX(display_order) as max_order FROM homepage_sections")['max_order'] ?? 0;
                            // Insert new
                            Database::query(
                                "INSERT INTO homepage_sections 
                                    (section_title, category_slug, subcategory, banner_bg_color, banner_svg_url, 
                                     banner_image_url, title_color, grid_bg_color, template_count, display_order, is_active,
                                     svg_position, image_position, banner_heights, svg_animation, image_animation, image_overflow)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                [$sectionTitle, $categorySlug, $subcategory, $bannerBgColor, $bannerSvgUrl,
                                 $bannerImageUrl, $titleColor, $gridBgColor, $templateCount, $maxOrder + 1, $isActive,
                                 $svgPosition, $imagePosition, $bannerHeights, $svgAnimation, $imageAnimation, $imageOverflow]
                            );
                            $success = 'Section created successfully!';
                        }
                    }
                }
            }
        }

        // Homepage Section - Delete
        if ($action === 'delete_section') {
            $sectionId = intval($_POST['section_id'] ?? 0);
            if ($sectionId > 0) {
                Database::query("DELETE FROM homepage_sections WHERE id = ?", [$sectionId]);
                $success = 'Section deleted successfully!';
            }
        }

        // Homepage Section - Reorder
        if ($action === 'reorder_sections') {
            $order = json_decode($_POST['order'] ?? '[]', true);
            if (is_array($order)) {
                foreach ($order as $index => $id) {
                    Database::query("UPDATE homepage_sections SET display_order = ? WHERE id = ?", [$index, intval($id)]);
                }
                $success = 'Sections reordered successfully!';
            }
        }

        // Homepage Section - Toggle Active
        if ($action === 'toggle_section') {
            $sectionId = intval($_POST['section_id'] ?? 0);
            if ($sectionId > 0) {
                Database::query("UPDATE homepage_sections SET is_active = NOT is_active WHERE id = ?", [$sectionId]);
                $success = 'Section status updated!';
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
    'hero_button_text' => getSetting('hero_button_text', 'Browse Templates'),
    'hero_button_link' => getSetting('hero_button_link', '/templates'),
    'theme_primary_color' => getSetting('theme_primary_color', '#7f13ec'),
    'theme_text_primary' => getSetting('theme_text_primary', '#0f172a'),
    'theme_text_secondary' => getSetting('theme_text_secondary', '#64748b'),
    'theme_bg_light' => getSetting('theme_bg_light', '#f7f6f8'),
    'theme_bg_dark' => getSetting('theme_bg_dark', '#191022'),
    'header_bg_color' => getSetting('header_bg_color', '#ffffff'),
    'header_text_color' => getSetting('header_text_color', '#1e293b'),
    'header_hover_color' => getSetting('header_hover_color', '#7f13ec'),
    'footer_bg_color' => getSetting('footer_bg_color', '#ffffff'),
    'footer_text_color' => getSetting('footer_text_color', '#1e293b'),
    'footer_hover_color' => getSetting('footer_hover_color', '#7f13ec'),
    'category_display_mode' => getSetting('category_display_mode', 'icon'),
];

// Get categories
$categories = Database::fetchAll("SELECT * FROM categories ORDER BY display_order ASC, name ASC");

// Get homepage sections
$homepageSections = [];
try {
    $homepageSections = Database::fetchAll("SELECT * FROM homepage_sections ORDER BY display_order ASC") ?? [];
} catch (Exception $e) {
    // Table may not exist yet
}

// Get section being edited (if any)
$editSection = null;
if (isset($_GET['edit_section'])) {
    $editSection = Database::fetchOne("SELECT * FROM homepage_sections WHERE id = ?", [intval($_GET['edit_section'])]);
}

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
                <a href="?tab=sections"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-t border-slate-100 <?= $currentTab === 'sections' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">
                    <span class="material-symbols-outlined text-lg">dashboard_customize</span>
                    Homepage Sections
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

                            <hr class="border-slate-200">

                            <!-- Hero Button -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Button Text</label>
                                    <input type="text" name="hero_button_text"
                                        value="<?= Security::escape($settings['hero_button_text']) ?>"
                                        placeholder="e.g. Browse Templates"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-slate-400 mt-1">Leave empty to hide button</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Button Link</label>
                                    <input type="text" name="hero_button_link"
                                        value="<?= Security::escape($settings['hero_button_link']) ?>"
                                        placeholder="e.g. /templates"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
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

                            <!-- Header Colors -->
                            <h4 class="text-sm font-semibold text-slate-900">Header Colors</h4>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Background</label>
                                    <p class="text-xs text-slate-400">Header background color</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="header_bg_color"
                                        value="<?= Security::escape($settings['header_bg_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['header_bg_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Text Color</label>
                                    <p class="text-xs text-slate-400">Header links and text</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="header_text_color"
                                        value="<?= Security::escape($settings['header_text_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['header_text_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Hover/Active</label>
                                    <p class="text-xs text-slate-400">Header link hover state</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="header_hover_color"
                                        value="<?= Security::escape($settings['header_hover_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['header_hover_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <hr class="border-slate-200">

                            <!-- Footer Colors -->
                            <h4 class="text-sm font-semibold text-slate-900">Footer Colors</h4>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Background</label>
                                    <p class="text-xs text-slate-400">Footer background color</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="footer_bg_color"
                                        value="<?= Security::escape($settings['footer_bg_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['footer_bg_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Text Color</label>
                                    <p class="text-xs text-slate-400">Footer links and text</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="footer_text_color"
                                        value="<?= Security::escape($settings['footer_text_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['footer_text_color']) ?>"
                                        class="w-28 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                        onchange="this.previousElementSibling.value = this.value"
                                        oninput="this.previousElementSibling.value = this.value">
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Hover/Active</label>
                                    <p class="text-xs text-slate-400">Footer link hover state</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="footer_hover_color"
                                        value="<?= Security::escape($settings['footer_hover_color']) ?>"
                                        class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                    <input type="text" value="<?= Security::escape($settings['footer_hover_color']) ?>"
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

                <?php if ($currentTab === 'sections'): ?>
                    <!-- Homepage Sections Settings -->
                    <div class="p-6 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Homepage Sections</h2>
                                <p class="text-sm text-slate-500 mt-1">Create custom template sections for the homepage (max 8)</p>
                            </div>
                            <?php if (!$editSection && count($homepageSections) < 8): ?>
                                <a href="?tab=sections&edit_section=new"
                                    class="flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                    <span class="material-symbols-outlined text-lg">add</span>
                                    Add Section
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($editSection || isset($_GET['edit_section'])): ?>
                        <!-- Section Editor Form -->
                        <form method="POST" enctype="multipart/form-data">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="save_section">
                            <input type="hidden" name="section_id" value="<?= $editSection['id'] ?? 0 ?>">
                            <?php if ($editSection): ?>
                                <input type="hidden" name="existing_banner_image" value="<?= Security::escape($editSection['banner_image_url'] ?? '') ?>">
                                <input type="hidden" name="existing_banner_svg" value="<?= Security::escape($editSection['banner_svg_url'] ?? '') ?>">
                            <?php endif; ?>

                            <div class="p-6 space-y-6">
                                <!-- Section Title -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Section Title *</label>
                                    <input type="text" name="section_title" required
                                        value="<?= Security::escape($editSection['section_title'] ?? '') ?>"
                                        placeholder="e.g., Mehandi, Christmas Specials"
                                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    <p class="text-xs text-slate-400 mt-1">Displayed on the homepage banner (e.g., "Mehandi")</p>
                                </div>

                                <hr class="border-slate-200">

                                <!-- Template Filtering -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                                        <select name="category_slug"
                                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= Security::escape($cat['slug']) ?>"
                                                    <?= ($editSection['category_slug'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                                                    <?= Security::escape($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Subcategory / Occasion</label>
                                        <input type="text" name="subcategory"
                                            value="<?= Security::escape($editSection['subcategory'] ?? '') ?>"
                                            placeholder="e.g., mehandi, haldi, christmas"
                                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <p class="text-xs text-slate-400 mt-1">Filter by specific occasion/subcategory</p>
                                    </div>
                                </div>

                                <hr class="border-slate-200">

                                <!-- Banner Styling -->
                                <h4 class="text-sm font-semibold text-slate-900">Header Banner Styling</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Banner Background Color -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Banner Background</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" name="banner_bg_color"
                                                value="<?= Security::escape($editSection['banner_bg_color'] ?? '#a11045') ?>"
                                                class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                            <input type="text" value="<?= Security::escape($editSection['banner_bg_color'] ?? '#a11045') ?>"
                                                class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                                onchange="this.previousElementSibling.value = this.value"
                                                oninput="this.previousElementSibling.value = this.value">
                                        </div>
                                    </div>

                                    <!-- Title Color -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Title Color</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" name="title_color"
                                                value="<?= Security::escape($editSection['title_color'] ?? '#d4a853') ?>"
                                                class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                            <input type="text" value="<?= Security::escape($editSection['title_color'] ?? '#d4a853') ?>"
                                                class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                                onchange="this.previousElementSibling.value = this.value"
                                                oninput="this.previousElementSibling.value = this.value">
                                        </div>
                                    </div>

                                    <!-- Grid Background Color -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Grid Container Background</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" name="grid_bg_color"
                                                value="<?= Security::escape($editSection['grid_bg_color'] ?? '#f5f0e8') ?>"
                                                class="w-12 h-12 rounded-lg border border-slate-300 cursor-pointer">
                                            <input type="text" value="<?= Security::escape($editSection['grid_bg_color'] ?? '#f5f0e8') ?>"
                                                class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono"
                                                onchange="this.previousElementSibling.value = this.value"
                                                oninput="this.previousElementSibling.value = this.value">
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Uploads -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Banner Image -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Category Image (Right Side)</label>
                                        <?php if (!empty($editSection['banner_image_url'])): ?>
                                            <div class="mb-2 relative w-32 h-24 rounded-lg overflow-hidden bg-slate-100">
                                                <img src="<?= Security::escape($editSection['banner_image_url']) ?>" 
                                                    alt="Banner" class="w-full h-full object-cover">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="banner_image" accept="image/*"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                        <p class="text-xs text-slate-400 mt-1">Image shown on the right side of banner</p>
                                    </div>

                                    <!-- SVG Pattern -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">SVG Pattern (Background)</label>
                                        <?php if (!empty($editSection['banner_svg_url'])): ?>
                                            <div class="mb-2 text-xs text-green-600 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                                SVG uploaded: <?= basename($editSection['banner_svg_url']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="banner_svg" accept=".svg,image/svg+xml"
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                        <p class="text-xs text-slate-400 mt-1">Decorative SVG pattern overlay</p>
                                    </div>
                                </div>

                                <hr class="border-slate-200">

                                <!-- Display Settings -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Templates to Show</label>
                                        <input type="number" name="template_count" min="3" max="6"
                                            value="<?= intval($editSection['template_count'] ?? 4) ?>"
                                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <p class="text-xs text-slate-400 mt-1">Number of templates per row (3-6)</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="is_active" value="1"
                                                <?= ($editSection['is_active'] ?? 1) ? 'checked' : '' ?>
                                                class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary/20">
                                            <span class="text-sm font-medium text-slate-700">Active (visible on homepage)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Visual Position Editor -->
                                <div class="p-4 rounded-lg border border-slate-200 bg-white">
                                    <h4 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-lg text-primary">tune</span>
                                        Visual Position Editor
                                    </h4>
                                    <p class="text-xs text-slate-500 mb-4">
                                        Configure SVG and Image positioning for each screen size. Use the breakpoint tabs to set different positions for mobile, tablet, and desktop views.
                                    </p>
                                    <div id="position-editor-container"></div>
                                </div>

                                <!-- Grid Preview -->
                                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                                    <h4 class="text-sm font-medium text-slate-700 mb-3">Template Grid Preview</h4>
                                    <div class="h-16 flex items-center justify-center rounded-lg"
                                        style="background-color: <?= Security::escape($editSection['grid_bg_color'] ?? '#f5f0e8') ?>">
                                        <span class="text-xs text-slate-500">Template cards will appear here</span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <a href="?tab=sections" class="text-slate-600 hover:text-slate-900">← Cancel</a>
                                <button type="submit"
                                    class="px-6 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                                    <?= $editSection ? 'Update Section' : 'Create Section' ?>
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- Sections List -->
                        <?php if (empty($homepageSections)): ?>
                            <div class="p-12 text-center">
                                <span class="material-symbols-outlined text-5xl text-slate-300 mb-4">dashboard_customize</span>
                                <h3 class="text-lg font-bold text-slate-600 mb-2">No Sections Yet</h3>
                                <p class="text-slate-400 mb-6">Create your first homepage section to showcase templates by category.</p>
                                <a href="?tab=sections&edit_section=new"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90">
                                    <span class="material-symbols-outlined">add</span>
                                    Create First Section
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-100" id="sections-list">
                                <?php foreach ($homepageSections as $section): ?>
                                    <div class="p-4 flex items-center gap-4 hover:bg-slate-50 group" data-section-id="<?= $section['id'] ?>">
                                        <!-- Drag Handle -->
                                        <span class="material-symbols-outlined text-slate-300 cursor-move drag-handle">drag_indicator</span>

                                        <!-- Preview -->
                                        <div class="w-32 h-16 rounded-lg overflow-hidden shrink-0">
                                            <div class="h-10 relative flex items-center px-2"
                                                style="background-color: <?= Security::escape($section['banner_bg_color']) ?>">
                                                <span class="text-xs italic truncate"
                                                    style="color: <?= Security::escape($section['title_color']) ?>">
                                                    <?= Security::escape($section['section_title']) ?>
                                                </span>
                                            </div>
                                            <div class="h-6"
                                                style="background-color: <?= Security::escape($section['grid_bg_color']) ?>">
                                            </div>
                                        </div>

                                        <!-- Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-slate-900 truncate">
                                                <?= Security::escape($section['section_title']) ?>
                                            </h4>
                                            <p class="text-xs text-slate-400">
                                                <?= $section['category_slug'] ? Security::escape($section['category_slug']) : 'All' ?>
                                                <?= $section['subcategory'] ? ' / ' . Security::escape($section['subcategory']) : '' ?>
                                                • <?= $section['template_count'] ?> templates
                                            </p>
                                        </div>

                                        <!-- Status -->
                                        <form method="POST" class="inline">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_section">
                                            <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                            <button type="submit" class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                                <?= $section['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                                                <span class="w-2 h-2 rounded-full <?= $section['is_active'] ? 'bg-green-500' : 'bg-slate-400' ?>"></span>
                                                <?= $section['is_active'] ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="?tab=sections&edit_section=<?= $section['id'] ?>"
                                                class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg" title="Edit">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this section?')">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="action" value="delete_section">
                                                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                                                <button type="submit"
                                                    class="p-2 text-red-500 hover:bg-red-50 rounded-lg" title="Delete">
                                                    <span class="material-symbols-outlined text-lg">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                                <p class="text-xs text-slate-400">
                                    <span class="material-symbols-outlined text-sm align-middle">info</span>
                                    Drag sections to reorder. Changes are saved automatically.
                                </p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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

<?php if ($currentTab === 'sections' && ($editSection || isset($_GET['edit_section']))): ?>
<!-- Position Editor Script -->
<script src="/admin/assets/js/position-editor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the position editor
        if (document.getElementById('position-editor-container')) {
            const editor = PositionEditor.create('position-editor-container', {
                // Existing positioning data
                svgPosition: <?= json_encode($editSection['svg_position'] ?? null) ?>,
                imagePosition: <?= json_encode($editSection['image_position'] ?? null) ?>,
                bannerHeights: <?= json_encode($editSection['banner_heights'] ?? null) ?>,
                svgAnimation: <?= json_encode($editSection['svg_animation'] ?? 'none') ?>,
                imageAnimation: <?= json_encode($editSection['image_animation'] ?? 'none') ?>,
                imageOverflow: <?= ($editSection['image_overflow'] ?? 1) ? 'true' : 'false' ?>,
                
                // Preview context
                bannerBgColor: <?= json_encode($editSection['banner_bg_color'] ?? '#a11045') ?>,
                titleColor: <?= json_encode($editSection['title_color'] ?? '#d4a853') ?>,
                sectionTitle: <?= json_encode($editSection['section_title'] ?? 'Section Title') ?>,
                svgUrl: <?= json_encode($editSection['banner_svg_url'] ?? '') ?>,
                imageUrl: <?= json_encode($editSection['banner_image_url'] ?? '') ?>
            });
            
            // Update editor when colors change
            const bgColorInput = document.querySelector('input[name="banner_bg_color"]');
            const titleColorInput = document.querySelector('input[name="title_color"]');
            const titleInput = document.querySelector('input[name="section_title"]');
            
            if (bgColorInput) {
                bgColorInput.addEventListener('input', function() {
                    const canvas = document.querySelector('.preview-canvas');
                    if (canvas) canvas.style.backgroundColor = this.value;
                });
            }
            
            if (titleColorInput) {
                titleColorInput.addEventListener('input', function() {
                    const title = document.querySelector('.preview-canvas span[style*="font-family"]');
                    if (title) title.style.color = this.value;
                });
            }
            
            if (titleInput) {
                titleInput.addEventListener('input', function() {
                    const title = document.querySelector('.preview-canvas span[style*="font-family"]');
                    if (title) title.textContent = this.value || 'Section Title';
                });
            }
        }
    });
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>