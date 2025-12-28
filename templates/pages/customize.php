<?php
/**
 * Template Customization Page
 * 
 * Dynamic form based on template fields
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Form/DynamicFormRenderer.php';

// Support both slug and ID for template lookup
$templateSlug = $_GET['template_slug'] ?? null;
$templateId = intval($_GET['template_id'] ?? 0);

// Get template by slug or ID
if ($templateSlug) {
    // Check if it's numeric (backward compatibility for ID-based URLs)
    if (is_numeric($templateSlug)) {
        $template = Database::fetchOne("SELECT * FROM templates WHERE id = ? AND is_active = 1", [$templateSlug]);
    } else {
        $template = Database::fetchOne("SELECT * FROM templates WHERE slug = ? AND is_active = 1", [$templateSlug]);
    }
} elseif ($templateId) {
    $template = Database::fetchOne("SELECT * FROM templates WHERE id = ? AND is_active = 1", [$templateId]);
} else {
    header('Location: /templates');
    exit;
}

if (!$template) {
    header('Location: /templates');
    exit;
}

$templateId = $template['id'];

// Initialize form renderer
$formRenderer = new DynamicFormRenderer();
$groupedFields = $formRenderer->getFields($templateId);

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        // Validate form
        $errors = $formRenderer->validate($templateId, $_POST, $_FILES);

        if (empty($errors)) {
            // Create order
            $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8));

            // Auto-detect country if not set
            if (!isset($_SESSION['user_country'])) {
                // Check timezone first (most reliable for browser users)
                $timezone = $_POST['user_timezone'] ?? '';
                if (strpos($timezone, 'Asia/Kolkata') !== false || strpos($timezone, 'Asia/Calcutta') !== false) {
                    $_SESSION['user_country'] = 'IN';
                } else {
                    // Fallback: Check IP-based geolocation using free ipapi.co service
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                    if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
                        $geoData = @file_get_contents("https://ipapi.co/{$ip}/json/");
                        if ($geoData) {
                            $geo = json_decode($geoData, true);
                            $_SESSION['user_country'] = $geo['country_code'] ?? 'US';
                        }
                    }
                }
                // Default to US if still not set
                if (!isset($_SESSION['user_country'])) {
                    $_SESSION['user_country'] = 'US';
                }
            }

            // Determine currency based on user country
            $userCountry = $_SESSION['user_country'] ?? 'US';
            $currency = ($userCountry === 'IN') ? 'INR' : 'USD';
            $amount = ($currency === 'INR') ? $template['price_inr'] : $template['price_usd'];

            // Store order
            $sql = "INSERT INTO orders (order_number, user_id, template_id, amount, currency, customization_data, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";

            Database::query($sql, [
                $orderNumber,
                $_SESSION['user_id'] ?? 1, // Guest user fallback
                $templateId,
                $amount,
                $currency,
                json_encode($_POST)
            ]);

            $orderId = Database::lastInsertId();

            // Handle file uploads
            foreach ($_FILES as $fieldName => $file) {
                if (!empty($file['tmp_name'])) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $storedFilename = uniqid() . '_' . $fieldName . '.' . $extension;
                    $filePath = UPLOAD_PATH . $storedFilename;

                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        $fileType = strpos($file['type'], 'audio') !== false ? 'music' : 'image';

                        Database::query(
                            "INSERT INTO order_uploads (order_id, field_name, file_type, original_filename, stored_filename, file_path, mime_type, file_size) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            [$orderId, $fieldName, $fileType, $file['name'], $storedFilename, $filePath, $file['type'], $file['size']]
                        );
                    }
                }
            }

            // Redirect to checkout
            header('Location: /checkout/' . $orderId);
            exit;
        }
    }
}

$pageTitle = 'Customize - ' . $template['title'];
?>

<?php ob_start(); ?>

<div class="max-w-7xl mx-auto px-4 md:px-8 py-8">

    <!-- Progress Bar -->
    <div class="max-w-[960px] mx-auto mb-10">
        <div class="flex flex-col gap-3">
            <div class="flex gap-6 justify-between items-end">
                <div class="flex flex-col gap-1">
                    <p class="text-slate-500 text-sm font-semibold uppercase tracking-wider">Step 2 of 4</p>
                    <h3 class="text-xl font-bold">Customize</h3>
                </div>
                <p class="text-primary font-bold">50%</p>
            </div>
            <div class="rounded-full bg-slate-200 dark:bg-slate-700 h-2 overflow-hidden">
                <div class="h-full rounded-full bg-primary transition-all duration-500" style="width: 50%;"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Form Section -->
        <div class="lg:col-span-8 flex flex-col gap-8">
            <div class="flex flex-col gap-3">
                <h1 class="text-3xl md:text-4xl font-black leading-tight tracking-tight">Customize Your Invitation</h1>
                <p class="text-slate-500 text-base md:text-lg">
                    Fill in the details below to personalize the
                    <span class="text-primary font-semibold">'<?= Security::escape($template['title']) ?>'</span>
                    template.
                </p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <?= Security::escape($errors['general']) ?>
                </div>
            <?php endif; ?>

            <form id="customize-form" method="POST" enctype="multipart/form-data" class="space-y-6">
                <?= Security::csrfField() ?>
                <input type="hidden" name="user_timezone" id="user_timezone" value="">

                <?= $formRenderer->render($templateId) ?>

                <!-- Mobile Submit Button -->
                <div
                    class="flex lg:hidden items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <a href="/templates"
                        class="px-6 py-3 rounded-lg border border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Back
                    </a>
                    <button type="submit"
                        class="flex-1 px-6 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-colors">
                        Continue to Checkout
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Sidebar -->
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-800">
                <div class="relative aspect-[9/16] md:aspect-video lg:aspect-[4/5] w-full bg-slate-200">
                    <img src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                        alt="<?= Security::escape($template['title']) ?>" class="w-full h-full object-cover">

                    <!-- Play Button -->
                    <div
                        class="absolute inset-0 flex items-center justify-center bg-black/20 hover:bg-black/30 transition-colors cursor-pointer">
                        <div
                            class="size-14 rounded-full bg-white/30 backdrop-blur-md flex items-center justify-center text-white border border-white/50 shadow-lg hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-3xl">play_arrow</span>
                        </div>
                    </div>

                    <div
                        class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm text-white text-xs font-bold px-2 py-1 rounded">
                        <?= $template['duration_seconds'] ?>s
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold"><?= Security::escape($template['title']) ?></h3>
                        <span class="text-primary text-xl font-bold">
                            $<?= number_format($template['price_usd'], 2) ?>
                        </span>
                    </div>

                    <p class="text-slate-500 text-sm mb-4">
                        <?= Security::escape($template['description'] ?? 'Beautiful video invitation template.') ?>
                    </p>

                    <ul class="space-y-2 mb-6">
                        <li class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                            Full HD (1080p) Download
                        </li>
                        <li class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                            Social Media Ready
                        </li>
                        <li class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <span class="material-symbols-outlined text-primary text-lg">check_circle</span>
                            Unlimited Revisions
                        </li>
                    </ul>

                    <!-- Desktop Submit Button -->
                    <div class="hidden lg:flex flex-col gap-3">
                        <button type="submit" form="customize-form"
                            class="w-full px-6 py-4 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2">
                            <span>Continue to Checkout</span>
                            <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </button>
                        <a href="/templates"
                            class="w-full px-6 py-3 rounded-lg border border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-center text-slate-500 hover:text-slate-700">
                            Back to Templates
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                <div
                    class="size-10 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined">support_agent</span>
                </div>
                <div>
                    <p class="font-bold text-sm">Need Help?</p>
                    <a class="text-xs text-primary hover:underline" href="/support">Chat with our designers</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Capture user timezone for country detection
    document.addEventListener('DOMContentLoaded', function () {
        const tzField = document.getElementById('user_timezone');
        if (tzField) {
            tzField.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>