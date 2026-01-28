<?php
/**
 * Checkout Page
 * 
 * Handles payment with Stripe (Global) or Razorpay (India)
 * Supports both draft_token (new flow) and order_id (legacy pending orders)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Services/DraftOrderService.php';

use InvitationVideos\Services\DraftOrderService;

// Disable bottom tabs on checkout - keep focus on payment
$showMobileBottomTabs = false;

// Determine if we're working with a draft token or legacy order_id
$identifier = $_GET['order_id'] ?? '';
$isDraft = false;
$order = null;
$draftToken = null;

// Check if identifier looks like a draft token (32 hex chars) or numeric order_id
if (strlen($identifier) === 32 && ctype_xdigit($identifier)) {
    // It's a draft token
    $draftToken = $identifier;
    $isDraft = true;
    $draftService = new DraftOrderService();
    $draft = $draftService->getDraftByToken($draftToken);

    if ($draft) {
        // Convert draft to order-like array for template compatibility
        $order = [
            'id' => $draft['id'],
            'draft_token' => $draftToken,
            'order_number' => 'DRAFT-' . strtoupper(substr($draftToken, 0, 8)),
            'user_id' => $draft['user_id'],
            'template_id' => $draft['template_id'],
            'amount' => $draft['amount'],
            'currency' => $draft['currency'],
            'customization_data' => $draft['customization_data'],
            'promo_code_id' => $draft['promo_code_id'],
            'discount_amount' => $draft['discount_amount'],
            'status' => 'pending',
            'template_title' => $draft['template_title'],
            'thumbnail_url' => $draft['thumbnail_url'],
            'is_draft' => true
        ];
    }
} else {
    // Legacy: numeric order_id for existing pending orders
    $orderId = intval($identifier);
    if ($orderId) {
        $order = Database::fetchOne(
            "SELECT o.*, t.title as template_title, t.thumbnail_url 
             FROM orders o 
             JOIN templates t ON o.template_id = t.id 
             WHERE o.id = ? AND o.status = 'pending'",
            [$orderId]
        );
        if ($order) {
            $order['is_draft'] = false;
        }
    }
}

if (!$order) {
    header('Location: /templates');
    exit;
}

// For display and JS purposes
$orderId = $order['id'];
$orderIdentifier = $isDraft ? $draftToken : $orderId;

// Get user info
$user = [];
if (!empty($_SESSION['user_id'])) {
    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]) ?? [];
}

// Determine payment gateway based on currency
$isIndian = ($order['currency'] === 'INR');
$gateway = $isIndian ? 'razorpay' : 'stripe';

// Fetch template's required fields for checkout
$templateFields = Database::fetchAll(
    "SELECT tfp.*, fp.name, fp.field_name, fp.field_type, fp.placeholder, fp.icon, fp.help_text, fp.sample_value, fp.category as preset_category
     FROM template_field_presets tfp
     JOIN field_presets fp ON tfp.preset_id = fp.id
     WHERE tfp.template_id = ?
     ORDER BY tfp.step_number, tfp.display_order",
    [$order['template_id']]
);

// Group fields by step
$fieldsByStep = [1 => [], 2 => [], 3 => []];
foreach ($templateFields as $field) {
    $step = $field['step_number'] ?? 1;
    $fieldsByStep[$step][] = $field;
}

// Check if we have any customization fields
// For drafts coming from customize.php, data is already collected, so skip showing fields
// Only show fields for legacy orders that haven't been through the customize flow
$hasCustomizationFields = !empty($templateFields) && !$isDraft;

// Get existing customization data (for editing)
$existingData = [];
if (!empty($order['customization_data'])) {
    // Handle both JSON string and already-decoded array
    if (is_array($order['customization_data'])) {
        $existingData = $order['customization_data'];
    } else {
        $existingData = json_decode($order['customization_data'], true) ?? [];
    }
}

/**
 * Render a checkout field based on its type
 */
