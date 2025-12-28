<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$pageTitle = 'Login';
?>

<?php ob_start(); ?>

<div class="min-h-[80vh] flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-4xl">movie_filter</span>
                <span class="text-2xl font-bold"><?= APP_NAME ?></span>
            </a>
            <h1 class="mt-6 text-3xl font-bold text-slate-900 dark:text-white">Welcome back</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">Sign in to your account</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-8 border border-slate-200 dark:border-slate-800">
            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    <?= Security::escape($error) ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-6">
                <?= Security::csrfField() ?>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="you@example.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <span id="eyeIcon" class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember"
                            class="rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Remember me</span>
                    </label>
                    <a href="/forgot-password" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-lg shadow-primary/30 transition-all">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-slate-600 dark:text-slate-400">
                    Don't have an account?
                    <a href="/register" class="text-primary font-semibold hover:underline">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>