<?php
/**
 * Admin - Order Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if ($status) {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
$totalOrders = Database::fetchOne($countSql, $params)['total'] ?? 0;
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$sql = "SELECT o.*, t.title as template_title, t.thumbnail_url, u.name as customer_name, u.email as customer_email
        FROM orders o
        LEFT JOIN templates t ON o.template_id = t.id
        LEFT JOIN users u ON o.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $perPage OFFSET $offset";
$orders = Database::fetchAll($sql, $params);

// Get stats
$stats = [
    'new' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c'] ?? 0,
    'processing' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'processing'")['c'] ?? 0,
    'completed' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'completed'")['c'] ?? 0,
    'revenue_today' => Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as r FROM orders WHERE status IN ('paid','completed') AND DATE(created_at) = CURDATE()")['r'] ?? 0,
];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['new_status'];

    if (in_array($newStatus, ['pending', 'paid', 'processing', 'completed', 'failed', 'refunded'])) {
        Database::query("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
        header('Location: /admin/orders.php?success=updated');
        exit;
    }
}

$pendingTickets = 0;
$pageTitle = 'Orders';
?>

<?php ob_start(); ?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold">Order Management</h2>
        <p class="text-slate-500 mt-1">View and manage customer orders</p>
    </div>

    <div class="flex items-center gap-3">
        <button
            class="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors text-sm font-medium">
            <span class="material-symbols-outlined text-lg">download</span>
            Export
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        Order <?= $_GET['success'] ?> successfully!
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">New Orders (24h)</p>
        <p class="text-2xl font-bold mt-1"><?= $stats['new'] ?></p>
    </div>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">Processing</p>
        <p class="text-2xl font-bold mt-1"><?= $stats['processing'] ?></p>
    </div>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">Completed</p>
        <p class="text-2xl font-bold mt-1"><?= $stats['completed'] ?></p>
    </div>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">Revenue Today</p>
        <p class="text-2xl font-bold mt-1 text-green-600">$<?= number_format($stats['revenue_today'], 2) ?></p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <div class="relative">
                <span
                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-lg">search</span>
                <input type="text" name="search" value="<?= Security::escape($search) ?>"
                    class="w-full h-10 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm"
                    placeholder="Search by order ID, customer name or email...">
            </div>
        </div>

        <select name="status"
            class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <option value="">All Status</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>

        <button type="submit"
            class="h-10 px-6 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
            Filter
        </button>

        <?php if ($search || $status): ?>
            <a href="/admin/orders.php" class="text-sm text-slate-500 hover:text-primary">Clear filters</a>
        <?php endif; ?>
    </form>
</div>

<!-- Orders Table -->
<div
    class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-4 w-10">
                        <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-4">Order ID</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Template</th>
                    <th class="px-6 py-4">Date</th>
                    <th class="px-6 py-4">Amount</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($orders as $order):
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'paid' => 'bg-green-100 text-green-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'failed' => 'bg-red-100 text-red-800',
                        'refunded' => 'bg-slate-100 text-slate-800',
                    ];
                    $statusColor = $statusColors[$order['status']] ?? 'bg-slate-100 text-slate-800';
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary">
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="font-bold text-slate-900 dark:text-white">#<?= Security::escape($order['order_number']) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-8 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold shrink-0">
                                    <?= strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">
                                        <?= Security::escape($order['customer_name'] ?? 'Unknown') ?></p>
                                    <p class="text-xs text-slate-500">
                                        <?= Security::escape($order['customer_email'] ?? '') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <?php if ($order['thumbnail_url']): ?>
                                    <div class="size-8 rounded bg-slate-100 bg-cover bg-center shrink-0"
                                        style="background-image: url('<?= Security::escape($order['thumbnail_url']) ?>');">
                                    </div>
                                <?php endif; ?>
                                <span><?= Security::escape($order['template_title'] ?? '-') ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            <?= date('M j, Y', strtotime($order['created_at'])) ?>
                            <br><span class="text-xs"><?= date('g:i A', strtotime($order['created_at'])) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-900 dark:text-white">
                                <?= $order['currency'] === 'INR' ? '₹' : '$' ?>    <?= number_format($order['amount'], 2) ?>
                            </span>
                            <br><span class="text-xs text-slate-500"><?= $order['payment_gateway'] ?? '-' ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <a href="/admin/orders.php?action=view&id=<?= $order['id'] ?>"
                                    class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 hover:text-primary transition-colors"
                                    title="View Details">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </a>

                                <div class="relative group">
                                    <button
                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 transition-colors">
                                        <span class="material-symbols-outlined text-lg">more_vert</span>
                                    </button>
                                    <div
                                        class="absolute right-0 top-full mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 py-1 w-40 hidden group-hover:block z-10">
                                        <form method="POST" class="contents">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <button type="submit" name="new_status" value="processing"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5">Mark
                                                Processing</button>
                                            <button type="submit" name="new_status" value="completed"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5">Mark
                                                Completed</button>
                                            <button type="submit" name="new_status" value="refunded"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5 text-red-600">Refund</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">shopping_bag</span>
                            <p class="text-lg font-medium">No orders found</p>
                            <p class="text-sm">Orders will appear here once customers make purchases</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <p class="text-sm text-slate-500">
                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalOrders) ?> of <?= $totalOrders ?> orders
            </p>

            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                        class="w-10 h-10 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-600' ?> font-medium text-sm">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>