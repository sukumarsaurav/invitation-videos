<?php
/**
 * Admin - User Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

// Filters
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if ($role) {
    $whereConditions[] = "role = ?";
    $params[] = $role;
}

if ($status) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$totalUsers = Database::fetchOne("SELECT COUNT(*) as total FROM users $whereClause", $params)['total'] ?? 0;
$totalPages = ceil($totalUsers / $perPage);

// Get users
$sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$users = Database::fetchAll($sql, $params);

// Stats
$stats = [
    'total' => Database::fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0,
    'active' => Database::fetchOne("SELECT COUNT(*) as c FROM users WHERE status = 'active'")['c'] ?? 0,
    'admins' => Database::fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'admin'")['c'] ?? 0,
];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status' && $userId) {
        $currentStatus = Database::fetchOne("SELECT status FROM users WHERE id = ?", [$userId])['status'] ?? '';
        $newStatus = $currentStatus === 'active' ? 'suspended' : 'active';
        Database::query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
        header('Location: /admin/users.php?success=status_updated');
        exit;
    }

    if ($action === 'change_role' && $userId) {
        $newRole = $_POST['new_role'] ?? 'customer';
        if (in_array($newRole, ['customer', 'admin', 'editor'])) {
            Database::query("UPDATE users SET role = ? WHERE id = ?", [$newRole, $userId]);
            header('Location: /admin/users.php?success=role_updated');
            exit;
        }
    }
}

$pendingTickets = 0;
$pageTitle = 'Users';
?>

<?php ob_start(); ?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold">User Management</h2>
        <p class="text-slate-500 mt-1">Manage customer accounts and admin users</p>
    </div>

    <div class="flex items-center gap-3">
        <button
            class="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors text-sm font-medium">
            <span class="material-symbols-outlined text-lg">download</span>
            Export
        </button>
        <a href="/admin/users.php?action=new"
            class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2 px-5 rounded-lg shadow-sm shadow-primary/30 transition-all text-sm">
            <span class="material-symbols-outlined text-lg">person_add</span>
            Add User
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">check_circle</span>
        User <?= str_replace('_', ' ', $_GET['success']) ?> successfully!
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">Total Users</p>
        <p class="text-2xl font-bold mt-1"><?= number_format($stats['total']) ?></p>
    </div>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">Active Users</p>
        <p class="text-2xl font-bold mt-1 text-green-600"><?= number_format($stats['active']) ?></p>
    </div>
    <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <p class="text-slate-500 text-xs font-medium uppercase">Admins</p>
        <p class="text-2xl font-bold mt-1 text-primary"><?= number_format($stats['admins']) ?></p>
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
                    placeholder="Search by name or email...">
            </div>
        </div>

        <select name="role"
            class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <option value="">All Roles</option>
            <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
        </select>

        <select name="status"
            class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
            <option value="">All Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>

        <button type="submit"
            class="h-10 px-6 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
            Filter
        </button>

        <?php if ($search || $role || $status): ?>
            <a href="/admin/users.php" class="text-sm text-slate-500 hover:text-primary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div
    class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-4 w-10">
                        <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary">
                    </th>
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4">Role</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Joined</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($users as $user):
                    $roleColors = [
                        'admin' => 'bg-purple-100 text-purple-700',
                        'editor' => 'bg-blue-100 text-blue-700',
                        'customer' => 'bg-slate-100 text-slate-700',
                    ];
                    $roleColor = $roleColors[$user['role']] ?? 'bg-slate-100 text-slate-700';
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-10 rounded-full bg-primary/20 flex items-center justify-center text-primary text-sm font-bold shrink-0">
                                    <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        <?= Security::escape($user['name'] ?? 'No Name') ?></p>
                                    <p class="text-xs text-slate-500"><?= Security::escape($user['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleColor ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <?php if ($user['status'] === 'active'): ?>
                                    <div class="size-2.5 rounded-full bg-green-500"></div>
                                    <span class="text-green-700">Active</span>
                                <?php elseif ($user['status'] === 'suspended'): ?>
                                    <div class="size-2.5 rounded-full bg-red-500"></div>
                                    <span class="text-red-700">Suspended</span>
                                <?php else: ?>
                                    <div class="size-2.5 rounded-full bg-slate-400"></div>
                                    <span class="text-slate-500">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <div class="relative group">
                                    <button
                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 transition-colors">
                                        <span class="material-symbols-outlined text-lg">more_vert</span>
                                    </button>
                                    <div
                                        class="absolute right-0 top-full mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 py-1 w-44 hidden group-hover:block z-10">
                                        <form method="POST" class="contents">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                                            <button type="submit" name="action" value="toggle_status"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5 flex items-center gap-2">
                                                <span
                                                    class="material-symbols-outlined text-lg"><?= $user['status'] === 'active' ? 'block' : 'check_circle' ?></span>
                                                <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                            </button>

                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <button type="submit" name="action" value="change_role"
                                                    onclick="this.form.elements.new_role.value='admin'"
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5 flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
                                                    Make Admin
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="change_role"
                                                    onclick="this.form.elements.new_role.value='customer'"
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/5 flex items-center gap-2">
                                                    <span class="material-symbols-outlined text-lg">person</span>
                                                    Make Customer
                                                </button>
                                            <?php endif; ?>

                                            <input type="hidden" name="new_role" value="">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">group</span>
                            <p class="text-lg font-medium">No users found</p>
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
                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalUsers) ?> of <?= $totalUsers ?> users
            </p>

            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&role=<?= $role ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&role=<?= $role ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                        class="w-10 h-10 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-100 text-slate-600' ?> font-medium text-sm">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&role=<?= $role ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
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