function renderCheckoutField($field, $existingData = [])
{
    $fieldName = $field['field_name'];
    $value = $existingData[$fieldName] ?? '';
    $required = $field['is_required'] ? 'required' : '';
    $placeholder = Security::escape($field['placeholder'] ?? '');
    $helpText = Security::escape($field['help_text'] ?? '');
    $label = Security::escape($field['name']);
    $icon = Security::escape($field['icon'] ?? 'text_fields');

    $inputClass = "h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary w-full";

    $html = '<label class="flex flex-col gap-2">';
    $html .= '<span class="text-sm font-medium text-slate-700 flex items-center gap-1.5">';
    $html .= '<span class="material-symbols-outlined text-lg text-primary">' . $icon . '</span>';
    $html .= $label;
    if ($field['is_required']) {
        $html .= '<span class="text-red-500">*</span>';
    }
    $html .= '</span>';

    switch ($field['field_type']) {
        case 'textarea':
            $html .= '<textarea name="' . $fieldName . '" ' . $required . ' class="' . $inputClass . ' py-3 min-h-[100px]" placeholder="' . $placeholder . '">' . Security::escape($value) . '</textarea>';
            break;

        case 'date':
            $html .= '<input type="date" name="' . $fieldName . '" ' . $required . ' class="' . $inputClass . '" value="' . Security::escape($value) . '">';
            break;

        case 'time':
            $html .= '<input type="time" name="' . $fieldName . '" ' . $required . ' class="' . $inputClass . '" value="' . Security::escape($value) . '">';
            break;

        case 'datetime':
            $html .= '<input type="datetime-local" name="' . $fieldName . '" ' . $required . ' class="' . $inputClass . '" value="' . Security::escape($value) . '">';
            break;

        case 'number':
            $html .= '<input type="number" name="' . $fieldName . '" ' . $required . ' class="' . $inputClass . '" placeholder="' . $placeholder . '" value="' . Security::escape($value) . '">';
            break;

        case 'color':
            $html .= '<input type="color" name="' . $fieldName . '" ' . $required . ' class="h-11 w-full rounded-lg border border-slate-200 cursor-pointer" value="' . ($value ?: '#970747') . '">';
            break;

        case 'image':
            $html .= '<div class="relative">';
            $html .= '<input type="file" name="' . $fieldName . '" ' . $required . ' accept="image/*" class="' . $inputClass . ' file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">';
            $html .= '</div>';
            break;

        case 'music':
            $html .= '<div class="relative">';
            $html .= '<input type="file" name="' . $fieldName . '" ' . $required . ' accept="audio/*" class="' . $inputClass . ' file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium hover:file:bg-primary/20">';
            $html .= '</div>';
            break;

        case 'text':
        default:
            $html .= '<input type="text" name="' . $fieldName . '" ' . $required . ' class="' . $inputClass . '" placeholder="' . $placeholder . '" value="' . Security::escape($value) . '">';
            break;
    }

    if ($helpText) {
        $html .= '<span class="text-xs text-slate-500">' . $helpText . '</span>';
    }

    $html .= '</label>';

    return $html;
}

$pageTitle = 'Checkout - ' . $order['order_number'];
?>

<?php ob_start(); ?>

<!-- GA4 DataLayer: Begin Checkout Event -->
<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null }); // Clear the previous ecommerce object
    window.dataLayer.push({
        'event': 'begin_checkout',
        'ecommerce': {
            'currency': '<?= $order['currency'] ?>',
            'value': <?= number_format($order['amount'], 2, '.', '') ?>,
            'items': [{
                'item_id': '<?= $order['template_id'] ?>',
                'item_name': '<?= addslashes($order['template_title']) ?>',
                'price': <?= number_format($order['amount'], 2, '.', '') ?>,
                'quantity': 1,
                'item_category': 'Video Invitation'
            }]
        },
        'order_id': '<?= $order['order_number'] ?>',
        'payment_gateway': '<?= $gateway ?>'
    });
</script>

<!-- Payment SDK Scripts (loaded only on checkout page) -->
<?php if ($isIndian && defined('RAZORPAY_KEY_ID') && RAZORPAY_KEY_ID): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php elseif (defined('STRIPE_PUBLIC_KEY') && STRIPE_PUBLIC_KEY): ?>
    <script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>

