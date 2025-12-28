<?php
/**
 * My Support Tickets - User can view and track their support tickets
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Require authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = '/my-tickets';
    header('Location: /login');
    exit;
}

$userId = $_SESSION['user_id'];

// Get current ticket ID if viewing details
$ticketId = intval($_GET['id'] ?? 0);

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticketId) {
    if (Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = Security::sanitizeString($_POST['message'] ?? '');
        if (!empty($message)) {
            // Verify ticket belongs to user
            $ticket = Database::fetchOne(
                "SELECT id FROM support_tickets WHERE id = ? AND user_id = ?",
                [$ticketId, $userId]
            );

            if ($ticket) {
                Database::query(
                    "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, 'user', ?, ?)",
                    [$ticketId, $userId, $message]
                );
                // Reopen ticket if it was resolved
                Database::query(
                    "UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ? AND status = 'resolved'",
                    [$ticketId]
                );
                $_SESSION['success'] = 'Your reply has been sent!';
            }
        }
    }
    header('Location: /my-tickets?id=' . $ticketId);
    exit;
}

// Get user's tickets
$tickets = Database::fetchAll(
    "SELECT t.*, o.order_number,
            (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count
     FROM support_tickets t
     LEFT JOIN orders o ON t.order_id = o.id
     WHERE t.user_id = ?
     ORDER BY t.updated_at DESC",
    [$userId]
);

// Get current ticket details if viewing
$currentTicket = null;
$ticketMessages = [];
if ($ticketId) {
    $currentTicket = Database::fetchOne(
        "SELECT t.*, o.order_number
         FROM support_tickets t
         LEFT JOIN orders o ON t.order_id = o.id
         WHERE t.id = ? AND t.user_id = ?",
        [$ticketId, $userId]
    );

    if ($currentTicket) {
        $ticketMessages = Database::fetchAll(
            "SELECT tm.*, 
                    CASE WHEN tm.sender_type = 'user' THEN 'You' ELSE 'Support Team' END as sender_name
             FROM ticket_messages tm
             WHERE tm.ticket_id = ?
             ORDER BY tm.created_at ASC",
            [$ticketId]
        );
    }
}

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

$pageTitle = 'My Support Tickets';
?>

<?php ob_start(); ?>

<div class="max-w-6xl mx-auto px-4 py-8 sm:py-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">My Support Tickets</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">Track your support requests and communicate with our team
            </p>
        </div>
        <a href="/support"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-lg shadow-primary/30">
            <span class="material-symbols-outlined">add</span>
            New Ticket
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= Security::escape($success) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <!-- Empty State -->
        <div class="text-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
            <span class="material-symbols-outlined text-6xl text-slate-300">support_agent</span>
            <h3 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">No support tickets</h3>
            <p class="mt-2 text-slate-500">Need help? Create a new support ticket</p>
            <a href="/support"
                class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined">add</span>
                Create Ticket
            </a>
        </div>
    <?php else: ?>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Tickets List -->
            <div class="w-full lg:w-96 shrink-0">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800">
                        <span class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Your Tickets</span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[600px] overflow-y-auto">
                        <?php foreach ($tickets as $ticket):
                            $isActive = $ticketId === intval($ticket['id']);
                            $statusColors = [
                                'open' => 'bg-blue-100 text-blue-700',
                                'in_progress' => 'bg-purple-100 text-purple-700',
                                'resolved' => 'bg-green-100 text-green-700',
                                'closed' => 'bg-slate-100 text-slate-600',
                            ];
                            $statusColor = $statusColors[$ticket['status']] ?? 'bg-slate-100 text-slate-600';
                            $timeAgo = (new DateTime($ticket['updated_at']))->diff(new DateTime());
                            if ($timeAgo->days > 0) {
                                $timeStr = $timeAgo->days . 'd ago';
                            } elseif ($timeAgo->h > 0) {
                                $timeStr = $timeAgo->h . 'h ago';
                            } else {
                                $timeStr = 'Just now';
                            }
                            ?>
                            <a href="/my-tickets?id=<?= $ticket['id'] ?>" class="block p-4 transition-colors <?= $isActive
                                  ? 'bg-primary/5 border-l-4 border-l-primary'
                                  : 'hover:bg-slate-50 dark:hover:bg-slate-800 border-l-4 border-l-transparent' ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="text-xs font-medium text-slate-500">#<?= Security::escape($ticket['ticket_number']) ?></span>
                                    <span class="text-xs text-slate-400"><?= $timeStr ?></span>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-1">
                                    <?= Security::escape($ticket['subject']) ?>
                                </h3>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="<?= $statusColor ?> text-[10px] font-bold px-2 py-0.5 rounded">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                    <?php if ($ticket['message_count'] > 0): ?>
                                        <span class="text-xs text-slate-400 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">chat</span>
                                            <?= $ticket['message_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Ticket Details -->
            <div class="flex-1">
                <?php if ($currentTicket): ?>
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <!-- Ticket Header -->
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                                        <?= Security::escape($currentTicket['subject']) ?>
                                    </h2>
                                    <p class="text-sm text-slate-500 mt-1">
                                        Ticket #<?= Security::escape($currentTicket['ticket_number']) ?>
                                        <?php if ($currentTicket['order_number']): ?>
                                            • Order #<?= Security::escape($currentTicket['order_number']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php
                                $statusColors = [
                                    'open' => 'bg-blue-100 text-blue-700',
                                    'in_progress' => 'bg-purple-100 text-purple-700',
                                    'resolved' => 'bg-green-100 text-green-700',
                                    'closed' => 'bg-slate-100 text-slate-600',
                                ];
                                $statusColor = $statusColors[$currentTicket['status']] ?? 'bg-slate-100 text-slate-600';
                                ?>
                                <span class="<?= $statusColor ?> text-xs font-bold px-3 py-1.5 rounded shrink-0">
                                    <?= ucfirst(str_replace('_', ' ', $currentTicket['status'])) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="p-5 space-y-4 max-h-[400px] overflow-y-auto">
                            <!-- Original Message -->
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-2 text-sm text-slate-500">
                                    <span class="material-symbols-outlined text-lg">mail</span>
                                    Original Message •
                                    <?= date('M j, Y g:i A', strtotime($currentTicket['created_at'])) ?>
                                </div>
                                <div class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                                    <?= nl2br(Security::escape($currentTicket['message'])) ?>
                                </div>
                            </div>

                            <!-- Thread Messages -->
                            <?php foreach ($ticketMessages as $msg): ?>
                                <div class="flex gap-3 <?= $msg['sender_type'] === 'user' ? 'flex-row-reverse' : '' ?>">
                                    <div
                                        class="size-8 rounded-full shrink-0 flex items-center justify-center text-xs font-bold
                                        <?= $msg['sender_type'] === 'user' ? 'bg-primary text-white' : 'bg-green-100 text-green-700' ?>">
                                        <?= $msg['sender_type'] === 'user' ? 'Y' : 'S' ?>
                                    </div>
                                    <div class="flex-1 max-w-[80%] <?= $msg['sender_type'] === 'user' ? 'text-right' : '' ?>">
                                        <div
                                            class="inline-block text-left rounded-xl p-4
                                            <?= $msg['sender_type'] === 'user'
                                                ? 'bg-primary text-white'
                                                : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' ?>">
                                            <p class="text-sm whitespace-pre-wrap"><?= nl2br(Security::escape($msg['message'])) ?>
                                            </p>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">
                                            <?= $msg['sender_name'] ?> • <?= date('M j, g:i A', strtotime($msg['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Reply Form -->
                        <?php if ($currentTicket['status'] !== 'closed'): ?>
                            <div class="p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <form method="POST">
                                    <?= Security::csrfField() ?>
                                    <div class="flex gap-3">
                                        <textarea name="message" required rows="2"
                                            class="flex-1 px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm resize-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                            placeholder="Type your reply..."></textarea>
                                        <button type="submit"
                                            class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-sm flex items-center gap-2 self-end">
                                            <span class="material-symbols-outlined text-lg">send</span>
                                            Send
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <p class="text-sm text-slate-500 text-center">
                                    This ticket has been closed. <a href="/support" class="text-primary hover:underline">Create a
                                        new ticket</a> if you need more help.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- No Ticket Selected -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                        <span class="material-symbols-outlined text-5xl text-slate-300">inbox</span>
                        <h3 class="mt-4 text-lg font-bold text-slate-700 dark:text-slate-300">Select a ticket</h3>
                        <p class="mt-2 text-sm text-slate-500">Choose a ticket from the list to view details and reply</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>