<?php
/**
 * Admin - Blog Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/auth.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_post') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = $_POST['content'] ?? '';
        $category = trim($_POST['category'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $featuredImage = trim($_POST['featured_image'] ?? '');

        // Generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
            $slug = trim($slug, '-');
        }

        // Set published_at if publishing
        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        if ($id > 0) {
            // Update existing post
            if ($status === 'published') {
                Database::query(
                    "UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, category = ?, 
                     status = ?, meta_title = ?, meta_description = ?, featured_image = ?,
                     published_at = COALESCE(published_at, NOW())
                     WHERE id = ?",
                    [$title, $slug, $excerpt, $content, $category, $status, $metaTitle, $metaDescription, $featuredImage, $id]
                );
            } else {
                Database::query(
                    "UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, category = ?, 
                     status = ?, meta_title = ?, meta_description = ?, featured_image = ?
                     WHERE id = ?",
                    [$title, $slug, $excerpt, $content, $category, $status, $metaTitle, $metaDescription, $featuredImage, $id]
                );
            }
            header('Location: /admin/blog.php?success=updated');
        } else {
            // Create new post
            Database::query(
                "INSERT INTO blog_posts (title, slug, excerpt, content, category, status, meta_title, meta_description, featured_image, author_id, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$title, $slug, $excerpt, $content, $category, $status, $metaTitle, $metaDescription, $featuredImage, $_SESSION['user_id'], $status === 'published' ? date('Y-m-d H:i:s') : null]
            );
            header('Location: /admin/blog.php?success=created');
        }
        exit;
    }

    if ($action === 'delete_post') {
        $id = intval($_POST['id'] ?? 0);
        Database::query("DELETE FROM blog_posts WHERE id = ?", [$id]);
        header('Location: /admin/blog.php?success=deleted');
        exit;
    }
}

// Get action and id
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Get post for editing
$post = null;
if ($action === 'edit' && $id > 0) {
    $post = Database::fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$id]);
}

// Get posts for list view
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$whereConditions = [];
$params = [];

if ($status) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(title LIKE ? OR excerpt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$totalPosts = Database::fetchOne("SELECT COUNT(*) as c FROM blog_posts $whereClause", $params)['c'] ?? 0;
$totalPages = ceil($totalPosts / $perPage);

$posts = Database::fetchAll(
    "SELECT p.*, u.name as author_name FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     $whereClause ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

// Get categories for filter
$categories = Database::fetchAll("SELECT DISTINCT category FROM blog_posts WHERE category IS NOT NULL AND category != '' ORDER BY category");

$pendingTickets = 0;
$pageTitle = ($action === 'new' || $action === 'edit') ? ($post ? 'Edit Post' : 'New Post') : 'Blog Management';
?>

<?php ob_start(); ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <!-- Editor View -->
    <div class="mb-6">
        <a href="/admin/blog.php"
            class="inline-flex items-center gap-2 text-slate-600 hover:text-primary transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Posts
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <input type="hidden" name="action" value="save_post">
        <input type="hidden" name="id" value="<?= $post['id'] ?? 0 ?>">

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Title -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <input type="text" name="title" id="post-title" value="<?= Security::escape($post['title'] ?? '') ?>"
                    required
                    class="w-full text-2xl font-bold border-none focus:ring-0 p-0 bg-transparent placeholder:text-slate-400"
                    placeholder="Post title..." oninput="generateSlugFromTitle()">
            </div>

            <!-- Slug -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4">
                <label class="text-xs text-slate-500 uppercase font-bold mb-1 block">URL Slug</label>
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-sm">/blog/</span>
                    <input type="text" name="slug" id="post-slug" value="<?= Security::escape($post['slug'] ?? '') ?>"
                        class="flex-1 text-sm border-none focus:ring-0 p-0 bg-transparent" placeholder="post-url-slug">
                </div>
                <p class="text-xs text-slate-400 mt-1">Auto-generated from title. Edit to customize.</p>
            </div>

            <!-- Excerpt -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <label class="text-xs text-slate-500 uppercase font-bold mb-2 block">Excerpt</label>
                <textarea name="excerpt" rows="2"
                    class="w-full border-none focus:ring-0 p-0 bg-transparent text-sm resize-none placeholder:text-slate-400"
                    placeholder="Brief summary for listings and SEO..."><?= Security::escape($post['excerpt'] ?? '') ?></textarea>
            </div>

            <!-- Content Editor -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div
                    class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-white/5 flex flex-wrap items-center gap-1">
                    <span class="text-xs text-slate-500 uppercase font-bold mr-2">Content</span>

                    <!-- Formatting Toolbar -->
                    <div class="flex items-center gap-1 border-r border-slate-200 pr-2 mr-2">
                        <button type="button" onclick="execCmd('bold')" class="toolbar-btn" title="Bold">
                            <span class="material-symbols-outlined text-lg">format_bold</span>
                        </button>
                        <button type="button" onclick="execCmd('italic')" class="toolbar-btn" title="Italic">
                            <span class="material-symbols-outlined text-lg">format_italic</span>
                        </button>
                        <button type="button" onclick="execCmd('underline')" class="toolbar-btn" title="Underline">
                            <span class="material-symbols-outlined text-lg">format_underlined</span>
                        </button>
                        <button type="button" onclick="execCmd('strikeThrough')" class="toolbar-btn" title="Strikethrough">
                            <span class="material-symbols-outlined text-lg">format_strikethrough</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-r border-slate-200 pr-2 mr-2">
                        <button type="button" onclick="execCmd('formatBlock', '<h2>')" class="toolbar-btn"
                            title="Heading 2">
                            <span class="font-bold text-sm">H2</span>
                        </button>
                        <button type="button" onclick="execCmd('formatBlock', '<h3>')" class="toolbar-btn"
                            title="Heading 3">
                            <span class="font-bold text-sm">H3</span>
                        </button>
                        <button type="button" onclick="execCmd('formatBlock', '<p>')" class="toolbar-btn" title="Paragraph">
                            <span class="material-symbols-outlined text-lg">format_paragraph</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-r border-slate-200 pr-2 mr-2">
                        <button type="button" onclick="execCmd('insertUnorderedList')" class="toolbar-btn"
                            title="Bullet List">
                            <span class="material-symbols-outlined text-lg">format_list_bulleted</span>
                        </button>
                        <button type="button" onclick="execCmd('insertOrderedList')" class="toolbar-btn"
                            title="Numbered List">
                            <span class="material-symbols-outlined text-lg">format_list_numbered</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-r border-slate-200 pr-2 mr-2">
                        <button type="button" onclick="execCmd('justifyLeft')" class="toolbar-btn" title="Align Left">
                            <span class="material-symbols-outlined text-lg">format_align_left</span>
                        </button>
                        <button type="button" onclick="execCmd('justifyCenter')" class="toolbar-btn" title="Align Center">
                            <span class="material-symbols-outlined text-lg">format_align_center</span>
                        </button>
                        <button type="button" onclick="execCmd('justifyRight')" class="toolbar-btn" title="Align Right">
                            <span class="material-symbols-outlined text-lg">format_align_right</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-r border-slate-200 pr-2 mr-2">
                        <button type="button" onclick="insertLink()" class="toolbar-btn" title="Insert Link">
                            <span class="material-symbols-outlined text-lg">link</span>
                        </button>
                        <button type="button" onclick="openImageModal()" class="toolbar-btn" title="Insert Image">
                            <span class="material-symbols-outlined text-lg">image</span>
                        </button>
                        <button type="button" onclick="execCmd('formatBlock', '<blockquote>')" class="toolbar-btn"
                            title="Blockquote">
                            <span class="material-symbols-outlined text-lg">format_quote</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 border-r border-slate-200 pr-2 mr-2">
                        <button type="button" onclick="execCmd('removeFormat')" class="toolbar-btn"
                            title="Clear Formatting">
                            <span class="material-symbols-outlined text-lg">format_clear</span>
                        </button>
                        <button type="button" onclick="toggleSource()" class="toolbar-btn" title="View HTML Source">
                            <span class="material-symbols-outlined text-lg">code</span>
                        </button>
                    </div>

                    <!-- Layouts Dropdown -->
                    <div class="relative">
                        <button type="button" onclick="toggleLayoutMenu()" class="toolbar-btn flex items-center gap-1"
                            title="Insert Layout Block">
                            <span class="material-symbols-outlined text-lg">view_column</span>
                            <span class="text-xs">Layouts</span>
                            <span class="material-symbols-outlined text-sm">expand_more</span>
                        </button>
                        <div id="layout-menu"
                            class="hidden absolute top-full left-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg z-50 min-w-[180px]">
                            <button type="button" onclick="insertImageTextBlock()"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg text-slate-500">image</span>
                                Image + Text
                            </button>
                            <button type="button" onclick="insertTwoColumnBlock()"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg text-slate-500">view_column</span>
                                Two Columns
                            </button>
                            <button type="button" onclick="insertCalloutBlock('info')"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg text-blue-500">info</span>
                                Info Callout
                            </button>
                            <button type="button" onclick="insertCalloutBlock('tip')"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg text-green-500">lightbulb</span>
                                Tip Callout
                            </button>
                            <button type="button" onclick="insertCalloutBlock('warning')"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg text-amber-500">warning</span>
                                Warning Callout
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <!-- Visual Editor -->
                    <div id="visual-editor" contenteditable="true"
                        class="min-h-[400px] p-4 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary prose prose-sm max-w-none"
                        oninput="syncContent()"><?= $post['content'] ?? '' ?></div>

                    <!-- Source Editor (hidden by default) -->
                    <textarea id="source-editor" name="content"
                        class="hidden w-full min-h-[400px] p-4 border border-slate-200 rounded-lg bg-slate-50 font-mono text-sm focus:ring-2 focus:ring-primary"
                        oninput="syncFromSource()"><?= Security::escape($post['content'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Publish Box -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">publish</span>
                    Publish
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select name="status"
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft
                            </option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                                Published</option>
                            <option value="archived" <?= ($post['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived
                            </option>
                        </select>
                    </div>

                    <?php if ($post && $post['published_at']): ?>
                        <p class="text-xs text-slate-500">
                            Published: <?= date('M j, Y g:i A', strtotime($post['published_at'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex gap-2 pt-2">
                        <button type="submit"
                            class="flex-1 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                            <?= $post ? 'Update' : 'Publish' ?>
                        </button>
                        <a href="<?= $post ? '/blog/' . $post['slug'] : '#' ?>" target="_blank"
                            class="px-4 py-2.5 border border-slate-200 rounded-lg hover:bg-slate-50 <?= !$post ? 'opacity-50 pointer-events-none' : '' ?>">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Category -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">category</span>
                    Category
                </h3>
                <input type="text" name="category" value="<?= Security::escape($post['category'] ?? '') ?>"
                    class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 text-sm"
                    placeholder="e.g., Wedding Tips, Birthday Ideas" list="category-suggestions">
                <datalist id="category-suggestions">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= Security::escape($cat['category']) ?>">
                        <?php endforeach; ?>
                </datalist>
            </div>

            <!-- Featured Image -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">image</span>
                    Featured Image
                </h3>

                <!-- Upload Zone -->
                <div id="featured-upload-zone"
                    class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors mb-3"
                    ondragover="event.preventDefault(); this.classList.add('border-primary', 'bg-primary/5');"
                    ondragleave="this.classList.remove('border-primary', 'bg-primary/5');"
                    ondrop="handleFeaturedDrop(event)" onclick="document.getElementById('featured-file-input').click()">
                    <span class="material-symbols-outlined text-3xl text-slate-400 mb-2">cloud_upload</span>
                    <p class="text-sm text-slate-500">Drop image here or click to upload</p>
                    <p class="text-xs text-slate-400 mt-1">Auto-compressed to WebP format</p>
                </div>
                <input type="file" id="featured-file-input" accept="image/*" class="hidden"
                    onchange="uploadFeaturedImage(this.files[0])">

                <!-- Upload Progress -->
                <div id="featured-upload-progress" class="hidden mb-3">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <div class="animate-spin w-4 h-4 border-2 border-primary border-t-transparent rounded-full"></div>
                        <span>Uploading & compressing...</span>
                    </div>
                </div>

                <!-- Compression Stats -->
                <div id="featured-compression-stats"
                    class="hidden mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    <span class="material-symbols-outlined text-base align-middle mr-1">check_circle</span>
                    <span id="featured-stats-text"></span>
                </div>

                <!-- OR divider -->
                <div class="flex items-center gap-3 my-3">
                    <div class="flex-1 h-px bg-slate-200"></div>
                    <span class="text-xs text-slate-400 uppercase">or enter URL</span>
                    <div class="flex-1 h-px bg-slate-200"></div>
                </div>

                <input type="url" name="featured_image" id="featured-image-url"
                    value="<?= Security::escape($post['featured_image'] ?? '') ?>"
                    class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 text-sm"
                    placeholder="https://..." onchange="updateFeaturedPreview()">

                <!-- Preview -->
                <div id="featured-preview-container"
                    class="mt-3 relative rounded-lg overflow-hidden bg-slate-100 <?= ($post && $post['featured_image']) ? '' : 'hidden' ?>">
                    <div class="aspect-video">
                        <img id="featured-preview-img" src="<?= Security::escape($post['featured_image'] ?? '') ?>" alt=""
                            class="w-full h-full object-cover">
                    </div>
                    <!-- Remove Button -->
                    <button type="button" onclick="clearFeaturedImage()"
                        class="absolute top-2 right-2 p-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow-lg transition-colors"
                        title="Remove image">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
            </div>

            <!-- SEO -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">search</span>
                    SEO Settings
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-slate-500 uppercase font-bold mb-1">Meta Title</label>
                        <input type="text" name="meta_title" value="<?= Security::escape($post['meta_title'] ?? '') ?>"
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 text-sm"
                            placeholder="SEO title (60 chars max)">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 uppercase font-bold mb-1">Meta Description</label>
                        <textarea name="meta_description" rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-sm resize-none"
                            placeholder="SEO description (160 chars max)"><?= Security::escape($post['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Custom Editor Styles & Scripts -->
    <style>
        .toolbar-btn {
            padding: 6px;
            border-radius: 6px;
            color: #64748b;
            transition: all 0.15s;
        }

        .toolbar-btn:hover {
            background: #e2e8f0;
            color: #7f13ec;
        }

        #visual-editor h2 {
            font-size: 1.5em;
            font-weight: bold;
            margin: 1em 0 0.5em;
        }

        #visual-editor h3 {
            font-size: 1.25em;
            font-weight: bold;
            margin: 1em 0 0.5em;
        }

        #visual-editor p {
            margin-bottom: 1em;
        }

        #visual-editor ul,
        #visual-editor ol {
            margin: 1em 0;
            padding-left: 2em;
        }

        #visual-editor blockquote {
            border-left: 4px solid #7f13ec;
            padding-left: 1em;
            margin: 1em 0;
            color: #64748b;
            font-style: italic;
        }

        #visual-editor a {
            color: #7f13ec;
            text-decoration: underline;
        }

        #visual-editor img {
            max-width: 100%;
            border-radius: 8px;
            margin: 1em 0;
        }

        /* Layout Block Styles */
        #visual-editor .blog-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin: 1.5rem 0;
            padding: 1rem;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }

        #visual-editor .blog-col {
            flex: 1 1 280px;
            min-width: 0;
        }

        #visual-editor .blog-col img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        #visual-editor .blog-callout {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        #visual-editor .blog-callout-info {
            background: #e0f2fe;
            border-left: 4px solid #0ea5e9;
        }

        #visual-editor .blog-callout-tip {
            background: #dcfce7;
            border-left: 4px solid #22c55e;
        }

        #visual-editor .blog-callout-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
    </style>
    <script>
        const visualEditor = document.getElementById('visual-editor');
        const sourceEditor = document.getElementById('source-editor');
        let isSourceMode = false;
        let slugManuallyEdited = <?= ($post && !empty($post['slug'])) ? 'true' : 'false' ?>;

        // Generate slug from title
        function generateSlugFromTitle() {
            // Don't overwrite if user has manually edited the slug
            if (slugManuallyEdited) return;

            const title = document.getElementById('post-title').value;
            const slug = title
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')          // Replace spaces with hyphens
                .replace(/-+/g, '-')           // Replace multiple hyphens with single
                .replace(/^-|-$/g, '');        // Remove leading/trailing hyphens

            document.getElementById('post-slug').value = slug;
        }

        // Track if user manually edits the slug
        document.addEventListener('DOMContentLoaded', function () {
            const slugInput = document.getElementById('post-slug');
            if (slugInput) {
                slugInput.addEventListener('input', function () {
                    slugManuallyEdited = true;
                });
            }
        });

        // Sync visual editor content to hidden textarea
        function syncContent() {
            sourceEditor.value = visualEditor.innerHTML;
        }

        // Sync source editor to visual editor
        function syncFromSource() {
            visualEditor.innerHTML = sourceEditor.value;
        }

        // Execute formatting command
        function execCmd(command, value = null) {
            document.execCommand(command, false, value);
            visualEditor.focus();
            syncContent();
        }

        // ========== Layout Block Functions ==========

        // Toggle layout menu dropdown
        function toggleLayoutMenu() {
            const menu = document.getElementById('layout-menu');
            menu.classList.toggle('hidden');

            // Close when clicking outside
            if (!menu.classList.contains('hidden')) {
                setTimeout(() => {
                    document.addEventListener('click', closeLayoutMenuOnClick, { once: true });
                }, 10);
            }
        }

        function closeLayoutMenuOnClick(e) {
            const menu = document.getElementById('layout-menu');
            if (!e.target.closest('#layout-menu')) {
                menu.classList.add('hidden');
            }
        }

        // Insert HTML at cursor position
        function insertHTMLAtCursor(html) {
            visualEditor.focus();
            document.execCommand('insertHTML', false, html);
            syncContent();
            document.getElementById('layout-menu').classList.add('hidden');
        }

        // Insert Image + Text block
        function insertImageTextBlock() {
            const html = `
                <div class="blog-row" contenteditable="false">
                    <div class="blog-col" contenteditable="true">
                        <p><em>Click here to add image or drag an image here...</em></p>
                    </div>
                    <div class="blog-col" contenteditable="true">
                        <h3>Your Heading</h3>
                        <p>Write your text content here. This column will stack below the image on mobile devices.</p>
                    </div>
                </div>
                <p></p>
            `;
            insertHTMLAtCursor(html);
        }

        // Insert Two Column block
        function insertTwoColumnBlock() {
            const html = `
                <div class="blog-row" contenteditable="false">
                    <div class="blog-col" contenteditable="true">
                        <h3>Column 1</h3>
                        <p>Enter content for the first column here.</p>
                    </div>
                    <div class="blog-col" contenteditable="true">
                        <h3>Column 2</h3>
                        <p>Enter content for the second column here.</p>
                    </div>
                </div>
                <p></p>
            `;
            insertHTMLAtCursor(html);
        }

        // Insert Callout block
        function insertCalloutBlock(type) {
            const icons = {
                'info': '💡',
                'tip': '✅',
                'warning': '⚠️'
            };
            const html = `
                <div class="blog-callout blog-callout-${type}">
                    <p><strong>${icons[type]} ${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> Write your important message here.</p>
                </div>
                <p></p>
            `;
            insertHTMLAtCursor(html);
        }

        // Insert link
        function insertLink() {
            const url = prompt('Enter URL:', 'https://');
            if (url) {
                execCmd('createLink', url);
            }
        }

        // ========== Image Upload Functions ==========

        // Upload featured image
        async function uploadFeaturedImage(file) {
            if (!file) return;

            const uploadZone = document.getElementById('featured-upload-zone');
            const progress = document.getElementById('featured-upload-progress');
            const stats = document.getElementById('featured-compression-stats');
            const statsText = document.getElementById('featured-stats-text');
            const urlInput = document.getElementById('featured-image-url');
            const previewContainer = document.getElementById('featured-preview-container');
            const previewImg = document.getElementById('featured-preview-img');

            // Show progress
            uploadZone.classList.add('hidden');
            progress.classList.remove('hidden');
            stats.classList.add('hidden');

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('/api/upload-blog-image.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Update URL input
                    urlInput.value = result.url;

                    // Show preview
                    previewImg.src = result.url;
                    previewContainer.classList.remove('hidden');

                    // Show compression stats
                    const originalKB = (result.compression.original_size / 1024).toFixed(1);
                    const compressedKB = (result.compression.compressed_size / 1024).toFixed(1);
                    statsText.textContent = `Compressed: ${originalKB}KB → ${compressedKB}KB (${result.compression.savings} saved)`;
                    stats.classList.remove('hidden');
                } else {
                    alert('Upload failed: ' + result.error);
                }
            } catch (error) {
                alert('Upload failed: ' + error.message);
            }

            // Hide progress, show upload zone
            progress.classList.add('hidden');
            uploadZone.classList.remove('hidden');
        }

        // Handle drag and drop for featured image
        function handleFeaturedDrop(event) {
            event.preventDefault();
            event.target.classList.remove('border-primary', 'bg-primary/5');
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                uploadFeaturedImage(file);
            }
        }

        // Update featured image preview from URL
        function updateFeaturedPreview() {
            const url = document.getElementById('featured-image-url').value;
            const previewContainer = document.getElementById('featured-preview-container');
            const previewImg = document.getElementById('featured-preview-img');

            if (url) {
                previewImg.src = url;
                previewContainer.classList.remove('hidden');
            } else {
                previewContainer.classList.add('hidden');
            }
        }

        // Clear featured image
        function clearFeaturedImage() {
            document.getElementById('featured-image-url').value = '';
            document.getElementById('featured-preview-container').classList.add('hidden');
            document.getElementById('featured-preview-img').src = '';
            document.getElementById('featured-compression-stats').classList.add('hidden');
        }

        // ========== Image Insert Modal ==========

        function openImageModal() {
            document.getElementById('image-modal').classList.remove('hidden');
            document.getElementById('modal-image-url').value = '';
            document.getElementById('modal-upload-stats').classList.add('hidden');
            switchModalTab('upload');
        }

        function closeImageModal() {
            document.getElementById('image-modal').classList.add('hidden');
        }

        function switchModalTab(tab) {
            const uploadTab = document.getElementById('modal-tab-upload');
            const urlTab = document.getElementById('modal-tab-url');
            const uploadPane = document.getElementById('modal-pane-upload');
            const urlPane = document.getElementById('modal-pane-url');

            if (tab === 'upload') {
                uploadTab.classList.add('bg-primary', 'text-white');
                uploadTab.classList.remove('bg-slate-100');
                urlTab.classList.remove('bg-primary', 'text-white');
                urlTab.classList.add('bg-slate-100');
                uploadPane.classList.remove('hidden');
                urlPane.classList.add('hidden');
            } else {
                urlTab.classList.add('bg-primary', 'text-white');
                urlTab.classList.remove('bg-slate-100');
                uploadTab.classList.remove('bg-primary', 'text-white');
                uploadTab.classList.add('bg-slate-100');
                urlPane.classList.remove('hidden');
                uploadPane.classList.add('hidden');
            }
        }

        async function uploadModalImage(file) {
            if (!file) return;

            const progress = document.getElementById('modal-upload-progress');
            const stats = document.getElementById('modal-upload-stats');
            const statsText = document.getElementById('modal-stats-text');
            const urlInput = document.getElementById('modal-image-url');

            progress.classList.remove('hidden');
            stats.classList.add('hidden');

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('/api/upload-blog-image.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    urlInput.value = result.url;

                    const originalKB = (result.compression.original_size / 1024).toFixed(1);
                    const compressedKB = (result.compression.compressed_size / 1024).toFixed(1);
                    statsText.textContent = `${originalKB}KB → ${compressedKB}KB (${result.compression.savings} saved)`;
                    stats.classList.remove('hidden');
                } else {
                    alert('Upload failed: ' + result.error);
                }
            } catch (error) {
                alert('Upload failed: ' + error.message);
            }

            progress.classList.add('hidden');
        }

        function handleModalDrop(event) {
            event.preventDefault();
            event.target.classList.remove('border-primary', 'bg-primary/5');
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                uploadModalImage(file);
            }
        }

        function insertImageFromModal() {
            const url = document.getElementById('modal-image-url').value;
            if (url) {
                execCmd('insertImage', url);
                closeImageModal();
            } else {
                alert('Please upload an image or enter a URL');
            }
        }

        // Insert image
        function insertImage() {
            const url = prompt('Enter image URL:', 'https://');
            if (url) {
                execCmd('insertImage', url);
            }
        }

        // Toggle between visual and source mode
        function toggleSource() {
            isSourceMode = !isSourceMode;
            if (isSourceMode) {
                sourceEditor.value = visualEditor.innerHTML;
                visualEditor.classList.add('hidden');
                sourceEditor.classList.remove('hidden');
            } else {
                visualEditor.innerHTML = sourceEditor.value;
                sourceEditor.classList.add('hidden');
                visualEditor.classList.remove('hidden');
            }
        }

        // Sync content on form submit
        document.querySelector('form').addEventListener('submit', function () {
            if (!isSourceMode) {
                syncContent();
            }
        });

        // Initialize - sync content on load
        syncContent();
    </script>

    <!-- Image Insert Modal -->
    <div id="image-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" onclick="closeImageModal()"></div>

        <!-- Modal Content -->
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="font-bold text-lg">Insert Image</h3>
                <button onclick="closeImageModal()" class="p-1 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 px-6 pt-4">
                <button type="button" id="modal-tab-upload" onclick="switchModalTab('upload')"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-primary text-white transition-colors">
                    Upload
                </button>
                <button type="button" id="modal-tab-url" onclick="switchModalTab('url')"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-100 transition-colors">
                    URL
                </button>
            </div>

            <!-- Upload Pane -->
            <div id="modal-pane-upload" class="p-6">
                <div class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors"
                    ondragover="event.preventDefault(); this.classList.add('border-primary', 'bg-primary/5');"
                    ondragleave="this.classList.remove('border-primary', 'bg-primary/5');" ondrop="handleModalDrop(event)"
                    onclick="document.getElementById('modal-file-input').click()">
                    <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">cloud_upload</span>
                    <p class="text-sm text-slate-500">Drag & drop or click to upload</p>
                    <p class="text-xs text-slate-400 mt-1">Auto-compressed to WebP</p>
                </div>
                <input type="file" id="modal-file-input" accept="image/*" class="hidden"
                    onchange="uploadModalImage(this.files[0])">

                <!-- Upload Progress -->
                <div id="modal-upload-progress" class="hidden mt-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <div class="animate-spin w-4 h-4 border-2 border-primary border-t-transparent rounded-full"></div>
                        <span>Uploading & compressing...</span>
                    </div>
                </div>

                <!-- Compression Stats -->
                <div id="modal-upload-stats"
                    class="hidden mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    <span class="material-symbols-outlined text-base align-middle mr-1">check_circle</span>
                    <span id="modal-stats-text"></span>
                </div>
            </div>

            <!-- URL Pane -->
            <div id="modal-pane-url" class="p-6 hidden">
                <label class="block text-sm font-medium mb-2">Image URL</label>
                <input type="url" id="modal-image-url-direct"
                    class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm" placeholder="https://..."
                    oninput="document.getElementById('modal-image-url').value = this.value">
            </div>

            <!-- Hidden URL field shared between tabs -->
            <input type="hidden" id="modal-image-url" value="">

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50">
                <button type="button" onclick="closeImageModal()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="insertImageFromModal()"
                    class="px-4 py-2 text-sm font-bold text-white bg-primary hover:bg-primary/90 rounded-lg transition-colors">
                    Insert Image
                </button>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- List View -->

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Blog Posts</h2>
            <p class="text-slate-500 mt-1">Manage your blog content for SEO</p>
        </div>
        <a href="/admin/blog.php?action=new"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined">add</span>
            New Post
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Post <?= Security::escape($_GET['success']) ?> successfully!
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-lg">search</span>
                    <input type="text" name="search" value="<?= Security::escape($search) ?>"
                        class="w-full h-10 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm"
                        placeholder="Search posts...">
                </div>
            </div>
            <select name="status"
                class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <option value="">All Status</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
            <button type="submit" class="h-10 px-6 bg-primary text-white font-bold rounded-lg hover:bg-primary/90">
                Filter
            </button>
        </form>
    </div>

    <!-- Posts Table -->
    <div
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Title</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Author</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Views</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($posts as $p):
                        $statusColors = [
                            'draft' => 'bg-yellow-100 text-yellow-700',
                            'published' => 'bg-green-100 text-green-700',
                            'archived' => 'bg-slate-100 text-slate-700',
                        ];
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if ($p['featured_image']): ?>
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 bg-cover bg-center flex-shrink-0"
                                            style="background-image: url('<?= Security::escape($p['featured_image']) ?>');"></div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white"><?= Security::escape($p['title']) ?>
                                        </p>
                                        <p class="text-xs text-slate-500 truncate max-w-xs">
                                            /blog/<?= Security::escape($p['slug']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500"><?= Security::escape($p['category'] ?? '—') ?></td>
                            <td class="px-6 py-4 text-slate-500"><?= Security::escape($p['author_name'] ?? '—') ?></td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$p['status']] ?? 'bg-slate-100' ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500"><?= number_format($p['view_count']) ?></td>
                            <td class="px-6 py-4 text-slate-500 text-xs">
                                <?= $p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : date('M j, Y', strtotime($p['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="/admin/blog.php?action=edit&id=<?= $p['id'] ?>"
                                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary"
                                        title="Edit">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    <a href="/blog/<?= $p['slug'] ?>" target="_blank"
                                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary"
                                        title="View">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this post?')">
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit"
                                            class="p-2 rounded-lg hover:bg-red-50 text-slate-500 hover:text-red-500"
                                            title="Delete">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">article</span>
                                <p class="text-lg font-medium">No blog posts yet</p>
                                <p class="text-sm">Create your first post to improve SEO</p>
                                <a href="/admin/blog.php?action=new"
                                    class="inline-flex items-center gap-2 mt-4 text-primary font-bold hover:underline">
                                    <span class="material-symbols-outlined">add</span>
                                    Create Post
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalPosts) ?> of <?= $totalPosts ?> posts
                </p>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-100' ?> font-medium text-sm">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>