<div class="max-w-7xl mx-auto px-4 md:px-8 py-8">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- Checkout Form -->
        <div class="lg:col-span-7 flex flex-col gap-6">

            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-sm font-medium">
                <a class="text-slate-500 hover:text-primary transition-colors" href="/templates">Templates</a>
                <span class="material-symbols-outlined text-base text-slate-400">chevron_right</span>
                <span class="text-primary font-bold">Checkout</span>
            </nav>

            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Secure Checkout</h1>
                <p class="text-slate-500">Complete your details to create your personalized video invitation.</p>
            </div>

            <?php if ($hasCustomizationFields): ?>
                <!-- Multi-Step Progress -->
                <div class="flex items-center justify-between mb-2">
                    <?php
                    $stepLabels = ['Event Details', 'Personal Info', 'Media & Extras', 'Payment'];
                    $totalSteps = 4;
                    // Hide empty steps
                    $activeSteps = [];
                    if (!empty($fieldsByStep[1]))
                        $activeSteps[] = 1;
                    if (!empty($fieldsByStep[2]))
                        $activeSteps[] = 2;
                    if (!empty($fieldsByStep[3]))
                        $activeSteps[] = 3;
                    $activeSteps[] = 4; // Payment is always shown
                
                    foreach ($activeSteps as $idx => $step):
                        $stepNum = $idx + 1;
                        $label = $step <= 3 ? $stepLabels[$step - 1] : 'Payment';
                        ?>
                        <div class="flex items-center <?= $idx < count($activeSteps) - 1 ? 'flex-1' : '' ?>">
                            <div class="step-indicator flex items-center justify-center size-8 rounded-full font-bold text-sm transition-all <?= $stepNum === 1 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500' ?>"
                                data-step="<?= $step ?>">
                                <?= $stepNum ?>
                            </div>
                            <span class="hidden sm:block ml-2 text-sm font-medium text-slate-600"><?= $label ?></span>
                            <?php if ($idx < count($activeSteps) - 1): ?>
                                <div class="flex-1 h-0.5 bg-slate-200 mx-3 step-line" data-after-step="<?= $step ?>"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="checkout-form" class="space-y-6">
                <?= Security::csrfField() ?>
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <?php if ($isDraft): ?>
                    <input type="hidden" name="draft_token" value="<?= Security::escape($draftToken) ?>">
                    <input type="hidden" name="is_draft" value="1">
                <?php endif; ?>
                <input type="hidden" name="gateway" value="<?= $gateway ?>">

                <?php if ($hasCustomizationFields): ?>
                    <!-- Step 1: Event Details -->
                    <?php if (!empty($fieldsByStep[1])): ?>
                        <section data-step="1" class="checkout-step bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                            <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                <span class="material-symbols-outlined text-primary text-2xl">event</span>
                                <h2 class="text-xl font-bold tracking-tight">Event Details</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($fieldsByStep[1] as $field): ?>
                                    <?php echo renderCheckoutField($field, $existingData); ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="button" onclick="nextStep()"
                                    class="btn-next flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-all">
                                    Next <span class="material-symbols-outlined">arrow_forward</span>
                                </button>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Step 2: Personal Info -->
                    <?php if (!empty($fieldsByStep[2])): ?>
                        <section data-step="2"
                            class="checkout-step hidden bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                            <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                <span class="material-symbols-outlined text-primary text-2xl">person</span>
                                <h2 class="text-xl font-bold tracking-tight">Personal Information</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($fieldsByStep[2] as $field): ?>
                                    <?php echo renderCheckoutField($field, $existingData); ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 flex justify-between">
                                <button type="button" onclick="prevStep()"
                                    class="flex items-center gap-2 text-slate-600 hover:text-primary font-bold py-3 px-6 rounded-lg transition-all">
                                    <span class="material-symbols-outlined">arrow_back</span> Back
                                </button>
                                <button type="button" onclick="nextStep()"
                                    class="btn-next flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-all">
                                    Next <span class="material-symbols-outlined">arrow_forward</span>
                                </button>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Step 3: Media & Extras -->
                    <?php if (!empty($fieldsByStep[3])): ?>
                        <section data-step="3"
                            class="checkout-step hidden bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                            <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                <span class="material-symbols-outlined text-primary text-2xl">photo_library</span>
                                <h2 class="text-xl font-bold tracking-tight">Media & Extras</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($fieldsByStep[3] as $field): ?>
                                    <?php echo renderCheckoutField($field, $existingData); ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 flex justify-between">
                                <button type="button" onclick="prevStep()"
                                    class="flex items-center gap-2 text-slate-600 hover:text-primary font-bold py-3 px-6 rounded-lg transition-all">
                                    <span class="material-symbols-outlined">arrow_back</span> Back
                                </button>
                                <button type="button" onclick="nextStep()"
                                    class="btn-next flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-all">
                                    Next <span class="material-symbols-outlined">arrow_forward</span>
                                </button>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Final Step: Billing & Payment -->
                <section data-step="4"
                    class="checkout-step <?= $hasCustomizationFields && (!empty($fieldsByStep[1]) || !empty($fieldsByStep[2]) || !empty($fieldsByStep[3])) ? 'hidden' : '' ?> bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                        <span class="material-symbols-outlined text-primary text-2xl">credit_card</span>
                        <h2 class="text-xl font-bold tracking-tight">Billing & Payment</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-slate-700">Full Name</span>
                            <input type="text" name="billing_name" required
                                class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="John Doe" value="<?= Security::escape($user['name'] ?? '') ?>">
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-slate-700">Email Address</span>
                            <input type="email" name="billing_email" required
                                class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="john@example.com" value="<?= Security::escape($user['email'] ?? '') ?>">
                        </label>

                        <?php if (!$isIndian): ?>
                            <label class="flex flex-col gap-2 md:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Billing Address</span>
                                <input type="text" name="billing_address"
                                    class="h-11 px-4 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="123 Main St, City, State, ZIP">
                            </label>
                        <?php endif; ?>
                    </div>

                    <div class="border-t border-slate-100 pt-6 mb-6">
                        <h3 class="text-lg font-bold mb-4">Payment Method</h3>
                        <?php if ($isIndian): ?>
                            <!-- Razorpay for India -->
                            <div class="text-center py-4">
                                <p class="text-slate-600 mb-4">You will be redirected to Razorpay's
                                    secure payment page</p>
                                <div class="flex justify-center gap-4 items-center flex-wrap">
                                    <img src="/assets/images/razorpay.png" alt="Razorpay" class="h-8" width="100"
                                        height="32">
                                    <span class="text-slate-400">|</span>
                                    <div class="flex items-center gap-2">
                                        <img src="/assets/images/upi_logo.png" alt="UPI" class="h-6" width="48" height="24">
                                        <span class="text-sm text-slate-500">• Cards • NetBanking • Wallets</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Stripe for Global -->
                            <div id="card-element" class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                                <!-- Stripe Elements will mount here -->
                            </div>
                            <div id="card-errors" class="text-red-500 text-sm mt-2"></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasCustomizationFields && (!empty($fieldsByStep[1]) || !empty($fieldsByStep[2]) || !empty($fieldsByStep[3]))): ?>
                        <div class="flex justify-between">
                            <button type="button" onclick="prevStep()"
                                class="flex items-center gap-2 text-slate-600 hover:text-primary font-bold py-3 px-6 rounded-lg transition-all">
                                <span class="material-symbols-outlined">arrow_back</span> Back
                            </button>
                        </div>
                    <?php endif; ?>
                </section>
            </form>

        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-lg">
                    <h3 class="text-lg font-bold mb-4">Order Summary</h3>

                    <div class="flex gap-4 mb-6">
                        <div class="w-24 h-16 shrink-0 rounded-lg bg-slate-100 shadow-sm overflow-hidden">
                            <img src="<?= Security::escape($order['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                                alt="<?= Security::escape($order['template_title']) ?>"
                                class="w-full h-full object-cover" width="96" height="64" loading="eager">
                        </div>
                        <div class="flex flex-col justify-center">
                            <h4 class="text-sm font-bold text-slate-900 leading-tight">
                                <?= Security::escape($order['template_title']) ?>
                            </h4>
                            <p class="text-xs text-slate-500 mt-1">Order
                                #<?= Security::escape($order['order_number']) ?></p>
                        </div>
                        <div class="ml-auto flex items-center">
                            <span class="font-bold text-slate-900">
                                <?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="flex gap-2 mb-6">
                        <input type="text" id="promo-code" placeholder="Promo code"
                            class="flex-1 h-10 px-4 rounded-lg border border-slate-200 bg-slate-50 text-sm focus:ring-2 focus:ring-primary/20">
                        <button type="button" onclick="applyPromo()"
                            class="px-4 h-10 rounded-lg bg-slate-100 hover:bg-slate-200:bg-slate-700 text-slate-700 text-sm font-bold transition-colors">
                            Apply
                        </button>
                    </div>

                    <hr class="border-slate-100 mb-4">

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm text-slate-600">
                            <span>Subtotal</span>
                            <span><?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-green-600 font-medium">
                            <span>Discount</span>
                            <span id="discount-amount">-<?= $order['currency'] === 'INR' ? '₹' : '$' ?>0.00</span>
                        </div>
                        <hr class="border-slate-100 border-dashed">
                        <div class="flex justify-between items-center text-lg font-bold text-slate-900 pt-2">
                            <span>Total</span>
                            <span
                                id="total-amount"><?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?></span>
                        </div>
                    </div>

                    <button type="button" id="pay-button" onclick="processPayment()"
                        class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 px-4 rounded-xl shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">lock</span>
                        Pay <?= $order['currency'] === 'INR' ? '₹' : '$' ?><?= number_format($order['amount'], 2) ?>
                    </button>

                    <div class="mt-4 flex flex-col items-center gap-2">
                        <div class="flex items-center justify-center gap-1 text-xs text-slate-400">
                            <span class="material-symbols-outlined text-sm">lock_clock</span>
                            <span>Payments are secure and encrypted</span>
                        </div>
                    </div>
                </div>

                <!-- Support Card -->
                <div class="bg-primary/5 rounded-xl border border-primary/10 p-4 flex items-start gap-3">
                    <div class="bg-primary/10 p-2 rounded-full shrink-0 text-primary">
                        <span class="material-symbols-outlined text-lg">support_agent</span>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-slate-900">Need help with your order?</h5>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Our support team is available 24/7 to
                            assist you.</p>
                        <a class="text-xs font-bold text-primary mt-2 inline-block hover:underline"
                            href="/support">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const orderId = <?= $orderId ?>;
    const orderIdentifier = '<?= $orderIdentifier ?>';
    const isDraft = <?= $isDraft ? 'true' : 'false' ?>;
    const draftToken = <?= $isDraft ? "'" . $draftToken . "'" : 'null' ?>;
    const gateway = '<?= $gateway ?>';
    const amount = <?= $order['amount'] ?>;
    const currency = '<?= $order['currency'] ?>';

    // Multi-step navigation
    const activeSteps = <?= json_encode($hasCustomizationFields ? array_values(array_filter([
        !empty($fieldsByStep[1]) ? 1 : null,
        !empty($fieldsByStep[2]) ? 2 : null,
        !empty($fieldsByStep[3]) ? 3 : null,
        4 // Payment step is always included
    ])) : [4]) ?>;

    let currentStepIndex = 0;

    function getCurrentStep() {
        return activeSteps[currentStepIndex];
    }

    function nextStep() {
        // Validate current step's required fields
        const currentSection = document.querySelector(`[data-step="${getCurrentStep()}"]`);
        const requiredFields = currentSection.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                isValid = false;
            } else {
                field.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields');
            return;
        }

        if (currentStepIndex < activeSteps.length - 1) {
            currentStepIndex++;
            showStep(getCurrentStep());
        }
    }

    function prevStep() {
        if (currentStepIndex > 0) {
            currentStepIndex--;
            showStep(getCurrentStep());
        }
    }

    function showStep(stepNumber) {
        // Hide all steps
        document.querySelectorAll('.checkout-step').forEach(section => {
            section.classList.add('hidden');
        });

        // Show current step
        const currentSection = document.querySelector(`[data-step="${stepNumber}"]`);
        if (currentSection) {
            currentSection.classList.remove('hidden');
        }

        // Update step indicators
        document.querySelectorAll('.step-indicator').forEach(indicator => {
            const indicatorStep = parseInt(indicator.dataset.step);
            const indicatorIndex = activeSteps.indexOf(indicatorStep);

            if (indicatorIndex !== -1 && indicatorIndex <= currentStepIndex) {
                indicator.classList.add('bg-primary', 'text-white');
                indicator.classList.remove('bg-slate-200', 'text-slate-500');
            } else {
                indicator.classList.remove('bg-primary', 'text-white');
                indicator.classList.add('bg-slate-200', 'text-slate-500');
            }
        });

        // Update step lines
        document.querySelectorAll('.step-line').forEach(line => {
            const afterStep = parseInt(line.dataset.afterStep);
            const lineIndex = activeSteps.indexOf(afterStep);

            if (lineIndex !== -1 && lineIndex < currentStepIndex) {
                line.classList.add('bg-primary');
                line.classList.remove('bg-slate-200');
            } else {
                line.classList.remove('bg-primary');
                line.classList.add('bg-slate-200');
            }
        });

        // Scroll to top of form
        window.scrollTo({ top: 150, behavior: 'smooth' });
    }

    <?php if (!$isIndian): ?>
        // Stripe initialization
        const stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {

            style: {
                base: {
                    fontSize: '16px',
                    color: '#1e293b',
                    '::placeholder': { color: '#94a3b8' }
                }
            }
        });
        cardElement.mount('#card-element');

        cardElement.on('change', function (event) {
            document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
        });
    <?php endif; ?>

    async function processPayment() {
        const button = document.getElementById('pay-button');
        button.disabled = true;
        button.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Processing...';

        try {
            if (gateway === 'stripe') {
                await processStripePayment();
            } else {
                await processRazorpayPayment();
            }
        } catch (error) {
            alert('Payment failed: ' + error.message);
            button.disabled = false;
            button.innerHTML = '<span class="material-symbols-outlined">lock</span> Pay ' + (currency === 'INR' ? '₹' : '$') + amount.toFixed(2);
        }
    }

    async function processStripePayment() {
        // Create payment intent
        const response = await fetch('/api/create-payment-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                draft_token: draftToken,
                is_draft: isDraft
            })
        });

        const { client_secret, error } = await response.json();

        if (error) throw new Error(error);

        // Confirm payment
        const { error: stripeError, paymentIntent } = await stripe.confirmCardPayment(client_secret, {
            payment_method: { card: cardElement }
        });

        if (stripeError) throw new Error(stripeError.message);

        // Redirect to confirmation
        window.location.href = '/order/' + orderId + '/confirmation';
    }

    async function processRazorpayPayment() {
        // Create Razorpay order
        const response = await fetch('/api/payments/index.php?action=create-razorpay-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                draft_token: draftToken,
                is_draft: isDraft
            })
        });

        const { razorpay_order_id, key_id, error } = await response.json();

        if (error) throw new Error(error);

        const options = {
            key: key_id,
            amount: amount * 100,
            currency: 'INR',
            name: 'Invitation Videos',
            description: 'Video Invitation',
            order_id: razorpay_order_id,
            handler: async function (response) {
                // Verify payment on server
                try {
                    const verifyResponse = await fetch('/api/payments/index.php?action=verify-razorpay', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            order_id: orderId,
                            draft_token: draftToken,
                            is_draft: isDraft,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    });

                    const result = await verifyResponse.json();

                    if (result.success && result.order_id) {
                        // Redirect to confirmation with the REAL order ID
                        window.location.href = '/order/' + result.order_id + '/confirmation';
                    } else if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        throw new Error(result.error || 'Payment verification failed');
                    }
                } catch (err) {
                    alert('Payment completed but verification failed. Please contact support.');
                    console.error('Verify error:', err);
                }
            },
            theme: { color: '#970747' }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    }

    let currentDiscount = 0;
    let currentTotal = amount;

    async function applyPromo() {
        const code = document.getElementById('promo-code').value.trim();
        if (!code) {
            showPromoError('Please enter a promo code');
            return;
        }

        const button = event.target;
        const originalText = button.innerText;
        button.disabled = true;
        button.innerText = 'Applying...';

        try {
            const response = await fetch('/api/promo/validate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    code: code,
                    order_id: orderId,
                    is_draft: isDraft,
                    draft_token: draftToken
                })
            });

            const result = await response.json();

            if (result.success) {
                currentDiscount = result.discount_amount;
                currentTotal = result.new_total;

                // Update UI
                document.getElementById('discount-amount').textContent = result.discount_display;
                document.getElementById('total-amount').textContent = result.new_total_display;

                // Update pay button
                const payBtn = document.getElementById('pay-button');
                payBtn.innerHTML = '<span class="material-symbols-outlined">lock</span> Pay ' + result.new_total_display;

                // Show success and disable input
                showPromoSuccess(result.message + ' (' + result.promo_description + ')');
                document.getElementById('promo-code').disabled = true;
                button.disabled = true;
                button.innerText = 'Applied';
                button.classList.remove('hover:bg-slate-200');
                button.classList.add('bg-green-100', 'text-green-700');
            } else {
                showPromoError(result.error);
                button.disabled = false;
                button.innerText = originalText;
            }
        } catch (error) {
            showPromoError('Failed to validate promo code');
            button.disabled = false;
            button.innerText = originalText;
        }
    }

    function showPromoError(message) {
        const container = document.getElementById('promo-code').parentElement;
        removePromoMessages();
        const errorDiv = document.createElement('div');
        errorDiv.id = 'promo-message';
        errorDiv.className = 'text-red-500 text-xs mt-2 flex items-center gap-1';
        errorDiv.innerHTML = '<span class="material-symbols-outlined text-sm">error</span>' + message;
        container.appendChild(errorDiv);
    }

    function showPromoSuccess(message) {
        const container = document.getElementById('promo-code').parentElement;
        removePromoMessages();
        const successDiv = document.createElement('div');
        successDiv.id = 'promo-message';
        successDiv.className = 'text-green-600 text-xs mt-2 flex items-center gap-1';
        successDiv.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span>' + message;
        container.appendChild(successDiv);
    }

    function removePromoMessages() {
        const existing = document.getElementById('promo-message');
        if (existing) existing.remove();
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>