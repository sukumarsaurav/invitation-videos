<?php
/**
 * Admin Campaign Manager
 * 
 * Create, manage and track marketing campaigns with UTM tracking
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/CampaignService.php';

// Handle actions
$action = $_GET['action'] ?? 'list';
$campaignId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$message = $_GET['message'] ?? null;
$error = $_GET['error'] ?? null;

// Get date range for stats
$range = $_GET['range'] ?? '30d';
switch ($range) {
    case '7d':
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $dateTo = date('Y-m-d 23:59:59');
        break;
    case '90d':
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-90 days'));
        $dateTo = date('Y-m-d 23:59:59');
        break;
    default: // 30d
        $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $dateTo = date('Y-m-d 23:59:59');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'create') {
        try {
            $id = CampaignService::create([
                'name' => $_POST['name'] ?? '',
                'utm_source' => $_POST['utm_source'] ?? '',
                'utm_medium' => $_POST['utm_medium'] ?? '',
                'utm_campaign' => $_POST['utm_campaign'] ?? '',
                'utm_term' => $_POST['utm_term'] ?? null,
                'utm_content' => $_POST['utm_content'] ?? null,
                'landing_page' => $_POST['landing_page'] ?? '/',
                'status' => $_POST['status'] ?? 'draft',
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'budget' => !empty($_POST['budget']) ? (float) $_POST['budget'] : null,
                'notes' => $_POST['notes'] ?? null,
                'created_by' => $_SESSION['user_id']
            ]);
            header('Location: /admin/campaigns.php?message=Campaign created successfully');
            exit;
        } catch (Exception $e) {
            $error = 'Error creating campaign: ' . $e->getMessage();
        }
    }

    if ($postAction === 'update' && $campaignId) {
        try {
            CampaignService::update($campaignId, [
                'name' => $_POST['name'] ?? '',
                'utm_source' => $_POST['utm_source'] ?? '',
                'utm_medium' => $_POST['utm_medium'] ?? '',
                'utm_campaign' => $_POST['utm_campaign'] ?? '',
                'utm_term' => $_POST['utm_term'] ?? null,
                'utm_content' => $_POST['utm_content'] ?? null,
                'landing_page' => $_POST['landing_page'] ?? '/',
                'status' => $_POST['status'] ?? 'draft',
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'budget' => !empty($_POST['budget']) ? (float) $_POST['budget'] : null,
                'notes' => $_POST['notes'] ?? null,
            ]);
            header('Location: /admin/campaigns.php?message=Campaign updated successfully');
            exit;
        } catch (Exception $e) {
            $error = 'Error updating campaign: ' . $e->getMessage();
        }
    }

    if ($postAction === 'delete' && $campaignId) {
        CampaignService::hardDelete($campaignId);
        header('Location: /admin/campaigns.php?message=Campaign deleted');
        exit;
    }

    if ($postAction === 'toggle_status' && $campaignId) {
        $newStatus = $_POST['new_status'] ?? 'paused';
        CampaignService::update($campaignId, ['status' => $newStatus]);
        header('Location: /admin/campaigns.php?message=Campaign status updated');
        exit;
    }
}

// Get campaigns with stats
$campaigns = CampaignService::getAllWithStats($dateFrom, $dateTo);

// Get campaign for edit
$campaign = null;
if ($action === 'edit' && $campaignId) {
    $campaign = CampaignService::getById($campaignId);
    if (!$campaign) {
        header('Location: /admin/campaigns.php?error=Campaign not found');
        exit;
    }
}

// Get traffic source breakdown for overview
$trafficSources = CampaignService::getTrafficSourceBreakdown($dateFrom, $dateTo);

$pageTitle = 'Campaigns';
?>

<?php ob_start(); ?>

<!-- Flash Messages -->
<?php if ($message): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-700">
        <span class="material-symbols-outlined">check_circle</span>
        <span>
            <?= htmlspecialchars($message) ?>
        </span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700">
        <span class="material-symbols-outlined">error</span>
        <span>
            <?= htmlspecialchars($error) ?>
        </span>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Campaign List View -->

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold">Campaign Manager</h1>
            <p class="text-slate-500 text-sm mt-1">Create and track marketing campaigns with UTM parameters</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Date Range Filter -->
            <div class="flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <a href="?range=7d"
                    class="px-3 py-2 text-sm font-medium <?= $range === '7d' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">7d</a>
                <a href="?range=30d"
                    class="px-3 py-2 text-sm font-medium <?= $range === '30d' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">30d</a>
                <a href="?range=90d"
                    class="px-3 py-2 text-sm font-medium <?= $range === '90d' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50' ?>">90d</a>
            </div>

            <a href="?action=new"
                class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm transition-all">
                <span class="material-symbols-outlined text-lg">add</span>
                <span>New Campaign</span>
            </a>
        </div>
    </div>

    <!-- Traffic Source Overview -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
        <?php
        $sourceIcons = [
            'paid' => ['icon' => 'paid', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50'],
            'organic' => ['icon' => 'search', 'color' => 'text-green-500', 'bg' => 'bg-green-50'],
            'social' => ['icon' => 'group', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50'],
            'email' => ['icon' => 'mail', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50'],
            'referral' => ['icon' => 'link', 'color' => 'text-cyan-500', 'bg' => 'bg-cyan-50'],
            'direct' => ['icon' => 'globe', 'color' => 'text-slate-500', 'bg' => 'bg-slate-50']
        ];
        $sourceData = [];
        foreach ($trafficSources as $src) {
            $sourceData[$src['traffic_source']] = (int) $src['count'];
        }
        $totalVisitors = array_sum($sourceData);
        ?>
        <?php foreach ($sourceIcons as $source => $config): ?>
            <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 mb-2">
                    <div class="size-8 <?= $config['bg'] ?> rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg <?= $config['color'] ?>">
                            <?= $config['icon'] ?>
                        </span>
                    </div>
                    <span class="text-xs font-medium text-slate-500 uppercase">
                        <?= ucfirst($source) ?>
                    </span>
                </div>
                <p class="text-xl font-bold">
                    <?= number_format($sourceData[$source] ?? 0) ?>
                </p>
                <?php if ($totalVisitors > 0): ?>
                    <p class="text-xs text-slate-400">
                        <?= round((($sourceData[$source] ?? 0) / $totalVisitors) * 100, 1) ?>%
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="text-left px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Campaign
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Source
                        </th>
                        <th class="text-left px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                        </th>
                        <th class="text-right px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Visitors
                        </th>
                        <th class="text-right px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Conv.
                        </th>
                        <th class="text-right px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Revenue
                        </th>
                        <th class="text-center px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-4xl mb-2 block">campaign</span>
                                <p class="font-medium">No campaigns yet</p>
                                <p class="text-sm mt-1">Create your first marketing campaign to start tracking</p>
                                <a href="?action=new"
                                    class="inline-flex items-center gap-2 mt-4 bg-primary text-white px-4 py-2 rounded-lg font-medium">
                                    <span class="material-symbols-outlined text-lg">add</span>
                                    Create Campaign
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $c): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 bg-primary/10 rounded-lg flex items-center justify-center">
                                            <span class="material-symbols-outlined text-primary">campaign</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars($c['name']) ?>
                                            </p>
                                            <p class="text-xs text-slate-400 font-mono">
                                                <?= htmlspecialchars($c['utm_campaign']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium">
                                            <?= ucfirst($c['utm_source']) ?>
                                        </span>
                                        <span class="text-slate-300">/</span>
                                        <span class="text-sm text-slate-500">
                                            <?= $c['utm_medium'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-600',
                                        'active' => 'bg-green-100 text-green-700',
                                        'paused' => 'bg-amber-100 text-amber-700',
                                        'ended' => 'bg-red-100 text-red-700'
                                    ];
                                    $statusIcons = [
                                        'draft' => 'edit_note',
                                        'active' => 'play_arrow',
                                        'paused' => 'pause',
                                        'ended' => 'stop'
                                    ];
                                    ?>
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusColors[$c['status']] ?>">
                                        <span class="material-symbols-outlined text-sm">
                                            <?= $statusIcons[$c['status']] ?>
                                        </span>
                                        <?= ucfirst($c['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold">
                                        <?= number_format($c['visitors']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold">
                                        <?= number_format($c['conversions']) ?>
                                    </span>
                                    <?php if ($c['visitors'] > 0): ?>
                                        <span class="text-xs text-slate-400">(
                                            <?= $c['conversion_rate'] ?>%)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold">₹
                                        <?= number_format($c['revenue']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Copy URL -->
                                        <button
                                            onclick="copyURL(<?= $c['id'] ?>, '<?= htmlspecialchars(CampaignService::generateURL($c['id'])) ?>')"
                                            class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                            title="Copy tracking URL">
                                            <span class="material-symbols-outlined text-lg">content_copy</span>
                                        </button>

                                        <!-- Edit -->
                                        <a href="?action=edit&id=<?= $c['id'] ?>"
                                            class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Edit campaign">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </a>

                                        <!-- Toggle Status -->
                                        <?php if ($c['status'] === 'active'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="new_status" value="paused">
                                                <button type="submit" formaction="?action=toggle&id=<?= $c['id'] ?>"
                                                    class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors"
                                                    title="Pause campaign">
                                                    <span class="material-symbols-outlined text-lg">pause</span>
                                                </button>
                                            </form>
                                        <?php elseif ($c['status'] !== 'ended'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" formaction="?action=toggle&id=<?= $c['id'] ?>"
                                                    class="p-2 text-slate-400 hover:text-green-500 hover:bg-green-50 rounded-lg transition-colors"
                                                    title="Activate campaign">
                                                    <span class="material-symbols-outlined text-lg">play_arrow</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- URL Copied Toast -->
    <div id="url-toast"
        class="fixed bottom-6 right-6 bg-slate-900 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 opacity-0 transition-opacity pointer-events-none">
        <span class="material-symbols-outlined text-green-400">check_circle</span>
        <span>Tracking URL copied to clipboard!</span>
    </div>

    <script>
        function copyURL(id, url) {
            navigator.clipboard.writeText(url).then(() => {
                const toast = document.getElementById('url-toast');
                toast.classList.remove('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'pointer-events-none');
                }, 2000);
            });
        }
    </script>

<?php elseif ($action === 'new' || $action === 'edit'): ?>
    <!-- Create/Edit Campaign Form -->

    <div class="flex items-center gap-4 mb-8">
        <a href="/admin/campaigns.php" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="text-2xl font-bold">
                <?= $action === 'new' ? 'Create Campaign' : 'Edit Campaign' ?>
            </h1>
            <p class="text-slate-500 text-sm mt-1">Configure UTM parameters for tracking</p>
        </div>
    </div>

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-lg mb-4">Campaign Details</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Campaign Name *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($campaign['name'] ?? '') ?>" required
                            class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                            placeholder="e.g., Wedding Promo January 2026">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">UTM Source *</label>
                            <select name="utm_source" required
                                class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary">
                                <option value="">Select source...</option>
                                <?php foreach (CampaignService::SOURCES as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($campaign['utm_source'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">UTM Medium *</label>
                            <select name="utm_medium" required
                                class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary">
                                <option value="">Select medium...</option>
                                <?php foreach (CampaignService::MEDIUMS as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($campaign['utm_medium'] ?? '') === $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Campaign Identifier *</label>
                        <input type="text" name="utm_campaign"
                            value="<?= htmlspecialchars($campaign['utm_campaign'] ?? '') ?>" required
                            class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary"
                            placeholder="e.g., wedding_promo_jan_2026">
                        <p class="text-xs text-slate-400 mt-1">This will appear as utm_campaign in your tracking URL</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">UTM Term (Optional)</label>
                            <input type="text" name="utm_term" value="<?= htmlspecialchars($campaign['utm_term'] ?? '') ?>"
                                class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary"
                                placeholder="e.g., wedding+videos">
                            <p class="text-xs text-slate-400 mt-1">Paid search keywords</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">UTM Content (Optional)</label>
                            <input type="text" name="utm_content"
                                value="<?= htmlspecialchars($campaign['utm_content'] ?? '') ?>"
                                class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary"
                                placeholder="e.g., banner_a">
                            <p class="text-xs text-slate-400 mt-1">A/B test variant</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Landing Page</label>
                        <input type="text" name="landing_page"
                            value="<?= htmlspecialchars($campaign['landing_page'] ?? '/') ?>"
                            class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary"
                            placeholder="/">
                        <p class="text-xs text-slate-400 mt-1">The page visitors will land on (e.g., / or
                            /templates?category=wedding)</p>
                    </div>
                </div>
            </div>

            <!-- Schedule & Budget -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-lg mb-4">Schedule & Budget</h3>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Start Date</label>
                        <input type="date" name="start_date" value="<?= $campaign['start_date'] ?? '' ?>"
                            class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">End Date</label>
                        <input type="date" name="end_date" value="<?= $campaign['end_date'] ?? '' ?>"
                            class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Budget (₹)</label>
                        <input type="number" name="budget" value="<?= $campaign['budget'] ?? '' ?>" step="0.01" min="0"
                            class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary"
                            placeholder="0.00">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary resize-none"
                        placeholder="Internal notes about this campaign..."><?= htmlspecialchars($campaign['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-lg mb-4">Status</h3>

                <select name="status"
                    class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary">
                    <option value="draft" <?= ($campaign['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="active" <?= ($campaign['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="paused" <?= ($campaign['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
                    <option value="ended" <?= ($campaign['status'] ?? '') === 'ended' ? 'selected' : '' ?>>Ended</option>
                </select>

                <div class="flex gap-3 mt-4">
                    <button type="submit"
                        class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-4 rounded-lg transition-all">
                        <?= $action === 'new' ? 'Create Campaign' : 'Save Changes' ?>
                    </button>
                </div>

                <?php if ($action === 'edit'): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <button type="button"
                            onclick="if(confirm('Are you sure you want to delete this campaign?')) { document.getElementById('delete-form').submit(); }"
                            class="w-full text-red-500 hover:bg-red-50 font-medium py-2 rounded-lg transition-colors">
                            Delete Campaign
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- URL Preview -->
            <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-lg mb-4">Generated URL</h3>

                <div class="bg-slate-50 rounded-lg p-4 break-all font-mono text-xs text-slate-600" id="url-preview">
                    <?php if ($campaign): ?>
                        <?= htmlspecialchars(CampaignService::generateURL($campaign['id'])) ?>
                    <?php else: ?>
                        <span class="text-slate-400">Fill in the form to generate URL...</span>
                    <?php endif; ?>
                </div>

                <?php if ($campaign): ?>
                    <button type="button"
                        onclick="copyURL(<?= $campaign['id'] ?>, '<?= htmlspecialchars(CampaignService::generateURL($campaign['id'])) ?>')"
                        class="w-full mt-4 flex items-center justify-center gap-2 border border-slate-200 hover:bg-slate-50 font-medium py-2.5 rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-lg">content_copy</span>
                        Copy URL
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($campaign): ?>
                <!-- Campaign Stats -->
                <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-bold text-lg mb-4">Performance</h3>

                    <?php $stats = CampaignService::getStats($campaign['id'], $dateFrom, $dateTo); ?>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Visitors</span>
                            <span class="font-semibold">
                                <?= number_format($stats['visitors']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Conversions</span>
                            <span class="font-semibold">
                                <?= number_format($stats['conversions']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Conv. Rate</span>
                            <span class="font-semibold">
                                <?= $stats['conversion_rate'] ?>%
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Revenue</span>
                            <span class="font-semibold">₹
                                <?= number_format($stats['revenue']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($action === 'edit'): ?>
        <form id="delete-form" method="POST" action="?action=delete&id=<?= $campaign['id'] ?>" class="hidden">
            <input type="hidden" name="action" value="delete">
        </form>
    <?php endif; ?>

    <!-- URL Copied Toast -->
    <div id="url-toast"
        class="fixed bottom-6 right-6 bg-slate-900 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 opacity-0 transition-opacity pointer-events-none">
        <span class="material-symbols-outlined text-green-400">check_circle</span>
        <span>Tracking URL copied to clipboard!</span>
    </div>

    <script>
        function copyURL(id, url) {
            navigator.clipboard.writeText(url).then(() => {
                const toast = document.getElementById('url-toast');
                toast.classList.remove('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'pointer-events-none');
                }, 2000);
            });
        }

        // Live URL preview update
        document.querySelectorAll('input[name="utm_source"], input[name="utm_medium"], input[name="utm_campaign"], input[name="landing_page"], select[name="utm_source"], select[name="utm_medium"]').forEach(input => {
            input.addEventListener('change', updateURLPreview);
            input.addEventListener('input', updateURLPreview);
        });

        function updateURLPreview() {
            const source = document.querySelector('[name="utm_source"]').value;
            const medium = document.querySelector('[name="utm_medium"]').value;
            const campaign = document.querySelector('[name="utm_campaign"]').value;
            const landingPage = document.querySelector('[name="landing_page"]').value || '/';

            if (source && medium && campaign) {
                const params = new URLSearchParams({
                    utm_source: source,
                    utm_medium: medium,
                    utm_campaign: campaign
                });

                document.getElementById('url-preview').textContent =
                    'https://invitationvideos.com' + landingPage + '?' + params.toString();
            }
        }
    </script>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/admin.php';
?>