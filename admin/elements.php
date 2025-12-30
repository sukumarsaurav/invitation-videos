<?php
/**
 * Admin - Design Elements Management
 * Manage elements (shapes, frames, graphics) for template builder
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$pageTitle = 'Design Elements';
$pendingTickets = 0;

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

$categories = ['shapes', 'frames', 'graphics', 'lines', 'stickers'];
$fileTypes = ['png', 'svg', 'jpg', 'gif'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $postAction = $_POST['action'] ?? '';
        
        if ($postAction === 'create' || $postAction === 'update') {
            $data = [
                'name' => Security::sanitizeString($_POST['name'] ?? ''),
                'category' => $_POST['category'] ?? 'graphics',
                'file_type' => $_POST['file_type'] ?? 'png',
                'width' => intval($_POST['width'] ?? 100),
                'height' => intval($_POST['height'] ?? 100),
                'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'] ?? 0)
            ];
            
            // Handle file upload
            $file_path = $_POST['existing_file_path'] ?? '';
            if (!empty($_FILES['element_file']['name'])) {
                $uploadDir = __DIR__ . '/../assets/elements/' . $data['category'] . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['element_file']['name'], PATHINFO_EXTENSION));
                $filename = 'element_' . time() . '_' . uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['element_file']['tmp_name'], $targetPath)) {
                    $file_path = '/assets/elements/' . $data['category'] . '/' . $filename;
                    $data['file_type'] = $ext;
                } else {
                    $error = 'Failed to upload file';
                }
            }
            $data['file_path'] = $file_path;
            
            if (empty($data['name']) || empty($data['file_path'])) {
                $error = 'Name and file are required';
            } elseif (!$error) {
                if ($postAction === 'create') {
                    Database::query(
                        "INSERT INTO design_elements (name, category, file_type, file_path, width, height, is_premium, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$data['name'], $data['category'], $data['file_type'], $data['file_path'], $data['width'], $data['height'], $data['is_premium'], $data['is_active'], $data['display_order']]
                    );
                    $message = 'Element created successfully';
                } else {
                    $id = intval($_POST['id'] ?? 0);
                    Database::query(
                        "UPDATE design_elements SET name=?, category=?, file_type=?, file_path=?, width=?, height=?, is_premium=?, is_active=?, display_order=? WHERE id=?",
                        [$data['name'], $data['category'], $data['file_type'], $data['file_path'], $data['width'], $data['height'], $data['is_premium'], $data['is_active'], $data['display_order'], $id]
                    );
                    $message = 'Element updated successfully';
                }
                header('Location: /admin/elements.php?message=' . urlencode($message));
                exit;
            }
        } elseif ($postAction === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            Database::query("DELETE FROM design_elements WHERE id = ?", [$id]);
            header('Location: /admin/elements.php?message=' . urlencode('Element deleted'));
            exit;
        }
    }
}

// Get message from redirect
if (isset($_GET['message'])) {
    $message = Security::escape($_GET['message']);
}

// Filter by category
$filterCategory = $_GET['category'] ?? '';
$whereClause = '';
$params = [];
if ($filterCategory && in_array($filterCategory, $categories)) {
    $whereClause = "WHERE category = ?";
    $params = [$filterCategory];
}

// Fetch elements
$elements = Database::fetchAll("SELECT * FROM design_elements $whereClause ORDER BY category, display_order", $params);

// Group by category
$elementsByCategory = [];
foreach ($elements as $element) {
    $cat = $element['category'] ?? 'graphics';
    $elementsByCategory[$cat][] = $element;
}

// Get single element for edit
$editElement = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editElement = Database::fetchOne("SELECT * FROM design_elements WHERE id = ?", [intval($_GET['id'])]);
}
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold">Design Elements</h1>
            <p class="text-slate-500">Shapes, frames, graphics for template builder</p>
        </div>
        <a href="/admin/elements.php?action=new" class="btn-primary">
            <span class="material-symbols-outlined text-lg">add</span>
            Add Element
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

    <!-- Category Filter -->
    <div class="flex gap-2 flex-wrap">
        <a href="/admin/elements.php" class="px-3 py-1.5 rounded-full text-sm <?= !$filterCategory ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200' ?>">
            All
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="/admin/elements.php?category=<?= $cat ?>" 
               class="px-3 py-1.5 rounded-full text-sm capitalize <?= $filterCategory === $cat ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200' ?>">
                <?= $cat ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($action === 'new' || $action === 'edit'): ?>
        <!-- Add/Edit Form -->
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-bold mb-4"><?= $editElement ? 'Edit Element' : 'Add New Element' ?></h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="<?= $editElement ? 'update' : 'create' ?>">
                <?php if ($editElement): ?>
                    <input type="hidden" name="id" value="<?= $editElement['id'] ?>">
                    <input type="hidden" name="existing_file_path" value="<?= Security::escape($editElement['file_path']) ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name *</label>
                        <input type="text" name="name" value="<?= Security::escape($editElement['name'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($editElement['category'] ?? 'graphics') === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Element File (PNG/SVG) *</label>
                        <input type="file" name="element_file" accept=".png,.svg,.jpg,.gif" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" <?= $editElement ? '' : 'required' ?>>
                        <?php if ($editElement && $editElement['file_path']): ?>
                            <p class="text-xs text-slate-500 mt-1">Current: <?= Security::escape($editElement['file_path']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Width (px)</label>
                            <input type="number" name="width" value="<?= $editElement['width'] ?? 100 ?>" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" min="10">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Height (px)</label>
                            <input type="number" name="height" value="<?= $editElement['height'] ?? 100 ?>" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" min="10">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Display Order</label>
                        <input type="number" name="display_order" value="<?= $editElement['display_order'] ?? 0 ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="is_active" <?= ($editElement['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label for="is_active" class="text-sm font-medium">Active</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_premium" id="is_premium" <?= ($editElement['is_premium'] ?? 0) ? 'checked' : '' ?>>
                        <label for="is_premium" class="text-sm font-medium">Premium</label>
                    </div>
                </div>

                <!-- Preview -->
                <?php if ($editElement && $editElement['file_path']): ?>
                    <div class="p-4 bg-slate-50 rounded-lg">
                        <p class="text-sm font-medium mb-2">Preview:</p>
                        <img src="<?= Security::escape($editElement['file_path']) ?>" alt="Preview" 
                            style="max-width: 150px; max-height: 150px;" class="rounded border bg-white p-2">
                    </div>
                <?php endif; ?>

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">
                        <?= $editElement ? 'Update Element' : 'Create Element' ?>
                    </button>
                    <a href="/admin/elements.php" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Elements Grid by Category -->
        <?php foreach ($elementsByCategory as $category => $categoryElements): ?>
            <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <h2 class="font-bold text-lg capitalize"><?= $category ?></h2>
                    <span class="text-sm text-slate-500"><?= count($categoryElements) ?> items</span>
                </div>
                <div class="p-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                    <?php foreach ($categoryElements as $element): ?>
                        <div class="group relative bg-slate-50 rounded-lg p-3 text-center border hover:border-primary transition-colors">
                            <div class="w-full aspect-square flex items-center justify-center mb-2 bg-white rounded">
                                <img src="<?= Security::escape($element['file_path']) ?>" alt="<?= Security::escape($element['name']) ?>" 
                                    class="max-w-full max-h-full object-contain" style="max-height: 60px;">
                            </div>
                            <p class="text-xs font-medium truncate"><?= Security::escape($element['name']) ?></p>
                            
                            <?php if ($element['is_premium']): ?>
                                <span class="absolute top-1 right-1 text-yellow-500">
                                    <span class="material-symbols-outlined text-sm" style="font-size: 14px;">star</span>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!$element['is_active']): ?>
                                <span class="absolute top-1 left-1 text-xs bg-slate-200 px-1 rounded">Off</span>
                            <?php endif; ?>
                            
                            <!-- Hover Actions -->
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                                <a href="/admin/elements.php?action=edit&id=<?= $element['id'] ?>" 
                                    class="p-2 bg-white rounded-full hover:bg-slate-100" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this element?')">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $element['id'] ?>">
                                    <button type="submit" class="p-2 bg-white rounded-full hover:bg-red-50" title="Delete">
                                        <span class="material-symbols-outlined text-sm text-red-500">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($elements)): ?>
            <div class="text-center py-12 text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2">shapes</span>
                <p>No elements yet. <a href="/admin/elements.php?action=new" class="text-primary">Add one</a></p>
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
.bg-primary { background: #7f13ec; }
.text-primary { color: #7f13ec; }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>
