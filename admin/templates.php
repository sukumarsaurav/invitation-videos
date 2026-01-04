<?php
/**
 * Admin - Template Management
 * Full functionality with SEO slugs, pricing, discounts, media, and field editor
 */

require_once __DIR__ . '/auth.php';  // Must be first for authentication
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$action = $_GET['action'] ?? 'list';
$templateId = intval($_GET['id'] ?? 0);
$error = null;
$success = null;

// Handle AJAX requests for template fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    switch ($_POST['ajax_action']) {
        case 'add_field':
            $fieldData = [
                'template_id' => intval($_POST['template_id']),
                'field_name' => Security::sanitizeString($_POST['field_name'] ?? ''),
                'field_label' => Security::sanitizeString($_POST['field_label'] ?? ''),
                'field_type' => $_POST['field_type'] ?? 'text',
                'field_subtype' => Security::sanitizeString($_POST['field_subtype'] ?? ''),
                'placeholder' => Security::sanitizeString($_POST['placeholder'] ?? ''),
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'] ?? 0),
                'field_group' => Security::sanitizeString($_POST['field_group'] ?? ''),
                'help_text' => Security::sanitizeString($_POST['help_text'] ?? ''),
            ];

            $sql = "INSERT INTO template_fields (template_id, field_name, field_label, field_type, field_subtype, placeholder, is_required, display_order, field_group, help_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            Database::query($sql, array_values($fieldData));
            $fieldId = Database::lastInsertId();

            echo json_encode(['success' => true, 'field_id' => $fieldId]);
            exit;

        case 'update_field':
            $sql = "UPDATE template_fields SET field_label=?, field_type=?, placeholder=?, is_required=?, display_order=?, field_group=?, help_text=? WHERE id=?";
            Database::query($sql, [
                Security::sanitizeString($_POST['field_label'] ?? ''),
                $_POST['field_type'] ?? 'text',
                Security::sanitizeString($_POST['placeholder'] ?? ''),
                isset($_POST['is_required']) ? 1 : 0,
                intval($_POST['display_order'] ?? 0),
                Security::sanitizeString($_POST['field_group'] ?? ''),
                Security::sanitizeString($_POST['help_text'] ?? ''),
                intval($_POST['field_id'])
            ]);
            echo json_encode(['success' => true]);
            exit;

        case 'delete_field':
            Database::query("DELETE FROM template_fields WHERE id = ?", [intval($_POST['field_id'])]);
            echo json_encode(['success' => true]);
            exit;

        case 'delete_gallery_image':
            $imageId = intval($_POST['image_id'] ?? 0);
            $image = Database::fetchOne("SELECT image_url FROM template_images WHERE id = ?", [$imageId]);
            if ($image) {
                // Delete from database
                Database::query("DELETE FROM template_images WHERE id = ?", [$imageId]);
                // Delete file from disk
                $filePath = __DIR__ . '/..' . $image['image_url'];
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            echo json_encode(['success' => true]);
            exit;

        case 'update_gallery_order':
            $order = json_decode($_POST['order'] ?? '[]', true);
            if (is_array($order)) {
                foreach ($order as $position => $imageId) {
                    Database::query("UPDATE template_images SET display_order = ? WHERE id = ?", [$position, intval($imageId)]);
                }
            }
            echo json_encode(['success' => true]);
            exit;

        case 'upload_lang_thumbnail':
            require_once __DIR__ . '/../src/Core/ImageHelper.php';

            $langCode = $_POST['language_code'] ?? 'en';
            $templateId = intval($_POST['template_id'] ?? 0);

            if (!isset($_FILES['lang_thumbnail']) || $_FILES['lang_thumbnail']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'No file uploaded']);
                exit;
            }

            $uploadDir = __DIR__ . '/../uploads/templates/lang/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $result = ImageHelper::processThumbnailUpload(
                $_FILES['lang_thumbnail'],
                $uploadDir,
                'lang_' . $langCode . '_',
                600,
                900,
                75
            );

            if ($result['success']) {
                $imageUrl = '/uploads/templates/lang/' . basename($result['url']);

                // Check if this is the first thumbnail for this language (make it primary)
                $existing = Database::fetchOne(
                    "SELECT COUNT(*) as count FROM template_thumbnails WHERE template_id = ? AND language_code = ?",
                    [$templateId, $langCode]
                );
                $isPrimary = ($existing['count'] == 0) ? 1 : 0;

                // Get max display order
                $maxOrder = Database::fetchOne(
                    "SELECT COALESCE(MAX(display_order), -1) as max_order FROM template_thumbnails WHERE template_id = ? AND language_code = ?",
                    [$templateId, $langCode]
                );

                Database::query(
                    "INSERT INTO template_thumbnails (template_id, language_code, thumbnail_url, is_primary, display_order) VALUES (?, ?, ?, ?, ?)",
                    [$templateId, $langCode, $imageUrl, $isPrimary, $maxOrder['max_order'] + 1]
                );

                $thumbId = Database::lastInsertId();
                echo json_encode([
                    'success' => true,
                    'thumb_id' => $thumbId,
                    'image_url' => $imageUrl,
                    'is_primary' => $isPrimary
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Upload failed']);
            }
            exit;

        case 'delete_lang_thumbnail':
            $thumbId = intval($_POST['thumb_id'] ?? 0);
            $thumb = Database::fetchOne("SELECT thumbnail_url FROM template_thumbnails WHERE id = ?", [$thumbId]);
            if ($thumb) {
                Database::query("DELETE FROM template_thumbnails WHERE id = ?", [$thumbId]);
                $filePath = __DIR__ . '/..' . $thumb['thumbnail_url'];
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            echo json_encode(['success' => true]);
            exit;

        case 'set_lang_thumb_primary':
            $thumbId = intval($_POST['thumb_id'] ?? 0);
            $langCode = $_POST['language_code'] ?? 'en';
            $templateId = intval($_POST['template_id'] ?? 0);

            // Unset all other primaries for this language
            Database::query(
                "UPDATE template_thumbnails SET is_primary = 0 WHERE template_id = ? AND language_code = ?",
                [$templateId, $langCode]
            );
            // Set this one as primary
            Database::query("UPDATE template_thumbnails SET is_primary = 1 WHERE id = ?", [$thumbId]);
            echo json_encode(['success' => true]);
            exit;
    }
}

// Handle thumbnail upload with compression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    require_once __DIR__ . '/../src/Core/ImageHelper.php';

    $uploadDir = __DIR__ . '/../uploads/templates/';

    // Get existing thumbnail URL from DATABASE (not POST) to safely delete after successful upload
    // This prevents accidentally deleting another template's image
    $oldThumbnailUrl = '';
    if ($templateId > 0) {
        $existingTemplate = Database::fetchOne("SELECT thumbnail_url FROM templates WHERE id = ?", [$templateId]);
        $oldThumbnailUrl = $existingTemplate['thumbnail_url'] ?? '';
    }

    // Process and compress the thumbnail with aggressive settings for ~40KB target
    $result = ImageHelper::processThumbnailUpload(
        $_FILES['thumbnail'],
        $uploadDir,
        'template_',
        600,   // Reduced max width for smaller file
        900,   // Reduced max height (maintains 9:16 ratio)
        70     // Lower quality for smaller files (~40KB target)
    );

    if ($result['success']) {
        $_POST['thumbnail_url'] = '/uploads/templates/' . basename($result['url']);

        // Generate responsive variants for srcset
        $baseFilename = pathinfo(basename($result['url']), PATHINFO_FILENAME);
        $mainImagePath = $uploadDir . basename($result['url']);

        $responsiveResult = ImageHelper::generateResponsiveThumbnails(
            $mainImagePath,
            $uploadDir,
            $baseFilename,
            [200, 300, 400],
            70
        );

        if ($responsiveResult['success']) {
            error_log(sprintf(
                "Generated %d responsive variants for template thumbnail",
                count($responsiveResult['variants'])
            ));
        }

        // Delete old thumbnail only if:
        // 1. We have a valid template ID (editing, not creating)
        // 2. Old URL exists and came from the database
        // 3. Old URL is different from new URL
        // 4. File actually exists
        if ($templateId > 0 && !empty($oldThumbnailUrl) && $oldThumbnailUrl !== $_POST['thumbnail_url']) {
            $oldFilePath = __DIR__ . '/..' . $oldThumbnailUrl;
            if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                @unlink($oldFilePath);
                error_log("Deleted old thumbnail for template {$templateId}: " . $oldFilePath);
            }

            // Also delete old responsive variants
            $oldPathInfo = pathinfo($oldThumbnailUrl);
            $oldBasename = $oldPathInfo['filename'];
            foreach ([200, 300, 400] as $width) {
                $oldVariant = __DIR__ . '/../uploads/templates/' . $oldBasename . '-' . $width . 'w.webp';
                if (file_exists($oldVariant)) {
                    @unlink($oldVariant);
                }
            }
        }

        // Log compression stats for debugging
        if (!empty($result['compression_stats'])) {
            $stats = $result['compression_stats'];
            error_log(sprintf(
                "Thumbnail compressed: %s -> %s (%s reduction, format: %s)",
                number_format($stats['original_size'] / 1024, 1) . 'KB',
                number_format($stats['compressed_size'] / 1024, 1) . 'KB',
                $stats['compression_ratio'],
                $stats['format']
            ));
        }
    } else {
        // Log error but don't fail the entire form submission
        error_log("Thumbnail compression failed: " . $result['error']);
    }
}

