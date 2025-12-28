<?php
/**
 * Admin - Support Ticket Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Get current ticket ID if viewing details
$ticketId = intval($_GET['id'] ?? 0);
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['ticket_id'] ?? 0);

    if ($action === 'reply' && $id) {
        $message = Security::sanitizeString($_POST['message'] ?? '');
        if ($message) {
            Database::query(
                "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, 'admin', ?, ?)",
                [$id, $_SESSION['admin_id'] ?? 1, $message]
            );
            // Update ticket status to in_progress if open
            Database::query("UPDATE support_tickets SET status = 'in_progress' WHERE id = ? AND status = 'open'", [$id]);
            header('Location: /admin/support.php?id=' . $id . '&success=replied');
            exit;
        }
    }

    if ($action === 'update_status' && $id) {
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['open', 'in_progress', 'resolved', 'closed'])) {
            Database::query("UPDATE support_tickets SET status = ? WHERE id = ?", [$newStatus, $id]);
            header('Location: /admin/support.php?id=' . $id . '&success=status_updated');
            exit;
        }
    }

    if ($action === 'update_priority' && $id) {
        $newPriority = $_POST['new_priority'] ?? '';
        if (in_array($newPriority, ['low', 'medium', 'high'])) {
            Database::query("UPDATE support_tickets SET priority = ? WHERE id = ?", [$newPriority, $id]);
            header('Location: /admin/support.php?id=' . $id . '&success=priority_updated');
            exit;
        }
    }
}

// Build query for tickets list
$whereConditions = [];
$params = [];

if ($filter === 'open') {
    $whereConditions[] = "t.status IN ('open', 'in_progress')";
} elseif ($filter === 'resolved') {
    $whereConditions[] = "t.status = 'resolved'";
} elseif ($filter === 'high') {
    $whereConditions[] = "t.priority = 'high'";
}

if ($search) {
    $whereConditions[] = "(t.subject LIKE ? OR t.ticket_number LIKE ? OR u.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get tickets
$tickets = Database::fetchAll(
    "SELECT t.*, u.name as customer_name, u.email as customer_email,
            (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count
     FROM support_tickets t
     LEFT JOIN users u ON t.user_id = u.id
     $whereClause
     ORDER BY 
        CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
        t.created_at DESC",
    $params
);

// Get current ticket details if viewing
$currentTicket = null;
$ticketMessages = [];
if ($ticketId) {
    $currentTicket = Database::fetchOne(
        "SELECT t.*, u.name as customer_name, u.email as customer_email, o.order_number
         FROM support_tickets t
         LEFT JOIN users u ON t.user_id = u.id
         LEFT JOIN orders o ON t.order_id = o.id
         WHERE t.id = ?",
        [$ticketId]
    );

    if ($currentTicket) {
        $ticketMessages = Database::fetchAll(
            "SELECT tm.*, 
                    CASE WHEN tm.sender_type = 'user' THEN u.name ELSE 'Support Team' END as sender_name
             FROM ticket_messages tm
             LEFT JOIN users u ON tm.sender_id = u.id AND tm.sender_type = 'user'
             WHERE tm.ticket_id = ?
             ORDER BY tm.created_at ASC",
            [$ticketId]
        );
    }
}

// Stats
$stats = [
    'total_open' => Database::fetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE status IN ('open', 'in_progress')")['c'] ?? 0,
    'high_priority' => Database::fetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE priority = 'high' AND status != 'closed'")['c'] ?? 0,
    'resolved_today' => Database::fetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE status = 'resolved' AND DATE(updated_at) = CURDATE()")['c'] ?? 0,
];

$pendingTickets = $stats['total_open'];
$pageTitle = 'Support';
?>

<?php ob_start(); ?>

<div class="flex h-[calc(100vh-8rem)] -m-8 -mb-8">

    <!-- Tickets List Panel -->
    <div
        class="w-full lg:w-[380px] bg-white dark:bg-surface-dark border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0">

        <!-- Filters Header -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Support Inbox</span>
                <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-0.5 rounded-full">
                    <?= $stats['total_open'] ?> Open
                </span>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2">
                <a href="/admin/support.php"
                    class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all <?= $filter === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-transparent hover:border-slate-300' ?>">
                    All
                </a>
                <a href="/admin/support.php?filter=open"
                    class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all <?= $filter === 'open' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-transparent hover:border-slate-300' ?>">
                    Open
                </a>
                <a href="/admin/support.php?filter=high"
                    class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all <?= $filter === 'high' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-transparent hover:border-slate-300' ?>">
                    High Priority
                </a>
                <a href="/admin/support.php?filter=resolved"
                    class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all <?= $filter === 'resolved' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-transparent hover:border-slate-300' ?>">
                    Resolved
                </a>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="flex-1 overflow-y-auto">
            <?php foreach ($tickets as $ticket):
                $isActive = $ticketId === intval($ticket['id']);
                $priorityColors = [
                    'high' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'low' => 'bg-slate-100 text-slate-600',
                ];
                $statusColors = [
                    'open' => 'bg-blue-100 text-blue-700',
                    'in_progress' => 'bg-purple-100 text-purple-700',
                    'resolved' => 'bg-green-100 text-green-700',
                    'closed' => 'bg-slate-100 text-slate-600',
                ];
                $timeAgo = (new DateTime($ticket['created_at']))->diff(new DateTime())->days;
                $timeStr = $timeAgo > 0 ? $timeAgo . 'd ago' : 'Today';
                ?>
                <a href="/admin/support.php?id=<?= $ticket['id'] ?>" class="flex flex-col gap-1 p-4 border-l-4 border-b border-b-slate-100 dark:border-b-slate-800 cursor-pointer transition-colors
               <?= $isActive
                   ? 'border-l-primary bg-primary/5 dark:bg-primary/10'
                   : 'border-l-transparent hover:bg-slate-50 dark:hover:bg-slate-800' ?>">
                    <div class="flex justify-between items-start mb-1">
                        <div class="flex items-center gap-2">
                            <?php if ($ticket['priority'] === 'high'): ?>
                                <span
                                    class="<?= $priorityColors['high'] ?> text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">High</span>
                            <?php endif; ?>
                            <span
                                class="text-xs text-slate-500 font-medium">#<?= Security::escape($ticket['ticket_number']) ?></span>
                        </div>
                        <span class="text-xs text-slate-400 font-medium"><?= $timeStr ?></span>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white leading-tight line-clamp-1">
                        <?= Security::escape($ticket['subject']) ?>
                    </h3>
                    <p class="text-xs text-slate-500 line-clamp-1 mt-1">
                        <?= Security::escape(strip_tags($ticket['message'] ?? '')) ?>
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <div
                            class="size-5 rounded-full bg-primary/20 flex items-center justify-center text-primary text-[10px] font-bold">
                            <?= strtoupper(substr($ticket['customer_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                            <?= Security::escape($ticket['customer_name'] ?? 'Unknown') ?>
                        </span>
                        <span
                            class="<?= $statusColors[$ticket['status']] ?? $statusColors['open'] ?> text-[10px] font-bold px-1.5 py-0.5 rounded ml-auto">
                            <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($tickets)): ?>
                <div class="flex flex-col items-center justify-center py-12 text-slate-500">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">inbox</span>
                    <p class="font-medium">No tickets found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Detail Panel -->
    <div class="flex-1 flex flex-col min-w-0 bg-slate-50 dark:bg-background-dark">

        <?php if ($currentTicket): ?>

            <!-- Ticket Header -->
            <div
                class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-surface-dark shrink-0">
                <div class="flex items-center gap-3 overflow-hidden">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white truncate">
                        <?= Security::escape($currentTicket['subject']) ?>
                    </h2>
                    <?php if ($currentTicket['priority'] === 'high'): ?>
                        <span class="shrink-0 bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded">High Priority</span>
                    <?php endif; ?>
                    <span
                        class="shrink-0 <?= $statusColors[$currentTicket['status']] ?? 'bg-blue-100 text-blue-700' ?> text-xs font-bold px-2 py-1 rounded">
                        <?= ucfirst(str_replace('_', ' ', $currentTicket['status'])) ?>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" class="contents">
                        <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
                        <input type="hidden" name="action" value="update_status">
                        <?php if ($currentTicket['status'] !== 'resolved'): ?>
                            <button type="submit" name="new_status" value="resolved"
                                class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                                <span class="material-symbols-outlined text-lg">check_circle</span>
                                <span class="hidden sm:inline">Mark Resolved</span>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="new_status" value="open"
                                class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-lg">refresh</span>
                                <span class="hidden sm:inline">Reopen</span>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Ticket Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-3xl mx-auto space-y-6">

                    <!-- Customer Info Card -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="size-12 rounded-full bg-primary/20 flex items-center justify-center text-primary text-lg font-bold">
                                <?= strtoupper(substr($currentTicket['customer_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-slate-900 dark:text-white">
                                    <?= Security::escape($currentTicket['customer_name'] ?? 'Unknown') ?>
                                </p>
                                <p class="text-sm text-slate-500">
                                    <?= Security::escape($currentTicket['customer_email'] ?? '') ?></p>
                            </div>
                            <div class="text-right text-sm">
                                <p class="text-slate-500">Ticket #<?= Security::escape($currentTicket['ticket_number']) ?>
                                </p>
                                <p class="text-slate-400">
                                    <?= date('M j, Y g:i A', strtotime($currentTicket['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php if ($currentTicket['order_number']): ?>
                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <p class="text-sm text-slate-500">
                                    Related Order:
                                    <a href="/admin/orders.php?search=<?= $currentTicket['order_number'] ?>"
                                        class="text-primary font-medium hover:underline">
                                        #<?= Security::escape($currentTicket['order_number']) ?>
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Original Message -->
                    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 p-5">
                        <div class="flex items-center gap-2 mb-3 text-sm text-slate-500">
                            <span class="material-symbols-outlined text-lg">mail</span>
                            Original Message
                        </div>
                        <div class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                            <?= nl2br(Security::escape($currentTicket['message'])) ?>
                        </div>
                    </div>

                    <!-- Conversation Thread -->
                    <?php foreach ($ticketMessages as $msg): ?>
                        <div class="flex gap-3 <?= $msg['sender_type'] === 'admin' ? 'flex-row-reverse' : '' ?>">
                            <div
                                class="size-8 rounded-full shrink-0 flex items-center justify-center text-xs font-bold
                        <?= $msg['sender_type'] === 'admin' ? 'bg-primary text-white' : 'bg-slate-200 text-slate-600' ?>">
                                <?= strtoupper(substr($msg['sender_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="flex-1 max-w-[80%] <?= $msg['sender_type'] === 'admin' ? 'text-right' : '' ?>">
                                <div class="inline-block text-left rounded-xl p-4 <?= $msg['sender_type'] === 'admin'
                                    ? 'bg-primary text-white'
                                    : 'bg-white dark:bg-surface-dark border border-slate-200 dark:border-slate-800' ?>">
                                    <p class="text-sm whitespace-pre-wrap"><?= nl2br(Security::escape($msg['message'])) ?></p>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    <?= $msg['sender_name'] ?> • <?= date('M j, g:i A', strtotime($msg['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <!-- Reply Form -->
            <?php if ($currentTicket['status'] !== 'closed'): ?>
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-surface-dark shrink-0">
                    <form method="POST" class="max-w-3xl mx-auto">
                        <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
                        <input type="hidden" name="action" value="reply">

                        <div class="flex gap-3">
                            <textarea name="message" required rows="2"
                                class="flex-1 px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm resize-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Type your reply..."></textarea>
                            <button type="submit"
                                class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-sm flex items-center gap-2 self-end">
                                <span class="material-symbols-outlined text-lg">send</span>
                                Send
                            </button>
                        </div>

                        <div class="flex items-center gap-4 mt-3">
                            <button type="button" class="text-slate-500 hover:text-primary text-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-lg">attach_file</span>
                                Attach File
                            </button>
                            <button type="button" class="text-slate-500 hover:text-primary text-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-lg">format_quote</span>
                                Canned Response
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <!-- Empty State -->
            <div class="flex-1 flex flex-col items-center justify-center text-slate-500">
                <span class="material-symbols-outlined text-7xl text-slate-300 mb-4">support_agent</span>
                <h3 class="text-xl font-bold text-slate-700 dark:text-slate-300 mb-2">Select a Ticket</h3>
                <p class="text-sm">Choose a support ticket from the list to view details and respond.</p>

                <!-- Stats Cards -->
                <div class="grid grid-cols-3 gap-4 mt-8 max-w-lg">
                    <div
                        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4 text-center">
                        <p class="text-2xl font-bold text-primary"><?= $stats['total_open'] ?></p>
                        <p class="text-xs text-slate-500 mt-1">Open Tickets</p>
                    </div>
                    <div
                        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4 text-center">
                        <p class="text-2xl font-bold text-red-600"><?= $stats['high_priority'] ?></p>
                        <p class="text-xs text-slate-500 mt-1">High Priority</p>
                    </div>
                    <div
                        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 p-4 text-center">
                        <p class="text-2xl font-bold text-green-600"><?= $stats['resolved_today'] ?></p>
                        <p class="text-xs text-slate-500 mt-1">Resolved Today</p>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>