<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <!-- Google Tag Manager -->
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            }); var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-NGZWTLGW');</script>
    <!-- End Google Tag Manager -->

    <!-- Social Media Schema (Entity SEO) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?php echo APP_NAME ?? 'VideoInvites'; ?>",
      "url": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
      "logo": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/assets/images/logo.png",
      "sameAs": [
        "<?= SOCIAL_FACEBOOK ?>",
        "<?= SOCIAL_INSTAGRAM ?>",
        "<?= SOCIAL_TWITTER ?>",
        "<?= SOCIAL_YOUTUBE ?>"
      ]
    }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Smart Title Logic
    $finalTitle = $pageTitle ?? 'VideoInvites - Create Stunning Video Invitations';
    if (isset($pageTitle) && strpos($pageTitle, 'VideoInvites') === false) {
        $finalTitle .= ' | VideoInvites';
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
            color: #7f13ec
        }

        .bg-primary {
            background-color: #7f13ec
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

        @media(min-width:768px) {
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
        }

        /* Loading state for fonts */
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined', sans-serif;
            font-size: 24px;
            display: inline-block;
            width: 24px;
            height: 24px
        }
    </style>

    <!-- Self-hosted fonts - preload for fast text rendering -->
    <link rel="preload" href="/assets/fonts/plus-jakarta-sans-variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/css/fonts.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="/assets/css/fonts.css">
    </noscript>

    <!-- Preconnect for CDN -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <!-- Main CSS - loaded async to prevent render blocking -->
    <link rel="preload" href="/assets/css/app.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="/assets/css/app.css">
    </noscript>

    <!-- Material Symbols - lazy load after page is interactive -->
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1&display=swap';
                document.head.appendChild(link);
            }, 100);
        });
    </script>

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
    <header
        class="sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <!-- Left Section: Logo + Categories -->
                <div class="flex items-center gap-6 lg:gap-8 flex-1">
                    <!-- Logo -->
                    <a href="/" class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        <img src="/assets/images/logo.png" alt="<?= APP_NAME ?? 'InvitationVideos' ?>"
                            class="h-9 sm:h-10 w-auto" width="40" height="40" loading="eager" fetchpriority="high">
                        <h2 class="text-lg sm:text-xl font-bold leading-tight tracking-tight">
                            <?= APP_NAME ?? 'VideoInvites' ?>
                        </h2>
                    </a>

                    <!-- Desktop Navigation - Categories directly after logo -->
                    <nav class="hidden lg:flex items-center gap-4 xl:gap-5">
                        <a href="/templates?category=wedding"
                            class="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-lg text-rose-500">favorite</span>
                            Wedding
                        </a>
                        <a href="/templates?category=birthday"
                            class="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-lg text-amber-500">cake</span>
                            Birthday
                        </a>
                        <a href="/templates?category=corporate"
                            class="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-lg text-blue-500">business_center</span>
                            Corporate
                        </a>
                        <a href="/templates?category=baby_shower"
                            class="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-lg text-teal-500">child_care</span>
                            Baby Shower
                        </a>
                        <a href="/templates?category=anniversary"
                            class="flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-lg text-purple-500">celebration</span>
                            Anniversary
                        </a>
                    </nav>
                </div>

                <!-- Desktop Auth (Right) -->
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
                        <a href="/login" class="text-sm font-medium text-slate-600 hover:text-primary">Login</a>
                        <a href="/register"
                            class="flex h-10 items-center justify-center rounded-lg bg-primary px-5 text-sm font-bold text-white shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()"
                    class="md:hidden p-2 -mr-2 rounded-lg hover:bg-slate-100 text-slate-600">
                    <span id="menuIcon" class="material-symbols-outlined text-2xl">menu</span>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu"
            class="md:hidden closed border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <nav class="px-4 py-4 space-y-1">
                <!-- Category Links -->
                <a href="/templates?category=wedding"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                    <span class="material-symbols-outlined text-rose-500">favorite</span>
                    Wedding
                </a>
                <a href="/templates?category=birthday"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                    <span class="material-symbols-outlined text-amber-500">cake</span>
                    Birthday
                </a>
                <a href="/templates?category=corporate"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                    <span class="material-symbols-outlined text-blue-500">business_center</span>
                    Corporate
                </a>
                <a href="/templates?category=baby_shower"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                    <span class="material-symbols-outlined text-teal-500">child_care</span>
                    Baby Shower
                </a>
                <a href="/templates?category=anniversary"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                    <span class="material-symbols-outlined text-purple-500">celebration</span>
                    Anniversary
                </a>

                <div class="border-t border-slate-200 my-3"></div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/my-orders"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                        <span class="material-symbols-outlined">shopping_bag</span>
                        My Orders
                    </a>
                    <a href="/my-tickets"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                        <span class="material-symbols-outlined">support_agent</span>
                        My Tickets
                    </a>
                    <a href="/logout"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 font-medium">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/login"
                        class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-600 hover:bg-slate-100 font-medium">
                        <span class="material-symbols-outlined">login</span>
                        Login
                    </a>
                    <a href="/register"
                        class="flex items-center justify-center gap-2 mt-2 py-3 rounded-lg bg-primary text-white font-bold shadow-lg shadow-primary/30">
                        <span class="material-symbols-outlined">rocket_launch</span>
                        Get Started Free
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        <?= $content ?? '' ?>
    </main>

    <!-- Floating Help Button -->
    <a href="/support"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 bg-primary text-white font-bold rounded-full shadow-xl shadow-primary/30 hover:bg-primary/90 hover:scale-105 transition-all group"
        title="Need help?">
        <span class="material-symbols-outlined text-xl">support_agent</span>
        <span class="hidden sm:group-hover:inline whitespace-nowrap text-sm">Need Help?</span>
    </a>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-8">
                <!-- Brand -->
                <div class="col-span-2 sm:col-span-3 lg:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="/assets/images/logo.png" alt="<?= APP_NAME ?? 'InvitationVideos' ?>"
                            class="h-8 w-auto" width="32" height="32" loading="lazy">
                        <span class="font-bold text-lg"><?= APP_NAME ?? 'VideoInvites' ?></span>
                    </div>
                    <p class="text-sm text-slate-500 mb-4">Create stunning video invitations for your special occasions.
                    </p>

                    <!-- Social Links -->
                    <div class="flex items-center gap-3">
                        <a href="<?= SOCIAL_FACEBOOK ?>" target="_blank" rel="noopener noreferrer"
                            class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                            </svg>
                        </a>
                        <a href="<?= SOCIAL_INSTAGRAM ?>" target="_blank" rel="noopener noreferrer"
                            class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                            </svg>
                        </a>
                        <a href="<?= SOCIAL_YOUTUBE ?>" target="_blank" rel="noopener noreferrer"
                            class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Categories -->
                <div>
                    <h4 class="font-bold mb-4">Categories</h4>
                    <ul class="space-y-2 text-sm text-slate-500">
                        <li><a href="/templates?category=wedding"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-rose-500">favorite</span> Wedding
                            </a></li>
                        <li><a href="/templates?category=birthday"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-amber-500">cake</span> Birthday
                            </a></li>
                        <li><a href="/templates?category=corporate"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-blue-500">business_center</span>
                                Corporate
                            </a></li>
                        <li><a href="/templates?category=baby_shower"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-teal-500">child_care</span> Baby
                                Shower
                            </a></li>
                        <li><a href="/templates?category=anniversary"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-purple-500">celebration</span>
                                Anniversary
                            </a></li>
                        <li><a href="/templates?category=holi"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-pink-500">palette</span> Holi
                            </a></li>
                        <li><a href="/templates?category=diwali"
                                class="hover:text-primary transition-colors flex items-center gap-1.5">
                                <span
                                    class="material-symbols-outlined text-base text-orange-500">local_fire_department</span>
                                Diwali
                            </a></li>
                    </ul>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="font-bold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm text-slate-500">
                        <li><a href="/templates" class="hover:text-primary transition-colors">All Templates</a></li>
                        <li><a href="/blog" class="hover:text-primary transition-colors">Blog</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-slate-500">
                        <li><a href="/support" class="hover:text-primary transition-colors">Help Center</a></li>
                        <li><a href="/contact" class="hover:text-primary transition-colors">Contact Us</a></li>
                        <li><a href="/faq" class="hover:text-primary transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="font-bold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-slate-500">
                        <li><a href="/privacy" class="hover:text-primary transition-colors">Privacy Policy</a></li>
                        <li><a href="/terms" class="hover:text-primary transition-colors">Terms of Service</a></li>
                        <li><a href="/refund" class="hover:text-primary transition-colors">Refund Policy</a></li>
                    </ul>
                </div>
            </div>

            <!-- Payment Methods & Copyright -->
            <div class="border-t border-slate-200 dark:border-slate-800 mt-8 pt-8">
                <!-- Payment Gateway Icons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 mb-6">
                    <div class="flex flex-col items-center sm:items-start gap-2">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Secure Payment
                            Methods</span>
                        <div class="flex items-center gap-3 flex-wrap justify-center sm:justify-start">
                            <!-- Visa -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-slate-200 dark:border-slate-700">
                                <svg class="h-6 w-auto" viewBox="0 0 780 500" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#1434CB"
                                        d="M293.2 348.7l33.4-195.8h53.4l-33.4 195.8zM540.5 159.1c-10.6-4-27.1-8.3-47.8-8.3-52.7 0-89.8 26.5-90.1 64.5-.3 28.1 26.5 43.8 46.8 53.1 20.8 9.5 27.8 15.6 27.7 24.1-.1 13-16.6 19-32 19-21.3 0-32.7-3-50.2-10.3l-6.9-3.1-7.5 43.8c12.5 5.5 35.6 10.2 59.5 10.4 56 0 92.4-26.1 92.8-66.8.2-22.3-14-39.2-44.8-53.2-18.6-9.1-30.1-15.1-30-24.3 0-8.1 9.7-16.8 30.6-16.8 17.4-.3 30.1 3.5 39.9 7.5l4.8 2.2 7.2-41.8M651.1 152.9h-41.2c-12.8 0-22.4 3.5-28 16.2l-79.3 179.5h56s9.2-24.1 11.2-29.4c6.1 0 60.5.1 68.3.1 1.6 6.9 6.5 29.3 6.5 29.3h49.5l-43-195.7zm-65.6 126.4c4.4-11.3 21.3-54.7 21.3-54.7-.3.5 4.4-11.3 7.1-18.7l3.6 16.9s10.2 46.9 12.4 56.5h-44.4zM214.4 152.9l-52.2 133.5-5.6-27c-9.7-31.2-39.8-65-73.5-82l47.7 171.1h56.4l83.8-195.6h-56.6" />
                                    <path fill="#F9A533"
                                        d="M131.9 152.9H46.2l-.7 4c66.9 16.2 111.2 55.3 129.6 102.2l-18.7-89.8c-3.2-12.4-12.6-16-24.5-16.4" />
                                </svg>
                            </div>
                            <!-- Mastercard -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-slate-200 dark:border-slate-700">
                                <svg class="h-6 w-auto" viewBox="0 0 780 500" xmlns="http://www.w3.org/2000/svg">
                                    <circle fill="#EB001B" cx="250" cy="250" r="150" />
                                    <circle fill="#F79E1B" cx="530" cy="250" r="150" />
                                    <path fill="#FF5F00"
                                        d="M325 127.5c-35.4 28.5-58.1 72.1-58.1 121s22.7 92.6 58.1 121c35.4-28.5 58.1-72.1 58.1-121s-22.7-92.5-58.1-121z" />
                                </svg>
                            </div>
                            <!-- Stripe -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-slate-200 dark:border-slate-700">
                                <svg class="h-6 w-auto" viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#635BFF"
                                        d="M59.64 14.28c0-4.77-2.31-8.55-6.73-8.55-4.44 0-7.13 3.78-7.13 8.52 0 5.62 3.17 8.49 7.72 8.49 2.22 0 3.9-.5 5.17-1.21v-3.75c-1.27.64-2.73 1.04-4.57 1.04-1.81 0-3.41-.64-3.62-2.82h9.12c0-.24.04-1.21.04-1.72zm-9.22-1.77c0-2.1 1.28-2.97 2.45-2.97 1.14 0 2.34.87 2.34 2.97h-4.79zm-10.14-6.78c-1.82 0-2.99.86-3.64 1.45l-.24-1.15h-4.1v21.76l4.66-.99.01-5.28c.67.49 1.65 1.17 3.28 1.17 3.32 0 6.34-2.67 6.34-8.56-.01-5.39-3.08-8.4-6.31-8.4zm-1.11 12.91c-1.09 0-1.73-.39-2.18-.87l-.02-6.86c.48-.53 1.14-.9 2.2-.9 1.68 0 2.84 1.89 2.84 4.31 0 2.47-1.14 4.32-2.84 4.32zm-14.23-14.01l4.67-.99V.01l-4.67.99v3.63zm0 1.4h4.67v16.02h-4.67V6.03zM17.64 7.43l-.3-1.4h-4.04v16.02h4.66v-10.9c1.1-1.44 2.96-1.17 3.54-.97V6.03c-.6-.23-2.79-.64-3.86 1.4zm-11.4-2.15C2.57 4.53.52 6.35.52 9.47v.63H0v3.6h.52v12.35h4.66V13.7h2.27v-3.6H5.18v-.68c0-1.08.45-1.69 1.49-1.69.75 0 1.31.15 1.75.32V4.66C7.72 4.44 7.01 4.28 5.85 4.28h.39c0-.37 0-.74 0-1z" />
                                </svg>
                            </div>
                            <!-- Razorpay -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-slate-200 dark:border-slate-700">
                                <img src="/assets/images/razorpay-dark.png" alt="Razorpay" class="h-5 w-auto" width="80"
                                    height="20" loading="lazy">
                            </div>
                            <!-- UPI -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-slate-200 dark:border-slate-700">
                                <img src="/assets/images/upi_logo.png" alt="UPI" class="h-5 w-auto" width="40"
                                    height="20" loading="lazy">
                            </div>
                            <!-- PayPal -->
                            <div
                                class="bg-white dark:bg-slate-800 rounded-lg px-3 py-2 border border-slate-200 dark:border-slate-700">
                                <svg class="h-6 w-auto" viewBox="0 0 124 33" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#253B80"
                                        d="M46.211 6.749h-6.839a.95.95 0 0 0-.939.802l-2.766 17.537a.57.57 0 0 0 .564.658h3.265a.95.95 0 0 0 .939-.803l.746-4.73a.95.95 0 0 1 .938-.803h2.165c4.505 0 7.105-2.18 7.784-6.5.306-1.89.013-3.375-.872-4.415-.972-1.142-2.696-1.746-4.985-1.746zM47 13.154c-.374 2.454-2.249 2.454-4.062 2.454h-1.032l.724-4.583a.57.57 0 0 1 .563-.481h.473c1.235 0 2.4 0 3.002.704.359.42.469 1.044.332 1.906zM66.654 13.075h-3.275a.57.57 0 0 0-.563.481l-.145.916-.229-.332c-.709-1.029-2.29-1.373-3.868-1.373-3.619 0-6.71 2.741-7.312 6.586-.313 1.918.132 3.752 1.22 5.031.998 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .562.66h2.95a.95.95 0 0 0 .939-.803l1.77-11.209a.568.568 0 0 0-.561-.658zm-4.565 6.374c-.316 1.871-1.801 3.127-3.695 3.127-.951 0-1.711-.305-2.199-.883-.484-.574-.668-1.391-.514-2.301.295-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.499.589.697 1.411.554 2.317zM84.096 13.075h-3.291a.954.954 0 0 0-.787.417l-4.539 6.686-1.924-6.425a.953.953 0 0 0-.912-.678h-3.234a.57.57 0 0 0-.541.754l3.625 10.638-3.408 4.811a.57.57 0 0 0 .465.9h3.287a.949.949 0 0 0 .781-.408l10.946-15.8a.57.57 0 0 0-.468-.895z" />
                                    <path fill="#179BD7"
                                        d="M94.992 6.749h-6.84a.95.95 0 0 0-.938.802l-2.766 17.537a.569.569 0 0 0 .562.658h3.51a.665.665 0 0 0 .656-.562l.785-4.971a.95.95 0 0 1 .938-.803h2.164c4.506 0 7.105-2.18 7.785-6.5.307-1.89.012-3.375-.873-4.415-.971-1.142-2.694-1.746-4.983-1.746zm.789 6.405c-.373 2.454-2.248 2.454-4.062 2.454h-1.031l.725-4.583a.568.568 0 0 1 .562-.481h.473c1.234 0 2.4 0 3.002.704.359.42.468 1.044.331 1.906zM115.434 13.075h-3.273a.567.567 0 0 0-.562.481l-.145.916-.23-.332c-.709-1.029-2.289-1.373-3.867-1.373-3.619 0-6.709 2.741-7.311 6.586-.312 1.918.131 3.752 1.219 5.031 1 1.176 2.426 1.666 4.125 1.666 2.916 0 4.533-1.875 4.533-1.875l-.146.91a.57.57 0 0 0 .564.66h2.949a.95.95 0 0 0 .938-.803l1.771-11.209a.571.571 0 0 0-.565-.658zm-4.565 6.374c-.314 1.871-1.801 3.127-3.695 3.127-.949 0-1.711-.305-2.199-.883-.484-.574-.666-1.391-.514-2.301.297-1.855 1.805-3.152 3.67-3.152.93 0 1.686.309 2.184.892.501.589.699 1.411.554 2.317zM119.295 7.23l-2.807 17.858a.569.569 0 0 0 .562.658h2.822c.469 0 .867-.34.939-.803l2.768-17.536a.57.57 0 0 0-.562-.659h-3.16a.571.571 0 0 0-.562.482z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Trust Badges -->
                    <div class="flex items-center gap-4 text-sm text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-green-500">verified_user</span>
                            <span>SSL Secured</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-blue-500">lock</span>
                            <span>100% Safe</span>
                        </div>
                    </div>
                </div>

                <!-- Copyright -->
                <div
                    class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <p>&copy; <?= date('Y') ?> <?= APP_NAME ?? 'VideoInvites' ?>. All rights reserved.</p>
                    <p class="text-xs">Made with <span class="text-red-500">❤</span> in India | Developed by <a
                            href="https://neowebx.com" target="_blank" rel="noopener"
                            class="text-primary hover:underline font-medium">NeoWebX.com</a></p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const icon = document.getElementById('menuIcon');

            menu.classList.toggle('closed');
            icon.textContent = menu.classList.contains('closed') ? 'menu' : 'close';
        }

        // Close menu on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                document.getElementById('mobileMenu').classList.add('closed');
                document.getElementById('menuIcon').textContent = 'menu';
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

</body>

</html>