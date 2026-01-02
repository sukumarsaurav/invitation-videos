<?php
/**
 * Admin - Backgrounds Management
 * Manage backgrounds (colors, gradients, images, videos) for template builder
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$pageTitle = 'Backgrounds';
$pendingTickets = 0;

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

$categories = ['solid', 'gradient', 'pattern', 'nature', 'abstract', 'wedding', 'celebration', 'custom'];
$types = ['color', 'gradient', 'image', 'video'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'create' || $postAction === 'update') {
            $bgType = $_POST['type'] ?? 'image';

            $data = [
                'name' => Security::sanitizeString($_POST['name'] ?? ''),
                'category' => $_POST['category'] ?? 'custom',
                'type' => $bgType,
                'color_value' => ($bgType === 'color') ? ($_POST['color_value'] ?? '#FFFFFF') : null,
                'gradient_value' => ($bgType === 'gradient') ? Security::sanitizeString($_POST['gradient_value'] ?? '') : null,
                'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'] ?? 0)
            ];

            // Handle file upload for image/video
            $filePath = $_POST['existing_file_path'] ?? '';
            $thumbnailPath = $_POST['existing_thumbnail'] ?? '';

            if (in_array($bgType, ['image', 'video']) && !empty($_FILES['bg_file']['name'])) {
                $uploadDir = __DIR__ . '/../assets/backgrounds/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['bg_file']['name'], PATHINFO_EXTENSION));
                $allowedImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $allowedVid = ['mp4', 'webm', 'mov'];

                $isValid = ($bgType === 'image' && in_array($ext, $allowedImg)) ||
                    ($bgType === 'video' && in_array($ext, $allowedVid));

                if ($isValid) {
                    if ($bgType === 'image') {
                        // Compress image uploads
                        require_once __DIR__ . '/../src/Core/ImageHelper.php';

                        $result = ImageHelper::processThumbnailUpload(
                            $_FILES['bg_file'],
                            $uploadDir,
                            'bg_',
                            1920,  // Max width for backgrounds
                            1080   // Max height for backgrounds
                        );

                        if ($result['success']) {
                            $filePath = '/assets/backgrounds/' . basename($result['url']);
                        } else {
                            $error = 'Failed to process image: ' . $result['error'];
                        }
                    } else {
                        // Video - just move without compression
                        $filename = 'bg_' . time() . '_' . uniqid() . '.' . $ext;
                        $targetPath = $uploadDir . $filename;

                        if (move_uploaded_file($_FILES['bg_file']['tmp_name'], $targetPath)) {
                            $filePath = '/assets/backgrounds/' . $filename;
                        } else {
                            $error = 'Failed to upload file';
                        }
                    }
                } else {
                    $error = 'Invalid file type. Images: jpg, png, webp, gif. Videos: mp4, webm, mov';
                }
            }

            // Handle thumbnail for videos (with compression)
            if ($bgType === 'video' && !empty($_FILES['thumbnail']['name'])) {
                require_once __DIR__ . '/../src/Core/ImageHelper.php';

                $thumbDir = __DIR__ . '/../assets/backgrounds/thumbs/';
                if (!file_exists($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }

                $result = ImageHelper::processThumbnailUpload(
                    $_FILES['thumbnail'],
                    $thumbDir,
                    'thumb_',
                    800,   // Max width for thumbnails
                    450    // Max height for thumbnails (16:9 aspect)
                );

                if ($result['success']) {
                    $thumbnailPath = '/assets/backgrounds/thumbs/' . basename($result['url']);
                }
            }

            $data['file_path'] = $filePath;
            $data['thumbnail_path'] = $thumbnailPath;

            if (empty($data['name'])) {
                $error = 'Name is required';
            } elseif (!$error) {
                if ($postAction === 'create') {
                    Database::query(
                        "INSERT INTO backgrounds (name, category, type, file_path, thumbnail_path, color_value, gradient_value, is_premium, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$data['name'], $data['category'], $data['type'], $data['file_path'], $data['thumbnail_path'], $data['color_value'], $data['gradient_value'], $data['is_premium'], $data['is_active'], $data['display_order']]
                    );
                    $message = 'Background added successfully';
                } else {
                    $id = intval($_POST['id'] ?? 0);
                    Database::query(
                        "UPDATE backgrounds SET name=?, category=?, type=?, file_path=?, thumbnail_path=?, color_value=?, gradient_value=?, is_premium=?, is_active=?, display_order=? WHERE id=?",
                        [$data['name'], $data['category'], $data['type'], $data['file_path'], $data['thumbnail_path'], $data['color_value'], $data['gradient_value'], $data['is_premium'], $data['is_active'], $data['display_order'], $id]
                    );
                    $message = 'Background updated successfully';
                }
                header('Location: /admin/backgrounds.php?message=' . urlencode($message));
                exit;
            }
        } elseif ($postAction === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            Database::query("DELETE FROM backgrounds WHERE id = ?", [$id]);
            header('Location: /admin/backgrounds.php?message=' . urlencode('Background deleted'));
            exit;
        }
    }
}

if (isset($_GET['message'])) {
    $message = Security::escape($_GET['message']);
}

// Filter
$filterType = $_GET['type'] ?? '';
$whereClause = '';
$params = [];
if ($filterType && in_array($filterType, $types)) {
    $whereClause = "WHERE type = ?";
    $params = [$filterType];
}

$backgrounds = Database::fetchAll("SELECT * FROM backgrounds $whereClause ORDER BY type, display_order", $params);

// Group by type
$bgByType = [];
foreach ($backgrounds as $bg) {
    $bgByType[$bg['type']][] = $bg;
}

$editBg = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editBg = Database::fetchOne("SELECT * FROM backgrounds WHERE id = ?", [intval($_GET['id'])]);
}
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold">Backgrounds</h1>
            <p class="text-slate-500">Colors, gradients, images & videos for templates</p>
        </div>
        <a href="/admin/backgrounds.php?action=new" class="btn-primary">
            <span class="material-symbols-outlined text-lg">add</span>
            Add Background
        </a>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?= $error ?></div>
    <?php endif; ?>

    <!-- Type Filter -->
    <div class="flex gap-2 flex-wrap">
        <a href="/admin/backgrounds.php"
            class="px-3 py-1.5 rounded-full text-sm <?= !$filterType ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200' ?>">All</a>
        <?php foreach ($types as $t): ?>
            <a href="/admin/backgrounds.php?type=<?= $t ?>"
                class="px-3 py-1.5 rounded-full text-sm capitalize <?= $filterType === $t ? 'bg-primary text-white' : 'bg-slate-100 hover:bg-slate-200' ?>"><?= $t ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($action === 'new' || $action === 'edit'): ?>
        <!-- Add/Edit Form -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold mb-4"><?= $editBg ? 'Edit Background' : 'Add New Background' ?></h2>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="<?= $editBg ? 'update' : 'create' ?>">
                <?php if ($editBg): ?>
                    <input type="hidden" name="id" value="<?= $editBg['id'] ?>">
                    <input type="hidden" name="existing_file_path" value="<?= Security::escape($editBg['file_path'] ?? '') ?>">
                    <input type="hidden" name="existing_thumbnail"
                        value="<?= Security::escape($editBg['thumbnail_path'] ?? '') ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name *</label>
                        <input type="text" name="name" value="<?= Security::escape($editBg['name'] ?? '') ?>"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Type</label>
                        <select name="type" id="bg-type" class="w-full px-3 py-2 border rounded-lg"
                            onchange="toggleBgFields()">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= ($editBg['type'] ?? 'image') === $t ? 'selected' : '' ?>>
                                    <?= ucfirst($t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border rounded-lg">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= ($editBg['category'] ?? 'custom') === $c ? 'selected' : '' ?>>
                                    <?= ucfirst($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Display Order</label>
                        <input type="number" name="display_order" value="<?= $editBg['display_order'] ?? 0 ?>"
                            class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <!-- Color field -->
                <div id="color-field" style="<?= ($editBg['type'] ?? 'image') !== 'color' ? 'display:none;' : '' ?>">
                    <label class="block text-sm font-medium mb-1">Color</label>
                    <input type="color" name="color_value"
                        value="<?= Security::escape($editBg['color_value'] ?? '#FFFFFF') ?>"
                        class="w-20 h-10 border rounded">
                </div>

                <!-- Gradient field -->
                <div id="gradient-field" style="<?= ($editBg['type'] ?? 'image') !== 'gradient' ? 'display:none;' : '' ?>">
                    <label class="block text-sm font-medium mb-1">CSS Gradient</label>
                    <input type="text" name="gradient_value"
                        value="<?= Security::escape($editBg['gradient_value'] ?? '') ?>"
                        class="w-full px-3 py-2 border rounded-lg"
                        placeholder="linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                </div>

                <!-- File upload -->
                <div id="file-field"
                    style="<?= !in_array($editBg['type'] ?? 'image', ['image', 'video']) ? 'display:none;' : '' ?>">
                    <label class="block text-sm font-medium mb-1">Background File (Image/Video)</label>
                    <input type="file" name="bg_file" accept="image/*,video/mp4,video/webm"
                        class="w-full px-3 py-2 border rounded-lg">
                    <?php if ($editBg && $editBg['file_path']): ?>
                        <p class="text-xs text-slate-500 mt-1">Current: <?= Security::escape($editBg['file_path']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Thumbnail for video -->
                <div id="thumb-field" style="<?= ($editBg['type'] ?? 'image') !== 'video' ? 'display:none;' : '' ?>">
                    <label class="block text-sm font-medium mb-1">Video Thumbnail</label>
                    <input type="file" name="thumbnail" accept="image/*" class="w-full px-3 py-2 border rounded-lg">
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" <?= ($editBg['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_premium"
                            <?= ($editBg['is_premium'] ?? 0) ? 'checked' : '' ?>> Premium</label>
                </div>

                <!-- Preview -->
                <?php if ($editBg): ?>
                    <div class="p-4 bg-slate-50 rounded-lg">
                        <p class="text-sm font-medium mb-2">Preview:</p>
                        <?php if ($editBg['type'] === 'color'): ?>
                            <div
                                style="width:100px;height:100px;background:<?= Security::escape($editBg['color_value']) ?>;border-radius:8px;border:1px solid #ddd;">
                            </div>
                        <?php elseif ($editBg['type'] === 'gradient'): ?>
                            <div
                                style="width:100px;height:100px;background:<?= Security::escape($editBg['gradient_value']) ?>;border-radius:8px;">
                            </div>
                        <?php elseif ($editBg['type'] === 'image' && $editBg['file_path']): ?>
                            <img src="<?= Security::escape($editBg['file_path']) ?>" style="max-width:150px;border-radius:8px;">
                        <?php elseif ($editBg['type'] === 'video' && $editBg['file_path']): ?>
                            <video src="<?= Security::escape($editBg['file_path']) ?>" style="max-width:150px;border-radius:8px;"
                                controls muted></video>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="flex gap-3">
                    <button type="submit" class="btn-primary"><?= $editBg ? 'Update' : 'Add' ?> Background</button>
                    <a href="/admin/backgrounds.php" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Cancel</a>
                </div>
            </form>
        </div>

        <script>
            function toggleBgFields() {
                const type = document.getElementById('bg-type').value;
                document.getElementById('color-field').style.display = type === 'color' ? '' : 'none';
                document.getElementById('gradient-field').style.display = type === 'gradient' ? '' : 'none';
                document.getElementById('file-field').style.display = ['image', 'video'].includes(type) ? '' : 'none';
                document.getElementById('thumb-field').style.display = type === 'video' ? '' : 'none';
            }
        </script>
    <?php else: ?>
        <!-- Backgrounds Grid by Type -->
        <?php foreach ($bgByType as $type => $typeBgs): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h2 class="font-bold text-lg capitalize"><?= $type ?>s</h2>
                    <span class="text-sm text-slate-500"><?= count($typeBgs) ?> items</span>
                </div>
                <div class="p-4 grid grid-cols-3 sm:grid-cols-5 md:grid-cols-8 lg:grid-cols-10 gap-3">
                    <?php foreach ($typeBgs as $bg): ?>
                        <div class="group relative aspect-[9/16] rounded-lg overflow-hidden border hover:border-primary transition-colors cursor-pointer"
                            style="<?php
                            if ($bg['type'] === 'color')
                                echo 'background:' . Security::escape($bg['color_value']) . ';';
                            elseif ($bg['type'] === 'gradient')
                                echo 'background:' . Security::escape($bg['gradient_value']) . ';';
                            ?>">
                            <?php if ($bg['type'] === 'image' && $bg['file_path']): ?>
                                <img src="<?= Security::escape($bg['file_path']) ?>" class="w-full h-full object-cover">
                            <?php elseif ($bg['type'] === 'video'): ?>
                                <img src="<?= Security::escape($bg['thumbnail_path'] ?: $bg['file_path']) ?>"
                                    class="w-full h-full object-cover">
                                <span class="absolute bottom-1 right-1 bg-black/60 rounded px-1 text-white text-xs">▶</span>
                            <?php endif; ?>

                            <?php if ($bg['is_premium']): ?>
                                <span class="absolute top-1 right-1 text-yellow-400 text-xs">★</span>
                            <?php endif; ?>

                            <!-- Hover Actions -->
                            <div
                                class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                <a href="/admin/backgrounds.php?action=edit&id=<?= $bg['id'] ?>"
                                    class="p-1.5 bg-white rounded-full hover:bg-slate-100">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $bg['id'] ?>">
                                    <button class="p-1.5 bg-white rounded-full hover:bg-red-50"><span
                                            class="material-symbols-outlined text-sm text-red-500">delete</span></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($backgrounds)): ?>
            <div class="text-center py-12 text-slate-500">
                <span class="material-symbols-outlined text-4xl mb-2">wallpaper</span>
                <p>No backgrounds yet. <a href="/admin/backgrounds.php?action=new" class="text-primary">Add one</a></p>
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

    .btn-primary:hover {
        background: #6b0fcc;
    }

    .bg-primary {
        background: #7f13ec;
    }

    .text-primary {
        color: #7f13ec;
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>