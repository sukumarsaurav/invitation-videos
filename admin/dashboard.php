<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/auth.php';  // Must be first for authentication
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Get stats
$stats = [
    'revenue' => Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM orders WHERE status IN ('paid', 'completed')")['total'] ?? 0,
    'orders' => Database::fetchOne("SELECT COUNT(*) as total FROM orders")['total'] ?? 0,
    'templates' => Database::fetchOne("SELECT COUNT(*) as total FROM templates WHERE is_active = 1")['total'] ?? 0,
    'support' => Database::fetchOne("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'")['total'] ?? 0,
];

// Recent orders
$recentOrders = Database::fetchAll(
    "SELECT o.*, t.title as template_title, u.name as customer_name, u.email as customer_email
     FROM orders o
     LEFT JOIN templates t ON o.template_id = t.id
     LEFT JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);

// Popular templates
$popularTemplates = Database::fetchAll(
    "SELECT title, purchase_count, 
            ROUND(purchase_count * 100.0 / NULLIF((SELECT SUM(purchase_count) FROM templates), 0), 1) as percentage
     FROM templates 
     WHERE is_active = 1 
     ORDER BY purchase_count DESC 
     LIMIT 4"
);

$pendingTickets = $stats['support'];
$pageTitle = 'Dashboard';
?>

<?php ob_start(); ?>

<!-- Breadcrumbs -->
<div class="flex items-center gap-2 text-sm mb-6">
    <a class="text-slate-500 hover:text-primary transition-colors" href="/admin">Home</a>
    <span class="material-symbols-outlined text-slate-300 text-[16px]">chevron_right</span>
    <span class="font-medium">Dashboard</span>
</div>

<!-- Header -->
<div class="flex justify-between items-end mb-8">
    <div>
        <h2 class="text-2xl font-bold tracking-tight">Dashboard Overview</h2>
        <p class="text-slate-500 mt-1">Here's what's happening with your video templates today.</p>
    </div>
    <span class="text-xs font-medium px-2 py-1 bg-white border border-slate-200 rounded text-slate-500">
        Last updated: Just now
    </span>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    <!-- Revenue -->
    <div
        class="bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="p-2 bg-primary/10 rounded-lg text-primary">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <span
                class="flex items-center text-xs font-bold text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-full">
                <span class="material-symbols-outlined text-[14px] mr-1">trending_up</span> 12%
            </span>
        </div>
        <div>
            <p class="text-slate-500 text-sm font-medium">Total Revenue</p>
            <h3 class="text-2xl font-bold mt-1">$<?= number_format($stats['revenue'], 2) ?></h3>
        </div>
    </div>

    <!-- Orders -->
    <div
        class="bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                <span class="material-symbols-outlined">shopping_cart</span>
            </div>
            <span
                class="flex items-center text-xs font-bold text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-full">
                <span class="material-symbols-outlined text-[14px] mr-1">trending_up</span> 5%
            </span>
        </div>
        <div>
            <p class="text-slate-500 text-sm font-medium">Total Orders</p>
            <h3 class="text-2xl font-bold mt-1"><?= number_format($stats['orders']) ?></h3>
        </div>
    </div>

    <!-- Templates -->
    <div
        class="bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-600">
                <span class="material-symbols-outlined">movie</span>
            </div>
            <span
                class="flex items-center text-xs font-bold text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-full">
                <span class="material-symbols-outlined text-[14px] mr-1">trending_up</span> 2%
            </span>
        </div>
        <div>
            <p class="text-slate-500 text-sm font-medium">Active Templates</p>
            <h3 class="text-2xl font-bold mt-1"><?= number_format($stats['templates']) ?></h3>
        </div>
    </div>

    <!-- Support -->
    <div
        class="bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg text-orange-600">
                <span class="material-symbols-outlined">support_agent</span>
            </div>
            <?php if ($stats['support'] > 0): ?>
                <span
                    class="flex items-center text-xs font-bold text-orange-600 bg-orange-100 dark:bg-orange-900/30 px-2 py-0.5 rounded-full">
                    <span class="material-symbols-outlined text-[14px] mr-1">priority_high</span> <?= $stats['support'] ?>
                    New
                </span>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-slate-500 text-sm font-medium">Pending Support</p>
            <h3 class="text-2xl font-bold mt-1"><?= number_format($stats['support']) ?></h3>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <!-- Revenue Chart -->
    <div
        class="lg:col-span-2 bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold">Revenue Over Time</h3>
                <p class="text-sm text-slate-500">Monthly performance</p>
            </div>
            <select
                class="bg-slate-100 dark:bg-white/5 border-none text-sm rounded-lg px-3 py-1 focus:ring-0 cursor-pointer">
                <option>Last 30 Days</option>
                <option>Last 6 Months</option>
                <option>Year to Date</option>
            </select>
        </div>

        <div class="h-64 w-full relative">
            <svg class="w-full h-full" viewBox="0 0 800 300" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="gradientPrimary" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#7f13ec" stop-opacity="0.2"></stop>
                        <stop offset="100%" stop-color="#7f13ec" stop-opacity="0"></stop>
                    </linearGradient>
                </defs>
                <!-- Grid lines -->
                <line x1="0" y1="299" x2="800" y2="299" stroke="#e2e8f0" stroke-dasharray="4 4" stroke-width="1" />
                <line x1="0" y1="225" x2="800" y2="225" stroke="#e2e8f0" stroke-dasharray="4 4" stroke-width="1" />
                <line x1="0" y1="150" x2="800" y2="150" stroke="#e2e8f0" stroke-dasharray="4 4" stroke-width="1" />
                <line x1="0" y1="75" x2="800" y2="75" stroke="#e2e8f0" stroke-dasharray="4 4" stroke-width="1" />
                <!-- Area -->
                <path d="M0,250 Q100,200 200,220 T400,150 T600,100 T800,50 L800,300 L0,300 Z"
                    fill="url(#gradientPrimary)" />
                <!-- Line -->
                <path d="M0,250 Q100,200 200,220 T400,150 T600,100 T800,50" fill="none" stroke="#7f13ec"
                    stroke-width="3" stroke-linecap="round" />
                <!-- Dots -->
                <circle cx="0" cy="250" r="4" fill="#7f13ec" />
                <circle cx="200" cy="220" r="4" fill="#7f13ec" />
                <circle cx="400" cy="150" r="4" fill="#7f13ec" />
                <circle cx="600" cy="100" r="4" fill="#7f13ec" />
                <circle cx="800" cy="50" r="4" fill="#7f13ec" />
            </svg>
        </div>

        <div class="flex justify-between mt-4 text-xs font-medium text-slate-500">
            <span>Week 1</span>
            <span>Week 2</span>
            <span>Week 3</span>
            <span>Week 4</span>
        </div>
    </div>

    <!-- Popular Templates -->
    <div
        class="lg:col-span-1 bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
        <div class="mb-6">
            <h3 class="text-lg font-bold">Popular Templates</h3>
            <p class="text-sm text-slate-500">By sales volume</p>
        </div>

        <div class="flex-1 flex flex-col justify-end gap-4">
            <?php
            $colors = ['bg-primary', 'bg-slate-800', 'bg-slate-500', 'bg-slate-300'];
            foreach ($popularTemplates as $i => $tpl):
                ?>
                <div class="flex flex-col gap-1">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium"><?= Security::escape($tpl['title']) ?></span>
                        <span class="text-slate-500"><?= $tpl['percentage'] ?? 0 ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-white/10 rounded-full h-2.5">
                        <div class="<?= $colors[$i] ?? 'bg-slate-400' ?> h-2.5 rounded-full"
                            style="width: <?= $tpl['percentage'] ?? 0 ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($popularTemplates)): ?>
                <p class="text-slate-500 text-sm text-center py-8">No template data yet</p>
            <?php endif; ?>
        </div>

        <button
            class="mt-6 w-full py-2 text-sm font-bold text-primary border border-primary/20 rounded-lg hover:bg-primary/5 transition-colors">
            View Full Report
        </button>
    </div>
