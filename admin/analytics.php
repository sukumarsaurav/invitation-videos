<?php
/**
 * Admin Analytics Dashboard
 * 
 * Real-time visitor stats, geo-location breakdown, and conversion funnel
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/VisitorTracker.php';
require_once __DIR__ . '/../src/Services/CampaignService.php';

// Date range filter
$range = $_GET['range'] ?? '7d';
$customFrom = $_GET['from'] ?? null;
$customTo = $_GET['to'] ?? null;

// Calculate date range
switch ($range) {
    case '24h':
        $dateFrom = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $dateTo = date('Y-m-d H:i:s');
        $rangeLabel = 'Last 24 Hours';
        break;
    case '30d':
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $dateTo = date('Y-m-d 23:59:59');
        $rangeLabel = 'Last 30 Days';
        break;
    case '90d':
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-90 days'));
        $dateTo = date('Y-m-d 23:59:59');
        $rangeLabel = 'Last 90 Days';
        break;
    case 'custom':
        $dateFrom = $customFrom ? date('Y-m-d 00:00:00', strtotime($customFrom)) : date('Y-m-d 00:00:00', strtotime('-7 days'));
        $dateTo = $customTo ? date('Y-m-d 23:59:59', strtotime($customTo)) : date('Y-m-d 23:59:59');
        $rangeLabel = 'Custom Range';
        break;
    default: // 7d
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $dateTo = date('Y-m-d 23:59:59');
        $rangeLabel = 'Last 7 Days';
}

// Get analytics data
$stats = VisitorTracker::getStats($dateFrom, $dateTo);
$activeVisitors = VisitorTracker::getActiveVisitorCount(5);
$visitorsByCountry = VisitorTracker::getVisitorsByCountry($dateFrom, $dateTo, 10);
$visitorsByCity = VisitorTracker::getVisitorsByCity($dateFrom, $dateTo, 10);
$funnel = VisitorTracker::getConversionFunnel($dateFrom, $dateTo);
$visitorsByDay = VisitorTracker::getVisitorsByDay($dateFrom, $dateTo);
$topPages = VisitorTracker::getTopPages($dateFrom, $dateTo, 10);
$deviceBreakdown = VisitorTracker::getDeviceBreakdown($dateFrom, $dateTo);
$browserBreakdown = VisitorTracker::getBrowserBreakdown($dateFrom, $dateTo);

// Get campaign/traffic source data
$trafficSourceBreakdown = CampaignService::getTrafficSourceBreakdown($dateFrom, $dateTo);
$topCampaigns = CampaignService::getTopCampaigns($dateFrom, $dateTo, 5);
$utmSourceBreakdown = CampaignService::getSourceBreakdown($dateFrom, $dateTo);

// Calculate funnel percentages
$funnelPercentages = [
    'template_views' => $funnel['visitors'] > 0 ? round(($funnel['template_views'] / $funnel['visitors']) * 100, 1) : 0,
    'registrations' => $funnel['visitors'] > 0 ? round(($funnel['registrations'] / $funnel['visitors']) * 100, 1) : 0,
    'purchases' => $funnel['visitors'] > 0 ? round(($funnel['purchases'] / $funnel['visitors']) * 100, 1) : 0,
];

// Chart data
$chartLabels = json_encode(array_map(fn($d) => date('M j', strtotime($d['date'])), $visitorsByDay));
$chartVisitors = json_encode(array_map(fn($d) => (int) $d['visitors'], $visitorsByDay));
$chartUnique = json_encode(array_map(fn($d) => (int) $d['unique_visitors'], $visitorsByDay));

$pageTitle = 'Analytics';
?>

<?php ob_start(); ?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold">Analytics Dashboard</h1>
        <p class="text-slate-500 text-sm mt-1">Track visitors, conversions, and user behavior</p>
    </div>

    <!-- Date Range Filter -->
    <div class="flex items-center gap-2">
        <div class="flex rounded-lg border border-slate-200 overflow-hidden">
            <a href="?range=24h"
                class="px-3 py-2 text-sm font-medium <?= $range === '24h' ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                24h
            </a>
            <a href="?range=7d"
                class="px-3 py-2 text-sm font-medium border-l border-slate-200 <?= $range === '7d' ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                7d
            </a>
            <a href="?range=30d"
                class="px-3 py-2 text-sm font-medium border-l border-slate-200 <?= $range === '30d' ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                30d
            </a>
            <a href="?range=90d"
                class="px-3 py-2 text-sm font-medium border-l border-slate-200 <?= $range === '90d' ? 'bg-primary text-white' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                90d
            </a>
        </div>
    </div>
</div>

<!-- Real-time Stats Bar -->
<div class="bg-gradient-to-r from-primary to-purple-600 rounded-xl p-4 mb-6 text-white">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                <span class="font-medium">Active Now</span>
            </div>
            <span class="text-3xl font-bold">
                <?= $activeVisitors ?>
            </span>
            <span class="text-white/70">visitors in last 5 minutes</span>
        </div>
        <button onclick="refreshStats()"
            class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors">
            <span class="material-symbols-outlined text-lg">refresh</span>
            Refresh
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- Total Visitors -->
    <div class="bg-white p-5 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
            <span class="material-symbols-outlined text-2xl text-blue-500">group</span>
        </div>
        <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Total Visitors</p>
        <h3 class="text-2xl font-bold mt-1">
            <?= number_format($stats['total_visitors']) ?>
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            <?= number_format($stats['unique_visitors']) ?> unique
        </p>
    </div>

    <!-- Page Views -->
    <div class="bg-white p-5 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
            <span class="material-symbols-outlined text-2xl text-green-500">visibility</span>
        </div>
        <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Page Views</p>
        <h3 class="text-2xl font-bold mt-1">
            <?= number_format($stats['total_pageviews']) ?>
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            <?= $stats['total_visitors'] > 0 ? number_format($stats['total_pageviews'] / $stats['total_visitors'], 1) : 0 ?>
            per visit
        </p>
    </div>

    <!-- Returning Visitors -->
    <div class="bg-white p-5 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
            <span class="material-symbols-outlined text-2xl text-amber-500">autorenew</span>
        </div>
        <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Returning</p>
        <h3 class="text-2xl font-bold mt-1">
            <?= number_format($stats['returning_visitors']) ?>
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            <?= $stats['total_visitors'] > 0 ? number_format(($stats['returning_visitors'] / $stats['total_visitors']) * 100, 1) : 0 ?>%
            return rate
        </p>
    </div>

    <!-- Conversion Rate -->
    <div class="bg-white p-5 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
            <span class="material-symbols-outlined text-2xl text-purple-500">trending_up</span>
        </div>
        <p class="text-xs text-slate-500 uppercase font-bold tracking-wide">Conversion</p>
        <h3 class="text-2xl font-bold mt-1">
            <?= $funnelPercentages['purchases'] ?>%
        </h3>
        <p class="text-xs text-slate-400 mt-1">
            <?= $funnel['purchases'] ?> purchases
        </p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Visitors Chart -->
    <div
        class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200">
        <h3 class="font-bold text-lg mb-1">Visitors Over Time</h3>
        <p class="text-sm text-slate-500 mb-4">
            <?= $rangeLabel ?>
        </p>
        <div class="h-64">
            <canvas id="visitorsChart"></canvas>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h3 class="font-bold text-lg mb-1">Conversion Funnel</h3>
        <p class="text-sm text-slate-500 mb-4">
            <?= $rangeLabel ?>
        </p>
        <div class="space-y-4">
            <!-- Visitors -->
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium">Visitors</span>
                    <span class="text-slate-500">
                        <?= number_format($funnel['visitors']) ?>
                    </span>
                </div>
                <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" style="width: 100%"></div>
                </div>
            </div>
            <!-- Template Views -->
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium">Viewed Templates</span>
                    <span class="text-slate-500">
                        <?= number_format($funnel['template_views']) ?> (
                        <?= $funnelPercentages['template_views'] ?>%)
                    </span>
                </div>
                <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full"
                        style="width: <?= min($funnelPercentages['template_views'], 100) ?>%"></div>
                </div>
            </div>
            <!-- Registrations -->
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium">Registered</span>
                    <span class="text-slate-500">
                        <?= number_format($funnel['registrations']) ?> (
                        <?= $funnelPercentages['registrations'] ?>%)
                    </span>
                </div>
                <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 rounded-full"
                        style="width: <?= min($funnelPercentages['registrations'], 100) ?>%"></div>
                </div>
            </div>
            <!-- Purchases -->
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium">Purchased</span>
                    <span class="text-slate-500">
                        <?= number_format($funnel['purchases']) ?> (
                        <?= $funnelPercentages['purchases'] ?>%)
                    </span>
                </div>
                <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-purple-500 rounded-full"
                        style="width: <?= min($funnelPercentages['purchases'], 100) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Geo & Pages Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Countries -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">public</span>
                Top Countries
            </h3>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($visitorsByCountry)): ?>
                <div class="p-8 text-center text-slate-500">
                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">analytics</span>
                    <p>No visitor data yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($visitorsByCountry as $i => $country): ?>
                    <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50:bg-slate-800/50">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">
                                <?= getCountryFlag($country['country_code']) ?>
                            </span>
                            <span class="font-medium">
                                <?= htmlspecialchars($country['country_name']) ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                                <?php $maxCount = $visitorsByCountry[0]['visitor_count'] ?? 1; ?>
                                <div class="h-full bg-blue-500 rounded-full"
                                    style="width: <?= ($country['visitor_count'] / $maxCount) * 100 ?>%"></div>
                            </div>
                            <span class="text-sm font-medium w-12 text-right">
                                <?= number_format($country['visitor_count']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Cities -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-green-500">location_city</span>
                Top Cities
            </h3>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($visitorsByCity)): ?>
                <div class="p-8 text-center text-slate-500">
                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">analytics</span>
                    <p>No city data yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($visitorsByCity as $city): ?>
                    <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50:bg-slate-800/50">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">
                                <?= getCountryFlag($city['country_code']) ?>
                            </span>
                            <div>
                                <span class="font-medium">
                                    <?= htmlspecialchars($city['city']) ?>
                                </span>
                                <span class="text-xs text-slate-400 ml-1">
                                    <?= $city['country_code'] ?>
                                </span>
                            </div>
                        </div>
                        <span class="text-sm font-medium">
                            <?= number_format($city['visitor_count']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Device & Browser Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Device Breakdown -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Devices</h3>
        <div class="space-y-3">
            <?php
            $deviceIcons = ['desktop' => 'computer', 'mobile' => 'smartphone', 'tablet' => 'tablet'];
            $deviceTotal = array_sum(array_column($deviceBreakdown, 'count'));
            foreach ($deviceBreakdown as $device):
                $percent = $deviceTotal > 0 ? round(($device['count'] / $deviceTotal) * 100, 1) : 0;
                ?>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-slate-400">
                        <?= $deviceIcons[$device['device_type']] ?? 'devices' ?>
                    </span>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="capitalize">
                                <?= $device['device_type'] ?>
                            </span>
                            <span class="text-slate-500">
                                <?= $percent ?>%
                            </span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Browser Breakdown -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Browsers</h3>
        <div class="space-y-3">
            <?php
            $browserTotal = array_sum(array_column($browserBreakdown, 'count'));
            foreach (array_slice($browserBreakdown, 0, 5) as $browser):
                $percent = $browserTotal > 0 ? round(($browser['count'] / $browserTotal) * 100, 1) : 0;
                ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">
                        <?= htmlspecialchars($browser['browser']) ?>
                    </span>
                    <span class="text-sm text-slate-500">
                        <?= $percent ?>%
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Pages -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Top Pages</h3>
        <div class="space-y-2">
            <?php foreach (array_slice($topPages, 0, 5) as $page): ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm truncate max-w-[180px]" title="<?= htmlspecialchars($page['page_url']) ?>">
                        <?= htmlspecialchars($page['page_url']) ?>
                    </span>
                    <span class="text-sm font-medium text-slate-500">
                        <?= number_format($page['views']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Traffic Sources & Campaigns -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Traffic Source Breakdown -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">Traffic Sources</h3>
            <a href="/admin/campaigns.php" class="text-sm text-primary hover:underline">Manage</a>
        </div>
        <?php
        $sourceIcons = [
            'paid' => ['icon' => 'paid', 'color' => 'bg-amber-500'],
            'organic' => ['icon' => 'search', 'color' => 'bg-green-500'],
            'social' => ['icon' => 'group', 'color' => 'bg-blue-500'],
            'email' => ['icon' => 'mail', 'color' => 'bg-purple-500'],
            'referral' => ['icon' => 'link', 'color' => 'bg-cyan-500'],
            'direct' => ['icon' => 'globe', 'color' => 'bg-slate-500']
        ];
        $sourceData = [];
        foreach ($trafficSourceBreakdown as $src) {
            $sourceData[$src['traffic_source']] = (int)$src['count'];
        }
        $totalTrafficSources = array_sum($sourceData);
        ?>
        <div class="space-y-3">
            <?php foreach ($sourceIcons as $source => $config): ?>
            <?php 
            $count = $sourceData[$source] ?? 0;
            $percent = $totalTrafficSources > 0 ? round(($count / $totalTrafficSources) * 100, 1) : 0;
            ?>
            <div class="flex items-center gap-3">
                <div class="size-8 <?= $config['color'] ?> rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-sm"><?= $config['icon'] ?></span>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="capitalize font-medium"><?= $source ?></span>
                        <span class="text-slate-500"><?= number_format($count) ?> (<?= $percent ?>%)</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $config['color'] ?> rounded-full" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Campaigns -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">Top Campaigns</h3>
            <a href="/admin/campaigns.php?action=new" class="text-sm text-primary hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">add</span>
                New
            </a>
        </div>
        <?php if (empty($topCampaigns)): ?>
        <div class="text-center py-8 text-slate-400">
            <span class="material-symbols-outlined text-3xl mb-2">campaign</span>
            <p class="text-sm">No campaign data yet</p>
            <a href="/admin/campaigns.php?action=new" class="text-primary text-sm hover:underline mt-2 inline-block">Create your first campaign</a>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($topCampaigns as $campaign): ?>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div>
                    <p class="font-medium text-sm truncate max-w-[150px]"><?= htmlspecialchars($campaign['name']) ?></p>
                    <p class="text-xs text-slate-400"><?= ucfirst($campaign['utm_source']) ?></p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-sm"><?= number_format($campaign['visitor_count']) ?></p>
                    <p class="text-xs text-slate-400">visitors</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- UTM Source Breakdown -->
    <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h3 class="font-bold text-lg mb-4">By Source</h3>
        <?php if (empty($utmSourceBreakdown)): ?>
        <div class="text-center py-8 text-slate-400">
            <span class="material-symbols-outlined text-3xl mb-2">analytics</span>
            <p class="text-sm">No UTM data yet</p>
        </div>
        <?php else: ?>
        <div class="space-y-2">
            <?php 
            $maxSourceCount = !empty($utmSourceBreakdown) ? (int)$utmSourceBreakdown[0]['count'] : 1;
            foreach (array_slice($utmSourceBreakdown, 0, 6) as $src): 
            ?>
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium capitalize"><?= htmlspecialchars($src['utm_source']) ?></span>
                        <span class="text-slate-500"><?= number_format($src['count']) ?></span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="width: <?= ($src['count'] / $maxSourceCount) * 100 ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Visitors Chart
    const ctx = document.getElementById('visitorsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $chartLabels ?>,
            datasets: [
                {
                    label: 'Total Visitors',
                    data: <?= $chartVisitors ?>,
                    borderColor: '#7f13ec',
                    backgroundColor: 'rgba(127, 19, 236, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Unique Visitors',
                    data: <?= $chartUnique ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Refresh stats
    function refreshStats() {
        window.location.reload();
    }

    // Auto-refresh every 30 seconds for real-time stats
    setInterval(() => {
        // Only refresh the active visitors count via AJAX
        fetch('/api/track.php?action=active_count')
            .then(r => r.json())
            .then(data => {
                if (data.count !== undefined) {
                    document.querySelector('.text-3xl.font-bold').textContent = data.count;
                }
            })
            .catch(() => { });
    }, 30000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';

// Helper function for country flags
function getCountryFlag(string $countryCode): string
{
    $code = strtoupper($countryCode);
    if (strlen($code) !== 2)
        return '🌐';

    $flagOffset = 0x1F1E6;
    $asciiOffset = 0x41;

    $firstChar = mb_chr($flagOffset + (ord($code[0]) - $asciiOffset));
    $secondChar = mb_chr($flagOffset + (ord($code[1]) - $asciiOffset));

    return $firstChar . $secondChar;
}
?>