<?php
// Require authentication for all admin pages
require_once __DIR__ . '/../auth.php';
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - VideoInvites</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#7f13ec",
                        "background-light": "#f7f6f8",
                        "background-dark": "#191022",
                        "surface-light": "#ffffff",
                        "surface-dark": "#251b30",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"],
                    },
                },
            },
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .icon-filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }

        /* Mobile sidebar transitions */
        #sidebar {
            transition: transform 0.3s ease-in-out;
        }

        #sidebar.closed {
            transform: translateX(-100%);
        }

        #overlay {
            transition: opacity 0.3s ease-in-out;
        }

        #overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        @media (min-width: 1024px) {
            #sidebar {
                transform: translateX(0) !important;
            }

            #overlay {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white overflow-hidden">

    <!-- Mobile Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <div class="flex h-screen w-full">

        <!-- Side Navigation -->
        <aside id="sidebar"
            class="fixed lg:relative z-50 flex w-64 flex-col justify-between border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-surface-dark flex-shrink-0 h-full closed lg:transform-none">
            <div>
                <!-- Logo -->
                <div class="flex items-center justify-between gap-3 px-6 py-6">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center size-10 rounded-xl bg-primary text-white">
                            <span class="material-symbols-outlined">movie_edit</span>
                        </div>
                        <div class="flex flex-col">
                            <h1 class="text-lg font-bold leading-tight"><?= APP_NAME ?? 'VideoInvites' ?></h1>
                            <p class="text-slate-500 text-xs font-medium">Admin Panel</p>
                        </div>
                    </div>
                    <!-- Close button for mobile -->
                    <button onclick="toggleSidebar()"
                        class="lg:hidden p-1 rounded-lg hover:bg-slate-100 text-slate-500">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex flex-col gap-1 px-4 mt-4">
                    <?php
                    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
                    $navItems = [
                        ['href' => '/admin/dashboard.php', 'icon' => 'dashboard', 'label' => 'Dashboard', 'page' => 'dashboard'],
                        ['href' => '/admin/orders.php', 'icon' => 'shopping_bag', 'label' => 'Orders', 'page' => 'orders'],
                        ['href' => '/admin/templates.php', 'icon' => 'video_library', 'label' => 'Templates', 'page' => 'templates'],
                        ['href' => '/admin/categories.php', 'icon' => 'category', 'label' => 'Categories', 'page' => 'categories'],
                        ['href' => '/admin/field-presets.php', 'icon' => 'input', 'label' => 'Field Presets', 'page' => 'field-presets'],
                        ['href' => '/admin/elements.php', 'icon' => 'shapes', 'label' => 'Elements', 'page' => 'elements'],
                        ['href' => '/admin/fonts.php', 'icon' => 'text_fields', 'label' => 'Fonts', 'page' => 'fonts'],
                        ['href' => '/admin/backgrounds.php', 'icon' => 'wallpaper', 'label' => 'Backgrounds', 'page' => 'backgrounds'],
                        ['href' => '/admin/promo-codes.php', 'icon' => 'confirmation_number', 'label' => 'Promo Codes', 'page' => 'promo-codes'],
                        ['href' => '/admin/template-builder.php', 'icon' => 'design_services', 'label' => 'Template Builder', 'page' => 'template-builder'],
                        ['href' => '/admin/users.php', 'icon' => 'group', 'label' => 'Users', 'page' => 'users'],
                        ['href' => '/admin/blog.php', 'icon' => 'article', 'label' => 'Blog', 'page' => 'blog'],
                        ['href' => '/admin/support.php', 'icon' => 'contact_support', 'label' => 'Support', 'page' => 'support', 'badge' => $pendingTickets ?? null],
                    ];

                    foreach ($navItems as $item):
                        $isActive = $currentPage === $item['page'];
                        ?>
                        <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= $isActive
                              ? 'bg-primary text-white'
                              : 'text-slate-500 hover:bg-primary/10 hover:text-primary' ?> transition-colors">
                            <span
                                class="material-symbols-outlined <?= $isActive ? 'icon-filled' : '' ?>"><?= $item['icon'] ?></span>
                            <span class="text-sm font-semibold"><?= $item['label'] ?></span>
                            <?php if (!empty($item['badge'])): ?>
                                <span
                                    class="ml-auto bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full"><?= $item['badge'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Bottom Section -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 relative">
                <!-- Admin Profile with Dropdown -->
                <div class="relative">
                    <!-- Dropdown Menu (hidden by default) -->
                    <div id="user-dropdown"
                        class="absolute bottom-full left-0 right-0 mb-2 bg-white dark:bg-surface-dark rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden hidden">
                        <a href="/admin/settings.php"
                            class="flex items-center gap-3 px-4 py-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <span class="material-symbols-outlined text-lg">settings</span>
                            <span class="text-sm font-medium">Settings</span>
                        </a>
                        <a href="/logout"
                            class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors border-t border-slate-100 dark:border-slate-700">
                            <span class="material-symbols-outlined text-lg">logout</span>
                            <span class="text-sm font-medium">Logout</span>
                        </a>
                    </div>

                    <!-- Profile Button -->
                    <button type="button" onclick="toggleUserDropdown()"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-50 dark:bg-white/5 cursor-pointer hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
                        <div
                            class="size-9 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm">
                            <?= substr($_SESSION['user_name'] ?? 'A', 0, 1) ?>
                        </div>
                        <div class="flex flex-col overflow-hidden text-left">
                            <p class="text-sm font-bold truncate"><?= $_SESSION['user_name'] ?? 'Admin' ?></p>
                            <p class="text-xs text-slate-500 truncate"><?= ucfirst($_SESSION['user_role'] ?? 'Admin') ?>
                            </p>
                        </div>
                        <span id="dropdown-arrow"
                            class="material-symbols-outlined ml-auto text-slate-400 text-[20px] transition-transform">expand_more</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex flex-col flex-1 h-full overflow-hidden w-full">

            <!-- Top Header -->
            <header
                class="h-14 sm:h-16 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-surface-dark px-4 sm:px-6 lg:px-8 py-3 flex-shrink-0">

                <!-- Mobile Menu Button -->
                <button onclick="toggleSidebar()"
                    class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 text-slate-600">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <!-- Search -->
                <div class="hidden sm:flex flex-1 max-w-md">
                    <div class="relative w-full">
                        <div
                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <span class="material-symbols-outlined text-[20px]">search</span>
                        </div>
                        <input type="text"
                            class="block w-full p-2.5 pl-10 text-sm border-none rounded-lg bg-slate-100 dark:bg-white/5 focus:ring-2 focus:ring-primary transition-all placeholder:text-slate-400"
                            placeholder="Search orders, users, or templates...">
                    </div>
                </div>

                <!-- Mobile Page Title -->
                <h1 class="lg:hidden text-lg font-bold truncate"><?= $pageTitle ?? 'Dashboard' ?></h1>

                <!-- Right Actions -->
                <div class="flex items-center gap-2 sm:gap-4 ml-4">
                    <!-- Mobile Search Button -->
                    <button class="sm:hidden p-2 text-slate-500 hover:bg-slate-100 rounded-full">
                        <span class="material-symbols-outlined">search</span>
                    </button>

                    <button
                        class="relative p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10 rounded-full transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                        <span
                            class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border-2 border-white dark:border-surface-dark"></span>
                    </button>

                    <a href="/admin/templates.php?action=new"
                        class="hidden sm:flex items-center gap-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold py-2 px-4 rounded-lg shadow-sm shadow-primary/30 transition-all">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span class="hidden md:inline">New Template</span>
                    </a>

                    <!-- Mobile Add Button -->
                    <a href="/admin/templates.php?action=new" class="sm:hidden p-2 bg-primary text-white rounded-lg">
                        <span class="material-symbols-outlined text-xl">add</span>
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    <?= $content ?? '' ?>
                </div>
            </div>

        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.toggle('closed');
            overlay.classList.toggle('hidden');
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('user-dropdown');
            const arrow = document.getElementById('dropdown-arrow');

            dropdown.classList.toggle('hidden');
            arrow.style.transform = dropdown.classList.contains('hidden') ? '' : 'rotate(180deg)';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            const dropdown = document.getElementById('user-dropdown');
            const profileBtn = e.target.closest('button[onclick="toggleUserDropdown()"]');

            if (!profileBtn && dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
                document.getElementById('dropdown-arrow').style.transform = '';
            }
        });

        // Close sidebar when clicking a link (mobile)
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    toggleSidebar();
                }
            });
        });
    </script>

</body>

</html>