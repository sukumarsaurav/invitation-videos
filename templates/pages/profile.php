<?php
/**
 * User Profile Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Require authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = '/profile';
    header('Location: /login');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $_SESSION['errors'] = ['Invalid security token'];
        header('Location: /profile');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($errors)) {
        Database::query(
            "UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?",
            [$name, $phone, $userId]
        );

        $_SESSION['user_name'] = $name;
        $_SESSION['success'] = 'Profile updated successfully!';
        header('Location: /profile');
        exit;
    }

    $_SESSION['errors'] = $errors;
}

// Get user data
$user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

$pageTitle = 'My Profile';
$success = $_SESSION['success'] ?? null;
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['success'], $_SESSION['errors']);
?>

<?php ob_start(); ?>

<div class="max-w-2xl mx-auto px-4 py-8 sm:py-12">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">My Profile</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">Manage your account details</p>
    </div>

    <!-- Profile Card -->
    <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <!-- Profile Header -->
        <div
            class="bg-gradient-to-r from-primary/10 to-purple-500/10 p-6 sm:p-8 flex flex-col sm:flex-row items-center gap-4">
            <?php if ($user['avatar_url']): ?>
                <img src="<?= Security::escape($user['avatar_url']) ?>" alt="Profile"
                    class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg">
            <?php else: ?>
                <div
                    class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold border-4 border-white shadow-lg">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div class="text-center sm:text-left">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                    <?= Security::escape($user['name'] ?? 'User') ?>
                </h2>
                <p class="text-slate-600 dark:text-slate-400"><?= Security::escape($user['email']) ?></p>
                <?php if ($user['google_id']): ?>
                    <span
                        class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 bg-white/80 rounded-full text-xs text-slate-600">
                        <svg class="w-3 h-3" viewBox="0 0 24 24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Linked with Google
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div
                class="mx-6 mt-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= Security::escape($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mx-6 mt-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?= Security::escape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Profile Form -->
        <form action="/profile" method="POST" class="p-6 sm:p-8 space-y-6">
            <?= Security::csrfField() ?>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Full Name
                    </label>
                    <input type="text" id="name" name="name" required
                        value="<?= Security::escape($user['name'] ?? '') ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Email Address
                    </label>
                    <input type="email" id="email" disabled value="<?= Security::escape($user['email']) ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 dark:bg-slate-800 text-slate-500 cursor-not-allowed">
                    <p class="text-xs text-slate-500 mt-1">Email cannot be changed</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" value="<?= Security::escape($user['phone'] ?? '') ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="+1 234 567 890">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Member Since
                    </label>
                    <input type="text" disabled value="<?= date('F j, Y', strtotime($user['created_at'])) ?>"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 dark:bg-slate-800 text-slate-500 cursor-not-allowed">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="submit"
                    class="flex-1 py-3 px-6 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">save</span>
                    Save Changes
                </button>

                <?php if (!$user['google_id']): ?>
                    <a href="/change-password"
                        class="flex-1 py-3 px-6 border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">lock</span>
                        Change Password
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Quick Links -->
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <a href="/my-orders"
            class="block p-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 hover:shadow-lg transition-all">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">shopping_bag</span>
                </div>
                <div>
                    <h3 class="font-medium text-slate-900 dark:text-white">My Orders</h3>
                    <p class="text-sm text-slate-500">View order history</p>
                </div>
            </div>
        </a>

        <a href="/templates"
            class="block p-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 hover:shadow-lg transition-all">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined">video_library</span>
                </div>
                <div>
                    <h3 class="font-medium text-slate-900 dark:text-white">Browse Templates</h3>
                    <p class="text-sm text-slate-500">Create new invitation</p>
                </div>
            </div>
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>