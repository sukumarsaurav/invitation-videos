<?php
/**
 * Register Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$pageTitle = 'Create Account';
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
            <h1 class="mt-6 text-3xl font-bold text-slate-900 dark:text-white">Create your account</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">Start creating beautiful video invitations</p>
        </div>

        <!-- Register Form -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-8 border border-slate-200 dark:border-slate-800">
            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?= Security::escape($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/register" method="POST" class="space-y-5">
                <?= Security::csrfField() ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Full Name
                    </label>
                    <input type="text" id="name" name="name" required
                        value="<?= Security::escape($old['name'] ?? '') ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="John Doe">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required
                        value="<?= Security::escape($old['email'] ?? '') ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="you@example.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Password
                    </label>
                    <input type="password" id="password" name="password" required minlength="8"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Minimum 8 characters">
                </div>

                <div>
                    <label for="confirm_password"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Repeat your password">
                </div>

                <div class="flex items-start gap-2">
                    <input type="checkbox" id="terms" name="terms" required
                        class="mt-1 rounded border-slate-300 text-primary focus:ring-primary">
                    <label for="terms" class="text-sm text-slate-600 dark:text-slate-400">
                        I agree to the <a href="/terms" class="text-primary hover:underline">Terms of Service</a>
                        and <a href="/privacy" class="text-primary hover:underline">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-lg shadow-primary/30 transition-all">
                    Create Account
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white dark:bg-slate-900 text-slate-500">or continue with</span>
                </div>
            </div>

            <!-- Google Sign Up -->
            <a href="/auth/google"
                class="flex items-center justify-center gap-3 w-full py-3 px-4 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                    <path fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                    <path fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                </svg>
                <span class="font-medium text-slate-700 dark:text-slate-300">Sign up with Google</span>
            </a>

            <div class="mt-6 text-center">
                <p class="text-slate-600 dark:text-slate-400">
                    Already have an account?
                    <a href="/login" class="text-primary font-semibold hover:underline">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>