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