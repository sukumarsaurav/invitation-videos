<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'VideoInvites' ?> - Create Stunning Video Invitations</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="<?= $metaDescription ?? 'Create stunning video invitations for weddings, birthdays, and special events. Easy customization, professional quality.' ?>">

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

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }

        /* Mobile menu transitions */
        #mobileMenu {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        #mobileMenu.closed {
            transform: translateY(-10px);
            opacity: 0;
            pointer-events: none;
        }
    </style>

    <?php if (defined('STRIPE_PUBLIC_KEY') && STRIPE_PUBLIC_KEY): ?>
        <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>

    <?php if (defined('RAZORPAY_KEY_ID') && RAZORPAY_KEY_ID): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display min-h-screen flex flex-col">

    <!-- Header -->
    <header
        class="sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <!-- Left Section: Logo + Categories -->
                <div class="flex items-center gap-6 lg:gap-8 flex-1">
                    <!-- Logo -->
                    <a href="/" class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        <div
                            class="flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-2xl sm:text-3xl">movie_filter</span>
                        </div>
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
                                        class="w-9 h-9 rounded-full object-cover border-2 border-primary/20">
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
                                            <?= Security::escape($userName) ?></p>
                                        <p class="text-xs text-slate-500 truncate">
                                            <?= Security::escape($_SESSION['user_email'] ?? '') ?></p>
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

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="col-span-2 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-2xl">movie_filter</span>
                        <span class="font-bold text-lg"><?= APP_NAME ?? 'VideoInvites' ?></span>
                    </div>
                    <p class="text-sm text-slate-500 mb-4">Create stunning video invitations for your special occasions.
                    </p>

                    <!-- Social Links -->
                    <div class="flex items-center gap-3">
                        <a href="#"
                            class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="p-2 rounded-lg bg-slate-100 hover:bg-primary hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="font-bold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm text-slate-500">
                        <li><a href="/templates" class="hover:text-primary transition-colors">Templates</a></li>
                        <li><a href="/pricing" class="hover:text-primary transition-colors">Pricing</a></li>
                        <li><a href="/features" class="hover:text-primary transition-colors">Features</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-slate-500">
                        <li><a href="/help" class="hover:text-primary transition-colors">Help Center</a></li>
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

            <div
                class="border-t border-slate-200 dark:border-slate-800 mt-8 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?? 'VideoInvites' ?>. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <span>Payment Partners:</span>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg"
                        alt="Stripe" class="h-6 opacity-50">
                    <img src="https://razorpay.com/assets/razorpay-glyph.svg" alt="Razorpay" class="h-6 opacity-50">
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