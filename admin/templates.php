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
            $templateId = Database::lastInsertId();
            header('Location: /admin/templates.php?action=edit&id=' . $templateId . '&success=created');
            exit;
        } elseif ($_POST['form_action'] === 'update' && $templateId) {
            $sql = "UPDATE templates SET title=?, slug=?, description=?, category=?, subcategory=?, cultural_tradition=?, price_usd=?, price_inr=?, discounted_price_usd=?, discounted_price_inr=?, preview_video_url=?, thumbnail_url=?, duration_seconds=?, is_premium=?, is_active=? WHERE id=?";
            $params = array_values($data);
            $params[] = $templateId;
            Database::query($sql, $params);
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

// Get templates for list view
$templates = [];
if ($action === 'list') {
    $templates = Database::fetchAll("SELECT * FROM templates ORDER BY created_at DESC");
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
if ($templateId) {
    $templateFields = Database::fetchAll(
        "SELECT * FROM template_fields WHERE template_id = ? ORDER BY display_order",
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

// Helper function to get YouTube embed URL
function getYouTubeEmbedUrl($url) {
    if (empty($url)) return '';
    
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

<div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
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
                                 style="background-image: url('<?= Security::escape($tpl['thumbnail_url'] ?? '') ?>');"></div>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white"><?= Security::escape($tpl['title']) ?></p>
                                <p class="text-xs text-slate-500"><?= $tpl['duration_seconds'] ?>s • <?= Security::escape($tpl['slug']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="capitalize"><?= $tpl['category'] ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if (!empty($tpl['discounted_price_usd']) && $tpl['discounted_price_usd'] < $tpl['price_usd']): ?>
                            <span class="text-slate-400 line-through text-xs">$<?= number_format($tpl['price_usd'], 2) ?></span><br>
                            <span class="font-semibold text-green-600">$<?= number_format($tpl['discounted_price_usd'], 2) ?></span>
                        <?php else: ?>
                            <span class="font-semibold">$<?= number_format($tpl['price_usd'], 2) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if (!empty($tpl['discounted_price_inr']) && $tpl['discounted_price_inr'] < $tpl['price_inr']): ?>
                            <span class="text-slate-400 line-through text-xs">₹<?= number_format($tpl['price_inr'], 0) ?></span><br>
                            <span class="font-semibold text-green-600">₹<?= number_format($tpl['discounted_price_inr'], 0) ?></span>
                        <?php else: ?>
                            <span class="font-semibold">₹<?= number_format($tpl['price_inr'], 0) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($tpl['is_active']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Draft</span>
                        <?php endif; ?>
                        <?php if ($tpl['is_premium']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ml-1">Premium</span>
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
                        <p class="text-lg font-medium">No templates yet</p>
                        <p class="text-sm">Create your first template to get started</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>

<!-- Create/Edit Form -->
<div class="flex items-center gap-4 mb-6">
    <a href="/admin/templates.php" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 transition-colors">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
        <h2 class="text-2xl font-bold"><?= $action === 'new' ? 'New Template' : 'Edit Template' ?></h2>
        <p class="text-slate-500 mt-1"><?= $action === 'new' ? 'Create a new video template' : 'Update template details and fields' ?></p>
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
        <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-lg font-bold mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex flex-col gap-2 md:col-span-2">
                    <span class="text-sm font-medium">Template Title</span>
                    <input type="text" name="title" required
                           class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           value="<?= Security::escape($template['title'] ?? '') ?>"
                           placeholder="e.g., Floral Elegance Wedding"
                           oninput="generateSlug(this.value)">
                </label>
                
                <label class="flex flex-col gap-2 md:col-span-2">
                    <span class="text-sm font-medium">SEO Slug <span class="text-slate-400 font-normal">(URL-friendly name)</span></span>
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
                    <select name="category" class="h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20">
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
        
        <!-- Pricing -->
        <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-lg font-bold mb-4">Pricing</h3>
            <p class="text-sm text-slate-500 mb-4">Set prices for both payment gateways: Stripe (USD) for international, Razorpay (INR) for India</p>
            
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
                        <span class="text-sm font-medium">Discounted Price (USD) <span class="text-slate-400 font-normal">Optional</span></span>
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
                        <span class="text-sm font-medium">Discounted Price (INR) <span class="text-slate-400 font-normal">Optional</span></span>
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
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 shadow-sm p-6">
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
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                            <span class="material-symbols-outlined text-sm">text_fields</span> Text Fields
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                            <span class="material-symbols-outlined text-sm">calendar_month</span> Date Fields
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
                            <span class="material-symbols-outlined text-sm">image</span> Photo Upload
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-white/60 dark:bg-white/10 rounded-full text-xs font-medium text-blue-800 dark:text-blue-200">
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
        <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
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
                <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-white/5 rounded-lg border border-slate-200 dark:border-slate-700" data-field-id="<?= $field['id'] ?>">
                    <span class="material-symbols-outlined text-slate-400 cursor-move">drag_indicator</span>
                    <div class="flex-1 grid grid-cols-4 gap-3">
                        <div>
                            <p class="font-medium text-sm"><?= Security::escape($field['field_label']) ?></p>
                            <p class="text-xs text-slate-400"><?= $field['field_name'] ?></p>
                        </div>
                        <span class="h-8 px-3 flex items-center text-xs font-medium text-slate-600 bg-white rounded border border-slate-200 w-fit"><?= $field['field_type'] ?></span>
                        <span class="h-8 px-3 flex items-center text-xs text-slate-500 bg-white rounded border border-slate-200 w-fit"><?= $field['field_group'] ?? '-' ?></span>
                        <span class="h-8 px-3 flex items-center text-xs <?= $field['is_required'] ? 'text-green-600 bg-green-50' : 'text-slate-400 bg-slate-100' ?> rounded w-fit">
                            <?= $field['is_required'] ? 'Required' : 'Optional' ?>
                        </span>
                    </div>
                    <button type="button" onclick="editField(<?= htmlspecialchars(json_encode($field)) ?>)" class="p-1 text-slate-400 hover:text-primary">
                        <span class="material-symbols-outlined text-lg">edit</span>
                    </button>
                    <button type="button" onclick="deleteField(<?= $field['id'] ?>)" class="p-1 text-slate-400 hover:text-red-500">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($templateFields)): ?>
                <p id="no-fields-msg" class="text-slate-500 text-sm text-center py-4">No fields defined yet. Click "Add Field" to create customization fields.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        
        <!-- Status -->
        <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-lg font-bold mb-4">Status</h3>
            
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" 
                           <?= ($template['is_active'] ?? 1) ? 'checked' : '' ?>
                           class="rounded border-slate-300 text-primary focus:ring-primary">
                    <span class="text-sm font-medium">Active (visible to users)</span>
                </label>
                
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_premium" value="1"
                           <?= ($template['is_premium'] ?? 0) ? 'checked' : '' ?>
                           class="rounded border-slate-300 text-primary focus:ring-primary">
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
        <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-lg font-bold mb-4">Media</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium block mb-2">Thumbnail Image</label>
                    <div id="thumbnail-preview" class="aspect-[9/16] rounded-lg bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors overflow-hidden"
                         onclick="document.getElementById('thumbnail-input').click()"
                         style="<?= !empty($template['thumbnail_url']) ? "background-image: url('" . Security::escape($template['thumbnail_url']) . "'); background-size: cover; background-position: center;" : '' ?>">
                        <?php if (empty($template['thumbnail_url'])): ?>
                        <div class="text-center">
                            <span class="material-symbols-outlined text-3xl text-slate-400">cloud_upload</span>
                            <p class="text-xs text-slate-500 mt-1">Click to upload</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="thumbnail-input" name="thumbnail" accept="image/*" class="hidden" onchange="previewThumbnail(this)">
                </div>
                
                <div>
                    <label class="text-sm font-medium block mb-2">YouTube Preview Video URL</label>
                    <input type="text" name="preview_video_url" id="youtube-url"
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm"
                           value="<?= Security::escape($template['preview_video_url'] ?? '') ?>"
                           placeholder="https://youtube.com/watch?v=..."
                           onchange="updateYouTubePreview()">
                    
                    <!-- YouTube Preview -->
                    <?php $embedUrl = getYouTubeEmbedUrl($template['preview_video_url'] ?? ''); ?>
                    <div id="youtube-preview" class="mt-3 <?= empty($embedUrl) ? 'hidden' : '' ?>">
                        <iframe id="youtube-iframe" 
                                src="<?= $embedUrl ?>" 
                                class="w-full aspect-video rounded-lg"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
        
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
            <div id="preset-selector" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
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
                <span class="text-sm font-medium">Field Name <span class="text-slate-400 font-normal">(internal)</span></span>
                <input type="text" name="field_name" id="field_name" required
                       class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50"
                       placeholder="e.g., groom_name">
            </label>
            
            <label class="flex flex-col gap-2">
                <span class="text-sm font-medium">Field Label <span class="text-slate-400 font-normal">(shown to users)</span></span>
                <input type="text" name="field_label" id="field_label" required
                       class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50"
                       placeholder="e.g., Groom's Name">
            </label>
            
            <div class="grid grid-cols-2 gap-4">
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Field Type</span>
                    <select name="field_type" id="field_type" class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50">
                        <?php foreach ($fieldTypes as $type): ?>
                        <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Field Group</span>
                    <select name="field_group" id="field_group" class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50">
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
                       class="h-10 px-3 rounded-lg border border-slate-200 bg-slate-50"
                       placeholder="e.g., Enter name...">
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
                <button type="button" onclick="closeFieldModal()" class="flex-1 py-2.5 px-4 border border-slate-200 rounded-lg font-medium hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
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
        reader.onload = function(e) {
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

document.getElementById('field-form')?.addEventListener('submit', async function(e) {
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
</script>

<?php endif; ?>

<?php 
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>
