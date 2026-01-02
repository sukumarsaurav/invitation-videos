<?php
/**
 * Admin - Field Presets Management
 * Manage reusable field presets for templates
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$pageTitle = 'Field Presets';
$pendingTickets = 0;

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $postAction = $_POST['action'] ?? '';
        
        if ($postAction === 'create' || $postAction === 'update') {
            $data = [
                'name' => Security::sanitizeString($_POST['name'] ?? ''),
                'field_name' => preg_replace('/[^a-z0-9_]/', '_', strtolower($_POST['field_name'] ?? '')),
                'field_type' => $_POST['field_type'] ?? 'text',
                'placeholder' => Security::sanitizeString($_POST['placeholder'] ?? ''),
                'sample_value' => Security::sanitizeString($_POST['sample_value'] ?? ''),
                'help_text' => Security::sanitizeString($_POST['help_text'] ?? ''),
                'category' => $_POST['category'] ?? 'general',
                'icon' => Security::sanitizeString($_POST['icon'] ?? 'text_fields'),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'] ?? 0)
            ];
            
            if (empty($data['name']) || empty($data['field_name'])) {
                $error = 'Name and Field Name are required';
            } else {
                if ($postAction === 'create') {
                    Database::query(
                        "INSERT INTO field_presets (name, field_name, field_type, placeholder, sample_value, help_text, category, icon, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        array_values($data)
                    );
                    $message = 'Preset created successfully';
                } else {
                    $id = intval($_POST['id'] ?? 0);
                    Database::query(
                        "UPDATE field_presets SET name=?, field_name=?, field_type=?, placeholder=?, sample_value=?, help_text=?, category=?, icon=?, is_active=?, display_order=? WHERE id=?",
                        [...array_values($data), $id]
                    );
                    $message = 'Preset updated successfully';
                }
                header('Location: /admin/field-presets.php?message=' . urlencode($message));
                exit;
            }
        } elseif ($postAction === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            Database::query("DELETE FROM field_presets WHERE id = ?", [$id]);
            header('Location: /admin/field-presets.php?message=' . urlencode('Preset deleted'));
            exit;
        }
    }
}

// Get message from redirect
if (isset($_GET['message'])) {
    $message = Security::escape($_GET['message']);
}

// Fetch presets
$presets = Database::fetchAll("SELECT * FROM field_presets ORDER BY category, display_order");

// Group by category
$presetsByCategory = [];
foreach ($presets as $preset) {
    $cat = $preset['category'] ?? 'general';
    $presetsByCategory[$cat][] = $preset;
}

// Get single preset for edit
$editPreset = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editPreset = Database::fetchOne("SELECT * FROM field_presets WHERE id = ?", [intval($_GET['id'])]);
}

$categories = [
    'wedding', 'wedding_hindu', 'wedding_muslim', 'wedding_punjabi', 'wedding_bihari', 'wedding_bengali', 'wedding_marathi',
    'birthday', 'baby_shower', 'corporate', 'anniversary', 'general'
];
$fieldTypes = ['text', 'textarea', 'date', 'time', 'datetime', 'image', 'music', 'color', 'select', 'number'];
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold">Field Presets</h1>
            <p class="text-slate-500">Reusable form fields for templates</p>
        </div>
        <a href="/admin/field-presets.php?action=new" class="btn-primary">
            <span class="material-symbols-outlined text-lg">add</span>
            Add Preset
        </a>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'new' || $action === 'edit'): ?>
        <!-- Add/Edit Form -->
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-bold mb-4"><?= $editPreset ? 'Edit Preset' : 'Add New Preset' ?></h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="<?= $editPreset ? 'update' : 'create' ?>">
                <?php if ($editPreset): ?>
                    <input type="hidden" name="id" value="<?= $editPreset['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Display Name *</label>
                        <input type="text" name="name" value="<?= Security::escape($editPreset['name'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Field Name *</label>
                        <input type="text" name="field_name" value="<?= Security::escape($editPreset['field_name'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required
                            pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                        <p class="text-xs text-slate-500 mt-1">e.g., groom_name (lowercase, underscores)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Field Type</label>
                        <select name="field_type" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                            <?php foreach ($fieldTypes as $type): ?>
                                <option value="<?= $type ?>" <?= ($editPreset['field_type'] ?? 'text') === $type ? 'selected' : '' ?>>
                                    <?= ucfirst($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($editPreset['category'] ?? 'general') === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $cat)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Placeholder</label>
                        <input type="text" name="placeholder" value="<?= Security::escape($editPreset['placeholder'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Sample Value</label>
                        <input type="text" name="sample_value" value="<?= Security::escape($editPreset['sample_value'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Icon</label>
                        <input type="text" name="icon" value="<?= Security::escape($editPreset['icon'] ?? 'text_fields') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-slate-500 mt-1">Material Symbol icon name</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Display Order</label>
                        <input type="number" name="display_order" value="<?= $editPreset['display_order'] ?? 0 ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Help Text</label>
                    <textarea name="help_text" rows="2" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"><?= Security::escape($editPreset['help_text'] ?? '') ?></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" <?= ($editPreset['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label for="is_active" class="text-sm font-medium">Active</label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">
                        <?= $editPreset ? 'Update Preset' : 'Create Preset' ?>
                    </button>
                    <a href="/admin/field-presets.php" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Presets List by Category -->
        <?php foreach ($presetsByCategory as $category => $categoryPresets): ?>
            <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-bold text-lg capitalize"><?= str_replace('_', ' ', $category) ?></h2>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($categoryPresets as $preset): ?>
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-white/5">
                            <div class="flex items-center gap-4">
                                <span class="material-symbols-outlined text-primary"><?= Security::escape($preset['icon']) ?></span>
                                <div>
                                    <p class="font-medium"><?= Security::escape($preset['name']) ?></p>
                                    <p class="text-sm text-slate-500"><?= $preset['field_type'] ?> • <?= Security::escape($preset['field_name']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if (!$preset['is_active']): ?>
                                    <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded">Inactive</span>
                                <?php endif; ?>
                                <a href="/admin/field-presets.php?action=edit&id=<?= $preset['id'] ?>" 
                                    class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">
                                    <span class="material-symbols-outlined text-slate-500">edit</span>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this preset?')">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $preset['id'] ?>">
                                    <button type="submit" class="p-2 hover:bg-red-50 rounded-lg" title="Delete">
                                        <span class="material-symbols-outlined text-red-500">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($presets)): ?>
            <div class="text-center py-12 text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                <p>No presets yet. <a href="/admin/field-presets.php?action=new" class="text-primary">Create one</a></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: #7f13ec;
    color: white;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.btn-primary:hover { background: #6b0fcc; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>
