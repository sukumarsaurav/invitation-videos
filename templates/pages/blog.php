<?php
/**
 * Public Blog Listing Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Get published posts
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$category = $_GET['category'] ?? '';
$whereClause = "WHERE status = 'published'";
$params = [];

if ($category) {
    $whereClause .= " AND category = ?";
    $params[] = $category;
}

$totalPosts = Database::fetchOne("SELECT COUNT(*) as c FROM blog_posts $whereClause", $params)['c'] ?? 0;
$totalPages = ceil($totalPosts / $perPage);

$posts = Database::fetchAll(
    "SELECT p.*, u.name as author_name FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     $whereClause 
     ORDER BY p.published_at DESC 
     LIMIT $perPage OFFSET $offset",
    $params
);

// Get categories for sidebar
$categories = Database::fetchAll(
    "SELECT category, COUNT(*) as count FROM blog_posts 
     WHERE status = 'published' AND category IS NOT NULL AND category != '' 
     GROUP BY category ORDER BY count DESC"
);

// Get featured/recent posts for sidebar
$recentPosts = Database::fetchAll(
    "SELECT id, title, slug, featured_image, published_at FROM blog_posts 
     WHERE status = 'published' ORDER BY published_at DESC LIMIT 5"
);

$pageTitle = 'Blog' . ($category ? ' - ' . ucfirst($category) : '');
$pageDescription = 'Tips, ideas, and inspiration for your video invitations. Wedding tips, birthday ideas, and more.';
?>

<?php ob_start(); ?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-primary via-purple-600 to-indigo-700 text-white py-16 md:py-24">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,...')] opacity-10"></div>
    <div class="max-w-6xl mx-auto px-4 text-center relative z-10">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Blog</h1>
        <p class="text-lg text-white/80 max-w-2xl mx-auto">
            Tips, trends, and inspiration for creating unforgettable video invitations
        </p>
    </div>
</section>

<!-- Main Content -->
<section class="py-12 md:py-16 bg-slate-50">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

            <!-- Posts Grid -->
            <div class="lg:col-span-3">
                <?php if ($category): ?>
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-xl font-bold capitalize"><?= Security::escape($category) ?></h2>
                        <a href="/blog" class="text-primary hover:underline text-sm">View all posts →</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($posts)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($posts as $i => $post): ?>
                            <article
                                class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow group <?= $i === 0 && !$category ? 'md:col-span-2' : '' ?>">
                                <a href="/blog/<?= Security::escape($post['slug']) ?>" class="block">
                                    <!-- Featured Image -->
                                    <div class="aspect-video bg-slate-100 relative overflow-hidden">
                                        <?php if ($post['featured_image']): ?>
                                            <img src="<?= Security::escape($post['featured_image']) ?>"
                                                alt="<?= Security::escape($post['title']) ?>"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                <span class="material-symbols-outlined text-6xl">article</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($post['category']): ?>
                                            <span
                                                class="absolute top-4 left-4 px-3 py-1 bg-primary text-white text-xs font-bold rounded-full">
                                                <?= Security::escape($post['category']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-6">
                                        <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
                                            <span><?= date('M j, Y', strtotime($post['published_at'])) ?></span>
                                            <span>•</span>
                                            <span><?= Security::escape($post['author_name'] ?? 'Admin') ?></span>
                                        </div>

                                        <h2
                                            class="text-xl font-bold text-slate-900 mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                            <?= Security::escape($post['title']) ?>
                                        </h2>

                                        <?php if ($post['excerpt']): ?>
                                            <p class="text-slate-600 text-sm line-clamp-2">
                                                <?= Security::escape($post['excerpt']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="mt-4 flex items-center text-primary font-bold text-sm">
                                            Read More
                                            <span
                                                class="material-symbols-outlined text-lg ml-1 group-hover:translate-x-1 transition-transform">arrow_forward</span>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-10 flex justify-center gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&category=<?= urlencode($category) ?>"
                                    class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary hover:text-primary transition-colors">
                                    ← Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?>&category=<?= urlencode($category) ?>"
                                    class="w-10 h-10 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'bg-white border border-slate-200 hover:border-primary hover:text-primary' ?> font-medium transition-colors">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&category=<?= urlencode($category) ?>"
                                    class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary hover:text-primary transition-colors">
                                    Next →
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-16 bg-white rounded-2xl">
                        <span class="material-symbols-outlined text-6xl text-slate-300">article</span>
                        <h3 class="mt-4 text-xl font-bold text-slate-700">No posts yet</h3>
                        <p class="text-slate-500 mt-2">Check back soon for tips and inspiration!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-6">
                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-lg mb-4">Categories</h3>
                        <ul class="space-y-2">
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="/blog?category=<?= urlencode($cat['category']) ?>"
                                        class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-slate-50 <?= $category === $cat['category'] ? 'bg-primary/10 text-primary' : 'text-slate-600' ?> transition-colors">
                                        <span class="capitalize"><?= Security::escape($cat['category']) ?></span>
                                        <span class="text-xs bg-slate-100 px-2 py-0.5 rounded-full"><?= $cat['count'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Recent Posts -->
                <?php if (!empty($recentPosts)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h3 class="font-bold text-lg mb-4">Recent Posts</h3>
                        <ul class="space-y-4">
                            <?php foreach ($recentPosts as $recent): ?>
                                <li>
                                    <a href="/blog/<?= Security::escape($recent['slug']) ?>"
                                        class="flex items-start gap-3 group">
                                        <div class="w-16 h-12 rounded-lg bg-slate-100 bg-cover bg-center flex-shrink-0"
                                            style="background-image: url('<?= Security::escape($recent['featured_image'] ?? '') ?>');">
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-medium text-slate-900 line-clamp-2 group-hover:text-primary transition-colors">
                                                <?= Security::escape($recent['title']) ?>
                                            </p>
                                            <span
                                                class="text-xs text-slate-500"><?= date('M j', strtotime($recent['published_at'])) ?></span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- CTA -->
                <div class="bg-gradient-to-br from-primary to-purple-600 rounded-2xl p-6 text-white">
                    <h3 class="font-bold text-lg mb-2">Ready to create?</h3>
                    <p class="text-white/80 text-sm mb-4">Browse our stunning video invitation templates</p>
                    <a href="/templates"
                        class="inline-block w-full text-center py-2.5 bg-white text-primary font-bold rounded-lg hover:bg-slate-100 transition-colors">
                        View Templates
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>