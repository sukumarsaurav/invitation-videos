<?php
/**
 * Admin Dashboard - Enhanced with Analytics
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Get time range for stats
$days = intval($_GET['days'] ?? 30);
$dateFrom = date('Y-m-d', strtotime("-$days days"));

// Get stats
$stats = [
    'revenue_total' => Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0,
    'revenue_period' => Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM orders WHERE payment_status = 'paid' AND created_at >= ?", [$dateFrom])['total'] ?? 0,
    'orders_total' => Database::fetchOne("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0,
    'orders_period' => Database::fetchOne("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'paid' AND created_at >= ?", [$dateFrom])['total'] ?? 0,
    'templates' => Database::fetchOne("SELECT COUNT(*) as total FROM templates WHERE is_active = 1")['total'] ?? 0,
    'support' => Database::fetchOne("SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'")['total'] ?? 0,
    'users_total' => Database::fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")['total'] ?? 0,
    'users_period' => Database::fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'customer' AND created_at >= ?", [$dateFrom])['total'] ?? 0,
];

// Revenue by day for chart (last 30 days)
$revenueByDay = Database::fetchAll(
    "SELECT DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as orders
     FROM orders 
     WHERE payment_status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date ASC"
);

// Build chart data with all days (including zeros)
$chartLabels = [];
$chartRevenue = [];
$chartOrders = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($date));
    $found = false;
    foreach ($revenueByDay as $row) {
        if ($row['date'] === $date) {
            $chartRevenue[] = floatval($row['revenue']);
            $chartOrders[] = intval($row['orders']);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chartRevenue[] = 0;
        $chartOrders[] = 0;
    }
}

// Sales by category
$salesByCategory = Database::fetchAll(
    "SELECT t.category, COUNT(o.id) as sales, SUM(o.amount) as revenue
     FROM orders o
     JOIN templates t ON o.template_id = t.id
     WHERE o.payment_status = 'paid'
     GROUP BY t.category
     ORDER BY sales DESC"
);

// Trending templates (by recent purchases)
$trendingTemplates = Database::fetchAll(
    "SELECT t.id, t.title, t.slug, t.thumbnail_url, t.price_usd, t.price_inr, 
            COUNT(o.id) as recent_sales, t.view_count
     FROM templates t
     LEFT JOIN orders o ON o.template_id = t.id AND o.payment_status = 'paid' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     WHERE t.is_active = 1
     GROUP BY t.id
     ORDER BY recent_sales DESC, t.view_count DESC
     LIMIT 5"
);

// Recent orders (more details)
$recentOrders = Database::fetchAll(
    "SELECT o.*, t.title as template_title, t.thumbnail_url, u.name as customer_name, u.email as customer_email, u.country_code
     FROM orders o
     LEFT JOIN templates t ON o.template_id = t.id
     LEFT JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 8"
);

// Users by country (for geographic overview)
$usersByCountry = Database::fetchAll(
    "SELECT country_code, COUNT(*) as count
     FROM users
     WHERE country_code IS NOT NULL AND country_code != ''
     GROUP BY country_code
     ORDER BY count DESC
     LIMIT 10"
);

// Conversion funnel data
$funnel = [
    'visitors' => 0, // Will come from visitors table when tracking is active
    'registered' => $stats['users_total'],
    'purchased' => Database::fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM orders WHERE payment_status = 'paid'")['c'] ?? 0,
];

// Order status breakdown
$orderStatus = Database::fetchAll(
    "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status"
);

$pendingTickets = $stats['support'];
$pageTitle = 'Dashboard';
?>

<?php ob_start(); ?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold tracking-tight">Dashboard Overview</h2>
        <p class="text-slate-500 mt-1">Analytics and performance metrics</p>
    </div>
    <div class="flex items-center gap-3">
        <select id="dateRange" onchange="window.location.href='/admin/dashboard.php?days=' + this.value"
            class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-surface-dark text-sm font-medium">
            <option value="7" <?= $days == 7 ? 'selected' : '' ?>>Last 7 days</option>
            <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Last 30 days</option>
            <option value="90" <?= $days == 90 ? 'selected' : '' ?>>Last 90 days</option>
            <option value="365" <?= $days == 365 ? 'selected' : '' ?>>Last year</option>
        </select>
        <span class="text-xs font-medium px-3 py-2 bg-green-100 text-green-700 rounded-lg flex items-center gap-1">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Live
        </span>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- Revenue -->
    <div class="bg-white dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">
                +<?= number_format($stats['revenue_period'], 0) ?>
            </span>
        </div>
        <p class="text-slate-500 text-xs font-medium uppercase">Total Revenue</p>
        <h3 class="text-2xl font-bold mt-1">$<?= number_format($stats['revenue_total'], 0) ?></h3>
    </div>

    <!-- Orders -->
    <div class="bg-white dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600">
                <span class="material-symbols-outlined">shopping_cart</span>
            </div>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                +<?= $stats['orders_period'] ?>
            </span>
        </div>
        <p class="text-slate-500 text-xs font-medium uppercase">Total Orders</p>
        <h3 class="text-2xl font-bold mt-1"><?= number_format($stats['orders_total']) ?></h3>
    </div>

    <!-- Customers -->
    <div class="bg-white dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-600">
                <span class="material-symbols-outlined">group</span>
            </div>
            <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded">
                +<?= $stats['users_period'] ?>
            </span>
        </div>
        <p class="text-slate-500 text-xs font-medium uppercase">Customers</p>
        <h3 class="text-2xl font-bold mt-1"><?= number_format($stats['users_total']) ?></h3>
    </div>

    <!-- Conversion Rate -->
    <div class="bg-white dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg text-orange-600">
                <span class="material-symbols-outlined">conversion_path</span>
            </div>
        </div>
        <p class="text-slate-500 text-xs font-medium uppercase">Conversion Rate</p>
        <?php $convRate = $stats['users_total'] > 0 ? round(($funnel['purchased'] / $stats['users_total']) * 100, 1) : 0; ?>
        <h3 class="text-2xl font-bold mt-1"><?= $convRate ?>%</h3>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Revenue Chart -->
    <div
        class="lg:col-span-2 bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold">Revenue & Orders</h3>
                <p class="text-sm text-slate-500">Last 30 days performance</p>
            </div>
        </div>
        <div class="h-72">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Sales by Category -->
    <div class="bg-white dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <h3 class="text-lg font-bold mb-2">Sales by Category</h3>
        <p class="text-sm text-slate-500 mb-6">All time distribution</p>
        <div class="h-56">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Trending & Recent -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
    <!-- Trending Templates -->
    <div
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-orange-500">local_fire_department</span>
                    Trending Now
                </h3>
                <p class="text-xs text-slate-500 mt-1">Top performing this week</p>
            </div>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($trendingTemplates as $i => $tpl): ?>
                <a href="/admin/templates.php?action=edit&id=<?= $tpl['id'] ?>"
                    class="flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-lg bg-slate-100 bg-cover bg-center"
                            style="background-image: url('<?= Security::escape($tpl['thumbnail_url'] ?? '') ?>');"></div>
                        <?php if ($i < 3): ?>
                            <span
                                class="absolute -top-1 -left-1 w-5 h-5 bg-orange-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                <?= $i + 1 ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm truncate"><?= Security::escape($tpl['title']) ?></p>
                        <p class="text-xs text-slate-500"><?= $tpl['recent_sales'] ?> sales this week</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-sm">$<?= number_format($tpl['price_usd'], 0) ?></p>
                        <p class="text-xs text-slate-400"><?= number_format($tpl['view_count']) ?> views</p>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (empty($trendingTemplates)): ?>
                <div class="p-8 text-center text-slate-500">
                    <span class="material-symbols-outlined text-4xl text-slate-300">trending_up</span>
                    <p class="mt-2">No trending data yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div
        class="xl:col-span-2 bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <h3 class="text-lg font-bold">Recent Orders</h3>
            <a href="/admin/orders.php" class="text-sm font-semibold text-primary hover:underline">View All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-medium text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($recentOrders as $order):
                        $statusColors = [
                            'awaiting_payment' => 'bg-yellow-100 text-yellow-700',
                            'queued' => 'bg-blue-100 text-blue-700',
                            'processing' => 'bg-purple-100 text-purple-700',
                            'completed' => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                        $statusColor = $statusColors[$order['order_status'] ?? 'awaiting_payment'] ?? 'bg-slate-100 text-slate-700';
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
                            <td class="px-5 py-3">
                                <a href="/admin/orders.php?action=view&id=<?= $order['id'] ?>"
                                    class="font-bold text-primary hover:underline">
                                    #<?= Security::escape($order['order_number']) ?>
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold">
                                        <?= strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium"><?= Security::escape($order['customer_name'] ?? 'Unknown') ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 font-bold">
                                <?= $order['currency'] === 'INR' ? '₹' : '$' ?>    <?= number_format($order['amount'], 0) ?>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                    <?= ucwords(str_replace('_', ' ', $order['order_status'] ?? 'pending')) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-500 text-xs">
                                <?= date('M j, g:i A', strtotime($order['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">No orders yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
    <!-- Geographic Overview -->
    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <h3 class="text-lg font-bold mb-1">Top Countries</h3>
        <p class="text-sm text-slate-500 mb-4">Customer distribution</p>
        <div class="space-y-3">
            <?php
            $countryFlags = ['US' => '🇺🇸', 'IN' => '🇮🇳', 'GB' => '🇬🇧', 'CA' => '🇨🇦', 'AU' => '🇦🇺', 'DE' => '🇩🇪', 'FR' => '🇫🇷', 'SG' => '🇸🇬', 'AE' => '🇦🇪', 'NZ' => '🇳🇿'];
            $countryNames = ['US' => 'United States', 'IN' => 'India', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'AU' => 'Australia', 'DE' => 'Germany', 'FR' => 'France', 'SG' => 'Singapore', 'AE' => 'UAE', 'NZ' => 'New Zealand'];
            $totalUsers = array_sum(array_column($usersByCountry, 'count'));
            foreach ($usersByCountry as $country):
                $pct = $totalUsers > 0 ? round(($country['count'] / $totalUsers) * 100) : 0;
                ?>
                <div class="flex items-center gap-3">
                    <span class="text-xl"><?= $countryFlags[$country['country_code']] ?? '🌍' ?></span>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span
                                class="font-medium"><?= $countryNames[$country['country_code']] ?? $country['country_code'] ?></span>
                            <span class="text-slate-500"><?= $country['count'] ?></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="bg-primary h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($usersByCountry)): ?>
                <p class="text-slate-500 text-sm text-center py-4">No location data yet</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Status Funnel -->
    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <h3 class="text-lg font-bold mb-1">Order Pipeline</h3>
        <p class="text-sm text-slate-500 mb-4">Current order status</p>
        <div class="space-y-3">
            <?php
            $statusData = array_column($orderStatus, 'count', 'order_status');
            $statusLabels = [
                'awaiting_payment' => ['label' => 'Awaiting Payment', 'color' => 'bg-yellow-500', 'icon' => 'hourglass_empty'],
                'queued' => ['label' => 'Queued', 'color' => 'bg-blue-500', 'icon' => 'schedule'],
                'processing' => ['label' => 'Processing', 'color' => 'bg-purple-500', 'icon' => 'autorenew'],
                'completed' => ['label' => 'Completed', 'color' => 'bg-green-500', 'icon' => 'check_circle'],
                'cancelled' => ['label' => 'Cancelled', 'color' => 'bg-red-500', 'icon' => 'cancel'],
            ];
            foreach ($statusLabels as $key => $info):
                $count = $statusData[$key] ?? 0;
                ?>
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-white/5">
                    <div class="w-8 h-8 rounded-lg <?= $info['color'] ?> text-white flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg"><?= $info['icon'] ?></span>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm"><?= $info['label'] ?></p>
                    </div>
                    <span class="text-lg font-bold"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="/admin/templates.php?action=new"
                class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">add_circle</span>
                <span class="text-xs font-bold">New Template</span>
            </a>
            <a href="/admin/orders.php"
                class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">receipt_long</span>
                <span class="text-xs font-bold">View Orders</span>
            </a>
            <a href="/admin/promo-codes.php"
                class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20">
                <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">sell</span>
                <span class="text-xs font-bold">Promo Codes</span>
            </a>
            <a href="/admin/support.php"
                class="flex flex-col items-center justify-center p-4 rounded-lg bg-slate-50 dark:bg-white/5 hover:bg-primary/10 hover:text-primary transition-all gap-2 group border border-transparent hover:border-primary/20 relative">
                <span class="material-symbols-outlined text-slate-600 group-hover:text-primary">support_agent</span>
                <span class="text-xs font-bold">Support</span>
                <?php if ($stats['support'] > 0): ?>
                    <span
                        class="absolute top-2 right-2 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                        <?= $stats['support'] ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Coming Soon -->
        <div class="mt-4 p-4 rounded-lg bg-gradient-to-br from-primary/10 to-purple-500/10 border border-primary/20">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-primary">auto_awesome</span>
                <span class="font-bold text-sm">Coming Soon</span>
            </div>
            <p class="text-xs text-slate-600">Blog management, visitor analytics, and SEO tools are coming in the next
                update!</p>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue & Orders Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode($chartRevenue) ?>,
                borderColor: '#7f13ec',
                backgroundColor: 'rgba(127, 19, 236, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Orders',
                data: <?= json_encode($chartOrders) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 20 }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { callback: v => '$' + v }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?= json_encode($salesByCategory) ?>;
    const categoryColors = {
        'wedding': '#ec4899',
        'birthday': '#f59e0b',
        'corporate': '#3b82f6',
        'baby_shower': '#10b981',
        'anniversary': '#8b5cf6',
        'other': '#6b7280'
    };
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(c => c.category.charAt(0).toUpperCase() + c.category.slice(1).replace('_', ' ')),
            datasets: [{
                data: categoryData.map(c => c.sales),
                backgroundColor: categoryData.map(c => categoryColors[c.category] || '#6b7280'),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 15, font: { size: 11 } }
                }
            }
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>