</div>

<!-- Bottom Section -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">

    <!-- Recent Orders Table -->
    <div
        class="xl:col-span-2 bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <h3 class="text-lg font-bold">Recent Orders</h3>
            <a href="/admin/orders.php" class="text-sm font-semibold text-primary hover:underline">View All</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-500">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Order ID</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Template</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($recentOrders as $order):
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'paid' => 'bg-green-100 text-green-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'failed' => 'bg-red-100 text-red-800',
                        ];
                        $statusColor = $statusColors[$order['status']] ?? 'bg-slate-100 text-slate-800';
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                #<?= Security::escape($order['order_number']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-6 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold">
                                        <?= strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <?= Security::escape($order['customer_name'] ?? 'Unknown') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?= Security::escape($order['template_title'] ?? '-') ?></td>
                            <td class="px-6 py-4"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                                <?= $order['currency'] === 'INR' ? '₹' : '$' ?>     <?= number_format($order['amount'], 2) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No orders yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="xl:col-span-1 flex flex-col gap-6">

        <div
            class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="/admin/templates.php?action=new"
                    class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                    <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">upload_file</span>
                    <span class="text-xs font-bold group-hover:text-primary">Upload Template</span>
                </a>
                <a href="/admin/users.php?action=new"
                    class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                    <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">group_add</span>
                    <span class="text-xs font-bold group-hover:text-primary">Add User</span>
                </a>
                <a href="/admin/reports.php"
                    class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                    <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">summarize</span>
                    <span class="text-xs font-bold group-hover:text-primary">Generate Report</span>
                </a>
                <a href="/admin/support.php"
                    class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                    <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">mail</span>
                    <span class="text-xs font-bold group-hover:text-primary">Support Queue</span>
                </a>
            </div>
        </div>

        <!-- Promo Card -->
        <div
            class="bg-gradient-to-br from-primary to-purple-800 rounded-xl shadow-md p-6 text-white relative overflow-hidden">
            <div class="relative z-10">
                <h4 class="text-lg font-bold mb-2">Pro Feature Available</h4>
                <p class="text-white/80 text-sm mb-4">Upgrade to unlock advanced analytics and unlimited template
                    storage.</p>
                <button
                    class="bg-white text-primary text-xs font-bold px-4 py-2 rounded-lg shadow hover:bg-slate-100 transition-colors">
                    View Plans
                </button>
            </div>
            <div class="absolute -right-6 -bottom-6 opacity-20">
                <span class="material-symbols-outlined text-[120px]">rocket_launch</span>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>