// Handle gallery image upload (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
    header('Content-Type: application/json');

    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    $galleryTemplateId = intval($_POST['template_id'] ?? 0);
    if ($galleryTemplateId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
        exit;
    }

    require_once __DIR__ . '/../src/Core/ImageHelper.php';

    $uploadDir = __DIR__ . '/../uploads/templates/gallery/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Process and compress the gallery image
    $result = ImageHelper::processThumbnailUpload(
        $_FILES['gallery_image'],
        $uploadDir,
        'gallery_',
        800,   // Max width for gallery images
        1200,  // Max height
        75     // Quality
    );

    if ($result['success']) {
        $imageUrl = '/uploads/templates/gallery/' . basename($result['url']);

        // Get current max display order
        $maxOrder = Database::fetchOne(
            "SELECT MAX(display_order) as max_order FROM template_images WHERE template_id = ?",
            [$galleryTemplateId]
        );
        $displayOrder = ($maxOrder['max_order'] ?? -1) + 1;

        // Insert into database
        Database::query(
            "INSERT INTO template_images (template_id, image_url, display_order) VALUES (?, ?, ?)",
            [$galleryTemplateId, $imageUrl, $displayOrder]
        );
        $imageId = Database::lastInsertId();

        echo json_encode([
            'success' => true,
            'image_id' => $imageId,
            'image_url' => $imageUrl,
            'display_order' => $displayOrder
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Upload failed']);
    }
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        // Generate slug from title if not provided
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($_POST['title'] ?? '')));
        }
        $slug = trim($slug, '-');

        $data = [
            'title' => Security::sanitizeString($_POST['title'] ?? ''),
            'slug' => $slug,
            'description' => Security::sanitizeString($_POST['description'] ?? ''),
            'category' => $_POST['category'] ?? 'wedding',
            'subcategory' => Security::sanitizeString($_POST['subcategory'] ?? ''),
            'cultural_tradition' => Security::sanitizeString($_POST['cultural_tradition'] ?? ''),
            'price_usd' => floatval($_POST['price_usd'] ?? 0),
            'price_inr' => floatval($_POST['price_inr'] ?? 0),
            'discounted_price_usd' => !empty($_POST['discounted_price_usd']) ? floatval($_POST['discounted_price_usd']) : null,
            'discounted_price_inr' => !empty($_POST['discounted_price_inr']) ? floatval($_POST['discounted_price_inr']) : null,
            'preview_video_url' => Security::sanitizeString($_POST['preview_video_url'] ?? ''),
            'thumbnail_url' => $_POST['thumbnail_url'] ?? ($template['thumbnail_url'] ?? ''),
            'duration_seconds' => intval($_POST['duration_seconds'] ?? 30),
            'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($_POST['form_action'] === 'create') {
            $sql = "INSERT INTO templates (title, slug, description, category, subcategory, cultural_tradition, price_usd, price_inr, discounted_price_usd, discounted_price_inr, preview_video_url, thumbnail_url, duration_seconds, is_premium, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            Database::query($sql, array_values($data));
            $newTemplateId = Database::lastInsertId();

            // Save category mappings for new template
            saveCategoryMappings($newTemplateId, 'template_style_map', 'style_id', $_POST['styles'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_format_map', 'format_id', $_POST['formats'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_religion_map', 'religion_id', $_POST['religions'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_function_map', 'function_id', $_POST['functions'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_party_map', 'party_type_id', $_POST['party_types'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_puja_map', 'puja_id', $_POST['pujas'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_festival_map', 'festival_id', $_POST['festivals'] ?? []);
            saveCategoryMappings($newTemplateId, 'template_language_map', 'language_id', $_POST['languages'] ?? []);

            header('Location: /admin/templates.php?action=edit&id=' . $newTemplateId . '&success=created');
            exit;
        } elseif ($_POST['form_action'] === 'update' && $templateId) {
            $sql = "UPDATE templates SET title=?, slug=?, description=?, category=?, subcategory=?, cultural_tradition=?, price_usd=?, price_inr=?, discounted_price_usd=?, discounted_price_inr=?, preview_video_url=?, thumbnail_url=?, duration_seconds=?, is_premium=?, is_active=? WHERE id=?";
            $params = array_values($data);
            $params[] = $templateId;
            Database::query($sql, $params);

            // Save category mappings for existing template
            saveCategoryMappings($templateId, 'template_style_map', 'style_id', $_POST['styles'] ?? []);
            saveCategoryMappings($templateId, 'template_format_map', 'format_id', $_POST['formats'] ?? []);
            saveCategoryMappings($templateId, 'template_religion_map', 'religion_id', $_POST['religions'] ?? []);
            saveCategoryMappings($templateId, 'template_function_map', 'function_id', $_POST['functions'] ?? []);
            saveCategoryMappings($templateId, 'template_party_map', 'party_type_id', $_POST['party_types'] ?? []);
            saveCategoryMappings($templateId, 'template_puja_map', 'puja_id', $_POST['pujas'] ?? []);
            saveCategoryMappings($templateId, 'template_festival_map', 'festival_id', $_POST['festivals'] ?? []);
            saveCategoryMappings($templateId, 'template_language_map', 'language_id', $_POST['languages'] ?? []);

            header('Location: /admin/templates.php?action=edit&id=' . $templateId . '&success=updated');
            exit;
        }
    }
}

// Handle delete
if ($action === 'delete' && $templateId) {
    Database::query("DELETE FROM templates WHERE id = ?", [$templateId]);
    header('Location: /admin/templates.php?success=deleted');
    exit;
}

// Get templates for list view with pagination and filtering
$templates = [];
$totalTemplates = 0;
$totalPages = 1;
$currentPage = 1;
$perPage = 25;
$filterCategory = '';
$filterStatus = '';
$sortBy = 'created_at';
$sortOrder = 'DESC';

if ($action === 'list') {
    // Pagination parameters
    $perPage = intval($_GET['per_page'] ?? 25);
    $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
    $currentPage = max(1, intval($_GET['page'] ?? 1));
    $offset = ($currentPage - 1) * $perPage;

    // Filter parameters
    $filterCategory = $_GET['category'] ?? '';
    $filterStatus = $_GET['status'] ?? '';

    // Sort parameters
    $sortBy = $_GET['sort'] ?? 'created_at';
    $sortOrder = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $allowedSorts = ['title', 'created_at', 'price_usd', 'purchase_count', 'category'];
    $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';

    // Build WHERE clause
    $where = [];
    $params = [];

    if (!empty($filterCategory)) {
        $where[] = "category = ?";
        $params[] = $filterCategory;
    }

    if ($filterStatus === 'active') {
        $where[] = "is_active = 1";
    } elseif ($filterStatus === 'draft') {
        $where[] = "is_active = 0";
    } elseif ($filterStatus === 'premium') {
        $where[] = "is_premium = 1";
    } elseif ($filterStatus === 'discounted') {
        $where[] = "discounted_price_usd IS NOT NULL AND discounted_price_usd > 0";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM templates $whereClause";
    $countResult = Database::fetchOne($countSql, $params);
    $totalTemplates = $countResult['total'] ?? 0;
    $totalPages = max(1, ceil($totalTemplates / $perPage));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    // Fetch templates with pagination
    $sql = "SELECT * FROM templates $whereClause ORDER BY $sortBy $sortOrder LIMIT $perPage OFFSET $offset";
    $templates = Database::fetchAll($sql, $params);
}

// Get template for edit view
$template = null;
if ($action === 'edit' && $templateId) {
    $template = Database::fetchOne("SELECT * FROM templates WHERE id = ?", [$templateId]);
    if (!$template) {
        header('Location: /admin/templates.php');
        exit;
    }
}

// Get template fields for field editor
$templateFields = [];
$galleryImages = [];
if ($templateId) {
    $templateFields = Database::fetchAll(
        "SELECT * FROM template_fields WHERE template_id = ? ORDER BY display_order",
        [$templateId]
    );
    $galleryImages = Database::fetchAll(
        "SELECT * FROM template_images WHERE template_id = ? ORDER BY display_order",
        [$templateId]
    );
}

$pendingTickets = 0;
$pageTitle = $action === 'new' ? 'New Template' : ($action === 'edit' ? 'Edit Template' : 'Templates');
$categories = Database::fetchAll("SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
$fieldTypes = ['text', 'textarea', 'date', 'time', 'datetime', 'image', 'music', 'color', 'select', 'number'];
$fieldGroups = ['couple_details', 'family_details', 'event_details', 'photos', 'audio', 'other'];

// Fetch field presets for quick field addition
$fieldPresets = Database::fetchAll("SELECT * FROM field_presets WHERE is_active = 1 ORDER BY category, display_order");
$presetsByCategory = [];
foreach ($fieldPresets as $preset) {
    $cat = $preset['category'] ?? 'general';
    $presetsByCategory[$cat][] = $preset;
}

// Fetch all category options for multi-select dropdowns
$allStyles = Database::fetchAll("SELECT id, name, slug FROM template_styles WHERE is_active = 1 ORDER BY display_order");
$allFormats = Database::fetchAll("SELECT id, name, slug FROM template_formats WHERE is_active = 1 ORDER BY display_order");
$allReligions = Database::fetchAll("SELECT id, name, slug FROM template_religions WHERE is_active = 1 ORDER BY display_order");
$allFunctions = Database::fetchAll("SELECT id, name, slug FROM template_functions WHERE is_active = 1 ORDER BY display_order");
$allPartyTypes = Database::fetchAll("SELECT id, name, slug FROM template_party_types WHERE is_active = 1 ORDER BY display_order");
$allPujas = Database::fetchAll("SELECT id, name, slug FROM template_pujas WHERE is_active = 1 ORDER BY display_order");
$allFestivals = Database::fetchAll("SELECT id, name, slug FROM template_festivals WHERE is_active = 1 ORDER BY display_order");
$allLanguages = Database::fetchAll("SELECT id, name, slug, native_name FROM template_languages WHERE is_active = 1 ORDER BY display_order");

// Get template's current category selections (for edit mode)
$templateStyles = [];
$templateFormats = [];
$templateReligions = [];
$templateFunctions = [];
$templatePartyTypes = [];
$templatePujas = [];
$templateFestivals = [];
$templateLanguages = [];

if ($templateId) {
    $templateStyles = array_column(Database::fetchAll("SELECT style_id FROM template_style_map WHERE template_id = ?", [$templateId]), 'style_id');
    $templateFormats = array_column(Database::fetchAll("SELECT format_id FROM template_format_map WHERE template_id = ?", [$templateId]), 'format_id');
    $templateReligions = array_column(Database::fetchAll("SELECT religion_id FROM template_religion_map WHERE template_id = ?", [$templateId]), 'religion_id');
    $templateFunctions = array_column(Database::fetchAll("SELECT function_id FROM template_function_map WHERE template_id = ?", [$templateId]), 'function_id');
    $templatePartyTypes = array_column(Database::fetchAll("SELECT party_type_id FROM template_party_map WHERE template_id = ?", [$templateId]), 'party_type_id');
    $templatePujas = array_column(Database::fetchAll("SELECT puja_id FROM template_puja_map WHERE template_id = ?", [$templateId]), 'puja_id');
    $templateFestivals = array_column(Database::fetchAll("SELECT festival_id FROM template_festival_map WHERE template_id = ?", [$templateId]), 'festival_id');
    $templateLanguages = array_column(Database::fetchAll("SELECT language_id FROM template_language_map WHERE template_id = ?", [$templateId]), 'language_id');
}

// Helper function to save category mappings
function saveCategoryMappings($templateId, $tableName, $columnName, $values)
{
    // Clear existing mappings
    Database::query("DELETE FROM {$tableName} WHERE template_id = ?", [$templateId]);

    // Insert new mappings
    if (!empty($values) && is_array($values)) {
        foreach ($values as $valueId) {
            $valueId = intval($valueId);
            if ($valueId > 0) {
                Database::query("INSERT IGNORE INTO {$tableName} (template_id, {$columnName}) VALUES (?, ?)", [$templateId, $valueId]);
            }
        }
    }
}

// Helper function to get YouTube embed URL
function getYouTubeEmbedUrl($url)
{
    if (empty($url))
        return '';

    $videoId = '';
    if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^?]+)/', $url, $matches)) {
        $videoId = $matches[1];
    }

    return $videoId ? "https://www.youtube.com/embed/{$videoId}" : '';
}
?>

