<?php
/**
 * Admin - Template Management
 * Full functionality with SEO slugs, pricing, discounts, media, and field editor
 */

require_once __DIR__ . '/auth.php';  // Must be first for authentication
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Load Remotion compositions config for dropdown
$remotionCompositions = [];
$compositionsConfigPath = __DIR__ . '/../config/remotion-compositions.json';
if (file_exists($compositionsConfigPath)) {
    $compositionsJson = file_get_contents($compositionsConfigPath);
    $compositionsData = json_decode($compositionsJson, true);
    $remotionCompositions = $compositionsData['compositions'] ?? [];
}

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

        case 'save_template_fields':
            $templateId = intval($_POST['template_id'] ?? 0);
            $fields = json_decode($_POST['fields'] ?? '[]', true);

            if ($templateId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
                exit;
            }

            // Clear existing field mappings
            Database::query("DELETE FROM template_field_presets WHERE template_id = ?", [$templateId]);

            // Insert new mappings
            if (is_array($fields)) {
                foreach ($fields as $order => $field) {
                    $presetId = intval($field['preset_id'] ?? 0);
                    $stepNumber = intval($field['step_number'] ?? 1);
                    $isRequired = isset($field['is_required']) ? (int) $field['is_required'] : 1;

                    if ($presetId > 0) {
                        Database::query(
                            "INSERT INTO template_field_presets (template_id, preset_id, is_required, display_order, step_number) VALUES (?, ?, ?, ?, ?)",
                            [$templateId, $presetId, $isRequired, $order, $stepNumber]
                        );
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Fields saved successfully']);
            exit;

        case 'get_template_fields':
            $templateId = intval($_POST['template_id'] ?? 0);

            $fields = Database::fetchAll(
                "SELECT tfp.*, fp.name, fp.field_name, fp.field_type, fp.placeholder, fp.icon, fp.help_text, fp.sample_value, fp.category as preset_category
                 FROM template_field_presets tfp
                 JOIN field_presets fp ON tfp.preset_id = fp.id
                 WHERE tfp.template_id = ?
                 ORDER BY tfp.step_number, tfp.display_order",
                [$templateId]
            );

            echo json_encode(['success' => true, 'fields' => $fields]);
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
            'remotion_composition_id' => !empty($_POST['remotion_composition_id']) ? Security::sanitizeString($_POST['remotion_composition_id']) : null,
            'default_music_url' => $_POST['default_music_url'] === 'custom'
                ? (!empty($_POST['default_music_url_custom']) ? Security::sanitizeString($_POST['default_music_url_custom']) : null)
                : (!empty($_POST['default_music_url']) ? Security::sanitizeString($_POST['default_music_url']) : null),
            'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'ai_caricature_enabled' => isset($_POST['ai_caricature_enabled']) ? 1 : 0,
        ];

        if ($_POST['form_action'] === 'create') {
            $sql = "INSERT INTO templates (title, slug, description, category, subcategory, cultural_tradition, price_usd, price_inr, discounted_price_usd, discounted_price_inr, preview_video_url, thumbnail_url, duration_seconds, remotion_composition_id, default_music_url, is_premium, is_active, ai_caricature_enabled)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
            $sql = "UPDATE templates SET title=?, slug=?, description=?, category=?, subcategory=?, cultural_tradition=?, price_usd=?, price_inr=?, discounted_price_usd=?, discounted_price_inr=?, preview_video_url=?, thumbnail_url=?, duration_seconds=?, remotion_composition_id=?, default_music_url=?, is_premium=?, is_active=?, ai_caricature_enabled=? WHERE id=?";
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

// Get gallery images for template
$galleryImages = [];
if ($templateId) {
    $galleryImages = Database::fetchAll(
        "SELECT * FROM template_images WHERE template_id = ? ORDER BY display_order",
        [$templateId]
    );
}

$pendingTickets = 0;
$pageTitle = $action === 'new' ? 'New Template' : ($action === 'edit' ? 'Edit Template' : 'Templates');
$categories = Database::fetchAll("SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC");

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

// Fetch all field presets for the Required Fields editor
$allFieldPresets = Database::fetchAll(
    "SELECT id, name, field_name, field_type, placeholder, icon, help_text, category 
     FROM field_presets 
     WHERE is_active = 1 
     ORDER BY category, display_order"
);

// Group field presets by category for easier display
$fieldPresetsByCategory = [];
foreach ($allFieldPresets as $preset) {
    $cat = $preset['category'] ?? 'general';
    $fieldPresetsByCategory[$cat][] = $preset;
}

// Get template's currently assigned fields (for edit mode)
$templateFields = [];
if ($templateId) {
    $templateFields = Database::fetchAll(
        "SELECT tfp.*, fp.name, fp.field_name, fp.field_type, fp.placeholder, fp.icon, fp.help_text, fp.category as preset_category
         FROM template_field_presets tfp
         JOIN field_presets fp ON tfp.preset_id = fp.id
         WHERE tfp.template_id = ?
         ORDER BY tfp.step_number, tfp.display_order",
        [$templateId]
    );
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
    <form method="GET" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <!-- Category Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Category</span>
                <select name="category" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[140px]">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['slug'] ?>" <?= $filterCategory === $cat['slug'] ? 'selected' : '' ?>>
                            <?= Security::escape($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Status Filter -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Status</span>
                <select name="status" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[130px]">
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
                    class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[130px]">
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
                    class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[100px]">
                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Descending</option>
                    <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                </select>
            </label>

            <!-- Per Page -->
            <label class="flex flex-col gap-1.5">
                <span class="text-xs font-medium text-slate-500">Per Page</span>
                <select name="per_page" onchange="this.form.submit()"
                    class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm min-w-[80px]">
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

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs">
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
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($templates as $tpl): ?>
                        <tr class="hover:bg-slate-50:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-12 rounded-lg bg-slate-100 bg-cover bg-center shrink-0"
                                        style="background-image: url('<?= Security::escape($tpl['thumbnail_url'] ?? '') ?>');">
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">
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
                                        class="p-2 rounded-lg hover:bg-slate-100:bg-white/10 text-slate-500 hover:text-primary transition-colors">
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
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
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
            class="p-2 rounded-lg hover:bg-slate-100:bg-white/10 text-slate-500 transition-colors">
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
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Basic Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2 md:col-span-2">
                        <span class="text-sm font-medium">Template Title</span>
                        <input type="text" name="title" required
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            value="<?= Security::escape($template['title'] ?? '') ?>"
                            placeholder="e.g., Floral Elegance Wedding" oninput="generateSlug(this.value)">
                    </label>

                    <label class="flex flex-col gap-2 md:col-span-2">
                        <span class="text-sm font-medium">SEO Slug <span class="text-slate-400 font-normal">(URL-friendly
                                name)</span></span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 text-sm">/templates/</span>
                            <input type="text" name="slug" id="slug-input"
                                class="flex-1 h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                value="<?= Security::escape($template['slug'] ?? '') ?>"
                                placeholder="floral-elegance-wedding">
                        </div>
                    </label>

                    <label class="flex flex-col gap-2 md:col-span-2">
                        <span class="text-sm font-medium">Description</span>
                        <textarea name="description" rows="3"
                            class="px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y"
                            placeholder="Describe this template..."><?= Security::escape($template['description'] ?? '') ?></textarea>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Category</span>
                        <select name="category"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['slug'] ?>" <?= ($template['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>><?= Security::escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Subcategory</span>
                        <input type="text" name="subcategory"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
                            value="<?= Security::escape($template['subcategory'] ?? '') ?>"
                            placeholder="e.g., haldi, sangeet">
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Cultural Tradition</span>
                        <input type="text" name="cultural_tradition"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
                            value="<?= Security::escape($template['cultural_tradition'] ?? '') ?>"
                            placeholder="e.g., hindu, muslim, christian">
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Duration (seconds)</span>
                        <input type="number" name="duration_seconds" min="10" max="300"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
                            value="<?= $template['duration_seconds'] ?? 30 ?>">
                    </label>
                </div>
            </div>

            <!-- Remotion Integration -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-bold mb-2">🎬 Remotion Integration</h3>
                <p class="text-sm text-slate-500 mb-4">Connect this template to a Remotion video composition for automated
                    rendering.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium flex items-center gap-2">
                            Composition ID
                            <span
                                class="text-xs px-2 py-0.5 rounded bg-purple-100 text-purple-700 font-mono">Important</span>
                        </span>
                        <select name="remotion_composition_id"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 font-mono">
                            <option value="">-- Select Composition --</option>
                            <?php foreach ($remotionCompositions as $comp): ?>
                                <option value="<?= Security::escape($comp['id']) ?>" <?= ($template['remotion_composition_id'] ?? '') === $comp['id'] ? 'selected' : '' ?>
                                    data-description="<?= Security::escape($comp['description'] ?? '') ?>"
                                    data-category="<?= Security::escape($comp['category'] ?? '') ?>">
                                    <?= Security::escape($comp['name']) ?> (<?= Security::escape($comp['id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-500">Select from available Remotion compositions defined in
                            <code class="bg-slate-100 px-1 rounded">config/remotion-compositions.json</code>
                        </p>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium">Default Music <span
                                class="text-slate-400 font-normal">Optional</span></span>
                        <select name="default_music_url"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20">
                            <option value="">-- No default music --</option>
                            <?php
                            $musicTracks = Database::fetchAll("SELECT * FROM music_library WHERE is_active = 1 ORDER BY category, name");
                            $currentCategory = '';
                            foreach ($musicTracks as $track):
                                if ($track['category'] !== $currentCategory):
                                    if ($currentCategory !== '')
                                        echo '</optgroup>';
                                    $currentCategory = $track['category'];
                                    echo '<optgroup label="' . ucfirst($currentCategory) . '">';
                                endif;
                                ?>
                                <option value="<?= Security::escape($track['s3_url']) ?>" <?= ($template['default_music_url'] ?? '') === $track['s3_url'] ? 'selected' : '' ?>>
                                    <?= Security::escape($track['name']) ?> (<?= $track['duration_seconds'] ?>s)
                                </option>
                            <?php endforeach; ?>
                            <?php if ($currentCategory !== '')
                                echo '</optgroup>'; ?>
                            <optgroup label="Custom URL">
                                <option value="custom" <?= !empty($template['default_music_url']) && !in_array($template['default_music_url'], array_column($musicTracks, 's3_url')) ? 'selected' : '' ?>>
                                    Enter custom URL...
                                </option>
                            </optgroup>
                        </select>
                        <input type="text" name="default_music_url_custom" id="music_custom_url"
                            class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 <?= !empty($template['default_music_url']) && !in_array($template['default_music_url'], array_column($musicTracks, 's3_url')) ? '' : 'hidden' ?>"
                            value="<?= !empty($template['default_music_url']) && !in_array($template['default_music_url'] ?? '', array_column($musicTracks, 's3_url')) ? Security::escape($template['default_music_url']) : '' ?>"
                            placeholder="https://example.com/music.mp3">
                        <p class="text-xs text-slate-500">Select from Music Library or enter custom URL</p>
                    </label>
                    <script>
                        document.querySelector('select[name="default_music_url"]').addEventListener('change', function () {
                            const customInput = document.getElementById('music_custom_url');
                            if (this.value === 'custom') {
                                customInput.classList.remove('hidden');
                                customInput.focus();
                            } else {
                                customInput.classList.add('hidden');
                                customInput.value = '';
                            }
                        });
                    </script>
                </div>

                <?php if (!empty($template['remotion_composition_id'])): ?>
                    <?php
                    // Find composition details
                    $currentComp = array_filter($remotionCompositions, fn($c) => $c['id'] === $template['remotion_composition_id']);
                    $currentComp = reset($currentComp);
                    ?>
                    <div class="mt-4 p-3 rounded-lg bg-green-50 border border-green-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-symbols-outlined text-green-600">check_circle</span>
                            <span class="text-sm text-green-700 font-medium">Linked to: <code
                                    class="font-mono font-bold"><?= Security::escape($template['remotion_composition_id']) ?></code></span>
                        </div>
                        <?php if ($currentComp): ?>
                            <p class="text-xs text-green-600 ml-7"><?= Security::escape($currentComp['description'] ?? '') ?></p>
                            <p class="text-xs text-green-500 ml-7 mt-1">
                                Duration: <?= $currentComp['duration'] ?? 10 ?>s •
                                <?= $currentComp['width'] ?? 1080 ?>x<?= $currentComp['height'] ?? 1920 ?> •
                                <?= $currentComp['fps'] ?? 30 ?>fps
                            </p>
                        <?php endif; ?>
                    </div>
                <?php elseif (empty($remotionCompositions)): ?>
                    <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <span class="text-sm text-red-700">No compositions found in <code
                                class="bg-red-100 px-1 rounded">config/remotion-compositions.json</code>. Add compositions to
                            enable the dropdown.</span>
                    </div>
                <?php else: ?>
                    <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600">warning</span>
                        <span class="text-sm text-amber-700">No Remotion composition linked. Videos cannot be auto-rendered
                            until you select a Composition.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Categories & Tags -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
                            class="h-28 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 text-sm">
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
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
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
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['price_usd'] ?? 0 ?>">
                            </div>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Discounted Price (USD) <span
                                    class="text-slate-400 font-normal">Optional</span></span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input type="number" name="discounted_price_usd" step="0.01" min="0"
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
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
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['price_inr'] ?? 0 ?>">
                            </div>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium">Discounted Price (INR) <span
                                    class="text-slate-400 font-normal">Optional</span></span>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">₹</span>
                                <input type="number" name="discounted_price_inr" step="1" min="0"
                                    class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20"
                                    value="<?= $template['discounted_price_inr'] ?? '' ?>"
                                    placeholder="Leave empty for no discount">
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Required Fields for Checkout -->
            <?php if ($action === 'edit' && $templateId): ?>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold">Required Fields</h3>
                            <p class="text-sm text-slate-500">Select which fields customers must fill during checkout</p>
                        </div>
                        <button type="button" onclick="openFieldSelector()"
                            class="flex items-center gap-1.5 bg-primary hover:bg-primary/90 text-white text-sm font-bold py-2 px-4 rounded-lg transition-colors">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add Field
                        </button>
                    </div>

                    <!-- Steps Tabs -->
                    <div class="border-b border-slate-200 mb-4">
                        <div class="flex gap-1 -mb-px" id="step-tabs">
                            <button type="button" data-step="1" onclick="switchStep(1)"
                                class="step-tab px-4 py-2.5 text-sm font-medium border-b-2 border-primary text-primary transition-colors">
                                Step 1: Event Details
                            </button>
                            <button type="button" data-step="2" onclick="switchStep(2)"
                                class="step-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-primary transition-colors">
                                Step 2: Personal Info
                            </button>
                            <button type="button" data-step="3" onclick="switchStep(3)"
                                class="step-tab px-4 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-primary transition-colors">
                                Step 3: Media & Extras
                            </button>
                        </div>
                    </div>

                    <!-- Fields Container for Each Step -->
                    <div id="fields-container">
                        <?php for ($step = 1; $step <= 3; $step++): ?>
                            <div class="step-panel <?= $step > 1 ? 'hidden' : '' ?>" data-step="<?= $step ?>">
                                <div class="space-y-2 sortable-fields" data-step="<?= $step ?>">
                                    <?php
                                    $stepFields = array_filter($templateFields, fn($f) => ($f['step_number'] ?? 1) == $step);
                                    if (empty($stepFields)):
                                        ?>
                                        <div class="no-fields-msg text-center py-8 text-slate-400">
                                            <span class="material-symbols-outlined text-3xl">input</span>
                                            <p class="text-sm mt-2">No fields in Step <?= $step ?></p>
                                            <p class="text-xs">Click "Add Field" to add customization fields</p>
                                        </div>
                                    <?php else:
                                        foreach ($stepFields as $field):
                                            ?>
                                            <div class="field-item flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-move"
                                                data-preset-id="<?= $field['preset_id'] ?>" data-step="<?= $step ?>"
                                                data-required="<?= $field['is_required'] ?>">
                                                <span class="material-symbols-outlined text-slate-400 drag-handle">drag_indicator</span>
                                                <span
                                                    class="material-symbols-outlined text-primary"><?= Security::escape($field['icon'] ?? 'text_fields') ?></span>
                                                <div class="flex-1">
                                                    <p class="font-medium text-sm"><?= Security::escape($field['name']) ?></p>
                                                    <p class="text-xs text-slate-500"><?= $field['field_type'] ?> •
                                                        <?= Security::escape($field['field_name']) ?>
                                                    </p>
                                                </div>
                                                <label class="flex items-center gap-1.5 text-xs">
                                                    <input type="checkbox" class="field-required rounded text-primary"
                                                        <?= $field['is_required'] ? 'checked' : '' ?>>
                                                    <span class="text-slate-500">Required</span>
                                                </label>
                                                <select class="field-step text-xs border-0 bg-transparent text-slate-500 cursor-pointer"
                                                    onchange="moveFieldToStep(this)">
                                                    <option value="1" <?= $step == 1 ? 'selected' : '' ?>>Step 1</option>
                                                    <option value="2" <?= $step == 2 ? 'selected' : '' ?>>Step 2</option>
                                                    <option value="3" <?= $step == 3 ? 'selected' : '' ?>>Step 3</option>
                                                </select>
                                                <button type="button" onclick="removeField(this)"
                                                    class="p-1.5 hover:bg-red-50 rounded text-slate-400 hover:text-red-500 transition-colors">
                                                    <span class="material-symbols-outlined text-lg">close</span>
                                                </button>
                                            </div>
                                            <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Save Fields Button -->
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <button type="button" onclick="saveTemplateFields()"
                            class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-5 rounded-lg transition-colors">
                            <span class="material-symbols-outlined text-lg">save</span>
                            Save Fields
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Status -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
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

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="ai_caricature_enabled" value="1" <?= ($template['ai_caricature_enabled'] ?? 0) ? 'checked' : '' ?>
                            class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="text-sm font-medium">AI Caricature</span>
                            <p class="text-xs text-slate-500">Enable dress selection for AI-generated caricatures</p>
                        </div>
                    </label>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-200">
                    <button type="submit"
                        class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 px-4 rounded-lg shadow-sm shadow-primary/30 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span>
                        <?= $action === 'new' ? 'Create Template' : 'Save Changes' ?>
                    </button>
                </div>
            </div>

            <!-- Media -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
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
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm"
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
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
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
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold">Language Thumbnails</h3>
                            <p class="text-xs text-slate-500">Upload different thumbnails for each language. Users will see
                                these based on their language selection.</p>
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div class="border-b border-slate-200 mb-4">
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

    <!-- Field Selector Modal -->
    <div id="field-selector-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeFieldSelector()"></div>
        <div
            class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[700px] md:max-h-[85vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <div>
                    <h3 class="text-lg font-bold">Add Field Preset</h3>
                    <p class="text-sm text-slate-500">Select a field to add to this template</p>
                </div>
                <button onclick="closeFieldSelector()" class="p-2 hover:bg-slate-100 rounded-lg text-slate-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <?php foreach ($fieldPresetsByCategory as $category => $presets): ?>
                    <div class="mb-6">
                        <h4 class="font-bold text-sm uppercase text-slate-400 mb-3">
                            <?= ucfirst(str_replace('_', ' ', $category)) ?>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <?php foreach ($presets as $preset): ?>
                                <button type="button"
                                    onclick="addFieldPreset(<?= htmlspecialchars(json_encode($preset), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="preset-option flex items-center gap-3 p-3 bg-slate-50 hover:bg-primary/10 hover:border-primary rounded-lg border border-slate-200 text-left transition-all"
                                    data-preset-id="<?= $preset['id'] ?>">
                                    <span
                                        class="material-symbols-outlined text-primary"><?= Security::escape($preset['icon'] ?? 'text_fields') ?></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate"><?= Security::escape($preset['name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= $preset['field_type'] ?></p>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($fieldPresetsByCategory)): ?>
                    <div class="text-center py-12 text-slate-400">
                        <span class="material-symbols-outlined text-4xl">inventory_2</span>
                        <p class="mt-2">No field presets available</p>
                        <a href="/admin/field-presets.php?action=new" class="text-primary font-bold mt-2 inline-block">Create
                            Field Presets →</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                <p class="text-xs text-slate-500">
                    <span class="material-symbols-outlined text-sm align-middle">info</span>
                    Click to add fields. Manage presets in <a href="/admin/field-presets.php"
                        class="text-primary font-bold">Field Presets</a>
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript for Required Fields Management -->
    <script>
        let currentStep = 1;

        function openFieldSelector() {
            document.getElementById('field-selector-modal').classList.remove('hidden');
            updatePresetAvailability();
        }

        function closeFieldSelector() {
            document.getElementById('field-selector-modal').classList.add('hidden');
        }

        function updatePresetAvailability() {
            // Get all currently added preset IDs
            const addedIds = new Set();
            document.querySelectorAll('.field-item').forEach(item => {
                addedIds.add(item.dataset.presetId);
            });

            // Disable/enable preset options based on whether they're already added
            document.querySelectorAll('.preset-option').forEach(option => {
                const presetId = option.dataset.presetId;
                if (addedIds.has(presetId)) {
                    option.classList.add('opacity-50', 'pointer-events-none');
                    option.querySelector('p.font-medium').innerHTML += ' <span class="text-xs text-green-600">(Added)</span>';
                }
            });
        }

        function switchStep(step) {
            currentStep = step;

            // Update tab styles
            document.querySelectorAll('.step-tab').forEach(tab => {
                if (parseInt(tab.dataset.step) === step) {
                    tab.classList.add('border-primary', 'text-primary');
                    tab.classList.remove('border-transparent', 'text-slate-500');
                } else {
                    tab.classList.remove('border-primary', 'text-primary');
                    tab.classList.add('border-transparent', 'text-slate-500');
                }
            });

            // Show/hide panels
            document.querySelectorAll('.step-panel').forEach(panel => {
                if (parseInt(panel.dataset.step) === step) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            });
        }

        function addFieldPreset(preset) {
            const container = document.querySelector(`.sortable-fields[data-step="${currentStep}"]`);

            // Remove "no fields" message if present
            const noFieldsMsg = container.querySelector('.no-fields-msg');
            if (noFieldsMsg) noFieldsMsg.remove();

            // Check if already added
            if (document.querySelector(`.field-item[data-preset-id="${preset.id}"]`)) {
                alert('This field is already added to the template');
                return;
            }

            // Create field item HTML
            const fieldHtml = `
                    <div class="field-item flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 cursor-move"
                        data-preset-id="${preset.id}"
                        data-step="${currentStep}"
                        data-required="1">
                        <span class="material-symbols-outlined text-slate-400 drag-handle">drag_indicator</span>
                        <span class="material-symbols-outlined text-primary">${preset.icon || 'text_fields'}</span>
                        <div class="flex-1">
                            <p class="font-medium text-sm">${preset.name}</p>
                            <p class="text-xs text-slate-500">${preset.field_type} • ${preset.field_name}</p>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs">
                            <input type="checkbox" class="field-required rounded text-primary" checked>
                            <span class="text-slate-500">Required</span>
                        </label>
                        <select class="field-step text-xs border-0 bg-transparent text-slate-500 cursor-pointer" onchange="moveFieldToStep(this)">
                            <option value="1" ${currentStep == 1 ? 'selected' : ''}>Step 1</option>
                            <option value="2" ${currentStep == 2 ? 'selected' : ''}>Step 2</option>
                            <option value="3" ${currentStep == 3 ? 'selected' : ''}>Step 3</option>
                        </select>
                        <button type="button" onclick="removeField(this)" class="p-1.5 hover:bg-red-50 rounded text-slate-400 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                `;

            container.insertAdjacentHTML('beforeend', fieldHtml);
            closeFieldSelector();
        }

        function removeField(button) {
            const fieldItem = button.closest('.field-item');
            const container = fieldItem.parentElement;
            fieldItem.remove();

            // Show "no fields" message if container is empty
            if (container.querySelectorAll('.field-item').length === 0) {
                const step = container.dataset.step;
                container.innerHTML = `
                        <div class="no-fields-msg text-center py-8 text-slate-400">
                            <span class="material-symbols-outlined text-3xl">input</span>
                            <p class="text-sm mt-2">No fields in Step ${step}</p>
                            <p class="text-xs">Click "Add Field" to add customization fields</p>
                        </div>
                    `;
            }
        }

        function moveFieldToStep(select) {
            const fieldItem = select.closest('.field-item');
            const newStep = parseInt(select.value);
            const oldStep = parseInt(fieldItem.dataset.step);

            if (newStep === oldStep) return;

            // Get target container
            const targetContainer = document.querySelector(`.sortable-fields[data-step="${newStep}"]`);
            const sourceContainer = fieldItem.parentElement;

            // Remove "no fields" message from target if present
            const noFieldsMsg = targetContainer.querySelector('.no-fields-msg');
            if (noFieldsMsg) noFieldsMsg.remove();

            // Update field data and move it
            fieldItem.dataset.step = newStep;
            targetContainer.appendChild(fieldItem);

            // Check if source container is now empty
            if (sourceContainer.querySelectorAll('.field-item').length === 0) {
                sourceContainer.innerHTML = `
                        <div class="no-fields-msg text-center py-8 text-slate-400">
                            <span class="material-symbols-outlined text-3xl">input</span>
                            <p class="text-sm mt-2">No fields in Step ${oldStep}</p>
                            <p class="text-xs">Click "Add Field" to add customization fields</p>
                        </div>
                    `;
            }
        }

        async function saveTemplateFields() {
            const fields = [];

            // Collect all fields from all steps
            [1, 2, 3].forEach(step => {
                const container = document.querySelector(`.sortable-fields[data-step="${step}"]`);
                container.querySelectorAll('.field-item').forEach((item, order) => {
                    fields.push({
                        preset_id: item.dataset.presetId,
                        step_number: step,
                        is_required: item.querySelector('.field-required').checked ? 1 : 0
                    });
                });
            });

            const formData = new FormData();
            formData.append('ajax_action', 'save_template_fields');
            formData.append('template_id', '<?= $templateId ?>');
            formData.append('fields', JSON.stringify(fields));
            formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= Security::generateCSRFToken() ?>');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    // Show success message
                    const btn = document.querySelector('button[onclick="saveTemplateFields()"]');
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="material-symbols-outlined text-lg">check</span> Saved!';
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-green-700');
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.add('bg-green-600');
                        btn.classList.remove('bg-green-700');
                    }, 2000);
                } else {
                    alert(result.error || 'Failed to save fields');
                }
            } catch (err) {
                alert('Error saving fields: ' + err.message);
            }
        }
    </script>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>