<?php
/**
 * Admin - Fonts Management
 * Manage custom fonts for template builder
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$pageTitle = 'Fonts';
$pendingTickets = 0;

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

$categories = ['sans-serif', 'serif', 'display', 'handwriting', 'monospace'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $postAction = $_POST['action'] ?? '';
        
        if ($postAction === 'create' || $postAction === 'update') {
            $isGoogleFont = isset($_POST['is_google_font']);
            
            $data = [
                'name' => Security::sanitizeString($_POST['name'] ?? ''),
                'font_family' => Security::sanitizeString($_POST['font_family'] ?? ''),
                'category' => $_POST['category'] ?? 'sans-serif',
                'is_google_font' => $isGoogleFont ? 1 : 0,
                'google_font_url' => $isGoogleFont ? Security::sanitizeString($_POST['google_font_url'] ?? '') : null,
                'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'] ?? 0)
            ];
            
            // Handle font file upload (if not Google Font)
            $fontFileRegular = $_POST['existing_font_file'] ?? '';
            if (!$isGoogleFont && !empty($_FILES['font_file']['name'])) {
                $uploadDir = __DIR__ . '/../assets/fonts/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['font_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['woff', 'woff2', 'ttf', 'otf'])) {
                    $filename = 'font_' . preg_replace('/[^a-z0-9]/', '', strtolower($data['font_family'])) . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['font_file']['tmp_name'], $targetPath)) {
                        $fontFileRegular = '/assets/fonts/' . $filename;
                    } else {
                        $error = 'Failed to upload font file';
                    }
                } else {
                    $error = 'Invalid font file type. Allowed: woff, woff2, ttf, otf';
                }
            }
            $data['font_file_regular'] = $fontFileRegular;
            
            if (empty($data['name']) || empty($data['font_family'])) {
                $error = 'Name and Font Family are required';
            } elseif ($isGoogleFont && empty($data['google_font_url'])) {
                $error = 'Google Font URL is required for Google Fonts';
            } elseif (!$error) {
                if ($postAction === 'create') {
                    Database::query(
                        "INSERT INTO custom_fonts (name, font_family, category, font_file_regular, google_font_url, is_google_font, is_premium, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$data['name'], $data['font_family'], $data['category'], $data['font_file_regular'], $data['google_font_url'], $data['is_google_font'], $data['is_premium'], $data['is_active'], $data['display_order']]
                    );
                    $message = 'Font added successfully';
                } else {
                    $id = intval($_POST['id'] ?? 0);
                    Database::query(
                        "UPDATE custom_fonts SET name=?, font_family=?, category=?, font_file_regular=?, google_font_url=?, is_google_font=?, is_premium=?, is_active=?, display_order=? WHERE id=?",
                        [$data['name'], $data['font_family'], $data['category'], $data['font_file_regular'], $data['google_font_url'], $data['is_google_font'], $data['is_premium'], $data['is_active'], $data['display_order'], $id]
                    );
                    $message = 'Font updated successfully';
                }
                header('Location: /admin/fonts.php?message=' . urlencode($message));
                exit;
            }
        } elseif ($postAction === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            Database::query("DELETE FROM custom_fonts WHERE id = ?", [$id]);
            header('Location: /admin/fonts.php?message=' . urlencode('Font deleted'));
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

// Fetch fonts
$fonts = Database::fetchAll("SELECT * FROM custom_fonts $whereClause ORDER BY category, display_order", $params);

// Group by category
$fontsByCategory = [];
foreach ($fonts as $font) {
    $cat = $font['category'] ?? 'sans-serif';
    $fontsByCategory[$cat][] = $font;
}

// Get single font for edit
$editFont = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editFont = Database::fetchOne("SELECT * FROM custom_fonts WHERE id = ?", [intval($_GET['id'])]);
}
?>

<?php ob_start(); ?>

<!-- Load all active custom fonts for preview -->
<style>
<?php foreach ($fonts as $font): ?>
    <?php if ($font['is_google_font'] && $font['google_font_url']): ?>
        @import url('<?= Security::escape($font['google_font_url']) ?>');
    <?php elseif ($font['font_file_regular']): ?>
        @font-face {
            font-family: '<?= Security::escape($font['font_family']) ?>';
            src: url('<?= Security::escape($font['font_file_regular']) ?>');
            font-weight: normal;
            font-style: normal;
        }
    <?php endif; ?>
<?php endforeach; ?>
</style>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold">Fonts</h1>
            <p class="text-slate-500">Manage fonts for template builder</p>
        </div>
        <a href="/admin/fonts.php?action=new" class="btn-primary">
            <span class="material-symbols-outlined text-lg">add</span>
            Add Font
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
        <a href="/admin/fonts.php" class="px-3 py-1.5 rounded-full text-sm <?= !$filterCategory ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200' ?>">
            All
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="/admin/fonts.php?category=<?= $cat ?>" 
               class="px-3 py-1.5 rounded-full text-sm capitalize <?= $filterCategory === $cat ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200' ?>">
                <?= $cat ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($action === 'new' || $action === 'edit'): ?>
        <!-- Add/Edit Form -->
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-bold mb-4"><?= $editFont ? 'Edit Font' : 'Add New Font' ?></h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="<?= $editFont ? 'update' : 'create' ?>">
                <?php if ($editFont): ?>
                    <input type="hidden" name="id" value="<?= $editFont['id'] ?>">
                    <input type="hidden" name="existing_font_file" value="<?= Security::escape($editFont['font_file_regular'] ?? '') ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Display Name *</label>
                        <input type="text" name="name" value="<?= Security::escape($editFont['name'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Font Family (CSS) *</label>
                        <input type="text" name="font_family" value="<?= Security::escape($editFont['font_family'] ?? '') ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required
                            placeholder="e.g., Roboto, Playfair Display">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($editFont['category'] ?? 'sans-serif') === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Display Order</label>
                        <input type="number" name="display_order" value="<?= $editFont['display_order'] ?? 0 ?>" 
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <!-- Font Source Toggle -->
                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-lg">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_google_font" id="is_google_font" 
                            class="w-4 h-4" <?= ($editFont['is_google_font'] ?? 0) ? 'checked' : '' ?>
                            onchange="toggleFontSource()">
                        <span class="text-sm font-medium">Use Google Fonts</span>
                    </label>
                </div>

                <!-- Google Font URL -->
                <div id="google-font-section" style="<?= ($editFont['is_google_font'] ?? 0) ? '' : 'display: none;' ?>">
                    <label class="block text-sm font-medium mb-1">Google Fonts URL</label>
                    <input type="url" name="google_font_url" value="<?= Security::escape($editFont['google_font_url'] ?? '') ?>" 
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary"
                        placeholder="https://fonts.googleapis.com/css2?family=...">
                    <p class="text-xs text-slate-500 mt-1">Get the URL from <a href="https://fonts.google.com" target="_blank" class="text-primary">fonts.google.com</a></p>
                </div>

                <!-- Upload Font File -->
                <div id="upload-font-section" style="<?= ($editFont['is_google_font'] ?? 0) ? 'display: none;' : '' ?>">
                    <label class="block text-sm font-medium mb-1">Upload Font File (.woff, .woff2, .ttf, .otf)</label>
                    <input type="file" name="font_file" accept=".woff,.woff2,.ttf,.otf" 
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary">
                    <?php if ($editFont && $editFont['font_file_regular']): ?>
                        <p class="text-xs text-slate-500 mt-1">Current: <?= Security::escape($editFont['font_file_regular']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="is_active" <?= ($editFont['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label for="is_active" class="text-sm font-medium">Active</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_premium" id="is_premium" <?= ($editFont['is_premium'] ?? 0) ? 'checked' : '' ?>>
                        <label for="is_premium" class="text-sm font-medium">Premium</label>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary">
                        <?= $editFont ? 'Update Font' : 'Add Font' ?>
                    </button>
                    <a href="/admin/fonts.php" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </div>

        <script>
        function toggleFontSource() {
            const isGoogle = document.getElementById('is_google_font').checked;
            document.getElementById('google-font-section').style.display = isGoogle ? '' : 'none';
            document.getElementById('upload-font-section').style.display = isGoogle ? 'none' : '';
        }
        </script>
    <?php else: ?>
        <!-- Fonts List by Category -->
        <?php foreach ($fontsByCategory as $category => $categoryFonts): ?>
            <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <h2 class="font-bold text-lg capitalize"><?= $category ?></h2>
                    <span class="text-sm text-slate-500"><?= count($categoryFonts) ?> fonts</span>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($categoryFonts as $font): ?>
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-white/5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 flex items-center justify-center bg-slate-100 rounded-lg">
                                    <span style="font-family: '<?= Security::escape($font['font_family']) ?>'; font-size: 1.5rem;">Aa</span>
                                </div>
                                <div>
                                    <p class="font-medium"><?= Security::escape($font['name']) ?></p>
                                    <p class="text-sm text-slate-500">
                                        <?= Security::escape($font['font_family']) ?>
                                        <?php if ($font['is_google_font']): ?>
                                            <span class="ml-2 text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded">Google</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($font['is_premium']): ?>
                                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">Premium</span>
                                <?php endif; ?>
                                <?php if (!$font['is_active']): ?>
                                    <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded">Inactive</span>
                                <?php endif; ?>
                                <a href="/admin/fonts.php?action=edit&id=<?= $font['id'] ?>" 
                                    class="p-2 hover:bg-slate-100 rounded-lg" title="Edit">
                                    <span class="material-symbols-outlined text-slate-500">edit</span>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this font?')">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $font['id'] ?>">
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

        <?php if (empty($fonts)): ?>
            <div class="text-center py-12 text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2">text_fields</span>
                <p>No fonts yet. <a href="/admin/fonts.php?action=new" class="text-primary">Add one</a></p>
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
