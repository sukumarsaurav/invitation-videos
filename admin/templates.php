<?php
/**
 * Admin - Template Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$action = $_GET['action'] ?? 'list';
$templateId = intval($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $data = [
            'title' => Security::sanitizeString($_POST['title'] ?? ''),
            'slug' => strtolower(preg_replace('/[^a-z0-9]+/', '-', $_POST['title'] ?? '')),
            'description' => Security::sanitizeString($_POST['description'] ?? ''),
            'category' => $_POST['category'] ?? 'wedding',
            'subcategory' => Security::sanitizeString($_POST['subcategory'] ?? ''),
            'cultural_tradition' => Security::sanitizeString($_POST['cultural_tradition'] ?? ''),
            'price_usd' => floatval($_POST['price_usd'] ?? 0),
            'price_inr' => floatval($_POST['price_inr'] ?? 0),
            'duration_seconds' => intval($_POST['duration_seconds'] ?? 30),
            'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        if ($_POST['form_action'] === 'create') {
            $sql = "INSERT INTO templates (title, slug, description, category, subcategory, cultural_tradition, price_usd, price_inr, duration_seconds, is_premium, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            Database::query($sql, array_values($data));
            $templateId = Database::lastInsertId();
            header('Location: /admin/templates.php?action=edit&id=' . $templateId . '&success=created');
            exit;
        } elseif ($_POST['form_action'] === 'update' && $templateId) {
            $sql = "UPDATE templates SET title=?, slug=?, description=?, category=?, subcategory=?, cultural_tradition=?, price_usd=?, price_inr=?, duration_seconds=?, is_premium=?, is_active=? WHERE id=?";
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
$categories = ['wedding', 'birthday', 'corporate', 'baby_shower', 'anniversary', 'other'];
$fieldTypes = ['text', 'textarea', 'date', 'time', 'datetime', 'image', 'music', 'color', 'select'];
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
                                <p class="text-xs text-slate-500"><?= $tpl['duration_seconds'] ?>s • <?= $tpl['aspect_ratio'] ?? '9:16' ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="capitalize"><?= $tpl['category'] ?></span>
                    </td>
                    <td class="px-6 py-4 font-semibold">$<?= number_format($tpl['price_usd'], 2) ?></td>
                    <td class="px-6 py-4 font-semibold">₹<?= number_format($tpl['price_inr'], 0) ?></td>
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

<form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?= Security::csrfField() ?>
    <input type="hidden" name="form_action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
    
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
                           placeholder="e.g., Floral Elegance Wedding">
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
                        <option value="<?= $cat ?>" <?= ($template['category'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $cat)) ?></option>
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Price (USD)</span>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                        <input type="number" name="price_usd" step="0.01" min="0"
                               class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                               value="<?= $template['price_usd'] ?? 0 ?>">
                    </div>
                </label>
                
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Price (INR)</span>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">₹</span>
                        <input type="number" name="price_inr" step="1" min="0"
                               class="h-11 pl-8 pr-4 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-primary/20"
                               value="<?= $template['price_inr'] ?? 0 ?>">
                    </div>
                </label>
            </div>
        </div>
        
        <?php if ($action === 'edit' && $templateId): ?>
        <!-- Template Fields Editor -->
        <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Customization Fields</h3>
                <button type="button" onclick="addField()" 
                        class="flex items-center gap-1 text-primary text-sm font-bold hover:underline">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Add Field
                </button>
            </div>
            
            <div id="fields-container" class="space-y-3">
                <?php foreach ($templateFields as $field): ?>
                <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-white/5 rounded-lg border border-slate-200 dark:border-slate-700">
                    <span class="material-symbols-outlined text-slate-400 cursor-move">drag_indicator</span>
                    <div class="flex-1 grid grid-cols-4 gap-3">
                        <input type="text" value="<?= Security::escape($field['field_label']) ?>" readonly 
                               class="h-9 px-3 rounded border border-slate-200 bg-white text-sm">
                        <span class="h-9 px-3 flex items-center text-sm text-slate-500 bg-white rounded border border-slate-200"><?= $field['field_type'] ?></span>
                        <span class="h-9 px-3 flex items-center text-sm text-slate-500 bg-white rounded border border-slate-200"><?= $field['field_group'] ?? '-' ?></span>
                        <span class="h-9 px-3 flex items-center text-xs <?= $field['is_required'] ? 'text-green-600' : 'text-slate-400' ?>">
                            <?= $field['is_required'] ? 'Required' : 'Optional' ?>
                        </span>
                    </div>
                    <button type="button" class="p-1 text-slate-400 hover:text-red-500">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($templateFields)): ?>
                <p class="text-slate-500 text-sm text-center py-4">No fields defined yet. Click "Add Field" to create customization fields.</p>
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
                    <div class="aspect-[9/16] rounded-lg bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors">
                        <div class="text-center">
                            <span class="material-symbols-outlined text-3xl text-slate-400">cloud_upload</span>
                            <p class="text-xs text-slate-500 mt-1">Click to upload</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="text-sm font-medium block mb-2">Preview Video URL</label>
                    <input type="text" name="preview_video_url" 
                           class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm"
                           value="<?= Security::escape($template['preview_video_url'] ?? '') ?>"
                           placeholder="https://...">
                </div>
            </div>
        </div>
        
    </div>
</form>

<?php endif; ?>

<script>
function addField() {
    alert('Field editor modal coming soon! For now, you can add fields directly in the database.');
}
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>
