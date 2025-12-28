<?php
/**
 * Admin - Category Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/auth.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_category') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $icon = trim($_POST['icon'] ?? 'category');
        $color = trim($_POST['color'] ?? '#7f13ec');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
            $slug = trim($slug, '_');
        }

        if ($id > 0) {
            Database::query(
                "UPDATE categories SET name = ?, slug = ?, icon = ?, color = ?, display_order = ?, is_active = ? WHERE id = ?",
                [$name, $slug, $icon, $color, $displayOrder, $isActive, $id]
            );
            header('Location: /admin/categories.php?success=updated');
        } else {
            Database::query(
                "INSERT INTO categories (name, slug, icon, color, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $slug, $icon, $color, $displayOrder, $isActive]
            );
            header('Location: /admin/categories.php?success=created');
        }
        exit;
    }

    if ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        // Check if any templates use this category
        $category = Database::fetchOne("SELECT slug FROM categories WHERE id = ?", [$id]);
        if ($category) {
            $templateCount = Database::fetchOne(
                "SELECT COUNT(*) as c FROM templates WHERE category = ?",
                [$category['slug']]
            )['c'] ?? 0;

            if ($templateCount > 0) {
                header('Location: /admin/categories.php?error=in_use');
                exit;
            }

            Database::query("DELETE FROM categories WHERE id = ?", [$id]);
        }
        header('Location: /admin/categories.php?success=deleted');
        exit;
    }
}

// Get action
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Get category for editing
$category = null;
if ($action === 'edit' && $id > 0) {
    $category = Database::fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
}

// Get all categories with template counts
$categories = Database::fetchAll(
    "SELECT c.*, COUNT(t.id) as template_count 
     FROM categories c 
     LEFT JOIN templates t ON t.category = c.slug
     GROUP BY c.id 
     ORDER BY c.display_order ASC, c.name ASC"
);

$pendingTickets = 0;
$pageTitle = ($action === 'new' || $action === 'edit') ? ($category ? 'Edit Category' : 'New Category') : 'Categories';
?>

<?php ob_start(); ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <!-- Edit/Create Form -->
    <div class="mb-6">
        <a href="/admin/categories.php"
            class="inline-flex items-center gap-2 text-slate-600 hover:text-primary transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Categories
        </a>
    </div>

    <div class="max-w-xl">
        <form method="POST"
            class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?= $category['id'] ?? 0 ?>">

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Category Name *</label>
                <input type="text" name="name" value="<?= Security::escape($category['name'] ?? '') ?>" required
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 text-sm focus:ring-2 focus:ring-primary"
                    placeholder="e.g., Engagement">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Slug (URL-safe)</label>
                <input type="text" name="slug" value="<?= Security::escape($category['slug'] ?? '') ?>"
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 text-sm focus:ring-2 focus:ring-primary"
                    placeholder="engagement (auto-generated if empty)">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Icon (Material Symbol)</label>
                    <input type="text" name="icon" value="<?= Security::escape($category['icon'] ?? 'category') ?>"
                        class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 text-sm focus:ring-2 focus:ring-primary"
                        placeholder="favorite">
                    <p class="text-xs text-slate-500 mt-1">
                        <a href="https://fonts.google.com/icons" target="_blank" class="text-primary hover:underline">Browse
                            icons →</a>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Color</label>
                    <div class="flex gap-2">
                        <input type="color" name="color" value="<?= Security::escape($category['color'] ?? '#7f13ec') ?>"
                            class="w-14 h-11 rounded-lg border border-slate-200 cursor-pointer">
                        <input type="text" value="<?= Security::escape($category['color'] ?? '#7f13ec') ?>" disabled
                            class="flex-1 h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Display Order</label>
                <input type="number" name="display_order" value="<?= intval($category['display_order'] ?? 0) ?>"
                    class="w-full h-11 px-4 rounded-lg border border-slate-200 dark:border-slate-700 text-sm focus:ring-2 focus:ring-primary"
                    placeholder="0">
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" <?= ($category['is_active'] ?? 1) ? 'checked' : '' ?>
                    class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary">
                <label for="is_active" class="text-sm font-medium">Active (visible on website)</label>
            </div>

            <div class="pt-4 border-t flex gap-3">
                <button type="submit"
                    class="flex-1 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                    <?= $category ? 'Update Category' : 'Create Category' ?>
                </button>
                <a href="/admin/categories.php"
                    class="px-6 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- List View -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Template Categories</h2>
            <p class="text-slate-500 mt-1">Manage categories for video invitation templates</p>
        </div>
        <a href="/admin/categories.php?action=new"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined">add</span>
            Add Category
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Category <?= Security::escape($_GET['success']) ?> successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'in_use'): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            Cannot delete category - it has templates associated with it.
        </div>
    <?php endif; ?>

    <div
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Slug</th>
                        <th class="px-6 py-4">Templates</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
                            <td class="px-6 py-4 text-slate-500 font-mono"><?= $cat['display_order'] ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                                        style="background-color: <?= Security::escape($cat['color']) ?>">
                                        <span class="material-symbols-outlined"><?= Security::escape($cat['icon']) ?></span>
                                    </div>
                                    <span class="font-bold"><?= Security::escape($cat['name']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500 font-mono text-xs"><?= Security::escape($cat['slug']) ?></td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 rounded-full text-xs font-medium">
                                    <span class="material-symbols-outlined text-sm">movie</span>
                                    <?= $cat['template_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($cat['is_active']): ?>
                                    <span
                                        class="inline-flex px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Active</span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs font-medium">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="/admin/categories.php?action=edit&id=<?= $cat['id'] ?>"
                                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary"
                                        title="Edit">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    <?php if ($cat['template_count'] == 0): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit"
                                                class="p-2 rounded-lg hover:bg-red-50 text-slate-500 hover:text-red-500"
                                                title="Delete">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="p-2 text-slate-300 cursor-not-allowed" title="Cannot delete - has templates">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">category</span>
                                <p class="text-lg font-medium">No categories yet</p>
                                <a href="/admin/categories.php?action=new"
                                    class="inline-flex items-center gap-2 mt-4 text-primary font-bold hover:underline">
                                    <span class="material-symbols-outlined">add</span>
                                    Add Category
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>