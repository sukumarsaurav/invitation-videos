<?php
/**
 * Template Customization Page - Multi-Step Flow
 * 
 * Step 0: Preview page (default)
 * Step 1: Text & Date fields
 * Step 2: Photo uploads
 * Step 3: Music selection
 * Then: Checkout
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';
require_once __DIR__ . '/../../src/Form/DynamicFormRenderer.php';

// Support both slug and ID for template lookup
$templateSlug = $_GET['template_slug'] ?? null;
$templateId = intval($_GET['template_id'] ?? 0);

// Get template by slug or ID
if ($templateSlug) {
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
$templateSlug = $template['slug'];

// Initialize form renderer
$formRenderer = new DynamicFormRenderer();
$groupedFields = $formRenderer->getFields($templateId);

// Current step (0 = preview, 1-3 = customization steps)
$step = intval($_GET['step'] ?? 0);

// Step configuration
$stepGroups = [
    1 => ['couple_details', 'family_details', 'event_details', 'general'],
    2 => ['photos'],
    3 => ['audio']
];

$stepTitles = [
    1 => 'Event Details',
    2 => 'Photos',
    3 => 'Music'
];

$stepIcons = [
    1 => 'edit_note',
    2 => 'add_photo_alternate',
    3 => 'music_note'
];

// Check which steps actually have fields
$availableSteps = [];
foreach ($stepGroups as $stepNum => $groups) {
    if ($formRenderer->hasFieldsInGroups($templateId, $groups)) {
        $availableSteps[] = $stepNum;
    }
}
$totalSteps = count($availableSteps);

// Get current step index in available steps
$currentStepIndex = array_search($step, $availableSteps);
if ($currentStepIndex === false && $step > 0) {
    $currentStepIndex = 0;
    $step = $availableSteps[0] ?? 1;
}

// Session storage for multi-step form data
if (!isset($_SESSION['customize_data'])) {
    $_SESSION['customize_data'] = [];
}
if (!isset($_SESSION['customize_template'])) {
    $_SESSION['customize_template'] = null;
}

// Reset session if different template
if ($_SESSION['customize_template'] !== $templateId) {
    $_SESSION['customize_data'] = [];
    $_SESSION['customize_template'] = $templateId;
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        // Store current step data in session
        foreach ($_POST as $key => $value) {
            if ($key !== CSRF_TOKEN_NAME && $key !== 'user_timezone') {
                $_SESSION['customize_data'][$key] = $value;
            }
        }

        // Store timezone
        if (!empty($_POST['user_timezone'])) {
            $_SESSION['user_timezone'] = $_POST['user_timezone'];
        }

        // Determine next step
        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex < $totalSteps) {
            // Go to next step
            $nextStep = $availableSteps[$nextStepIndex];
            header('Location: /template/' . $templateSlug . '?step=' . $nextStep);
            exit;
        } else {
            // Final step - create order
            $allData = $_SESSION['customize_data'];

            // Auto-detect country
            if (!isset($_SESSION['user_country'])) {
                $timezone = $_SESSION['user_timezone'] ?? '';
                if (strpos($timezone, 'Asia/Kolkata') !== false || strpos($timezone, 'Asia/Calcutta') !== false) {
                    $_SESSION['user_country'] = 'IN';
                } else {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                    if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
                        $geoData = @file_get_contents("https://ipapi.co/{$ip}/json/");
                        if ($geoData) {
                            $geo = json_decode($geoData, true);
                            $_SESSION['user_country'] = $geo['country_code'] ?? 'US';
                        }
                    }
                }
                if (!isset($_SESSION['user_country'])) {
                    $_SESSION['user_country'] = 'US';
                }
            }

            // Determine currency
            $userCountry = $_SESSION['user_country'] ?? 'US';
            $currency = ($userCountry === 'IN') ? 'INR' : 'USD';
            $amount = ($currency === 'INR') ? $template['price_inr'] : $template['price_usd'];

            // Create order
            $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8));
            $sql = "INSERT INTO orders (order_number, user_id, template_id, amount, currency, customization_data, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";

            Database::query($sql, [
                $orderNumber,
                $_SESSION['user_id'] ?? 1,
                $templateId,
                $amount,
                $currency,
                json_encode($allData)
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

            // Clear session data
            unset($_SESSION['customize_data']);
            unset($_SESSION['customize_template']);

            // Redirect to checkout
            header('Location: /checkout/' . $orderId);
            exit;
        }
    }
}

// Get stored values for current step
$storedValues = $_SESSION['customize_data'] ?? [];

// Calculate progress
$progressPercent = $totalSteps > 0 ? round((($currentStepIndex + 1) / $totalSteps) * 100) : 0;

$pageTitle = ($step === 0 ? '' : 'Customize - ') . $template['title'];
?>

<?php ob_start(); ?>

<div class="max-w-7xl mx-auto px-4 md:px-8 py-6 sm:py-8">

    <?php if ($step === 0): ?>
        <!-- ==================== PREVIEW PAGE ==================== -->

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
            <span class="text-slate-400">/</span>
            <a class="text-slate-500 hover:text-primary transition-colors" href="/templates">Templates</a>
            <span class="text-slate-400">/</span>
            <span class="font-medium text-slate-900 dark:text-white"><?= Security::escape($template['title']) ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Left: Template Info -->
            <div class="lg:col-span-5 xl:col-span-5 space-y-6">

                <!-- Title & Category -->
                <div>
                    <span
                        class="inline-flex items-center gap-1 text-xs font-bold text-primary uppercase tracking-wider mb-2">
                        <?= ucfirst(str_replace('_', ' ', $template['category'] ?? 'General')) ?>
                    </span>
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white leading-tight">
                        <?= Security::escape($template['title']) ?>
                    </h1>
                </div>

                <!-- Rating (mock) -->
                <div class="flex items-center gap-2">
                    <div class="flex gap-0.5 text-yellow-500">
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star_half</span>
                    </div>
                    <span class="text-sm text-slate-600">4.8 (<?= rand(50, 200) ?> reviews)</span>
                </div>

                <!-- Price -->
                <div class="flex items-baseline gap-3">
                    <span class="text-3xl font-black text-primary">
                        $<?= number_format($template['price_usd'], 0) ?>
                    </span>
                    <?php if ($template['price_inr']): ?>
                        <span class="text-lg text-slate-500">/ ₹<?= number_format($template['price_inr'], 0) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    <?= Security::escape($template['description'] ?? 'Beautiful video invitation template perfect for your special occasion.') ?>
                </p>

                <!-- Features -->
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Full HD 1080p Video Download</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Optimized for WhatsApp & Social Media</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Delivered in 24-48 Hours</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Free Revisions Included</span>
                    </li>
                </ul>

                <!-- CTA Button -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <a href="/template/<?= Security::escape($templateSlug) ?>?step=<?= $availableSteps[0] ?? 1 ?>"
                        class="flex-1 flex items-center justify-center gap-2 px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all text-lg">
                        <span>Customize Now</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </a>
                </div>

                <!-- Trust badges -->
                <div class="flex items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="material-symbols-outlined text-lg">verified_user</span>
                        <span>Secure Payment</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="material-symbols-outlined text-lg">support_agent</span>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>

            <!-- Right: Template Preview -->
            <div class="lg:col-span-7 xl:col-span-7">
                <div class="sticky top-24">
                    <div
                        class="relative aspect-[4/5] sm:aspect-[9/14] lg:aspect-[4/5] w-full max-w-lg mx-auto rounded-2xl overflow-hidden shadow-2xl bg-slate-200">
                        <img src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                            alt="<?= Security::escape($template['title']) ?>" class="w-full h-full object-cover">

                        <!-- Play Button Overlay -->
                        <?php if (!empty($template['preview_video_url'])): ?>
                            <div id="play-video-btn"
                                data-video-url="<?= htmlspecialchars($template['preview_video_url'], ENT_QUOTES, 'UTF-8') ?>"
                                class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors cursor-pointer group">
                                <div
                                    class="size-20 rounded-full bg-white/30 backdrop-blur-md flex items-center justify-center text-white border border-white/50 shadow-lg group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-4xl">play_arrow</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Duration Badge -->
                        <div
                            class="absolute bottom-4 right-4 bg-black/70 backdrop-blur-sm text-white text-sm font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-lg">schedule</span>
                            <?= $template['duration_seconds'] ?>s
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ==================== CUSTOMIZATION STEPS ==================== -->

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Left: Form Section -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Template Title & Progress -->
                <div class="space-y-4">
                    <div>
                        <a href="/template/<?= Security::escape($templateSlug) ?>"
                            class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary mb-2">
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                            Back to preview
                        </a>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">
                            <?= Security::escape($template['title']) ?>
                        </h1>
                    </div>

                    <!-- Progress Indicator (Below Title) -->
                    <div
                        class="flex flex-col gap-3 bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                    <span class="material-symbols-outlined"><?= $stepIcons[$step] ?? 'edit' ?></span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Step
                                        <?= $currentStepIndex + 1 ?> of <?= $totalSteps ?>
                                    </p>
                                    <h2 class="font-bold text-slate-900 dark:text-white">
                                        <?= $stepTitles[$step] ?? 'Details' ?>
                                    </h2>
                                </div>
                            </div>
                            <span class="text-primary font-bold"><?= $progressPercent ?>%</span>
                        </div>
                        <div class="rounded-full bg-slate-200 dark:bg-slate-700 h-2 overflow-hidden">
                            <div class="h-full rounded-full bg-primary transition-all duration-500"
                                style="width: <?= $progressPercent ?>%;"></div>
                        </div>

                        <!-- Step Dots -->
                        <div class="flex items-center justify-between pt-2">
                            <?php foreach ($availableSteps as $idx => $s): ?>
                                <div class="flex items-center gap-2 <?= $idx < count($availableSteps) - 1 ? 'flex-1' : '' ?>">
                                    <div
                                        class="size-8 rounded-full flex items-center justify-center text-sm font-bold transition-all
                                <?= $s < $step ? 'bg-green-500 text-white' : ($s === $step ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500') ?>">
                                        <?php if ($s < $step): ?>
                                            <span class="material-symbols-outlined text-sm">check</span>
                                        <?php else: ?>
                                            <?= $idx + 1 ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($idx < count($availableSteps) - 1): ?>
                                        <div
                                            class="flex-1 h-0.5 <?= $s < $step ? 'bg-green-500' : 'bg-slate-200 dark:bg-slate-700' ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                        <span class="material-symbols-outlined">error</span>
                        <?= Security::escape($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form id="customize-form" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="user_timezone" id="user_timezone" value="">

                    <?= $formRenderer->renderByGroups($templateId, $stepGroups[$step] ?? [], $storedValues) ?>

                    <!-- Navigation Buttons -->
                    <div
                        class="flex items-center justify-between gap-4 pt-6 border-t border-slate-200 dark:border-slate-700">
                        <?php if ($currentStepIndex > 0): ?>
                            <a href="/template/<?= Security::escape($templateSlug) ?>?step=<?= $availableSteps[$currentStepIndex - 1] ?>"
                                class="flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Back
                            </a>
                        <?php else: ?>
                            <a href="/template/<?= Security::escape($templateSlug) ?>"
                                class="flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Back
                            </a>
                        <?php endif; ?>

                        <button type="submit"
                            class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-colors">
                            <?php if ($currentStepIndex < $totalSteps - 1): ?>
                                <span>Next Step</span>
                                <span class="material-symbols-outlined">arrow_forward</span>
                            <?php else: ?>
                                <span>Continue to Checkout</span>
                                <span class="material-symbols-outlined">shopping_cart</span>
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Preview Sidebar -->
            <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-800">
                    <div class="relative aspect-[4/5] w-full bg-slate-200">
                        <img src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                            alt="<?= Security::escape($template['title']) ?>" class="w-full h-full object-cover">
                        <div
                            class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm text-white text-xs font-bold px-2 py-1 rounded">
                            <?= $template['duration_seconds'] ?>s
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-500">Total</span>
                            <span
                                class="text-xl font-bold text-primary">$<?= number_format($template['price_usd'], 0) ?></span>
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
                        <a class="text-xs text-primary hover:underline" href="/support">Chat with our team</a>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
    // Capture user timezone for country detection
    document.addEventListener('DOMContentLoaded', funct i on() {
        const tzField = document.getElementById('user_timezone');
        if (tzField) {
            tzField.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
        }
    });

  // Video Modal Functions
    function getYouTubeEmbedUrl(url) {
        // Handle various YouTube URL formats
        var videoId = null;
        
        // youtube.com/watch?v=VIDEO_ID
        var watchMatch = url.match(/[?&]v=([^&]+)/);
        if (watchMatch) videoId = watchMatch[1];
        
        // youtu.be/VIDEO_ID
        var shortMatch = url.match(/youtu\.be\/([^?&]+)/);
        if (shortMatch) videoId = shortMatch[1];
        
        // youtube.com/embed/VIDEO_ID
        var embedMatch = url.match(/youtube\.com\/embed\/([^?&]+)/);
        if (embedMatch) videoId = embedMatch[1];
        
        // youtube.com/shorts/VIDEO_ID
        var shortsMatch = url.match(/youtube\.com\/shorts\/([^?&]+)/);
        if (shortsMatch) videoId = shortsMatch[1];
        
        if (videoId) {
            return 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
        }
        
        // Return original URL if not YouTube
        return url;
    }

    function openVideoModal(videoUrl) {
        var embedUrl = getYouTubeEmbedUrl(videoUrl);
        
        // Create modal
        var modal = document.createElement('div');
        modal.id = 'video-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm';
        
        var container = document.createElement('div');
        container.className = 'relative w-full max-w-4xl aspect-[9/16] sm:aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl';
        
        var iframe = document.createElement('iframe');
        iframe.src = embedUrl;
        iframe.className = 'w-full h-full';
        iframe.frameBorder = '0';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;
        
        var closeBtn = document.createElement('button');
        closeBtn.className = 'absolute top-4 right-4 size-10 rounded-full bg-black/50 hover:bg-black/70 text-white flex items-center justify-center transition-colors';
        closeBtn.innerHTML = '<span class="material-symbols-outlined">close</span>';
        closeBtn.onclick = closeVideoModal;
        
        container.appendChild(iframe);
        container.appendChild(closeBtn);
        modal.appendChild(container);
        
        // Close on backdrop click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeVideoModal();
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
                document.removeEventListener('keydown', escHandler);
            }
        });
        
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
    }

    function closeVideoModal() {
        var modal = document.getElementById('video-modal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    }
    
    // Attach click handler to play button
    document.addEventListener('DOMContentLoaded', function() {
        var playBtn = document.getElementById('play-video-btn');
        if (playBtn) {
            playBtn.addEventListener('click', function() {
                var videoUrl = this.getAttribute('data-video-url');
                if (videoUrl) {
                    openVideoModal(videoUrl);
                }
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>