<?php
/**
 * Admin Login Page
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            // Check rate limiting
            $rateLimitKey = 'login_' . md5($email);
            if (!Security::checkRateLimit($rateLimitKey, 5, 900)) {
                $error = 'Too many login attempts. Please try again in 15 minutes.';
            } else {
                // Find user by email
                $user = Database::fetchOne(
                    "SELECT * FROM users WHERE email = ? AND role IN ('admin', 'editor') AND status = 'active'",
                    [$email]
                );

                if ($user && Security::verifyPassword($password, $user['password_hash'])) {
                    // Login successful
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_name'] = $user['name'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['admin_logged_in_at'] = time();

                    // Update last login
                    Database::query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

                    // Redirect to dashboard or intended URL
                    $redirect = $_SESSION['admin_redirect_url'] ?? '/admin/dashboard.php';
                    unset($_SESSION['admin_redirect_url']);

                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - VideoInvites</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#7f13ec",
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
    </style>
</head>

<body
    class="min-h-screen bg-gradient-to-br from-purple-900 via-purple-800 to-indigo-900 flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        <!-- Logo -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center size-14 rounded-2xl bg-white/10 backdrop-blur-sm text-white mb-4">
                <span class="material-symbols-outlined text-3xl">movie_edit</span>
            </div>
            <h1 class="text-2xl font-bold text-white">VideoInvites</h1>
            <p class="text-white/60 mt-1">Admin Panel</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-slate-900">Welcome Back</h2>
                <p class="text-slate-500 text-sm mt-1">Sign in to access your admin dashboard</p>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">error</span>
                    <?= Security::escape($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div
                    class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-lg">check_circle</span>
                    You have been logged out successfully.
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <?= Security::csrfField() ?>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-xl">mail</span>
                        <input type="email" name="email" required autofocus
                            class="w-full h-12 pl-12 pr-4 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            placeholder="admin@example.com" value="<?= Security::escape($email) ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-xl">lock</span>
                        <input type="password" name="password" required
                            class="w-full h-12 pl-12 pr-12 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword(this)"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-outlined text-xl">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember"
                            class="rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                    <a href="/admin/forgot-password.php" class="text-sm text-primary font-medium hover:underline">
                        Forgot password?
                    </a>
                </div>

                <button type="submit"
                    class="w-full h-12 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-xl">login</span>
                    Sign In
                </button>
            </form>

        </div>

        <!-- Footer -->
        <p class="text-center text-white/40 text-sm mt-6">
            &copy; <?= date('Y') ?> VideoInvites. All rights reserved.
        </p>

    </div>

    <script>
        function togglePassword(button) {
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('.material-symbols-outlined');

            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>

</body>

</html>