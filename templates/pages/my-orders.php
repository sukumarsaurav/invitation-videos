<?php
/**
 * My Orders Page - User order history
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Require authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = '/my-orders';
    header('Location: /login');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's orders
$orders = Database::fetchAll(
    "SELECT o.*, t.title as template_title, t.thumbnail_url 
     FROM orders o 
     LEFT JOIN templates t ON o.template_id = t.id 
     WHERE o.user_id = ? 
     ORDER BY o.created_at DESC",
    [$userId]
);

$pageTitle = 'My Orders';
?>

<?php ob_start(); ?>

<div class="max-w-6xl mx-auto px-4 py-8 sm:py-12">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">My Orders</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">Track and manage your video invitation orders</p>
    </div>

    <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="text-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
            <span class="material-symbols-outlined text-6xl text-slate-300">shopping_bag</span>
            <h3 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">No orders yet</h3>
            <p class="mt-2 text-slate-500">Start by browsing our beautiful templates</p>
            <a href="/templates"
                class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined">explore</span>
                Browse Templates
            </a>
        </div>
    <?php else: ?>
        <!-- Orders List -->
        <div class="space-y-4">
            <?php foreach ($orders as $order):
                $paymentColors = [
                    'pending' => 'bg-yellow-100 text-yellow-700',
                    'paid' => 'bg-green-100 text-green-700',
                    'failed' => 'bg-red-100 text-red-700',
                    'refunded' => 'bg-slate-100 text-slate-700'
                ];
                $orderColors = [
                    'awaiting_payment' => 'bg-yellow-100 text-yellow-700',
                    'queued' => 'bg-blue-100 text-blue-700',
                    'processing' => 'bg-purple-100 text-purple-700',
                    'completed' => 'bg-green-100 text-green-700',
                    'cancelled' => 'bg-red-100 text-red-700'
                ];
                $paymentColor = $paymentColors[$order['payment_status'] ?? 'pending'] ?? 'bg-slate-100 text-slate-700';
                $orderColor = $orderColors[$order['order_status'] ?? 'awaiting_payment'] ?? 'bg-slate-100 text-slate-700';

                // Calculate video expiry
                $videoExpired = false;
                $daysLeft = 0;
                if ($order['video_expires_at']) {
                    $daysLeft = ceil((strtotime($order['video_expires_at']) - time()) / 86400);
                    $videoExpired = $daysLeft <= 0;
                }
                ?>
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Thumbnail -->
                            <div class="w-full sm:w-32 h-48 sm:h-44 flex-shrink-0 rounded-lg overflow-hidden bg-slate-100">
                                <div class="w-full h-full bg-cover bg-center"
                                    style="background-image: url('<?= Security::escape($order['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>');">
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                            <?= Security::escape($order['template_title'] ?? 'Video Invitation') ?>
                                        </h3>
                                        <p class="text-sm text-slate-500 mt-1">Order
                                            #<?= Security::escape($order['order_number']) ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <span
                                            class="inline-flex self-start px-3 py-1 rounded-full text-xs font-bold <?= $paymentColor ?>">
                                            💳 <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                        </span>
                                        <span
                                            class="inline-flex self-start px-3 py-1 rounded-full text-xs font-bold <?= $orderColor ?>">
                                            📦
                                            <?= ucwords(str_replace('_', ' ', $order['order_status'] ?? 'awaiting_payment')) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 text-sm">
                                    <div>
                                        <span class="text-slate-500">Amount</span>
                                        <p class="font-bold text-slate-900 dark:text-white">
                                            <?= $order['currency'] === 'INR' ? '₹' : '$' ?>
                                            <?= number_format($order['amount'], 2) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Payment</span>
                                        <p class="font-medium text-slate-900 dark:text-white capitalize">
                                            <?= $order['payment_gateway'] ?? 'Pending' ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Date</span>
                                        <p class="font-medium text-slate-900 dark:text-white">
                                            <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Delivery</span>
                                        <p class="font-medium text-slate-900 dark:text-white">
                                            <?php if (($order['order_status'] ?? '') === 'completed'): ?>
                                                <span class="text-green-600">Ready</span>
                                            <?php elseif (($order['order_status'] ?? '') === 'processing'): ?>
                                                <span class="text-purple-600">In Progress</span>
                                            <?php elseif (($order['order_status'] ?? '') === 'queued'): ?>
                                                <span class="text-blue-600">In Queue</span>
                                            <?php else: ?>
                                                <span class="text-slate-400">—</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Video Download Section - Show if video exists -->
                                <?php if (!empty($order['output_video_url'])): ?>
                                    <div
                                        class="mt-4 p-3 rounded-lg <?= $videoExpired ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' ?>">
                                        <?php if ($videoExpired): ?>
                                            <div class="flex items-center gap-2 text-red-700">
                                                <span class="material-symbols-outlined">error</span>
                                                <p class="font-medium">Video download has expired. Please contact support if you need
                                                    access.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex items-center justify-between flex-wrap gap-3">
                                                <div class="flex items-center gap-2 text-green-700">
                                                    <span class="material-symbols-outlined">check_circle</span>
                                                    <div>
                                                        <p class="font-medium">Your video is ready!</p>
                                                        <?php if ($daysLeft > 0): ?>
                                                            <p class="text-sm">Download available for <?= $daysLeft ?> more
                                                                day<?= $daysLeft !== 1 ? 's' : '' ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <a href="<?= Security::escape($order['output_video_url']) ?>"
                                                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white font-bold text-sm rounded-lg hover:bg-green-700 transition-colors"
                                                        download>
                                                        <span class="material-symbols-outlined text-lg">download</span>
                                                        Download
                                                    </a>
                                                    <a href="<?= Security::escape($order['output_video_url']) ?>"
                                                        class="inline-flex items-center gap-2 px-4 py-2 border border-green-300 text-green-700 font-medium text-sm rounded-lg hover:bg-green-100 transition-colors"
                                                        target="_blank">
                                                        <span class="material-symbols-outlined text-lg">play_circle</span>
                                                        Preview
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="flex flex-wrap gap-3 mt-5 pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <?php if (($order['payment_status'] ?? 'pending') === 'pending'): ?>
                                        <a href="/checkout/<?= $order['id'] ?>"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white font-bold text-sm rounded-lg hover:bg-primary/90 transition-colors">
                                            <span class="material-symbols-outlined text-lg">payment</span>
                                            Complete Payment
                                        </a>
                                    <?php endif; ?>

                                    <?php if (in_array($order['order_status'] ?? '', ['awaiting_payment', 'queued', 'processing'])): ?>
                                        <a href="/support?order=<?= $order['order_number'] ?>"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-slate-600 font-medium text-sm rounded-lg hover:bg-slate-100 transition-colors">
                                            <span class="material-symbols-outlined text-lg">help</span>
                                            Get Help
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>