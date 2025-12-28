<?php
/**
 * Promo Code Validation API
 * 
 * Validates and applies promo codes to orders
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$code = strtoupper(trim($input['code'] ?? ''));
$orderId = intval($input['order_id'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a promo code']);
    exit;
}

if (!$orderId) {
    echo json_encode(['success' => false, 'error' => 'Invalid order']);
    exit;
}

// Get the order
$order = Database::fetchOne(
    "SELECT * FROM orders WHERE id = ? AND status = 'pending'",
    [$orderId]
);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Check if order already has a promo code
if (!empty($order['promo_code_id'])) {
    echo json_encode(['success' => false, 'error' => 'A promo code is already applied']);
    exit;
}

// Get the promo code
$promo = Database::fetchOne(
    "SELECT * FROM promo_codes WHERE code = ? AND is_active = 1",
    [$code]
);

if (!$promo) {
    echo json_encode(['success' => false, 'error' => 'Invalid promo code']);
    exit;
}

// Check validity dates
$now = time();
if (!empty($promo['valid_from']) && strtotime($promo['valid_from']) > $now) {
    echo json_encode(['success' => false, 'error' => 'This promo code is not yet active']);
    exit;
}

if (!empty($promo['valid_until']) && strtotime($promo['valid_until']) < $now) {
    echo json_encode(['success' => false, 'error' => 'This promo code has expired']);
    exit;
}

// Check usage limit
if (!empty($promo['max_uses']) && $promo['used_count'] >= $promo['max_uses']) {
    echo json_encode(['success' => false, 'error' => 'This promo code has reached its usage limit']);
    exit;
}

// Check minimum order amount
$originalAmount = floatval($order['amount']);
if (!empty($promo['min_order_amount']) && $originalAmount < $promo['min_order_amount']) {
    $minAmount = ($order['currency'] === 'INR' ? '₹' : '$') . number_format($promo['min_order_amount'], 2);
    echo json_encode(['success' => false, 'error' => "Minimum order amount of {$minAmount} required"]);
    exit;
}

// Calculate discount
$discountAmount = 0;
if ($promo['discount_type'] === 'percentage') {
    $discountAmount = $originalAmount * ($promo['discount_value'] / 100);
} else {
    // Fixed discount - convert if needed
    $discountAmount = floatval($promo['discount_value']);
    if ($order['currency'] === 'INR') {
        $discountAmount = $promo['discount_value'] * 83; // Approximate USD to INR
    }
}

// Cap discount at order amount
$discountAmount = min($discountAmount, $originalAmount);
$newAmount = $originalAmount - $discountAmount;

// Update the order with promo code
Database::query(
    "UPDATE orders SET 
        promo_code_id = ?,
        discount_amount = ?,
        amount = ?
     WHERE id = ?",
    [$promo['id'], $discountAmount, $newAmount, $orderId]
);

// Increment usage count
Database::query(
    "UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?",
    [$promo['id']]
);

$currencySymbol = $order['currency'] === 'INR' ? '₹' : '$';

echo json_encode([
    'success' => true,
    'message' => 'Promo code applied successfully!',
    'discount_amount' => $discountAmount,
    'discount_display' => '-' . $currencySymbol . number_format($discountAmount, 2),
    'new_total' => $newAmount,
    'new_total_display' => $currencySymbol . number_format($newAmount, 2),
    'promo_description' => $promo['discount_type'] === 'percentage'
        ? $promo['discount_value'] . '% off'
        : $currencySymbol . number_format($discountAmount, 2) . ' off'
]);
