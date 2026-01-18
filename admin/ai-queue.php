<?php
/**
 * Admin - AI Generation Queue
 * Monitor and manage AI image generation jobs
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/../src/Services/AIGenerationService.php';

use InvitationVideos\Services\AIGenerationService;

$pageTitle = 'AI Queue';
$pendingTickets = 0;

$aiService = new AIGenerationService();

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $postAction = $_POST['action'] ?? '';

        try {
            switch ($postAction) {
                case 'retry':
                    $queueId = intval($_POST['queue_id'] ?? 0);
                    if ($aiService->retryQueueItem($queueId)) {
                        $message = 'Generation retried successfully';
                    } else {
                        $error = 'Retry failed - check error logs';
                    }
                    break;

                case 'process_queue':
                    $stats = $aiService->processQueue(5);
                    $message = "Processed: {$stats['processed']}, Succeeded: {$stats['succeeded']}, Failed: {$stats['failed']}";
                    break;
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Fetch queue items
$queueItems = $aiService->getQueueItems($statusFilter ?: null, $perPage, $offset);

// Get counts
$counts = Database::fetchAll(
    "SELECT status, COUNT(*) as cnt FROM ai_generation_queue GROUP BY status"
);
$statusCounts = [];
foreach ($counts as $c) {
    $statusCounts[$c['status']] = $c['cnt'];
}

// Check if AI is enabled
$isEnabled = $aiService->isEnabled();
$provider = $aiService->getProvider();
?>

<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold">AI Generation Queue</h1>
            <p class="text-slate-500">Monitor AI image generation jobs</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="process_queue">
                <button type="submit" class="btn-secondary" <?= !$isEnabled ? 'disabled' : '' ?>>
                    <span class="material-symbols-outlined text-lg">play_arrow</span>
                    Process Queue
                </button>
            </form>
            <a href="/admin/dress-designs.php" class="btn-secondary">
                <span class="material-symbols-outlined text-lg">checkroom</span>
                Dress Designs
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= Security::escape($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= Security::escape($error) ?>
        </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-600">hourglass_top</span>
                </div>
                <div>
                    <p class="text-2xl font-bold">
                        <?= $statusCounts['pending'] ?? 0 ?>
                    </p>
                    <p class="text-sm text-slate-500">Pending</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-amber-600">sync</span>
                </div>
                <div>
                    <p class="text-2xl font-bold">
                        <?= $statusCounts['processing'] ?? 0 ?>
                    </p>
                    <p class="text-sm text-slate-500">Processing</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-green-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                </div>
                <div>
                    <p class="text-2xl font-bold">
                        <?= $statusCounts['completed'] ?? 0 ?>
                    </p>
                    <p class="text-sm text-slate-500">Completed</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-600">error</span>
                </div>
                <div>
                    <p class="text-2xl font-bold">
                        <?= $statusCounts['failed'] ?? 0 ?>
                    </p>
                    <p class="text-sm text-slate-500">Failed</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div
                    class="size-10 rounded-full <?= $isEnabled ? 'bg-green-100' : 'bg-red-100' ?> flex items-center justify-center">
                    <span class="material-symbols-outlined <?= $isEnabled ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $isEnabled ? 'power' : 'power_off' ?>
                    </span>
                </div>
                <div>
                    <p class="text-sm font-bold">
                        <?= $provider->getProviderName() ?>
                    </p>
                    <p class="text-sm text-slate-500">
                        <?= $isEnabled ? 'Enabled' : 'Disabled' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-4">
            <a href="/admin/ai-queue.php"
                class="py-3 px-1 border-b-2 <?= !$statusFilter ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                All
            </a>
            <a href="/admin/ai-queue.php?status=pending"
                class="py-3 px-1 border-b-2 <?= $statusFilter === 'pending' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                Pending
            </a>
            <a href="/admin/ai-queue.php?status=processing"
                class="py-3 px-1 border-b-2 <?= $statusFilter === 'processing' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                Processing
            </a>
            <a href="/admin/ai-queue.php?status=completed"
                class="py-3 px-1 border-b-2 <?= $statusFilter === 'completed' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                Completed
            </a>
            <a href="/admin/ai-queue.php?status=failed"
                class="py-3 px-1 border-b-2 <?= $statusFilter === 'failed' ? 'border-primary text-primary font-medium' : 'border-transparent text-slate-500 hover:text-slate-700' ?>">
                Failed
            </a>
        </nav>
    </div>

    <!-- Queue Table -->
    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-white/5">
                        <th class="text-left px-6 py-3 text-sm font-medium text-slate-500">Order</th>
                        <th class="text-left px-6 py-3 text-sm font-medium text-slate-500">Dress / Color</th>
                        <th class="text-center px-6 py-3 text-sm font-medium text-slate-500">Status</th>
                        <th class="text-center px-6 py-3 text-sm font-medium text-slate-500">Attempts</th>
                        <th class="text-left px-6 py-3 text-sm font-medium text-slate-500">Created</th>
                        <th class="text-left px-6 py-3 text-sm font-medium text-slate-500">Result</th>
                        <th class="text-right px-6 py-3 text-sm font-medium text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($queueItems)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-4xl mb-2">inbox</span>
                                <p>No queue items found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($queueItems as $item): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5">
                                <td class="px-6 py-4">
                                    <a href="/admin/orders.php?search=<?= urlencode($item['order_number']) ?>"
                                        class="font-medium text-primary hover:underline">
                                        <?= Security::escape($item['order_number']) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium">
                                        <?= Security::escape($item['dress_name'] ?? 'Unknown') ?>
                                    </p>
                                    <?php if ($item['color_name']): ?>
                                        <p class="text-sm text-slate-500">
                                            <?= Security::escape($item['color_name']) ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'bg-blue-100 text-blue-700',
                                        'processing' => 'bg-amber-100 text-amber-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'failed' => 'bg-red-100 text-red-700'
                                    ];
                                    $statusClass = $statusClasses[$item['status']] ?? 'bg-slate-100 text-slate-700';
                                    ?>
                                    <span class="px-2 py-1 text-sm rounded-full <?= $statusClass ?>">
                                        <?= ucfirst($item['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="<?= $item['attempts'] >= $item['max_attempts'] ? 'text-red-600 font-medium' : '' ?>">
                                        <?= $item['attempts'] ?>/
                                        <?= $item['max_attempts'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <?= date('M j, g:ia', strtotime($item['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($item['status'] === 'completed' && $item['generated_image_url']): ?>
                                        <a href="<?= Security::escape($item['generated_image_url']) ?>" target="_blank"
                                            class="inline-flex items-center gap-1 text-primary hover:underline text-sm">
                                            <span class="material-symbols-outlined text-lg">image</span>
                                            View Image
                                        </a>
                                    <?php elseif ($item['status'] === 'failed' && $item['error_message']): ?>
                                        <span class="text-red-600 text-sm cursor-help"
                                            title="<?= Security::escape($item['error_message']) ?>">
                                            <?= Security::escape(substr($item['error_message'], 0, 50)) ?>...
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($item['status'] === 'failed' && $item['attempts'] < $item['max_attempts']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="retry">
                                                <input type="hidden" name="queue_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="p-2 hover:bg-slate-100 rounded-lg" title="Retry">
                                                    <span class="material-symbols-outlined text-primary">refresh</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" onclick="showPrompt(<?= $item['id'] ?>)"
                                            class="p-2 hover:bg-slate-100 rounded-lg" title="View Prompt">
                                            <span class="material-symbols-outlined text-slate-500">description</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Prompt Modal -->
    <div id="prompt-modal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-surface-dark rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="font-bold">Generation Prompt</h3>
                <button onclick="closePromptModal()" class="p-2 hover:bg-slate-100 rounded-lg">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6" id="prompt-content">
                <div class="animate-pulse bg-slate-100 h-32 rounded"></div>
            </div>
        </div>
    </div>

    <!-- Cron Setup Info -->
    <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-6">
        <h3 class="font-bold mb-2">⏰ Cron Setup</h3>
        <p class="text-sm text-slate-600 mb-3">
            To process the queue automatically, add this cron job (runs every minute):
        </p>
        <pre
            class="bg-slate-800 text-green-400 p-4 rounded-lg text-sm overflow-x-auto">* * * * * php <?= dirname(__DIR__) ?>/cron/process-ai-queue.php >> /var/log/ai-queue.log 2>&1</pre>
    </div>
</div>

<style>
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        background: #7f13ec;
        color: white;
        font-weight: 600;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        background: #6b0fcc;
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        background: white;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .btn-secondary:hover {
        background: #f8fafc;
    }

    .btn-secondary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<script>
    // Queue item prompts cache
    const prompts = <?= json_encode(array_column($queueItems, 'prompt_used', 'id')) ?>;

    function showPrompt(queueId) {
        document.getElementById('prompt-modal').classList.remove('hidden');
        document.getElementById('prompt-content').innerHTML = `<pre class="whitespace-pre-wrap text-sm">${prompts[queueId] || 'No prompt available'}</pre>`;
    }

    function closePromptModal() {
        document.getElementById('prompt-modal').classList.add('hidden');
    }

    // Close modal on outside click
    document.getElementById('prompt-modal').addEventListener('click', function (e) {
        if (e.target === this) closePromptModal();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>