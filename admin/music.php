<?php
/**
 * Admin - Music Library Management
 * Manage music tracks for video templates with S3 upload support
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$action = $_GET['action'] ?? 'list';
$musicId = intval($_GET['id'] ?? 0);
$error = null;
$success = null;

// Music categories
$categories = [
    'wedding' => 'Wedding',
    'party' => 'Party',
    'festival' => 'Festival',
    'puja' => 'Puja',
    'modern' => 'Modern',
    'traditional' => 'Traditional',
    'romantic' => 'Romantic',
    'upbeat' => 'Upbeat'
];

// Handle file upload to S3
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['music_file']) && $_FILES['music_file']['error'] === UPLOAD_ERR_OK) {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $file = $_FILES['music_file'];
        $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];

        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Only MP3, WAV, and OGG files are allowed.';
        } elseif ($file['size'] > 20 * 1024 * 1024) { // 20MB limit
            $error = 'File too large. Maximum size is 20MB.';
        } else {
            try {
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'music/' . uniqid() . '_' . preg_replace('/[^a-z0-9]/', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME))) . '.' . $ext;

                // Upload to S3
                require_once __DIR__ . '/../vendor/autoload.php';

                $s3 = new Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => AWS_DEFAULT_REGION,
                    'credentials' => [
                        'key' => AWS_ACCESS_KEY_ID,
                        'secret' => AWS_SECRET_ACCESS_KEY,
                    ],
                ]);

                $bucket = 'invitation-video-assets-permanent';

                $result = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key' => $filename,
                    'SourceFile' => $file['tmp_name'],
                    'ContentType' => $file['type'],
                ]);

                $_POST['s3_url'] = $result['ObjectURL'];
                $_POST['file_size_kb'] = round($file['size'] / 1024);

            } catch (Exception $e) {
                $error = 'S3 upload failed: ' . $e->getMessage();
                error_log('[Music Upload] S3 Error: ' . $e->getMessage());
            }
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && !$error) {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $name = Security::sanitizeString($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)));
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'category' => $_POST['category'] ?? 'wedding',
            's3_url' => $_POST['s3_url'] ?? '',
            'duration_seconds' => intval($_POST['duration_seconds'] ?? 30),
            'file_size_kb' => intval($_POST['file_size_kb'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if (empty($data['name']) || empty($data['s3_url'])) {
            $error = 'Name and music file are required.';
        } else {
            if ($_POST['form_action'] === 'create') {
                $sql = "INSERT INTO music_library (name, slug, category, s3_url, duration_seconds, file_size_kb, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
                Database::query($sql, array_values($data));
                header('Location: /admin/music.php?success=created');
                exit;
            } elseif ($_POST['form_action'] === 'update' && $musicId) {
                $sql = "UPDATE music_library SET name=?, slug=?, category=?, s3_url=?, duration_seconds=?, file_size_kb=?, is_active=? WHERE id=?";
                $params = array_values($data);
                $params[] = $musicId;
                Database::query($sql, $params);
                header('Location: /admin/music.php?success=updated');
                exit;
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $musicId) {
    Database::query("DELETE FROM music_library WHERE id = ?", [$musicId]);
    header('Location: /admin/music.php?success=deleted');
    exit;
}

// Get music for list view
$musicTracks = [];
if ($action === 'list') {
    $musicTracks = Database::fetchAll("SELECT * FROM music_library ORDER BY category, name");
}

// Get music for edit view
$music = null;
if ($action === 'edit' && $musicId) {
    $music = Database::fetchOne("SELECT * FROM music_library WHERE id = ?", [$musicId]);
    if (!$music) {
        header('Location: /admin/music.php');
        exit;
    }
}

$pendingTickets = 0;
$pageTitle = $action === 'new' ? 'Add Music' : ($action === 'edit' ? 'Edit Music' : 'Music Library');
?>

<?php ob_start(); ?>

<?php if ($action === 'list'): ?>

    <!-- List View -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold">Music Library</h2>
            <p class="text-slate-500 mt-1">Manage music tracks for video templates</p>
        </div>
        <a href="/admin/music.php?action=new"
            class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm shadow-primary/30 transition-all">
            <span class="material-symbols-outlined text-lg">add</span>
            Add Music
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Music
            <?= $_GET['success'] ?> successfully!
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-4">Name</th>
                    <th class="px-6 py-4">Category</th>
                    <th class="px-6 py-4">Duration</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($musicTracks as $track): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary">music_note</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        <?= Security::escape($track['name']) ?>
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        <?= Security::escape($track['slug']) ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="capitalize">
                                <?= $categories[$track['category']] ?? $track['category'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?= $track['duration_seconds'] ?>s
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($track['is_active']): ?>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= $track['s3_url'] ?>" target="_blank"
                                    class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary transition-colors"
                                    title="Play">
                                    <span class="material-symbols-outlined text-lg">play_circle</span>
                                </a>
                                <a href="/admin/music.php?action=edit&id=<?= $track['id'] ?>"
                                    class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                <a href="/admin/music.php?action=delete&id=<?= $track['id'] ?>"
                                    onclick="return confirm('Delete this music track?')"
                                    class="p-2 rounded-lg hover:bg-red-50 text-slate-500 hover:text-red-600 transition-colors">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($musicTracks)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">music_off</span>
                            <p class="text-lg font-medium">No music tracks yet</p>
                            <p class="text-sm">Add music tracks for templates to use</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>

    <!-- Add/Edit Form -->
    <div class="flex items-center gap-4 mb-6">
        <a href="/admin/music.php" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h2 class="text-2xl font-bold">
            <?= $action === 'new' ? 'Add Music' : 'Edit Music' ?>
        </h2>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= Security::escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data"
        class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Security::generateCSRFToken() ?>">
        <input type="hidden" name="form_action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
        <?php if ($music): ?>
            <input type="hidden" name="s3_url" value="<?= Security::escape($music['s3_url'] ?? '') ?>">
            <input type="hidden" name="file_size_kb" value="<?= $music['file_size_kb'] ?? 0 ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Name *</label>
                <input type="text" name="name" value="<?= Security::escape($music['name'] ?? '') ?>" required
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="e.g., Traditional Wedding March">
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Slug</label>
                <input type="text" name="slug" value="<?= Security::escape($music['slug'] ?? '') ?>"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="auto-generated-from-name">
            </div>

            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                <select name="category"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($music['category'] ?? 'wedding') === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Duration -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Duration (seconds)</label>
                <input type="number" name="duration_seconds" value="<?= $music['duration_seconds'] ?? 30 ?>" min="1"
                    max="300"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary focus:border-primary transition-all">
            </div>
        </div>

        <!-- Music File Upload -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Music File
                <?= $action === 'new' ? '*' : '(optional - keep existing)' ?>
            </label>
            <?php if ($music && !empty($music['s3_url'])): ?>
                <div class="mb-3 p-3 bg-slate-50 rounded-lg flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary">music_note</span>
                    <span class="text-sm text-slate-600 flex-1 truncate">
                        <?= Security::escape($music['s3_url']) ?>
                    </span>
                    <audio controls class="h-8">
                        <source src="<?= Security::escape($music['s3_url']) ?>" type="audio/mpeg">
                    </audio>
                </div>
            <?php endif; ?>
            <input type="file" name="music_file" accept=".mp3,.wav,.ogg,audio/*" <?= $action === 'new' ? 'required' : '' ?>
                class="w-full px-4 py-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary
        focus:border-primary transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary
        file:text-white file:font-medium file:cursor-pointer">
            <p class="mt-1 text-xs text-slate-500">Accepted formats: MP3, WAV, OGG. Max size: 20MB</p>
        </div>

        <!-- Active Status -->
        <div class="flex items-center gap-3">
            <input type="checkbox" name="is_active" id="is_active" class="w-5 h-5 rounded text-primary focus:ring-primary"
                <?= ($music['is_active'] ?? 1) ? 'checked' : '' ?>>
            <label for="is_active" class="text-sm font-medium text-slate-700">Active (visible in template dropdown)</label>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="/admin/music.php"
                class="px-6 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">Cancel</a>
            <button type="submit"
                class="px-6 py-2.5 rounded-lg bg-primary hover:bg-primary/90 text-white font-bold shadow-sm shadow-primary/30 transition-all">
                <?= $action === 'new' ? 'Add Music' : 'Update Music' ?>
            </button>
        </div>
    </form>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>