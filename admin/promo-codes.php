<?php
/**
 * Admin - Promo Codes Management
 * Full CRUD for discount codes
 */

require_once __DIR__ . '/auth.php';  // Must be first for authentication
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$action = $_GET['action'] ?? 'list';
$promoId = intval($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $data = [
            'code' => strtoupper(Security::sanitizeString($_POST['code'] ?? '')),
            'discount_type' => $_POST['discount_type'] ?? 'percentage',
            'discount_value' => floatval($_POST['discount_value'] ?? 0),
            'min_order_amount' => floatval($_POST['min_order_amount'] ?? 0),
            'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
            'valid_from' => !empty($_POST['valid_from']) ? $_POST['valid_from'] : null,
            'valid_until' => !empty($_POST['valid_until']) ? $_POST['valid_until'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($_POST['form_action'] === 'create') {
            // Check if code already exists
            $existing = Database::fetchOne("SELECT id FROM promo_codes WHERE code = ?", [$data['code']]);
            if ($existing) {
                $error = 'A promo code with this name already exists';
            } else {
                $sql = "INSERT INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_uses, valid_from, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                Database::query($sql, array_values($data));
                header('Location: /admin/promo-codes.php?success=created');
                exit;
            }
        } elseif ($_POST['form_action'] === 'update' && $promoId) {
            $sql = "UPDATE promo_codes SET code=?, discount_type=?, discount_value=?, min_order_amount=?, max_uses=?, valid_from=?, valid_until=?, is_active=? WHERE id=?";
            $params = array_values($data);
            $params[] = $promoId;
            Database::query($sql, $params);
            header('Location: /admin/promo-codes.php?success=updated');
            exit;
        }
    }
}

// Handle delete
if ($action === 'delete' && $promoId) {
    Database::query("DELETE FROM promo_codes WHERE id = ?", [$promoId]);
    header('Location: /admin/promo-codes.php?success=deleted');
    exit;
}

// Handle toggle active
if ($action === 'toggle' && $promoId) {
    Database::query("UPDATE promo_codes SET is_active = NOT is_active WHERE id = ?", [$promoId]);
    header('Location: /admin/promo-codes.php?success=updated');
    exit;
}

// Get promo codes for list view
$promoCodes = [];
if ($action === 'list') {
    $promoCodes = Database::fetchAll("SELECT * FROM promo_codes ORDER BY created_at DESC");
}

// Get promo code for edit view
$promo = null;
if ($action === 'edit' && $promoId) {
    $promo = Database::fetchOne("SELECT * FROM promo_codes WHERE id = ?", [$promoId]);
    if (!$promo) {
        header('Location: /admin/promo-codes.php');
        exit;
    }
}

$pendingTickets = 0;
$pageTitle = $action === 'new' ? 'New Promo Code' : ($action === 'edit' ? 'Edit Promo Code' : 'Promo Codes');
?>

<?php ob_start(); ?>

<?php if ($action === 'list'): ?>

    <!-- List View -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold">Promo Codes</h2>
            <p class="text-slate-500 mt-1">Manage discount codes for customers</p>
        </div>
        <a href="/admin/promo-codes.php?action=new"
            class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm shadow-primary/30 transition-all">
            <span class="material-symbols-outlined text-lg">add</span>
            New Promo Code
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Promo code <?= Security::escape($_GET['success']) ?> successfully!
        </div>
    <?php endif; ?>

    <div
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Code</th>
                        <th class="px-6 py-4">Discount</th>
                        <th class="px-6 py-4">Min Order</th>
                        <th class="px-6 py-4">Usage</th>
                        <th class="px-6 py-4">Validity</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($promoCodes as $code):
                        $isExpired = !empty($code['valid_until']) && strtotime($code['valid_until']) < time();
                        $usageLimit = $code['max_uses'] ? ($code['used_count'] >= $code['max_uses']) : false;
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-primary bg-primary/10 px-2 py-1 rounded">
                                    <?= Security::escape($code['code']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                <?php if ($code['discount_type'] === 'percentage'): ?>
                                    <?= number_format($code['discount_value'], 0) ?>%
                                <?php else: ?>
                                    $<?= number_format($code['discount_value'], 2) ?> /
                                    ₹<?= number_format($code['discount_value'] * 83, 0) ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($code['min_order_amount'] > 0): ?>
                                    $<?= number_format($code['min_order_amount'], 2) ?>
                                <?php else: ?>
                                    <span class="text-slate-400">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="<?= $usageLimit ? 'text-red-600' : '' ?>">
                                    <?= number_format($code['used_count']) ?>
                                    <?php if ($code['max_uses']): ?>
                                        / <?= number_format($code['max_uses']) ?>
                                    <?php else: ?>
                                        <span class="text-slate-400">/ ∞</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <?php if ($code['valid_from'] || $code['valid_until']): ?>
                                    <div class="<?= $isExpired ? 'text-red-600' : '' ?>">
                                        <?= $code['valid_from'] ? date('M j, Y', strtotime($code['valid_from'])) : 'Start' ?>
                                        →
                                        <?= $code['valid_until'] ? date('M j, Y', strtotime($code['valid_until'])) : 'Forever' ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400">Always valid</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($isExpired): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>
                                <?php elseif ($usageLimit): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Used
                                        Up</span>
                                <?php elseif ($code['is_active']): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/promo-codes.php?action=toggle&id=<?= $code['id'] ?>"
                                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary transition-colors"
                                        title="Toggle Active">
                                        <span
                                            class="material-symbols-outlined text-lg"><?= $code['is_active'] ? 'toggle_on' : 'toggle_off' ?></span>
                                    </a>
                                    <a href="/admin/promo-codes.php?action=edit&id=<?= $code['id'] ?>"
                                        class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </a>
                                    <a href="/admin/promo-codes.php?action=delete&id=<?= $code['id'] ?>"
                                        onclick="return confirm('Delete this promo code?')"
                                        class="p-2 rounded-lg hover:bg-red-50 text-slate-500 hover:text-red-600 transition-colors">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($promoCodes)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">confirmation_number</span>
                                <p class="text-lg font-medium">No promo codes yet</p>
                                <p class="text-sm">Create your first promo code to offer discounts</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'new' || $action === 'edit'): ?>

    <!-- Create/Edit Form -->
    <div class="flex items-center gap-4 mb-6">
        <a href="/admin/promo-codes.php"
            class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold"><?= $action === 'new' ? 'New Promo Code' : 'Edit Promo Code' ?></h2>
            <p class="text-slate-500 mt-1">
                <?= $action === 'new' ? 'Create a new discount code' : 'Update promo code settings' ?>
            </p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            <?= Security::escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="max-w-2xl">
        <?= Security::csrfField() ?>
        <input type="hidden" name="form_action" value="<?= $action === 'new' ? 'create' : 'update' ?>">

        <div
            class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">

            <!-- Code -->
            <div>
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Promo Code</span>
                    <input type="text" name="code" required
                        class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 font-mono uppercase tracking-wider"
                        value="<?= Security::escape($promo['code'] ?? '') ?>" placeholder="SUMMER2024"
                        style="text-transform: uppercase;">
                    <span class="text-xs text-slate-500">Customers will enter this code at checkout</span>
                </label>
            </div>

            <!-- Discount Type & Value -->
            <div class="grid grid-cols-2 gap-4">
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Discount Type</span>
                    <select name="discount_type" class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50">
                        <option value="percentage" <?= ($promo['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                        <option value="fixed" <?= ($promo['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount
                        </option>
                    </select>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Discount Value</span>
                    <input type="number" name="discount_value" step="0.01" min="0" required
                        class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50"
                        value="<?= $promo['discount_value'] ?? '' ?>" placeholder="e.g., 20">
                </label>
            </div>

            <!-- Min Order -->
            <label class="flex flex-col gap-2">
                <span class="text-sm font-medium">Minimum Order Amount (USD)</span>
                <input type="number" name="min_order_amount" step="0.01" min="0"
                    class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50"
                    value="<?= $promo['min_order_amount'] ?? 0 ?>" placeholder="0">
                <span class="text-xs text-slate-500">Leave at 0 for no minimum</span>
            </label>

            <!-- Max Uses -->
            <label class="flex flex-col gap-2">
                <span class="text-sm font-medium">Maximum Uses</span>
                <input type="number" name="max_uses" min="1"
                    class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50" value="<?= $promo['max_uses'] ?? '' ?>"
                    placeholder="Unlimited">
                <span class="text-xs text-slate-500">Leave empty for unlimited uses</span>
            </label>

            <!-- Validity Dates -->
            <div class="grid grid-cols-2 gap-4">
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Valid From</span>
                    <input type="datetime-local" name="valid_from"
                        class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50"
                        value="<?= $promo['valid_from'] ? date('Y-m-d\TH:i', strtotime($promo['valid_from'])) : '' ?>">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Valid Until</span>
                    <input type="datetime-local" name="valid_until"
                        class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50"
                        value="<?= $promo['valid_until'] ? date('Y-m-d\TH:i', strtotime($promo['valid_until'])) : '' ?>">
                </label>
            </div>

            <!-- Status -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= ($promo['is_active'] ?? 1) ? 'checked' : '' ?>
                    class="rounded border-slate-300 text-primary focus:ring-primary">
                <span class="text-sm font-medium">Active (can be used by customers)</span>
            </label>

            <!-- Submit -->
            <div class="pt-4 flex gap-3">
                <a href="/admin/promo-codes.php"
                    class="flex-1 py-3 px-4 border border-slate-200 rounded-lg font-medium hover:bg-slate-50 text-center">
                    Cancel
                </a>
                <button type="submit"
                    class="flex-1 py-3 px-4 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">save</span>
                    <?= $action === 'new' ? 'Create Promo Code' : 'Save Changes' ?>
                </button>
            </div>
        </div>
    </form>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>