<?php ob_start(); ?>

<?php if ($action === 'list'): ?>

    <!-- List View -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold">Templates</h2>
            <p class="text-slate-500 mt-1">Manage your video invitation templates</p>
        </div>
        <a href="/admin/templates.php?action=new"
            class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm shadow-primary/30 transition-all">
            <span class="material-symbols-outlined text-lg">add</span>
            New Template
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Template <?= $_GET['success'] ?> successfully!
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET"
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <!-- Category Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Category</span>
                <select name="category" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm min-w-[140px]">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['slug'] ?>" <?= $filterCategory === $cat['slug'] ? 'selected' : '' ?>>
                            <?= Security::escape($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Status Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Status</span>
                <select name="status" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm min-w-[130px]">
                    <option value="">All Status</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="premium" <?= $filterStatus === 'premium' ? 'selected' : '' ?>>Premium</option>
                    <option value="discounted" <?= $filterStatus === 'discounted' ? 'selected' : '' ?>>Discounted</option>
                </select>
            </label>

            <!-- Sort By -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Sort By</span>
                <select name="sort" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm min-w-[130px]">
                    <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                    <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                    <option value="price_usd" <?= $sortBy === 'price_usd' ? 'selected' : '' ?>>Price</option>
                    <option value="purchase_count" <?= $sortBy === 'purchase_count' ? 'selected' : '' ?>>Sales</option>
                    <option value="category" <?= $sortBy === 'category' ? 'selected' : '' ?>>Category</option>
                </select>
            </label>

            <!-- Sort Order -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Order</span>
                <select name="order" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm min-w-[100px]">
                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Descending</option>
                    <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                </select>
            </label>

            <!-- Per Page -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Per Page</span>
                <select name="per_page" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm min-w-[80px]">
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
                </select>
            </label>

            <!-- Reset Filters -->
            <?php if ($filterCategory || $filterStatus || $sortBy !== 'created_at' || $sortOrder !== 'DESC'): ?>
                <a href="/admin/templates.php"
                    class="h-10 px-4 flex items-center gap-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined text-base">close</span>
                    Reset
                </a>
            <?php endif; ?>

            <!-- Results Count -->
            <div class="ml-auto text-sm text-slate-500">
                Showing <?= (($currentPage - 1) * $perPage) + 1 ?>-<?= min($currentPage * $perPage, $totalTemplates) ?> of
                <?= number_format($totalTemplates) ?> templates
            </div>
        </div>
    </form>

    <div
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Template</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Price (USD)</th>
                        <th class="px-6 py-4">Price (INR)</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Sales</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($templates as $tpl): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-12 rounded-lg bg-slate-100 bg-cover bg-center shrink-0"
                                        style="background-image: url('<?= Security::escape($tpl['thumbnail_url'] ?? '') ?>');">
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            <?= Security::escape($tpl['title']) ?>
                                        </p>
                                        <p class="text-xs text-slate-500"><?= $tpl['duration_seconds'] ?>s •
                                            <?= Security::escape($tpl['slug']) ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="capitalize"><?= $tpl['category'] ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($tpl['discounted_price_usd']) && $tpl['discounted_price_usd'] < $tpl['price_usd']): ?>
                                    <span
                                        class="text-slate-400 line-through text-xs">$<?= number_format($tpl['price_usd'], 2) ?></span><br>
                                    <span
                                        class="font-semibold text-green-600">$<?= number_format($tpl['discounted_price_usd'], 2) ?></span>
                                <?php else: ?>
                                    <span class="font-semibold">$<?= number_format($tpl['price_usd'], 2) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($tpl['discounted_price_inr']) && $tpl['discounted_price_inr'] < $tpl['price_inr']): ?>
                                    <span
                                        class="text-slate-400 line-through text-xs">₹<?= number_format($tpl['price_inr'], 0) ?></span><br>
                                    <span
                                        class="font-semibold text-green-600">₹<?= number_format($tpl['discounted_price_inr'], 0) ?></span>
                                <?php else: ?>
                                    <span class="font-semibold">₹<?= number_format($tpl['price_inr'], 0) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($tpl['is_active']): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Draft</span>
                                <?php endif; ?>
                                <?php if ($tpl['is_premium']): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ml-1">Premium</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4"><?= number_format($tpl['purchase_count']) ?></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/templates.php?action=edit&id=<?= $tpl['id'] ?>"
                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    <a href="/admin/templates.php?action=delete&id=<?= $tpl['id'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this template?')"
                                        class="p-2 rounded-lg hover:bg-red-50 text-slate-500 hover:text-red-600 transition-colors">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">video_library</span>
                                <p class="text-lg font-medium">No templates found</p>
                                <p class="text-sm">Try adjusting your filters or create a new template</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                <div class="text-sm text-slate-500">
                    Page <?= $currentPage ?> of <?= $totalPages ?>
                </div>
                <div class="flex items-center gap-1">
                    <?php
                    // Build query string for pagination links
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $baseUrl = '/admin/templates.php?' . http_build_query($queryParams);
                    $baseUrl .= empty($queryParams) ? 'page=' : '&page=';
                    ?>

                    <!-- First & Previous -->
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= $baseUrl ?>1" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors"
                            title="First">
                            <span class="material-symbols-outlined text-lg">first_page</span>
                        </a>
                        <a href="<?= $baseUrl ?><?= $currentPage - 1 ?>"
                            class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors" title="Previous">
                            <span class="material-symbols-outlined text-lg">chevron_left</span>
                        </a>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <?php if ($i === $currentPage): ?>
                            <span
                                class="size-9 flex items-center justify-center rounded-lg bg-primary text-white font-semibold text-sm"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $baseUrl ?><?= $i ?>"
                                class="size-9 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-600 text-sm transition-colors"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next & Last -->
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= $baseUrl ?><?= $currentPage + 1 ?>"
                            class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors" title="Next">
                            <span class="material-symbols-outlined text-lg">chevron_right</span>
                        </a>
                        <a href="<?= $baseUrl ?><?= $totalPages ?>"
                            class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors" title="Last">
                            <span class="material-symbols-outlined text-lg">last_page</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>

    <!-- Create/Edit Form -->
    <div class="flex items-center gap-4 mb-6">
        <a href="/admin/templates.php"
            class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold"><?= $action === 'new' ? 'New Template' : 'Edit Template' ?></h2>
            <p class="text-slate-500 mt-1">
                <?= $action === 'new' ? 'Create a new video template' : 'Update template details and fields' ?>
            </p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Template <?= $_GET['success'] ?> successfully!
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?= Security::csrfField() ?>
        <input type="hidden" name="form_action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
        <input type="hidden" name="thumbnail_url" value="<?= Security::escape($template['thumbnail_url'] ?? '') ?>">

        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Basic Info -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Basic Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2 md:col-span-2">
                        <span class="text-sm font-medium">Template Title</span>
                        <input type="text" name="title" required
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            value="<?= Security::escape($template['title'] ?? '') ?>"
                            placeholder="e.g., Floral Elegance Wedding" oninput="generateSlug(this.value)">
                    </label>

                    <label class="flex flex-col gap-2 md:col-span-2">
                        <span class="text-sm font-medium">SEO Slug <span class="text-slate-400 font-normal">(URL-friendly
                                name)</span></span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 text-sm">/templates/</span>
                            <input type="text" name="slug" id="slug-input"
                                class="flex-1 h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                value="<?= Security::escape($template['slug'] ?? '') ?>"
                                placeholder="floral-elegance-wedding">
                        </div>
                    </label>

                    <label class="flex flex-col gap-2 md:col-span-2">
                        <span class="text-sm font-medium">Description</span>
                        <textarea name="description" rows="3"
                            class="px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y"
                            placeholder="Describe this template..."><?= Security::escape($template['description'] ?? '') ?></textarea>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Category</span>
                        <select name="category"
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['slug'] ?>" <?= ($template['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>><?= Security::escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Subcategory</span>
                        <input type="text" name="subcategory"
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                            value="<?= Security::escape($template['subcategory'] ?? '') ?>"
                            placeholder="e.g., haldi, sangeet">
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Cultural Tradition</span>
                        <input type="text" name="cultural_tradition"
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                            value="<?= Security::escape($template['cultural_tradition'] ?? '') ?>"
                            placeholder="e.g., hindu, muslim, christian">
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Duration (seconds)</span>
                        <input type="number" name="duration_seconds" min="10" max="300"
                            class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                            value="<?= $template['duration_seconds'] ?? 30 ?>">
                    </label>
                </div>
            </div>

            <!-- Categories & Tags -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-2">Categories & Tags</h3>
                <p class="text-sm text-slate-500 mb-4">Select all applicable categories for this template. These help users
                    find templates through the mega menu filters.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Style -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-purple-500">style</span> Style
                        </span>
                        <select name="styles[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allStyles as $style): ?>
                                <option value="<?= $style['id'] ?>" <?= in_array($style['id'], $templateStyles) ? 'selected' : '' ?>><?= Security::escape($style['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Format -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-blue-500">video_file</span> Format
                        </span>
                        <select name="formats[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allFormats as $format): ?>
                                <option value="<?= $format['id'] ?>" <?= in_array($format['id'], $templateFormats) ? 'selected' : '' ?>><?= Security::escape($format['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Religion -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-amber-500">temple_hindu</span> Religion
                        </span>
                        <select name="religions[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allReligions as $religion): ?>
                                <option value="<?= $religion['id'] ?>" <?= in_array($religion['id'], $templateReligions) ? 'selected' : '' ?>><?= Security::escape($religion['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Function -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-rose-500">event</span> Function
                        </span>
                        <select name="functions[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allFunctions as $function): ?>
                                <option value="<?= $function['id'] ?>" <?= in_array($function['id'], $templateFunctions) ? 'selected' : '' ?>><?= Security::escape($function['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Party Type -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-teal-500">celebration</span> Party Type
                        </span>
                        <select name="party_types[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allPartyTypes as $party): ?>
                                <option value="<?= $party['id'] ?>" <?= in_array($party['id'], $templatePartyTypes) ? 'selected' : '' ?>><?= Security::escape($party['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Puja & Rituals -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-orange-500">self_improvement</span> Puja &
                            Rituals
                        </span>
                        <select name="pujas[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allPujas as $puja): ?>
                                <option value="<?= $puja['id'] ?>" <?= in_array($puja['id'], $templatePujas) ? 'selected' : '' ?>><?= Security::escape($puja['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Festivals -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-pink-500">festival</span> Festivals
                        </span>
                        <select name="festivals[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allFestivals as $festival): ?>
                                <option value="<?= $festival['id'] ?>" <?= in_array($festival['id'], $templateFestivals) ? 'selected' : '' ?>><?= Security::escape($festival['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <!-- Language -->
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-base text-indigo-500">translate</span> Language
                        </span>
                        <select name="languages[]" multiple
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <?php foreach ($allLanguages as $lang): ?>
                                <option value="<?= $lang['id'] ?>" <?= in_array($lang['id'], $templateLanguages) ? 'selected' : '' ?>><?= Security::escape($lang['name']) ?>
                                    <?= $lang['native_name'] && $lang['native_name'] !== $lang['name'] ? '(' . Security::escape($lang['native_name']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <p class="text-xs text-slate-400 mt-3">Hold Ctrl/Cmd to select multiple options</p>
            </div>

            <!-- Pricing -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Pricing</h3>
                <p class="text-sm text-slate-500 mb-4">Set prices for both payment gateways: Stripe (USD) for international,
                    Razorpay (INR) for India</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <p class="text-sm font-semibold text-blue-600">💳 Stripe (USD - International)</p>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Regular Price (USD)</span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input type="number" name="price_usd" step="0.01" min="0"
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['price_usd'] ?? 0 ?>">
                            </div>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Discounted Price (USD) <span
                                    class="text-slate-400 font-normal">Optional</span></span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input type="number" name="discounted_price_usd" step="0.01" min="0"
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['discounted_price_usd'] ?? '' ?>"
                                    placeholder="Leave empty for no discount">
                            </div>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <p class="text-sm font-semibold text-green-600">🇮🇳 Razorpay (INR - India)</p>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Regular Price (INR)</span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">₹</span>
                                <input type="number" name="price_inr" step="1" min="0"
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['price_inr'] ?? 0 ?>">
                            </div>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Discounted Price (INR) <span
                                    class="text-slate-400 font-normal">Optional</span></span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">₹</span>
                                <input type="number" name="discounted_price_inr" step="1" min="0"
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['discounted_price_inr'] ?? '' ?>"
                                    placeholder="Leave empty for no discount">
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <?php if ($action === 'new'): ?>
                <!-- Template Fields Placeholder for New Template -->
                <div
                    class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 shadow-sm p-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg text-blue-600">
                            <span class="material-symbols-outlined text-2xl">playlist_add</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100">Customization Fields</h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1 mb-4">
                                After saving this template, you can add custom fields like:
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                                    <span class="material-symbols-outlined text-sm">text_fields</span> Text Fields
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                                    <span class="material-symbols-outlined text-sm">calendar_month</span> Date Fields
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                                    <span class="material-symbols-outlined text-sm">image</span> Photo Upload
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                                    <span class="material-symbols-outlined text-sm">music_note</span> Music Upload
                                </span>
                            </div>
                            <p class="text-xs text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">info</span>
                                Click "Create Template" to save first, then add your customization fields.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'edit' && $templateId): ?>
                <!-- Template Fields Editor -->
                <div
                    class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold">Customization Fields</h3>
                            <p class="text-sm text-slate-500">Fields users will fill when ordering this template</p>
                        </div>
                        <button type="button" onclick="openFieldModal()"
                            class="flex items-center gap-1 text-primary text-sm font-bold hover:underline">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add Field
                        </button>
                    </div>

                    <div id="fields-container" class="space-y-3">
                        <?php foreach ($templateFields as $field): ?>
                            <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-white/5 rounded-lg border border-slate-200 dark:border-slate-700"
                                data-field-id="<?= $field['id'] ?>">
                                <span class="material-symbols-outlined text-slate-400 cursor-move">drag_indicator</span>
                                <div class="flex-1 grid grid-cols-4 gap-3">
                                    <div>
                                        <p class="font-medium text-sm"><?= Security::escape($field['field_label']) ?></p>
                                        <p class="text-xs text-slate-400"><?= $field['field_name'] ?></p>
                                    </div>
                                    <span
                                        class="h-8 px-3 flex items-center text-xs font-medium text-slate-600 bg-white rounded border border-slate-200 w-fit"><?= $field['field_type'] ?></span>
                                    <span
                                        class="h-8 px-3 flex items-center text-xs text-slate-500 bg-white rounded border border-slate-200 w-fit"><?= $field['field_group'] ?? '-' ?></span>
                                    <span
                                        class="h-8 px-3 flex items-center text-xs <?= $field['is_required'] ? 'text-green-600 bg-green-50' : 'text-slate-400 bg-slate-100' ?> rounded w-fit">
                                        <?= $field['is_required'] ? 'Required' : 'Optional' ?>
                                    </span>
                                </div>
                                <button type="button" onclick="editField(<?= htmlspecialchars(json_encode($field)) ?>)"
                                    class="p-1 text-slate-400 hover:text-primary">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <button type="button" onclick="deleteField(<?= $field['id'] ?>)"
                                    class="p-1 text-slate-400 hover:text-red-500">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($templateFields)): ?>
                            <p id="no-fields-msg" class="text-slate-500 text-sm text-center py-4">No fields defined yet. Click "Add
                                Field" to create customization fields.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Status -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Status</h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= ($template['is_active'] ?? 1) ? 'checked' : '' ?> class="rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="text-sm font-medium">Active (visible to users)</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_premium" value="1" <?= ($template['is_premium'] ?? 0) ? 'checked' : '' ?> class="rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="text-sm font-medium">Premium Template</span>
                    </label>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                    <button type="submit"
                        class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 px-4 rounded-lg shadow-sm shadow-primary/30 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span>
                        <?= $action === 'new' ? 'Create Template' : 'Save Changes' ?>
                    </button>
                </div>
            </div>

            <!-- Media -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Media</h3>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium block mb-2">Thumbnail Image</label>
                        <div id="thumbnail-preview"
                            class="aspect-[9/16] rounded-lg bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors overflow-hidden"
                            onclick="document.getElementById('thumbnail-input').click()"
                            style="<?= !empty($template['thumbnail_url']) ? "background-image: url('" . Security::escape($template['thumbnail_url']) . "'); background-size: cover; background-position: center;" : '' ?>">
                            <?php if (empty($template['thumbnail_url'])): ?>
                                <div class="text-center">
                                    <span class="material-symbols-outlined text-3xl text-slate-400">cloud_upload</span>
                                    <p class="text-xs text-slate-500 mt-1">Click to upload</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="thumbnail-input" name="thumbnail" accept="image/*" class="hidden"
                            onchange="previewThumbnail(this)">
                    </div>

                    <div>
                        <label class="text-sm font-medium block mb-2">YouTube Preview Video URL</label>
                        <input type="text" name="preview_video_url" id="youtube-url"
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm"
                            value="<?= Security::escape($template['preview_video_url'] ?? '') ?>"
                            placeholder="https://youtube.com/watch?v=..." onchange="updateYouTubePreview()">

                        <!-- YouTube Preview -->
                        <?php $embedUrl = getYouTubeEmbedUrl($template['preview_video_url'] ?? ''); ?>
                        <div id="youtube-preview" class="mt-3 <?= empty($embedUrl) ? 'hidden' : '' ?>">
                            <iframe id="youtube-iframe" src="<?= $embedUrl ?>" class="w-full aspect-video rounded-lg"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($action === 'edit' && $templateId): ?>
                <!-- Gallery Images -->
                <div
                    class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Gallery Images</h3>
                        <button type="button" onclick="document.getElementById('gallery-input').click()"
                            class="flex items-center gap-1 text-primary text-sm font-bold hover:underline">
                            <span class="material-symbols-outlined text-lg">add_photo_alternate</span>
                            Add Image
                        </button>
                    </div>
                    <input type="file" id="gallery-input" accept="image/*" class="hidden" onchange="uploadGalleryImage(this)">

                    <div id="gallery-container" class="grid grid-cols-3 gap-2">
                        <?php foreach ($galleryImages as $img): ?>
                            <div class="relative group aspect-[9/16] rounded-lg overflow-hidden bg-slate-100"
                                data-image-id="<?= $img['id'] ?>">
                                <img src="<?= Security::escape($img['image_url']) ?>" alt="Gallery image"
                                    class="w-full h-full object-cover">
                                <button type="button" onclick="deleteGalleryImage(<?= $img['id'] ?>)"
                                    class="absolute top-1 right-1 size-6 rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($galleryImages)): ?>
                            <div id="no-gallery-msg" class="col-span-3 text-center py-6 text-slate-400">
                                <span class="material-symbols-outlined text-3xl">collections</span>
                                <p class="text-sm mt-1">No gallery images yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-400 mt-3">Add multiple preview images for the template. These will be shown as
                        a gallery on the template detail page.</p>
                </div>

                <!-- Language-Specific Thumbnails -->
                <div
                    class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold">Language Thumbnails</h3>
                            <p class="text-xs text-slate-500">Upload different thumbnails for each language. Users will see
                                these based on their language selection.</p>
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div class="border-b border-slate-200 dark:border-slate-700 mb-4">
                        <div class="flex gap-1 -mb-px overflow-x-auto" id="lang-tabs">
                            <?php
                            $languages = Database::fetchAll("SELECT code, name, native_name FROM languages ORDER BY display_order");
                            foreach ($languages as $idx => $lang):
                                $isFirst = ($idx === 0);
                                ?>
                                <button type="button" data-lang="<?= $lang['code'] ?>"
                                    onclick="switchLangTab('<?= $lang['code'] ?>')"
                                    class="lang-tab px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors <?= $isFirst ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary' ?>">
                                    <?= Security::escape($lang['native_name']) ?>
                                    <span class="text-xs text-slate-400">(<?= $lang['code'] ?>)</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Language Content Panels -->
                    <?php foreach ($languages as $idx => $lang):
                        $langThumbnails = Database::fetchAll(
                            "SELECT * FROM template_thumbnails WHERE template_id = ? AND language_code = ? ORDER BY is_primary DESC, display_order",
                            [$templateId, $lang['code']]
                        );
                        $isFirst = ($idx === 0);
                        ?>
                        <div class="lang-panel <?= $isFirst ? '' : 'hidden' ?>" data-lang="<?= $lang['code'] ?>">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-slate-700"><?= Security::escape($lang['name']) ?>
                                    Thumbnails</span>
                                <button type="button"
                                    onclick="document.getElementById('lang-thumb-input-<?= $lang['code'] ?>').click()"
                                    class="flex items-center gap-1 text-primary text-sm font-bold hover:underline">
                                    <span class="material-symbols-outlined text-lg">add_photo_alternate</span>
                                    Add
                                </button>
                            </div>
                            <input type="file" id="lang-thumb-input-<?= $lang['code'] ?>" accept="image/*" class="hidden"
                                onchange="uploadLangThumbnail(this, '<?= $lang['code'] ?>')">

                            <div class="grid grid-cols-4 gap-2 lang-thumb-grid" data-lang="<?= $lang['code'] ?>">
                                <?php foreach ($langThumbnails as $thumb): ?>
                                    <div class="relative group aspect-[9/16] rounded-lg overflow-hidden bg-slate-100 <?= $thumb['is_primary'] ? 'ring-2 ring-primary' : '' ?>"
                                        data-thumb-id="<?= $thumb['id'] ?>">
                                        <img src="<?= Security::escape($thumb['thumbnail_url']) ?>" alt="Language thumbnail"
                                            class="w-full h-full object-cover">
                                        <?php if ($thumb['is_primary']): ?>
                                            <span
                                                class="absolute top-1 left-1 bg-primary text-white text-xs px-1.5 py-0.5 rounded">Primary</span>
                                        <?php endif; ?>
                                        <div
                                            class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                            <button type="button"
                                                onclick="setLangThumbPrimary(<?= $thumb['id'] ?>, '<?= $lang['code'] ?>')"
                                                class="size-7 rounded-full bg-white text-primary flex items-center justify-center"
                                                title="Set as primary">
                                                <span class="material-symbols-outlined text-sm">star</span>
                                            </button>
                                            <button type="button"
                                                onclick="deleteLangThumbnail(<?= $thumb['id'] ?>, '<?= $lang['code'] ?>')"
                                                class="size-7 rounded-full bg-red-500 text-white flex items-center justify-center"
                                                title="Delete">
                                                <span class="material-symbols-outlined text-sm">close</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (empty($langThumbnails)): ?>
                                    <div class="col-span-4 text-center py-6 text-slate-400 no-thumbs-msg">
                                        <span class="material-symbols-outlined text-2xl">image</span>
                                        <p class="text-xs mt-1">No <?= strtolower($lang['name']) ?> thumbnails yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </form>

    <!-- Field Modal -->
    <div id="field-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-xl w-full max-w-lg">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-bold" id="modal-title">Add Field</h3>
                <button type="button" onclick="closeFieldModal()" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="field-form" class="p-6 space-y-4">
                <?= Security::csrfField() ?>
                <input type="hidden" name="ajax_action" value="add_field">
                <input type="hidden" name="template_id" value="<?= $templateId ?>">
                <input type="hidden" name="field_id" id="field_id" value="">

                <!-- Preset Selector -->
                <div id="preset-selector"
                    class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200 flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">auto_awesome</span>
                            Quick Add from Presets
                        </span>
                        <select id="preset_select" onchange="applyPreset(this.value)"
                            class="h-10 px-3 rounded-lg border border-blue-200 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 text-sm">
                            <option value="">-- Choose a preset to auto-fill --</option>
                            <?php foreach ($presetsByCategory as $category => $presets): ?>
                                <optgroup label="<?= ucfirst(str_replace('_', ' ', $category)) ?>">
                                    <?php foreach ($presets as $preset): ?>
                                        <option value="<?= htmlspecialchars(json_encode($preset), ENT_QUOTES) ?>">
                                            <?= Security::escape($preset['name']) ?> (<?= $preset['field_type'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-xs text-blue-600 dark:text-blue-400">Or fill in the fields manually below</span>
                    </label>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-700 pt-4"></div>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Field Name <span
                            class="text-slate-400 font-normal">(internal)</span></span>
                    <input type="text" name="field_name" id="field_name" required
                        class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50" placeholder="e.g., groom_name">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Field Label <span class="text-slate-400 font-normal">(shown to
                            users)</span></span>
                    <input type="text" name="field_label" id="field_label" required
                        class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50" placeholder="e.g., Groom's Name">
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Field Type</span>
                        <select name="field_type" id="field_type"
                            class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50">
                            <?php foreach ($fieldTypes as $type): ?>
                                <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Field Group</span>
                        <select name="field_group" id="field_group"
                            class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50">
                            <option value="">-- Select --</option>
                            <?php foreach ($fieldGroups as $group): ?>
                                <option value="<?= $group ?>"><?= ucfirst(str_replace('_', ' ', $group)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Placeholder Text</span>
                    <input type="text" name="placeholder" id="placeholder"
                        class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50" placeholder="e.g., Enter name...">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Help Text</span>
                    <input type="text" name="help_text" id="help_text"
                        class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50"
                        placeholder="Additional instructions for users">
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Display Order</span>
                        <input type="number" name="display_order" id="display_order" value="0"
                            class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50">
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer pt-6">
                        <input type="checkbox" name="is_required" id="is_required" value="1" checked
                            class="rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="text-sm font-medium">Required Field</span>
                    </label>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeFieldModal()"
                        class="flex-1 py-2.5 px-4 border border-slate-200 rounded-lg font-medium hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 px-4 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
                        Save Field
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generateSlug(title) {
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            document.getElementById('slug-input').value = slug;
        }

        function previewThumbnail(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById('thumbnail-preview');
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                    preview.innerHTML = '';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updateYouTubePreview() {
            const url = document.getElementById('youtube-url').value;
            const preview = document.getElementById('youtube-preview');
            const iframe = document.getElementById('youtube-iframe');

            let videoId = '';
            const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?]+)/);
            if (match) {
                videoId = match[1];
            }

            if (videoId) {
                iframe.src = `https://www.youtube.com/embed/${videoId}`;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }

        function openFieldModal() {
            document.getElementById('modal-title').textContent = 'Add Field';
            document.getElementById('field-form').reset();
            document.getElementById('field_id').value = '';
            document.querySelector('#field-form input[name="ajax_action"]').value = 'add_field';
            document.getElementById('preset-selector').classList.remove('hidden');
            document.getElementById('preset_select').value = '';
            document.getElementById('field-modal').classList.remove('hidden');
        }

        function applyPreset(presetJson) {
            if (!presetJson) return;

            try {
                const preset = JSON.parse(presetJson);

                // Fill in the form fields with preset values
                document.getElementById('field_name').value = preset.field_name || '';
                document.getElementById('field_label').value = preset.name || '';
                document.getElementById('field_type').value = preset.field_type || 'text';
                document.getElementById('placeholder').value = preset.placeholder || '';
                document.getElementById('help_text').value = preset.help_text || '';

                // Set a reasonable default group based on preset category
                const categoryToGroup = {
                    'wedding': 'couple_details',
                    'wedding_hindu': 'couple_details',
                    'wedding_muslim': 'couple_details',
                    'wedding_punjabi': 'couple_details',
                    'wedding_bihari': 'couple_details',
                    'wedding_bengali': 'couple_details',
                    'wedding_marathi': 'couple_details',
                    'birthday': 'event_details',
                    'baby_shower': 'event_details',
                    'corporate': 'event_details',
                    'anniversary': 'couple_details',
                    'general': 'other'
                };
                const suggestedGroup = categoryToGroup[preset.category] || 'other';
                document.getElementById('field_group').value = suggestedGroup;

                // Visual feedback
                const form = document.getElementById('field-form');
                form.classList.add('ring-2', 'ring-primary/30');
                setTimeout(() => form.classList.remove('ring-2', 'ring-primary/30'), 500);

            } catch (e) {
                console.error('Error parsing preset:', e);
            }
        }

        function closeFieldModal() {
            document.getElementById('field-modal').classList.add('hidden');
        }

        function editField(field) {
            document.getElementById('modal-title').textContent = 'Edit Field';
            document.getElementById('field_id').value = field.id;
            document.getElementById('field_name').value = field.field_name;
            document.getElementById('field_label').value = field.field_label;
            document.getElementById('field_type').value = field.field_type;
            document.getElementById('field_group').value = field.field_group || '';
            document.getElementById('placeholder').value = field.placeholder || '';
            document.getElementById('help_text').value = field.help_text || '';
            document.getElementById('display_order').value = field.display_order || 0;
            document.getElementById('is_required').checked = field.is_required == 1;
            document.querySelector('#field-form input[name="ajax_action"]').value = 'update_field';
            // Hide preset selector when editing existing field
            document.getElementById('preset-selector').classList.add('hidden');
            document.getElementById('field-modal').classList.remove('hidden');
        }

        async function deleteField(fieldId) {
            if (!confirm('Delete this field?')) return;

            const formData = new FormData();
            formData.append('ajax_action', 'delete_field');
            formData.append('field_id', fieldId);
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                document.querySelector(`[data-field-id="${fieldId}"]`).remove();
            }
        }

        document.getElementById('field-form')?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                closeFieldModal();
                window.location.reload();
            } else {
                alert(result.error || 'Error saving field');
            }
        });

        // Gallery Image Functions
        async function uploadGalleryImage(input) {
            if (!input.files || !input.files[0]) return;

            const formData = new FormData();
            formData.append('gallery_image', input.files[0]);
            formData.append('template_id', '<?= $templateId ?>');
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    // Remove "no images" message if present
                    const noMsg = document.getElementById('no-gallery-msg');
                    if (noMsg) noMsg.remove();

                    // Add new image to gallery
                    const container = document.getElementById('gallery-container');
                    const div = document.createElement('div');
                    div.className = 'relative group aspect-[9/16] rounded-lg overflow-hidden bg-slate-100';
                    div.dataset.imageId = result.image_id;
                    div.innerHTML = `
                <img src="${result.image_url}" alt="Gallery image" class="w-full h-full object-cover">
                <button type="button" onclick="deleteGalleryImage(${result.image_id})"
                        class="absolute top-1 right-1 size-6 rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            `;
                    container.appendChild(div);
                } else {
                    alert(result.error || 'Failed to upload image');
                }
            } catch (err) {
                alert('Upload error: ' + err.message);
            }

            // Reset input
            input.value = '';
        }

        async function deleteGalleryImage(imageId) {
            if (!confirm('Delete this gallery image?')) return;

            const formData = new FormData();
            formData.append('ajax_action', 'delete_gallery_image');
            formData.append('image_id', imageId);
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    document.querySelector(`[data-image-id="${imageId}"]`).remove();

                    // Show "no images" message if container is empty
                    const container = document.getElementById('gallery-container');
                    if (container.children.length === 0) {
                        container.innerHTML = `
                    <div id="no-gallery-msg" class="col-span-3 text-center py-6 text-slate-400">
                        <span class="material-symbols-outlined text-3xl">collections</span>
                        <p class="text-sm mt-1">No gallery images yet</p>
                    </div>
                `;
                    }
                }
            } catch (err) {
                alert('Delete error: ' + err.message);
            }
        }
        // Language Thumbnail Tab Switching
        function switchLangTab(langCode) {
            // Update tab styles
            document.querySelectorAll('.lang-tab').forEach(tab => {
                if (tab.dataset.lang === langCode) {
                    tab.classList.add('border-primary', 'text-primary');
                    tab.classList.remove('border-transparent', 'text-slate-500');
                } else {
                    tab.classList.remove('border-primary', 'text-primary');
                    tab.classList.add('border-transparent', 'text-slate-500');
                }
            });

            // Show/hide panels
            document.querySelectorAll('.lang-panel').forEach(panel => {
                if (panel.dataset.lang === langCode) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            });
        }

        // Upload language-specific thumbnail
        async function uploadLangThumbnail(input, langCode) {
            if (!input.files || !input.files[0]) return;

            const formData = new FormData();
            formData.append('ajax_action', 'upload_lang_thumbnail');
            formData.append('lang_thumbnail', input.files[0]);
            formData.append('language_code', langCode);
            formData.append('template_id', '<?= $templateId ?>');
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    const grid = document.querySelector(`.lang-thumb-grid[data-lang="${langCode}"]`);

                    // Remove "no thumbnails" message if present
                    const noMsg = grid.querySelector('.no-thumbs-msg');
                    if (noMsg) noMsg.remove();

                    // Add new thumbnail
                    const div = document.createElement('div');
                    div.className = `relative group aspect-[9/16] rounded-lg overflow-hidden bg-slate-100 ${result.is_primary ? 'ring-2 ring-primary' : ''}`;
                    div.dataset.thumbId = result.thumb_id;
                    div.innerHTML = `
                    <img src="${result.image_url}" alt="Language thumbnail" class="w-full h-full object-cover">
                    ${result.is_primary ? '<span class="absolute top-1 left-1 bg-primary text-white text-xs px-1.5 py-0.5 rounded">Primary</span>' : ''}
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                        <button type="button" onclick="setLangThumbPrimary(${result.thumb_id}, '${langCode}')"
                                class="size-7 rounded-full bg-white text-primary flex items-center justify-center" title="Set as primary">
                            <span class="material-symbols-outlined text-sm">star</span>
                        </button>
                        <button type="button" onclick="deleteLangThumbnail(${result.thumb_id}, '${langCode}')"
                                class="size-7 rounded-full bg-red-500 text-white flex items-center justify-center" title="Delete">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>
                `;
                    grid.appendChild(div);
                } else {
                    alert(result.error || 'Failed to upload thumbnail');
                }
            } catch (err) {
                alert('Upload error: ' + err.message);
            }

            input.value = '';
        }

        // Delete language thumbnail
        async function deleteLangThumbnail(thumbId, langCode) {
            if (!confirm('Delete this thumbnail?')) return;

            const formData = new FormData();
            formData.append('ajax_action', 'delete_lang_thumbnail');
            formData.append('thumb_id', thumbId);
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    const thumb = document.querySelector(`[data-thumb-id="${thumbId}"]`);
                    if (thumb) thumb.remove();

                    // Show "no thumbnails" message if empty
                    const grid = document.querySelector(`.lang-thumb-grid[data-lang="${langCode}"]`);
                    if (grid && grid.children.length === 0) {
                        grid.innerHTML = `
                        <div class="col-span-4 text-center py-6 text-slate-400 no-thumbs-msg">
                            <span class="material-symbols-outlined text-2xl">image</span>
                            <p class="text-xs mt-1">No thumbnails yet</p>
                        </div>
                    `;
                    }
                }
            } catch (err) {
                alert('Delete error: ' + err.message);
            }
        }

        // Set language thumbnail as primary
        async function setLangThumbPrimary(thumbId, langCode) {
            const formData = new FormData();
            formData.append('ajax_action', 'set_lang_thumb_primary');
            formData.append('thumb_id', thumbId);
            formData.append('language_code', langCode);
            formData.append('template_id', '<?= $templateId ?>');
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    const grid = document.querySelector(`.lang-thumb-grid[data-lang="${langCode}"]`);

                    // Remove primary styling from all
                    grid.querySelectorAll('[data-thumb-id]').forEach(el => {
                        el.classList.remove('ring-2', 'ring-primary');
                        const badge = el.querySelector('.bg-primary.text-white.text-xs');
                        if (badge) badge.remove();
                    });

                    // Add primary styling to selected
                    const selectedThumb = grid.querySelector(`[data-thumb-id="${thumbId}"]`);
                    if (selectedThumb) {
                        selectedThumb.classList.add('ring-2', 'ring-primary');
                        const img = selectedThumb.querySelector('img');
                        const badge = document.createElement('span');
                        badge.className = 'absolute top-1 left-1 bg-primary text-white text-xs px-1.5 py-0.5 rounded';
                        badge.textContent = 'Primary';
                        selectedThumb.insertBefore(badge, img.nextSibling);
                    }
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
    </script>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>