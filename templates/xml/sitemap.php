<?php
/**
 * Dynamic XML Sitemap Generator
 * 
 * Generates a sitemap.xml from static pages and database content.
 * Route: /sitemap.xml
 */

// Load configuration and database
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Set XML header
header('Content-Type: application/xml; charset=utf-8');

// Base URL
$baseUrl = rtrim(APP_URL, '/');

// Current date for static pages
$today = date('Y-m-d');

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php
    // ===================
// STATIC PAGES
// ===================
    $staticPages = [
        ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['url' => '/templates', 'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => '/blog', 'priority' => '0.8', 'changefreq' => 'daily'],
        ['url' => '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => '/support', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => '/faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['url' => '/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['url' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ['url' => '/refund', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    foreach ($staticPages as $page): ?>
        <url>
            <loc>
                <?= htmlspecialchars($baseUrl . $page['url']) ?>
            </loc>
            <lastmod>
                <?= $today ?>
            </lastmod>
            <changefreq>
                <?= $page['changefreq'] ?>
            </changefreq>
            <priority>
                <?= $page['priority'] ?>
            </priority>
        </url>
    <?php endforeach; ?>

    <?php
    // ===================
// SERVICE PAGES (SEO Landing Pages)
// ===================
    $servicePages = [
        '/wedding-invitation-video',
        '/birthday-video-invitation-maker',
        '/baby-shower-invitation-video',
        '/save-the-date-video-maker',
        '/whatsapp-wedding-invitation-video',
        '/roka-ceremony-invitation-video',
    ];

    foreach ($servicePages as $page): ?>
        <url>
            <loc>
                <?= htmlspecialchars($baseUrl . $page) ?>
            </loc>
            <lastmod>
                <?= $today ?>
            </lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
        </url>
    <?php endforeach; ?>

    <?php
    // ===================
// CATEGORY PAGES (SEO Landing Pages)
// ===================
    $categoryPages = [
        ['url' => '/templates?category=wedding', 'priority' => '0.9'],
        ['url' => '/templates?category=birthday', 'priority' => '0.85'],
        ['url' => '/templates?category=anniversary', 'priority' => '0.85'],
        ['url' => '/templates?category=baby_shower', 'priority' => '0.85'],
        ['url' => '/templates?category=corporate', 'priority' => '0.85'],
        ['url' => '/templates?category=holi', 'priority' => '0.8'],
        ['url' => '/templates?category=diwali', 'priority' => '0.8'],
        ['url' => '/templates?category=graduation', 'priority' => '0.8'],
        ['url' => '/templates?category=farewell', 'priority' => '0.8'],
        ['url' => '/templates?category=holidays', 'priority' => '0.8'],
        ['url' => '/templates?category=housewarming', 'priority' => '0.8'],
        ['url' => '/templates?category=parties', 'priority' => '0.8'],
        ['url' => '/templates?category=religious', 'priority' => '0.8'],
        ['url' => '/templates?category=save_the_date', 'priority' => '0.8'],
    ];

    foreach ($categoryPages as $page): ?>
        <url>
            <loc>
                <?= htmlspecialchars($baseUrl . $page['url']) ?>
            </loc>
            <lastmod>
                <?= $today ?>
            </lastmod>
            <changefreq>weekly</changefreq>
            <priority><?= $page['priority'] ?></priority>
        </url>
    <?php endforeach; ?>

    <?php
    // ===================
// TRADITION PAGES (Cultural Wedding SEO)
// ===================
    $traditionPages = [
        ['url' => '/templates?tradition=hindu', 'priority' => '0.85'],
        ['url' => '/templates?tradition=muslim', 'priority' => '0.85'],
        ['url' => '/templates?tradition=christian', 'priority' => '0.85'],
        ['url' => '/templates?tradition=sikh', 'priority' => '0.85'],
        ['url' => '/templates?tradition=jewish', 'priority' => '0.8'],
        ['url' => '/templates?tradition=chinese', 'priority' => '0.8'],
        ['url' => '/templates?tradition=western', 'priority' => '0.8'],
    ];

    foreach ($traditionPages as $page): ?>
        <url>
            <loc>
                <?= htmlspecialchars($baseUrl . $page['url']) ?>
            </loc>
            <lastmod>
                <?= $today ?>
            </lastmod>
            <changefreq>weekly</changefreq>
            <priority><?= $page['priority'] ?></priority>
        </url>
    <?php endforeach; ?>

    <?php
    // ===================
// DYNAMIC TEMPLATES FROM DATABASE
// ===================
    try {
        $templates = Database::fetchAll(
            "SELECT slug, updated_at FROM templates WHERE is_active = 1 ORDER BY purchase_count DESC"
        );

        foreach ($templates as $template):
            $lastmod = $template['updated_at'] ? date('Y-m-d', strtotime($template['updated_at'])) : $today;
            ?>
            <url>
                <loc>
                    <?= htmlspecialchars($baseUrl . '/template/' . $template['slug']) ?>
                </loc>
                <lastmod>
                    <?= $lastmod ?>
                </lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.8</priority>
            </url>
        <?php endforeach;
    } catch (Exception $e) {
        // Silently fail for templates if DB error
    }
    ?>

    <?php
    // ===================
// DYNAMIC BLOG POSTS FROM DATABASE
// ===================
    try {
        $posts = Database::fetchAll(
            "SELECT slug, published_at, updated_at FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC"
        );

        foreach ($posts as $post):
            $lastmod = $post['updated_at']
                ? date('Y-m-d', strtotime($post['updated_at']))
                : ($post['published_at'] ? date('Y-m-d', strtotime($post['published_at'])) : $today);
            ?>
            <url>
                <loc>
                    <?= htmlspecialchars($baseUrl . '/blog/' . $post['slug']) ?>
                </loc>
                <lastmod>
                    <?= $lastmod ?>
                </lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.6</priority>
            </url>
        <?php endforeach;
    } catch (Exception $e) {
        // Silently fail for blog posts if DB error
    }
    ?>

</urlset>