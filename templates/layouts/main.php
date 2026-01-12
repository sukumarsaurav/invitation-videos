<?php
// =====================================================
// Load categories and subcategories from consolidated table
// =====================================================
$mainCategories = [];
$categorySubcategories = [];

try {
    // Get all categories with their parent relationship
    $allCategories = Database::fetchAll(
        "SELECT c.*, p.slug as parent_slug, p.name as parent_name 
         FROM categories c 
         LEFT JOIN categories p ON c.parent_id = p.id 
         WHERE c.is_active = 1 
         ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.display_order"
    ) ?? [];

    // Organize into main categories and subcategories
    foreach ($allCategories as $cat) {
        if ($cat['parent_id'] === null) {
            // Main category
            $mainCategories[$cat['slug']] = [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'icon' => $cat['icon'],
                'color' => $cat['color'],
                'image' => $cat['image_url'] ?? '/assets/images/categories/' . $cat['slug'] . '.png',
            ];
            $categorySubcategories[$cat['slug']] = [];
        } else {
            // Subcategory - add to parent
            $parentSlug = $cat['parent_slug'];
            if ($parentSlug) {
                $categorySubcategories[$parentSlug][] = [
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'icon' => $cat['icon'],
                ];
            }
        }
    }
} catch (Exception $e) {
    // Categories table may not exist or be empty, use fallback
    $mainCategories = [
        'wedding' => ['id' => 1, 'name' => 'Wedding', 'slug' => 'wedding', 'icon' => 'favorite', 'color' => '#ec4899', 'image' => '/assets/images/categories/wedding.png'],
        'birthday' => ['id' => 2, 'name' => 'Birthday', 'slug' => 'birthday', 'icon' => 'cake', 'color' => '#f59e0b', 'image' => '/assets/images/categories/birthday.png'],
        'party' => ['id' => 3, 'name' => 'Party', 'slug' => 'party', 'icon' => 'celebration', 'color' => '#10b981', 'image' => '/assets/images/categories/parties.png'],
        'pooja-rituals' => ['id' => 4, 'name' => 'Pooja & Rituals', 'slug' => 'pooja-rituals', 'icon' => 'self_improvement', 'color' => '#8b5cf6', 'image' => '/assets/images/categories/religious.png'],
        'festivals' => ['id' => 5, 'name' => 'Festivals', 'slug' => 'festivals', 'icon' => 'festival', 'color' => '#ef4444', 'image' => '/assets/images/categories/holidays.png'],
        'miscellaneous' => ['id' => 6, 'name' => 'Miscellaneous', 'slug' => 'miscellaneous', 'icon' => 'apps', 'color' => '#6b7280', 'image' => null],
    ];
    $categorySubcategories = [];
}



