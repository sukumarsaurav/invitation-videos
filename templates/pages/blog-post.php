<?php
/**
 * Single Blog Post Page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Get post by slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /blog');
    exit;
}

$post = Database::fetchOne(
    "SELECT p.*, u.name as author_name FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     WHERE p.slug = ? AND p.status = 'published'",
    [$slug]
);

if (!$post) {
    http_response_code(404);
    include __DIR__ . '/../errors/404.php';
    exit;
}

// Increment view count
Database::query("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?", [$post['id']]);

// Get related posts (same category)
$relatedPosts = Database::fetchAll(
    "SELECT id, title, slug, featured_image, published_at FROM blog_posts 
     WHERE status = 'published' AND id != ? AND category = ?
     ORDER BY published_at DESC LIMIT 3",
    [$post['id'], $post['category']]
);

// If not enough related, fill with recent
if (count($relatedPosts) < 3) {
    $existingIds = array_merge([$post['id']], array_column($relatedPosts, 'id'));
    $idPlaceholders = implode(',', array_fill(0, count($existingIds), '?'));
    $moreRelated = Database::fetchAll(
        "SELECT id, title, slug, featured_image, published_at FROM blog_posts 
         WHERE status = 'published' AND id NOT IN ($idPlaceholders)
         ORDER BY published_at DESC LIMIT " . (3 - count($relatedPosts)),
        $existingIds
    );
    $relatedPosts = array_merge($relatedPosts, $moreRelated);
}

$pageTitle = $post['meta_title'] ?: $post['title'];
$pageDescription = $post['meta_description'] ?: $post['excerpt'];
$shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/blog/' . $post['slug'];
?>

<?php ob_start(); ?>

<!-- Article Header -->
<article class="max-w-4xl mx-auto px-4 py-8 md:py-16">
    <!-- Breadcrumbs -->
    <nav class="mb-6">
        <ol class="flex items-center gap-2 text-sm text-slate-500">
            <li><a href="/" class="hover:text-primary">Home</a></li>
            <li><span class="material-symbols-outlined text-xs">chevron_right</span></li>
            <li><a href="/blog" class="hover:text-primary">Blog</a></li>
            <?php if ($post['category']): ?>
                <li><span class="material-symbols-outlined text-xs">chevron_right</span></li>
                <li><a href="/blog?category=<?= urlencode($post['category']) ?>"
                        class="hover:text-primary capitalize"><?= Security::escape($post['category']) ?></a></li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Title -->
    <header class="mb-8">
        <?php if ($post['category']): ?>
            <a href="/blog?category=<?= urlencode($post['category']) ?>"
                class="inline-block px-3 py-1 bg-primary/10 text-primary text-xs font-bold rounded-full mb-4 hover:bg-primary/20 transition-colors capitalize">
                <?= Security::escape($post['category']) ?>
            </a>
        <?php endif; ?>

        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight mb-6">
            <?= Security::escape($post['title']) ?>
        </h1>

        <div class="flex items-center gap-4 text-sm text-slate-500">
            <div class="flex items-center gap-2">
                <div
                    class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold">
                    <?= strtoupper(substr($post['author_name'] ?? 'A', 0, 1)) ?>
                </div>
                <span class="font-medium text-slate-700"><?= Security::escape($post['author_name'] ?? 'Admin') ?></span>
            </div>
            <span>•</span>
            <time datetime="<?= $post['published_at'] ?>"><?= date('F j, Y', strtotime($post['published_at'])) ?></time>
            <span>•</span>
            <span><?= number_format($post['view_count']) ?> views</span>
        </div>
    </header>

    <!-- Featured Image -->
    <?php if ($post['featured_image']): ?>
        <figure class="mb-10">
            <div class="aspect-video rounded-2xl overflow-hidden bg-slate-100">
                <img src="<?= Security::escape($post['featured_image']) ?>" alt="<?= Security::escape($post['title']) ?>"
                    class="w-full h-full object-cover" width="896" height="504" loading="eager">
            </div>
        </figure>
    <?php endif; ?>

    <!-- Content -->
    <div class="prose prose-lg max-w-none prose-headings:font-bold prose-headings:text-slate-900 
                prose-p:text-slate-600 prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                prose-img:rounded-xl prose-strong:text-slate-900">
        <?= $post['content'] ?>
    </div>

    <!-- Share Buttons -->
    <div class="mt-10 pt-8 border-t border-slate-200">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <span class="font-bold text-slate-700">Share this article:</span>
            <div class="flex items-center gap-3">
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($post['title']) ?>"
                    target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-blue-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                    </svg>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-blue-600 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                    </svg>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($shareUrl) ?>&title=<?= urlencode($post['title']) ?>"
                    target="_blank"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-blue-700 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                    </svg>
                </a>
                <button
                    onclick="navigator.clipboard.writeText(window.location.href); this.innerHTML='<span class=\'material-symbols-outlined\'>check</span>'"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                    <span class="material-symbols-outlined">link</span>
                </button>
            </div>
        </div>
    </div>
</article>

<!-- Internal Linking CTA -->
<div class="max-w-4xl mx-auto px-4 mb-16">
    <?php
    // Determine CTA based on category or default to Wedding
    $ctaLink = '/wedding-invitation-video';
    $ctaTitle = 'Create Your Wedding Invitation Video';
    $ctaDesc = 'Choose from hundreds of stunning wedding templates and create your video in minutes.';
    $ctaBtn = 'Create Wedding Invite';

    // Map categories to new service pages
    $categoryLower = strtolower($post['category'] ?? '');

    if (strpos($categoryLower, 'birth') !== false) {
        $ctaLink = '/birthday-video-invitation-maker';
        $ctaTitle = 'Planning a Birthday Party?';
        $ctaDesc = 'Create a fun and exciting birthday video invitation that will get everyone talking!';
        $ctaBtn = 'Create Birthday Invite';
    } elseif (strpos($categoryLower, 'baby') !== false || strpos($categoryLower, 'shower') !== false) {
        $ctaLink = '/baby-shower-invitation-video';
        $ctaTitle = 'Is it a Boy or Girl?';
        $ctaDesc = 'Announce the big news with our adorable Baby Shower and Gender Reveal video templates.';
        $ctaBtn = 'Create Baby Shower Invite';
    } elseif (strpos($categoryLower, 'save') !== false || strpos($categoryLower, 'date') !== false) {
        $ctaLink = '/save-the-date-video-maker';
        $ctaTitle = 'Announce Your Date in Style';
        $ctaDesc = 'Send a Save the Date video that tells your love story before the formal invitation.';
        $ctaBtn = 'Create Save the Date';
    } elseif (strpos($categoryLower, 'roka') !== false) {
        $ctaLink = '/roka-ceremony-invitation-video';
        $ctaTitle = 'Roka Ceremony Coming Up?';
        $ctaDesc = 'Invite your loved ones to bless your union with a traditional Roka ceremony video card.';
        $ctaBtn = 'Create Roka Invite';
    }
    ?>

    <div
        class="bg-gradient-to-r from-primary/10 to-purple-500/10 rounded-2xl p-8 md:p-12 text-center border border-primary/20">
        <h3 class="text-2xl md:text-3xl font-bold text-slate-900 mb-4"><?= $ctaTitle ?></h3>
        <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto"><?= $ctaDesc ?></p>
        <a href="<?= $ctaLink ?>"
            class="inline-flex items-center gap-2 px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all">
            <?= $ctaBtn ?>
            <span class="material-symbols-outlined">arrow_forward</span>
        </a>
    </div>
</div>

<!-- Related Posts -->
<?php if (!empty($relatedPosts)): ?>
    <section class="bg-slate-50 py-12 md:py-16">
        <div class="max-w-6xl mx-auto px-4">
            <h2 class="text-2xl font-bold mb-8">Related Articles</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($relatedPosts as $related): ?>
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow group">
                        <a href="/blog/<?= Security::escape($related['slug']) ?>" class="block">
                            <div class="aspect-video bg-slate-100 relative overflow-hidden">
                                <?php if ($related['featured_image']): ?>
                                    <img src="<?= Security::escape($related['featured_image']) ?>"
                                        alt="<?= Security::escape($related['title']) ?>"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        width="400" height="225" loading="lazy" decoding="async">
                                <?php endif; ?>
                            </div>
                            <div class="p-5">
                                <time
                                    class="text-xs text-slate-500"><?= date('M j, Y', strtotime($related['published_at'])) ?></time>
                                <h3
                                    class="font-bold text-slate-900 mt-2 group-hover:text-primary transition-colors line-clamp-2">
                                    <?= Security::escape($related['title']) ?>
                                </h3>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- CTA -->
<section class="py-12 md:py-16">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Ready to create your video invitation?</h2>
        <p class="text-slate-600 mb-6">Browse our stunning templates and bring your vision to life</p>
        <a href="/templates"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined">explore</span>
            Browse Templates
        </a>
    </div>
</section>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .prose {
        line-height: 1.8;
    }

    .prose h2 {
        margin-top: 2em;
        margin-bottom: 0.5em;
    }

    .prose h3 {
        margin-top: 1.5em;
        margin-bottom: 0.5em;
    }

    .prose p {
        margin-bottom: 1.25em;
    }

    .prose ul,
    .prose ol {
        margin-bottom: 1.25em;
        padding-left: 1.5em;
    }

    .prose li {
        margin-bottom: 0.5em;
    }

    /* Layout Block Styles - Responsive */
    .prose .blog-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .prose .blog-col {
        flex: 1 1 300px;
        min-width: 0;
    }

    .prose .blog-col img {
        width: 100%;
        height: auto;
        border-radius: 0.75rem;
        object-fit: cover;
    }

    .prose .blog-callout {
        padding: 1.25rem 1.5rem;
        border-radius: 0.75rem;
        margin: 2rem 0;
    }

    .prose .blog-callout p {
        margin-bottom: 0;
    }

    .prose .blog-callout-info {
        background: #e0f2fe;
        border-left: 4px solid #0ea5e9;
        color: #0c4a6e;
    }

    .prose .blog-callout-tip {
        background: #dcfce7;
        border-left: 4px solid #22c55e;
        color: #14532d;
    }

    .prose .blog-callout-warning {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        color: #78350f;
    }

    /* Stack columns on mobile */
    @media (max-width: 640px) {
        .prose .blog-row {
            flex-direction: column;
        }

        .prose .blog-col {
            flex: 1 1 100%;
        }
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>