// Fetch CMS theme settings
$cmsSettings = [];
try {
    $cmsSettingsRows = Database::fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'hero_%' OR setting_key LIKE 'theme_%' OR setting_key LIKE 'category_%' OR setting_key LIKE 'header_%' OR setting_key LIKE 'footer_%'");
    foreach ($cmsSettingsRows as $row) {
        $cmsSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Settings table may not exist yet
}

// Default CMS values
$themePrimaryColor = $cmsSettings['theme_primary_color'] ?? '#7f13ec';
$themeTextPrimary = $cmsSettings['theme_text_primary'] ?? '#0f172a';
$themeTextSecondary = $cmsSettings['theme_text_secondary'] ?? '#64748b';
$themeBgLight = $cmsSettings['theme_bg_light'] ?? '#f7f6f8';
$themeBgDark = $cmsSettings['theme_bg_dark'] ?? '#191022';
$heroImageDesktop = $cmsSettings['hero_image_desktop'] ?? '';
$heroImageMobile = $cmsSettings['hero_image_mobile'] ?? '';
$heroTitle = $cmsSettings['hero_title'] ?? 'Create Beautiful <span class="text-primary">Invitation Videos</span>';
$heroSubtitle = $cmsSettings['hero_subtitle'] ?? 'Stunning video invitations for weddings, birthdays, and special events. Easy to customize, ready to share.';
$categoryDisplayMode = $cmsSettings['category_display_mode'] ?? 'icon';
// Header colors
$headerBgColor = $cmsSettings['header_bg_color'] ?? '#ffffff';
$headerTextColor = $cmsSettings['header_text_color'] ?? '#1e293b';
$headerHoverColor = $cmsSettings['header_hover_color'] ?? '#7f13ec';
// Footer colors
$footerBgColor = $cmsSettings['footer_bg_color'] ?? '#ffffff';
$footerTextColor = $cmsSettings['footer_text_color'] ?? '#1e293b';
$footerHoverColor = $cmsSettings['footer_hover_color'] ?? '#7f13ec';
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <!-- Google Tag Manager (Delayed for Performance) -->
    <script>
        window.dataLayer = window.dataLayer || [];

        // Delayed GTM loader - fires on interaction or 3s timeout
        (function () {
            var gtmLoaded = false;
            function loadGTM() {
                if (gtmLoaded) return;
                gtmLoaded = true;

                dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                var script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtm.js?id=GTM-NGZWTLGW';
                document.head.appendChild(script);
            }

            // Load on user interaction (scroll, click, touch, keypress)
            ['scroll', 'click', 'touchstart', 'keydown'].forEach(function (event) {
                window.addEventListener(event, loadGTM, { once: true, passive: true });
            });

            // Fallback: load after 3 seconds regardless
            setTimeout(loadGTM, 3000);
        })();
    </script>
    <!-- End Google Tag Manager -->

    <!-- Social Media Schema (Entity SEO) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?php echo APP_NAME ?? 'InvitationVideos'; ?>",
      "url": "https://invitationvideos.com",
      "logo": "https://invitationvideos.com/assets/images/inivitationVideoslogo.png",
      "sameAs": [
        "<?= SOCIAL_FACEBOOK ?>",
        "<?= SOCIAL_INSTAGRAM ?>",
        "<?= SOCIAL_TWITTER ?>",
        "<?= SOCIAL_YOUTUBE ?>"
      ]
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Invitation Videos",
      "alternateName": "InvitationVideos",
      "url": "https://invitationvideos.com/",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "https://invitationvideos.com/templates?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Service",
      "serviceType": "Invitation Video Maker",
      "provider": {
        "@type": "Organization",
        "name": "InvitationVideos",
        "url": "https://invitationvideos.com"
      },
      "areaServed": "Worldwide",
      "description": "Create stunning Full HD invitation videos for weddings, birthdays, and special events using professional templates."
    }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Smart Title Logic
    $finalTitle = $pageTitle ?? 'Invitation Videos - Create Stunning Video Invitations';
    if (isset($pageTitle) && strpos($pageTitle, 'Invitation Videos') === false) {
        $finalTitle .= ' | Invitation Videos';
    }
    ?>
    <title><?= $finalTitle ?></title>

    <!-- SEO Meta Tags -->
    <?php
    // Generate canonical URL (always use non-www, remove trailing slash except for homepage)
    $canonicalPath = strtok($_SERVER['REQUEST_URI'], '?');
    $canonicalPath = rtrim($canonicalPath, '/');
    if (empty($canonicalPath)) {
        $canonicalPath = '/';
    }
    $canonicalUrl = 'https://invitationvideos.com' . $canonicalPath;

    // Include important query params for category/filter pages
    $seoParams = [];
    if (!empty($_GET['category'])) {
        $seoParams['category'] = $_GET['category'];
    }
    if (!empty($_GET['tradition'])) {
        $seoParams['tradition'] = $_GET['tradition'];
    }
    if (!empty($seoParams)) {
        $canonicalUrl .= '?' . http_build_query($seoParams);
    }

    // Default OG image
    $defaultOgImage = 'https://invitationvideos.com/assets/images/og-default.jpg';
    $ogImageUrl = $ogImage ?? $defaultOgImage;

    // Default meta description
    $defaultDescription = 'Create stunning video invitations for weddings, birthdays, and special events. Easy customization, professional quality.';
    $finalDescription = $metaDescription ?? $defaultDescription;
    ?>
    <meta name="description" content="<?= htmlspecialchars($finalDescription) ?>">
    <meta name="author" content="Sukumar Saurav - NeoWebX.com">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= $canonicalUrl ?>">

    <!-- Open Graph Tags (Facebook, LinkedIn, etc.) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($finalTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($finalDescription) ?>">
    <meta property="og:image" content="<?= $ogImageUrl ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="InvitationVideos">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@invitationvids">
    <meta name="twitter:title" content="<?= htmlspecialchars($finalTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($finalDescription) ?>">
    <meta name="twitter:image" content="<?= $ogImageUrl ?>">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="shortcut icon" href="/favicon.ico">

    <!-- Critical CSS Inline - Immediate render without blocking -->
    <style>
        /* Dynamic Theme Colors from CMS */
        :root {
            --color-primary:
                <?= htmlspecialchars($themePrimaryColor) ?>
            ;
            --color-text-primary:
                <?= htmlspecialchars($themeTextPrimary) ?>
            ;
            --color-text-secondary:
                <?= htmlspecialchars($themeTextSecondary) ?>
            ;
            --color-bg-light:
                <?= htmlspecialchars($themeBgLight) ?>
            ;
            --color-bg-dark:
                <?= htmlspecialchars($themeBgDark) ?>
            ;
            --header-bg-color:
                <?= htmlspecialchars($headerBgColor) ?>
            ;
            --header-text-color:
                <?= htmlspecialchars($headerTextColor) ?>
            ;
            --header-hover-color:
                <?= htmlspecialchars($headerHoverColor) ?>
            ;
            --footer-bg-color:
                <?= htmlspecialchars($footerBgColor) ?>
            ;
            --footer-text-color:
                <?= htmlspecialchars($footerTextColor) ?>
            ;
            --footer-hover-color:
                <?= htmlspecialchars($footerHoverColor) ?>
            ;
        }

        /* Critical CSS for above-the-fold content */
        *,
        ::after,
        ::before {
            box-sizing: border-box;
            border: 0 solid #e5e7eb
        }

        html {
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
            font-family: Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif
        }

        body {
            margin: 0;
            line-height: inherit;
            background-color: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column
        }

        /* Fix Chrome H1UserAgentFontSizeInSection deprecation */
        h1 {
            font-size: 2em;
            margin: 0.67em 0
        }

        img,
        video {
            max-width: 100%;
            height: auto;
            display: block
        }

        .font-display {
            font-family: Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif
        }

        .bg-background-light {
            background-color: #f8fafc
        }

        .text-slate-900 {
            color: #0f172a
        }

        .min-h-screen {
            min-height: 100vh
        }

        .flex {
            display: flex
        }

        .flex-col {
            flex-direction: column
        }

        .items-center {
            align-items: center
        }

        .justify-between {
            justify-content: space-between
        }

        .gap-2 {
            gap: .5rem
        }

        .gap-4 {
            gap: 1rem
        }

        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem
        }

        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem
        }

        .h-16 {
            height: 4rem
        }

        .max-w-7xl {
            max-width: 80rem
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto
        }

        .sticky {
            position: sticky
        }

        .top-0 {
            top: 0
        }

        .z-50 {
            z-index: 50
        }

        .bg-white {
            background-color: #fff
        }

        .border-b {
            border-bottom-width: 1px
        }

        .border-slate-200 {
            border-color: #e2e8f0
        }

        .shadow-sm {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, .05)
        }

        .rounded-lg {
            border-radius: .5rem
        }

        .rounded-xl {
            border-radius: .75rem
        }

        .rounded-2xl {
            border-radius: 1rem
        }

        .font-bold {
            font-weight: 700
        }

        .text-lg {
            font-size: 1.125rem;
            line-height: 1.75rem
        }

        .text-xl {
            font-size: 1.25rem;
            line-height: 1.75rem
        }

        .text-sm {
            font-size: .875rem;
            line-height: 1.25rem
        }

        .hidden {
            display: none
        }

        .block {
            display: block
        }

        .w-full {
            width: 100%
        }

        .h-full {
            height: 100%
        }

        .object-cover {
            object-fit: cover
        }

        .overflow-hidden {
            overflow: hidden
        }

        .absolute {
            position: absolute
        }

        .relative {
            position: relative
        }

        .inset-0 {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0
        }

        .aspect-\[4\/5\] {
            aspect-ratio: 4/5
        }

        .grid {
            display: grid
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .gap-6 {
            gap: 1.5rem
        }

        .p-4 {
            padding: 1rem
        }

        .mb-4 {
            margin-bottom: 1rem
        }

        .mb-6 {
            margin-bottom: 1.5rem
        }

        .text-primary {
            color: var(--color-primary, #7f13ec)
        }

        .bg-primary {
            background-color: var(--color-primary, #7f13ec)
        }

        .text-white {
            color: #fff
        }

        @media(min-width:640px) {
            .sm\:px-6 {
                padding-left: 1.5rem;
                padding-right: 1.5rem
            }
        }

        @media(min-width:769px) {
            .sm\:block {
                display: block !important
            }

            .sm\:hidden {
                display: none
            }

            .sm\:grid {
                display: grid
            }

            .md\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr))
            }
        }

        @media(min-width:1024px) {
            .lg\:flex {
                display: flex
            }

            .lg\:hidden {
                display: none
            }

            .lg\:px-8 {
                padding-left: 2rem;
                padding-right: 2rem
            }

            .lg\:block {
                display: block !important
            }
        }

        /* Alpine.js cloak - hide elements until Alpine is initialized */
        [x-cloak] {
            display: none !important
        }

        /* Footer content - always visible on desktop (>= 769px) */
        @media(min-width:769px) {

            .footer-content,
            .footer-content[x-cloak] {
                display: block !important
            }
        }

        /* Loading state for fonts - stable dimensions to prevent CLS */
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined', sans-serif;
            font-size: 24px;
            display: inline-block;
            width: 1em;
            height: 1em;
            min-width: 24px;
            min-height: 24px;
            line-height: 1;
            vertical-align: middle;
            overflow: hidden
        }

        /* Footer toggle animation utilities */
        .rotate-180 {
            transform: rotate(180deg)
        }

        .transition-transform {
            transition-property: transform;
            transition-timing-function: cubic-bezier(.4, 0, .2, 1);
            transition-duration: .3s
        }

        .duration-300 {
            transition-duration: .3s
        }

        /* Hide scrollbar for mobile category scroll */
        .category-scroll-container::-webkit-scrollbar {
            display: none
        }
    </style>

    <!-- Self-hosted fonts - preload for fast text rendering -->
    <link rel="preload" href="/assets/fonts/plus-jakarta-sans-variable.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Preconnect for CDN -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <!-- Main CSS - loaded async to prevent render blocking -->
    <link rel="preload" href="/assets/css/app.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="/assets/css/app.css">
    </noscript>

    <!-- Section Positioning CSS for responsive homepage banners -->
    <link rel="stylesheet" href="/assets/css/section-positioning.css">

    <!-- Dynamic Theme Color Overrides (loaded after app.css) -->
    <style>
        /* Override Tailwind's hardcoded primary colors with CSS variables */
        .text-primary {
            color: var(--color-primary) !important;
        }

        .bg-primary {
            background-color: var(--color-primary) !important;
        }

        .border-primary {
            border-color: var(--color-primary) !important;
        }

        .border-l-primary {
            border-left-color: var(--color-primary) !important;
        }

        .from-primary {
            --tw-gradient-from: var(--color-primary) !important;
        }

        .to-primary {
            --tw-gradient-to: var(--color-primary) !important;
        }

        .ring-primary {
            --tw-ring-color: var(--color-primary) !important;
        }

        .shadow-primary\/25 {
            --tw-shadow-color: color-mix(in srgb, var(--color-primary) 25%, transparent) !important;
        }

        .shadow-primary\/30 {
            --tw-shadow-color: color-mix(in srgb, var(--color-primary) 30%, transparent) !important;
        }

        .hover\:text-primary:hover {
            color: var(--color-primary) !important;
        }

        .hover\:bg-primary:hover {
            background-color: var(--color-primary) !important;
        }

        .hover\:bg-primary\/90:hover {
            background-color: color-mix(in srgb, var(--color-primary) 90%, transparent) !important;
        }

        .hover\:bg-primary\/10:hover {
            background-color: color-mix(in srgb, var(--color-primary) 10%, transparent) !important;
        }

        .hover\:border-primary:hover {
            border-color: var(--color-primary) !important;
        }

        .hover\:border-primary\/30:hover {
            border-color: color-mix(in srgb, var(--color-primary) 30%, transparent) !important;
        }

        .focus\:ring-primary:focus {
            --tw-ring-color: var(--color-primary) !important;
        }

        .focus\:border-primary:focus {
            border-color: var(--color-primary) !important;
        }

        .group:hover .group-hover\:text-primary {
            color: var(--color-primary) !important;
        }

        .bg-primary\/5 {
            background-color: color-mix(in srgb, var(--color-primary) 5%, transparent) !important;
        }

        .bg-primary\/10 {
            background-color: color-mix(in srgb, var(--color-primary) 10%, transparent) !important;
        }

        .bg-primary\/20 {
            background-color: color-mix(in srgb, var(--color-primary) 20%, transparent) !important;
        }

        .from-primary\/5 {
            --tw-gradient-from: color-mix(in srgb, var(--color-primary) 5%, transparent) !important;
        }

        .from-primary\/10 {
            --tw-gradient-from: color-mix(in srgb, var(--color-primary) 10%, transparent) !important;
        }

        .dark\:from-primary\/10 {
            --tw-gradient-from: color-mix(in srgb, var(--color-primary) 10%, transparent) !important;
        }

        .dark\:from-primary\/20 {
            --tw-gradient-from: color-mix(in srgb, var(--color-primary) 20%, transparent) !important;
        }

        /* Header Colors */
        header a {
            color: var(--header-text-color, inherit);
        }

        header a:hover {
            color: var(--header-hover-color, var(--color-primary)) !important;
        }

        header .text-slate-600,
        header .text-slate-700,
        header .text-slate-900 {
            color: var(--header-text-color) !important;
        }

        /* Footer Colors */
        footer a {
            color: var(--footer-text-color, inherit);
        }

        footer a:hover,
        footer a.hover\:text-primary:hover {
            color: var(--footer-hover-color, var(--color-primary)) !important;
        }

        footer .text-slate-500,
        footer .text-slate-600,
        footer .text-slate-700 {
            color: var(--footer-text-color) !important;
        }

        /* Footer divider uses footer text color */
        footer .border-t {
            border-color: var(--footer-text-color) !important;
            opacity: 0.3;
        }
    </style>

    <!-- Material Symbols - preload for fast icon rendering (CLS fix) -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1&display=swap"
        as="style" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1&display=swap"
        media="print" onload="this.media='all'" crossorigin>
    <noscript>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1&display=swap"
            crossorigin>
    </noscript>

    <!-- Alpine.js Collapse Plugin + Core - deferred -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>

<body
    class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display min-h-screen flex flex-col">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NGZWTLGW" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Header -->
    <header class="sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800 backdrop-blur-sm shadow-sm"
        style="background-color: var(--header-bg-color, #ffffff); color: var(--header-text-color, #1e293b);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left Section: Mobile Menu + Logo -->
                <div class="flex items-center gap-2 lg:gap-8 flex-1">
                    <?php if (($isGalleryPage ?? false) && !empty($galleryCategories)): ?>
                        <!-- Mobile: Back Arrow (Gallery pages only) -->
                        <a href="/"
                            class="sm:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300"
                            aria-label="Back to home">
                            <span class="material-symbols-outlined text-2xl">arrow_back</span>
                        </a>
                    <?php elseif ($isTemplateDetailPage ?? false): ?>
                        <!-- Mobile: Back Arrow (Template detail pages) -->
                        <a href="<?= $templateBackUrl ?? '/templates' ?>"
                            class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300"
                            aria-label="Go back">
                            <span class="material-symbols-outlined text-2xl">arrow_back</span>
                        </a>
                    <?php elseif ($showBackButton ?? false): ?>
                        <!-- Mobile: Back Arrow (Auth pages) -->
                        <a href="/"
                            class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300"
                            aria-label="Go back">
                            <span class="material-symbols-outlined text-2xl">arrow_back</span>
                        </a>
                    <?php else: ?>
                        <!-- Mobile Menu Button (Left) -->
                        <button onclick="openMobileDrawer()"
                            class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300"
                            aria-label="Open menu">
                            <span class="material-symbols-outlined text-2xl">menu</span>
                        </button>
                    <?php endif; ?>

                    <!-- Logo -->
                    <a href="/" class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        <img src="/assets/images/inivitationVideoslogo.png" alt="<?= APP_NAME ?? 'InvitationVideos' ?>"
                            class="h-9 sm:h-10 w-auto" width="40" height="40" loading="eager" fetchpriority="high">
                        <h2 class="hidden sm:block text-lg sm:text-xl font-bold leading-tight tracking-tight">
                            <?= APP_NAME ?? 'Invitation Videos' ?>
                        </h2>
                    </a>

                    <?php if (($isGalleryPage ?? false) && !empty($galleryCategories)): ?>
                        <!-- Mobile: Title + Count after logo -->
                        <div class="sm:hidden flex flex-col">
                            <span class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                <?= ($currentCategory ?? null) && isset($galleryCategories[$currentCategory])
                                    ? $galleryCategories[$currentCategory]['name']
                                    : 'All Templates' ?>
                            </span>
                            <span class="text-xs text-slate-500"><?= $galleryTotalTemplates ?? 0 ?> Items</span>
                        </div>
                    <?php elseif ($isTemplateDetailPage ?? false): ?>
                        <!-- Mobile: Template name after logo -->
                        <span
                            class="lg:hidden text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[150px] sm:max-w-[200px]">
                            <?= Security::escape($templateTitle ?? '') ?>
                        </span>
                    <?php elseif ($mobilePageTitle ?? false): ?>
                        <!-- Mobile: Page title (auth pages) -->
                        <span class="lg:hidden text-sm font-bold text-slate-900 dark:text-white">
                            <?= Security::escape($mobilePageTitle) ?>
                        </span>
                    <?php elseif (isset($_GET['active']) && !empty($mainCategories)): ?>
                        <!-- Mobile: Category name on categories page -->
                        <?php
                        $activeCatSlug = $_GET['active'];
                        $activeCatName = isset($mainCategories[$activeCatSlug]) ? $mainCategories[$activeCatSlug]['name'] : ucfirst($activeCatSlug);
                        ?>
                        <span class="sm:hidden text-sm font-bold text-slate-900 dark:text-white truncate max-w-[150px]">
                            <?= Security::escape($activeCatName) ?>
                        </span>
                    <?php endif; ?>

                    <!-- Desktop Navigation with Mega Menu -->
                    <nav class="hidden lg:flex items-center gap-6">
                        <?php
                        // Display each main category as a dropdown (excluding Miscellaneous from main nav)
                        foreach ($mainCategories as $catSlug => $category):
                            if ($catSlug === 'miscellaneous')
                                continue; // Handle miscellaneous separately
                            $subcats = $categorySubcategories[$catSlug] ?? [];
                            ?>
                            <div class="relative group" id="<?= $catSlug ?>-mega-menu">
                                <button
                                    class="flex items-center gap-1 text-sm font-medium text-slate-700 hover:text-primary transition-colors py-3">
                                    <?= Security::escape($category['name']) ?>
                                    <span
                                        class="material-symbols-outlined text-base transition-transform group-hover:rotate-180">expand_more</span>
                                </button>

                                <div class="absolute left-0 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
                                    style="width: 280px;">
                                    <div
                                        class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 p-4 max-h-[400px] overflow-y-auto">
                                        <?php if (!empty($subcats)): ?>
                                            <ul class="space-y-1">
                                                <?php foreach (array_slice($subcats, 0, 20) as $subcat): ?>
                                                    <li>
                                                        <a href="/templates?category=<?= $subcat['slug'] ?>"
                                                            class="block py-2 px-3 text-sm text-slate-600 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors">
                                                            <?= Security::escape($subcat['name']) ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <div class="border-t border-slate-100 dark:border-slate-800 mt-3 pt-3">
                                            <a href="/templates?category=<?= $catSlug ?>"
                                                class="block py-2 px-3 text-sm text-primary font-medium hover:bg-primary/5 rounded-lg transition-colors">
                                                View All <?= Security::escape($category['name']) ?> Templates →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Miscellaneous Dropdown -->
                        <?php if (isset($mainCategories['miscellaneous'])): ?>
                            <div class="relative group" id="misc-mega-menu">
                                <button
                                    class="flex items-center gap-1 text-sm font-medium text-slate-700 hover:text-primary transition-colors py-3">
                                    Miscellaneous
                                    <span
                                        class="material-symbols-outlined text-base transition-transform group-hover:rotate-180">expand_more</span>
                                </button>
                                <div class="absolute right-0 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
                                    style="width: 280px;">
                                    <div
                                        class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 p-4">
                                        <ul class="space-y-1">
                                            <?php foreach ($categorySubcategories['miscellaneous'] ?? [] as $subcat): ?>
                                                <li>
                                                    <a href="/templates?category=<?= $subcat['slug'] ?>"
                                                        class="block py-2 px-3 text-sm text-slate-600 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors">
                                                        <?= Security::escape($subcat['name']) ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                            <li>
                                                <a href="/templates"
                                                    class="block py-2 px-3 text-sm text-primary font-medium hover:bg-primary/5 rounded-lg transition-colors">
                                                    View All Templates →
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </nav>

                </div>

                <!-- Right Section -->
                <div class="flex items-center gap-4">
                    <!-- Desktop Auth -->
                    <div class="hidden lg:flex items-center gap-4">
                        <?php if (isset($_SESSION['user_id'])):
                            $userAvatar = $_SESSION['user_avatar'] ?? '';
                            $userName = $_SESSION['user_name'] ?? 'User';
                            $userInitial = strtoupper(substr($userName, 0, 1));
                            ?>
                            <!-- Profile Dropdown -->
                            <div class="relative group">
                                <button
                                    class="flex items-center gap-2 p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <?php if ($userAvatar): ?>
                                        <img src="<?= Security::escape($userAvatar) ?>" alt="Profile"
                                            class="w-9 h-9 rounded-full object-cover border-2 border-primary/20" width="36"
                                            height="36">
                                    <?php else: ?>
                                        <div
                                            class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold">
                                            <?= $userInitial ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="material-symbols-outlined text-slate-400 text-lg">expand_more</span>
                                </button>

                                <!-- Dropdown Menu -->
                                <div
                                    class="absolute right-0 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <div
                                        class="bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-2 min-w-[200px]">
                                        <!-- User Info -->
                                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                            <p class="font-medium text-slate-900 dark:text-white">
                                                <?= Security::escape($userName) ?>
                                            </p>
                                            <p class="text-xs text-slate-500 truncate">
                                                <?= Security::escape($_SESSION['user_email'] ?? '') ?>
                                            </p>
                                        </div>

                                        <a href="/profile"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                            <span class="material-symbols-outlined text-lg">person</span>
                                            Profile
                                        </a>
                                        <a href="/my-orders"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                            <span class="material-symbols-outlined text-lg">shopping_bag</span>
                                            My Orders
                                        </a>
                                        <a href="/wishlist"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                            <span class="material-symbols-outlined text-lg">favorite</span>
                                            My Wishlist
                                        </a>
                                        <a href="/my-tickets"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                            <span class="material-symbols-outlined text-lg">support_agent</span>
                                            My Tickets
                                        </a>

                                        <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>

                                        <a href="/logout"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            <span class="material-symbols-outlined text-lg">logout</span>
                                            Sign Out
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="/register"
                                class="flex h-10 items-center justify-center rounded-lg bg-primary px-5 text-sm font-bold text-white shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all">
                                Get Started
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile Profile Icon (Right) -->
                    <div class="lg:hidden">
                        <?php if (isset($_SESSION['user_id'])):
                            $userAvatar = $_SESSION['user_avatar'] ?? '';
                            $userName = $_SESSION['user_name'] ?? 'User';
                            $userInitial = strtoupper(substr($userName, 0, 1));
                            ?>
                            <a href="/profile" class="block p-1">
                                <?php if ($userAvatar): ?>
                                    <img src="<?= Security::escape($userAvatar) ?>" alt="Profile"
                                        class="w-9 h-9 rounded-full object-cover border-2 border-primary/20" width="36"
                                        height="36">
                                <?php else: ?>
                                    <div
                                        class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                                        <?= $userInitial ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <a href="/login"
                                class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300"
                                aria-label="Login">
                                <span class="material-symbols-outlined text-2xl">person</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if (($isGalleryPage ?? false) && !empty($galleryCategories)): ?>
        <!-- Mobile Category Scroll Bar (only on gallery pages, < 768px) -->
        <div class="sm:hidden sticky top-16 z-40 bg-white dark:bg-slate-900">
            <div class="category-scroll-container overflow-x-auto px-4 py-3"
                style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                <div class="flex gap-3" style="min-width: max-content;">
                    <!-- All Templates -->
                    <a href="/templates"
                        class="flex flex-col items-center flex-shrink-0 <?= !($currentCategory ?? null) ? 'category-selected' : '' ?>">
                        <div
                            class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-1 <?= !($currentCategory ?? null) ? 'border-2 border-primary' : 'border-2 border-transparent' ?>">
                            <span
                                class="material-symbols-outlined text-2xl text-slate-600 dark:text-slate-300">grid_view</span>
                        </div>
                        <span
                            class="text-[10px] font-medium <?= !($currentCategory ?? null) ? 'text-primary' : 'text-slate-700 dark:text-slate-300' ?> text-center leading-tight">All</span>
                    </a>
                    <!-- Category Images from Config -->
                    <?php foreach ($galleryCategories as $slug => $cat): ?>
                        <a href="/templates?category=<?= $slug ?>" class="flex flex-col items-center flex-shrink-0">
                            <div
                                class="w-14 h-14 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-1 overflow-hidden <?= ($currentCategory ?? null) === $slug ? 'border-2 border-primary' : 'border-2 border-transparent' ?>">
                                <img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"
                                    class="w-full h-full object-cover">
                            </div>
                            <span
                                class="text-[10px] font-medium <?= ($currentCategory ?? null) === $slug ? 'text-primary' : 'text-slate-700 dark:text-slate-300' ?> text-center leading-tight"><?= $cat['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mobile Side Drawer Backdrop -->
    <div id="drawerBackdrop" onclick="closeMobileDrawer()"
        class="fixed inset-0 bg-black/50 z-[100] hidden opacity-0 transition-opacity duration-300 lg:hidden"></div>

    <!-- Mobile Side Drawer -->
    <div id="mobileDrawer"
        class="fixed top-0 left-0 h-full w-72 max-w-[80vw] bg-white dark:bg-slate-900 z-[110] transform -translate-x-full transition-transform duration-300 ease-out shadow-2xl lg:hidden">
        <!-- Drawer Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800">
            <a href="/" class="flex items-center gap-2">
                <img src="/assets/images/inivitationVideoslogo.png" alt="<?= APP_NAME ?? 'InvitationVideos' ?>"
                    class="h-8 w-auto" width="32" height="32">
                <span class="font-bold text-lg"><?= APP_NAME ?? 'Invitation Videos' ?></span>
            </a>
            <button onclick="closeMobileDrawer()"
                class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300"
                aria-label="Close menu">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Drawer Navigation -->
        <nav class="p-4 space-y-1 overflow-y-auto" style="max-height: calc(100vh - 80px);">
            <?php
            // Display each main category as expandable section (excluding Miscellaneous)
            foreach ($mainCategories as $catSlug => $category):
                if ($catSlug === 'miscellaneous')
                    continue;
                $subcats = $categorySubcategories[$catSlug] ?? [];
                ?>
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                        <span><?= Security::escape($category['name']) ?></span>
                        <span class="material-symbols-outlined text-base transition-transform"
                            :class="open ? 'rotate-180' : ''">expand_more</span>
                    </button>
                    <div x-show="open" x-collapse class="ml-4 space-y-1 mt-1">
                        <?php foreach (array_slice($subcats, 0, 12) as $subcat): ?>
                            <a href="/templates?category=<?= $subcat['slug'] ?>"
                                class="block px-4 py-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm transition-colors">
                                <?= Security::escape($subcat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="/templates?category=<?= $catSlug ?>"
                            class="block px-4 py-2 rounded-lg text-primary font-medium text-sm transition-colors">
                            View All →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Miscellaneous Section -->
            <?php if (isset($mainCategories['miscellaneous']) && !empty($categorySubcategories['miscellaneous'])): ?>
                <div class="border-t border-slate-200 dark:border-slate-700 my-3"></div>
                <p class="px-4 py-1 text-xs font-bold text-slate-400 uppercase tracking-wide">Miscellaneous</p>
                <?php foreach ($categorySubcategories['miscellaneous'] as $subcat): ?>
                    <a href="/templates?category=<?= $subcat['slug'] ?>"
                        class="block px-4 py-3 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                        <?= Security::escape($subcat['name']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>


            <div class="border-t border-slate-200 dark:border-slate-700 my-3"></div>

            <a href="/blog"
                class="block px-4 py-3 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                Blog
            </a>

            <div class="border-t border-slate-200 dark:border-slate-700 my-3"></div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/my-orders"
                    class="block px-4 py-3 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                    My Orders
                </a>
                <a href="/wishlist"
                    class="block px-4 py-3 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                    My Wishlist
                </a>
                <a href="/my-tickets"
                    class="block px-4 py-3 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors">
                    My Tickets
                </a>
                <a href="/logout"
                    class="block px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors">
                    Logout
                </a>
            <?php endif; ?>

            <!-- Support Links (smaller, lighter) -->
            <div class="border-t border-slate-200 dark:border-slate-700 my-3"></div>
            <a href="/support"
                class="block px-4 py-2 rounded-lg text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm font-normal transition-colors">
                Help Center
            </a>
            <a href="/contact"
                class="block px-4 py-2 rounded-lg text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm font-normal transition-colors">
                Contact Us
            </a>

            <!-- Get Started Free Button (bottom) -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="pt-4">
                    <a href="/register"
                        class="block px-4 py-3 rounded-lg bg-primary text-white text-center font-bold shadow-lg shadow-primary/30 hover:bg-primary/90 transition-colors">
                        Get Started Free
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="flex-1">
        <?= $content ?? '' ?>
    </main>
    <!-- Floating Help Button - Only on landing page -->
    <?php if (($isHomePage ?? false)): ?>
        <a href="/support"
            class="fixed bottom-20 right-4 sm:bottom-6 sm:right-6 z-40 flex items-center justify-center w-12 h-12 sm:w-auto sm:h-auto sm:px-4 sm:py-3 font-bold rounded-full shadow-xl hover:scale-105 transition-all group"
            style="background-color: var(--footer-text-color, #1e293b); color: var(--footer-bg-color, #ffffff);"
            title="Need help?">
            <span class="material-symbols-outlined text-xl">support_agent</span>
            <span class="hidden sm:group-hover:inline whitespace-nowrap text-sm">Need Help?</span>
        </a>
    <?php endif; ?>



    <!-- Footer - Can be hidden on mobile for specific pages like gallery -->
    <footer
        class="border-t border-slate-200 dark:border-slate-800 mt-auto <?= ($hideFooterOnMobile ?? false) ? 'hidden sm:block' : '' ?>"
        style="background-color: var(--footer-bg-color, #ffffff); color: var(--footer-text-color, #1e293b);"
        x-data="{ footerOpen: window.innerWidth >= 769 }"
        x-init="window.addEventListener('resize', () => { if (window.innerWidth >= 769) footerOpen = true })">
        <!-- Mobile Footer Toggle Bar (visible only on small screens < 769px) -->
        <div class="sm:hidden">
            <button @click="footerOpen = !footerOpen"
                class="w-full flex items-center justify-between px-4 py-4 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                :aria-expanded="footerOpen" aria-controls="footerContent">
                <div class="flex items-center gap-2">
                    <img src="/assets/images/inivitationVideoslogo.png" alt="<?= APP_NAME ?? 'InvitationVideos' ?>"
                        class="h-6 w-auto" width="24" height="24" loading="lazy">
                    <span class="font-medium text-sm"><?= APP_NAME ?? 'Invitation Videos' ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500" x-text="footerOpen ? 'Hide Footer' : 'Show Footer'"></span>
                    <span class="material-symbols-outlined text-xl transition-transform duration-300"
                        :class="footerOpen ? 'rotate-180' : ''">expand_more</span>
                </div>
            </button>
        </div>

        <?php
        // Fetch top 10 published blogs for footer
        $footerBlogs = Database::fetchAll(
            "SELECT id, title, slug FROM blog_posts 
             WHERE status = 'published' 
             ORDER BY view_count DESC, published_at DESC 
             LIMIT 10"
        );
        ?>
        <!-- Footer Content (collapsible on mobile < 769px, always visible on sm and above) -->
        <div id="footerContent" class="footer-content" x-show="footerOpen" x-cloak x-collapse.duration.300ms>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-8">
                    <!-- Column 1: Brand + Social -->
                    <div class="col-span-2 sm:col-span-3 lg:col-span-1">
                        <div class="flex items-center gap-2 mb-4">
                            <img src="/assets/images/inivitationVideoslogo.png"
                                alt="<?= APP_NAME ?? 'InvitationVideos' ?>" class="h-8 w-auto" width="32" height="32"
                                loading="lazy">
                            <span class="font-bold text-lg"><?= APP_NAME ?? 'Invitation Videos' ?></span>
                        </div>
                        <p class="text-sm text-slate-500 mb-4">Create stunning video invitations for your special
                            occasions.
                        </p>

                        <!-- Social Links -->
                        <div class="flex items-center gap-3">
                            <a href="<?= SOCIAL_FACEBOOK ?>" target="_blank" rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors"
                                aria-label="Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
                                </svg>
                            </a>
                            <a href="<?= SOCIAL_INSTAGRAM ?>" target="_blank" rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors"
                                aria-label="Instagram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                </svg>
                            </a>
                            <a href="<?= SOCIAL_YOUTUBE ?>" target="_blank" rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors"
                                aria-label="YouTube">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                                </svg>
                            </a>
                            <a href="<?= SOCIAL_TWITTER ?>" target="_blank" rel="noopener noreferrer"
                                class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors"
                                aria-label="Twitter">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Column 2: Trending Categories (Part 1) -->
                    <div>
                        <h4 class="font-bold mb-4">Wedding Templates</h4>
                        <ul class="space-y-2 text-sm text-slate-500">
                            <li><a href="/templates?religion=hindu" class="hover:text-primary transition-colors">Hindu
                                    Wedding</a></li>
                            <li><a href="/templates?religion=christian"
                                    class="hover:text-primary transition-colors">Christian Wedding</a></li>
                            <li><a href="/templates?religion=muslim" class="hover:text-primary transition-colors">Muslim
                                    Wedding</a></li>
                            <li><a href="/templates?religion=sikh" class="hover:text-primary transition-colors">Sikh
                                    Wedding</a></li>
                            <li><a href="/templates?religion=jain" class="hover:text-primary transition-colors">Jain
                                    Wedding</a></li>
                            <li><a href="/templates?religion=buddhist"
                                    class="hover:text-primary transition-colors">Buddhist Wedding</a></li>
                        </ul>
                    </div>

                    <!-- Column 3: Trending Categories (Part 2) -->
                    <div>
                        <h4 class="font-bold mb-4">Popular Categories</h4>
                        <ul class="space-y-2 text-sm text-slate-500">
                            <li><a href="/templates?category=wedding"
                                    class="hover:text-primary transition-colors">Wedding Invitations</a></li>
                            <li><a href="/templates?category=birthday"
                                    class="hover:text-primary transition-colors">Birthday Invites</a></li>
                            <li><a href="/templates?category=anniversary"
                                    class="hover:text-primary transition-colors">Anniversary Videos</a></li>
                            <li><a href="/templates?category=baby_shower"
                                    class="hover:text-primary transition-colors">Baby Shower</a></li>
                            <li><a href="/templates?category=corporate"
                                    class="hover:text-primary transition-colors">Corporate Events</a></li>
                            <li><a href="/templates?festival=diwali" class="hover:text-primary transition-colors">Diwali
                                    Invitations</a></li>
                        </ul>
                    </div>

                    <!-- Column 4: Top Blogs -->
                    <div>
                        <h4 class="font-bold mb-4">Latest Articles</h4>
                        <ul class="space-y-2 text-sm text-slate-500">
                            <?php if (!empty($footerBlogs)): ?>
                                <?php foreach ($footerBlogs as $blog): ?>
                                    <li>
                                        <a href="/blog/<?= Security::escape($blog['slug']) ?>"
                                            class="hover:text-primary transition-colors line-clamp-1"
                                            title="<?= Security::escape($blog['title']) ?>">
                                            <?= Security::escape($blog['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="/blog" class="hover:text-primary transition-colors">Visit Our Blog</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Column 5: Product, Support & Legal -->
                    <div>
                        <h4 class="font-bold mb-4">Product</h4>
                        <ul class="space-y-2 text-sm text-slate-500 mb-4">
                            <li><a href="/templates" class="hover:text-primary transition-colors">All Templates</a></li>
                            <li><a href="/blog" class="hover:text-primary transition-colors">Blog</a></li>
                        </ul>

                        <h4 class="font-bold mb-4">Support</h4>
                        <ul class="space-y-2 text-sm text-slate-500 mb-4">
                            <li><a href="/support" class="hover:text-primary transition-colors">Help Center</a></li>
                            <li><a href="/contact" class="hover:text-primary transition-colors">Contact Us</a></li>
                            <li><a href="/faq" class="hover:text-primary transition-colors">FAQ</a></li>
                        </ul>

                        <h4 class="font-bold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm text-slate-500">
                            <li><a href="/privacy" class="hover:text-primary transition-colors">Privacy Policy</a></li>
                            <li><a href="/terms" class="hover:text-primary transition-colors">Terms of Service</a></li>
                            <li><a href="/refund" class="hover:text-primary transition-colors">Refund Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Full-width divider -->
            <div class="border-t border-slate-200 dark:border-slate-800 mt-8"></div>

            <!-- Bottom Section: Copyright & Payment -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row items-start justify-between gap-6">
                    <!-- Left: Copyright (two rows, stacked) -->
                    <div class="flex flex-col gap-1">
                        <p class="text-sm" style="color: var(--footer-text-color);">&copy; <?= date('Y') ?>
                            <?= APP_NAME ?? 'InvitationVideos' ?>. All rights reserved.
                        </p>
                        <p class="text-xs" style="color: var(--footer-text-color); opacity: 0.7;">Made with <span
                                class="text-red-500">❤</span> in India |
                            Developed by <a href="https://neowebx.com" target="_blank" rel="noopener"
                                class="text-primary hover:underline font-medium">NeoWebX.com</a></p>
                    </div>

                    <!-- Right: Payment Icons -->
                    <div class="flex flex-col items-end gap-2">
                        <span class="text-xs uppercase tracking-wide" style="color: var(--footer-text-color);">Payment
                            secured by</span>
                        <div class="flex items-center gap-2 flex-wrap justify-end">
                            <img src="/assets/images/ivitationvideos-200x120.webp" alt="Visa"
                                class="h-6 sm:h-8 w-auto object-contain bg-white rounded-sm p-1" width="60" height="36"
                                loading="lazy">
                            <img src="/assets/images/ivitationvideos-razorpay-.webp" alt="Razorpay"
                                class="h-6 sm:h-8 w-auto object-contain bg-white rounded-sm p-1" width="80" height="36"
                                loading="lazy">
                            <img src="/assets/images/ivitationvideos-Payment6-200x120.webp" alt="PayPal"
                                class="h-6 sm:h-8 w-auto object-contain bg-white rounded-sm p-1" width="50" height="36"
                                loading="lazy">
                            <img src="/assets/images/ivitationvideos-mastercard-200x120.webp" alt="MasterCard"
                                class="h-6 sm:h-8 w-auto object-contain bg-white rounded-sm p-1" width="50" height="36"
                                loading="lazy">
                            <img src="/assets/images/ivitationvideos-upi_logo.webp" alt="UPI"
                                class="h-6 sm:h-8 w-auto object-contain bg-white rounded-sm p-1" width="50" height="36"
                                loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile drawer functions
        function openMobileDrawer() {
            const drawer = document.getElementById('mobileDrawer');
            const backdrop = document.getElementById('drawerBackdrop');

            drawer.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            setTimeout(() => backdrop.classList.add('opacity-100'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeMobileDrawer() {
            const drawer = document.getElementById('mobileDrawer');
            const backdrop = document.getElementById('drawerBackdrop');

            drawer.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        }

        // Close drawer on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeMobileDrawer();
            }
        });

        // Image preview
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Music preview
        let currentAudio = null;
        function playPreview(url) {
            if (currentAudio) {
                currentAudio.pause();
            }
            currentAudio = new Audio(url);
            currentAudio.play();
        }
    </script>

    <!-- Cookie Consent Banner -->
    <div id="cookie-consent-banner"
        class="fixed bottom-0 left-0 right-0 z-[200] transform translate-y-full opacity-0 transition-all duration-300 ease-out">
        <div class="bg-slate-900 text-white shadow-2xl border-t border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <p class="text-sm text-slate-300">
                            <span class="font-semibold text-white">We value your privacy.</span>
                            We use cookies to analyze site traffic and improve your experience.
                            <a href="/privacy" class="underline hover:text-primary">Learn more</a>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button onclick="CookieConsent.decline()"
                            class="px-4 py-2 text-sm font-medium text-slate-400 hover:text-white transition-colors">
                            Decline
                        </button>
                        <button onclick="CookieConsent.accept()"
                            class="px-6 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-colors shadow-lg shadow-primary/30">
                            Accept Cookies
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cookie Consent Script -->
    <script src="/assets/js/cookie-consent.js" defer></script>

    <!-- Section Positioning Script for responsive homepage banners -->
    <script src="/assets/js/section-positioning.js" defer></script>

    <!-- Template Carousel Script -->
    <script src="/assets/js/section-carousel.js" defer></script>



</body>

</html>