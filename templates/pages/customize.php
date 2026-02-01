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
require_once __DIR__ . '/../../src/Services/DressDesignService.php';

use InvitationVideos\Services\DressDesignService;

// Disable bottom tabs - customize page has its own contextual bottom bar
$showMobileBottomTabs = false;

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

// Fetch gallery images for this template
$galleryImages = Database::fetchAll(
    "SELECT * FROM template_images WHERE template_id = ? ORDER BY display_order",
    [$templateId]
);

// Fetch related templates (same category, excluding current)
$relatedTemplates = Database::fetchAll(
    "SELECT id, title, slug, thumbnail_url, price_usd, price_inr, discounted_price_usd, discounted_price_inr, duration_seconds 
     FROM templates 
     WHERE category = ? AND id != ? AND is_active = 1 
     ORDER BY purchase_count DESC, created_at DESC 
     LIMIT 4",
    [$template['category'], $templateId]
);

// Check if template is in user's wishlist
$isInWishlist = false;
if (!empty($_SESSION['user_id'])) {
    $wishlistCheck = Database::fetchOne(
        "SELECT id FROM wishlist WHERE user_id = ? AND template_id = ?",
        [$_SESSION['user_id'], $templateId]
    );
    $isInWishlist = (bool) $wishlistCheck;
}

// Initialize form renderer
$formRenderer = new DynamicFormRenderer();
$groupedFields = $formRenderer->getFields($templateId);

// ============================================
// NEW: Slide-Based Visual Editor Support
// ============================================

// Parse template_definition to determine if we use slide-based editor
$templateDefinition = json_decode($template['template_definition'] ?? 'null', true);
$hasSlideEditor = !empty($templateDefinition['slides']) && is_array($templateDefinition['slides']);
$slides = $hasSlideEditor ? $templateDefinition['slides'] : [];
$totalSlides = count($slides);

// Check for slide parameter (?slide=0, ?slide=1, etc.)
$currentSlideIndex = isset($_GET['slide']) ? intval($_GET['slide']) : null;
$isSlideEditorMode = $hasSlideEditor && $currentSlideIndex !== null;
$isMusicStep = isset($_GET['music']);
$isCheckoutMode = isset($_GET['checkout']);

// Helper: Extract editable fields from a slide config
function extractEditableFields(array $slideConfig): array
{
    $fields = [];
    foreach ($slideConfig['layers'] ?? [] as $layer) {
        if (!empty($layer['fieldKey'])) {
            $fields[] = [
                'fieldKey' => $layer['fieldKey'],
                'layerId' => $layer['id'] ?? $layer['fieldKey'],
                'type' => $layer['type'] ?? 'text',  // 'text' or 'image'
                'label' => ucwords(str_replace(['_', '-'], ' ', $layer['fieldKey'])),
                'defaultValue' => $layer['defaultValue'] ?? '',
                'style' => $layer['style'] ?? [],
                'position' => $layer['position'] ?? [],
            ];
        }
    }
    return $fields;
}

// If in slide editor mode, validate and get current slide
$currentSlideConfig = null;
$editableFields = [];
if ($isSlideEditorMode) {
    // Validate slide index
    if ($currentSlideIndex < 0 || $currentSlideIndex >= $totalSlides) {
        header('Location: /template/' . $templateSlug . '?slide=0');
        exit;
    }
    $currentSlideConfig = $slides[$currentSlideIndex];
    $editableFields = extractEditableFields($currentSlideConfig);
}

// ============================================
// END: Slide-Based Visual Editor Support
// ============================================

// Current step (0 = preview, 1-3 = customization steps) - for legacy mode
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

// Check if this template has AI caricature enabled
$isAiCaricatureEnabled = !empty($template['ai_caricature_enabled']);
$dressDesigns = [];
$hasDressStep = false;

if ($isAiCaricatureEnabled) {
    $dressService = new DressDesignService();
    $dressDesigns = $dressService->getDesignsForTemplate($templateId);
    $hasDressStep = count($dressDesigns) > 0;
}

// Check which steps actually have fields
$availableSteps = [];
foreach ($stepGroups as $stepNum => $groups) {
    if ($formRenderer->hasFieldsInGroups($templateId, $groups)) {
        $availableSteps[] = $stepNum;
    }
}
$totalSteps = count($availableSteps);

// Get current step index in available steps
// For AI templates with dress step, 'dress' is step 0.5 (before step 1)
$isDressStep = ($step === 0 && isset($_GET['dress']) && $hasDressStep);
$currentStepIndex = array_search($step, $availableSteps);
if ($currentStepIndex === false && $step > 0) {
    $currentStepIndex = 0;
    $step = $availableSteps[0] ?? 1;
}

// Adjust total steps count if dress step exists
$effectiveTotalSteps = $totalSteps + ($hasDressStep ? 1 : 0);

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

        // Handle dress/color selection for AI templates
        if ($isDressStep && isset($_POST['dress_id'])) {
            $_SESSION['customize_dress_id'] = intval($_POST['dress_id']);
            $_SESSION['customize_color_id'] = !empty($_POST['color_id']) ? intval($_POST['color_id']) : null;

            // Store in customize_data as well for draft creation
            $_SESSION['customize_data']['ai_dress_id'] = $_SESSION['customize_dress_id'];
            $_SESSION['customize_data']['ai_color_id'] = $_SESSION['customize_color_id'];
        }

        // IMPORTANT: Handle file uploads IMMEDIATELY at each step, not just final step
        // Files are stored in organized draft directory and paths saved in session
        if (!isset($_SESSION['customize_uploads'])) {
            $_SESSION['customize_uploads'] = [];
        }

        // Create/get organized draft directory for this session
        if (!isset($_SESSION['customize_draft_dir'])) {
            $draftDirName = bin2hex(random_bytes(8)); // 16-char unique name
            $_SESSION['customize_draft_dir'] = 'drafts/' . $draftDirName . '/';
        }
        $draftDir = UPLOAD_PATH . $_SESSION['customize_draft_dir'];

        // ALWAYS ensure directory exists (create if not) - runs on every step
        if (!is_dir($draftDir)) {
            $created = @mkdir($draftDir, 0755, true);
            error_log("customize.php: Creating draft dir: {$draftDir} - " . ($created ? 'SUCCESS' : 'FAILED'));
            if (!$created) {
                error_log("customize.php: mkdir error: " . print_r(error_get_last(), true));
            }
        }

        // DEBUG: Log all received files
        error_log("customize.php: POST received. Step={$step}, FILES count=" . count($_FILES));
        error_log("customize.php: FILES keys: " . implode(', ', array_keys($_FILES)));
        error_log("customize.php: Using draft dir: " . $draftDir . " (exists: " . (is_dir($draftDir) ? 'YES' : 'NO') . ")");

        foreach ($_FILES as $fieldName => $file) {
            error_log("customize.php: Processing file '{$fieldName}': name={$file['name']}, type={$file['type']}, size={$file['size']}, error={$file['error']}, tmp_name={$file['tmp_name']}");

            if (!empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
                // Validate file size
                if ($file['size'] > UPLOAD_MAX_SIZE) {
                    error_log("customize.php: File '{$fieldName}' rejected - size {$file['size']} exceeds limit " . UPLOAD_MAX_SIZE);
                    continue;
                }

                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $storedFilename = uniqid() . '_' . $fieldName . '.' . $extension;

                // Full path for actual file storage
                $fullPath = $draftDir . $storedFilename;

                // Web-relative path for database storage (standardized format)
                $webPath = '/uploads/' . $_SESSION['customize_draft_dir'] . $storedFilename;

                error_log("customize.php: Attempting to move '{$file['tmp_name']}' to '{$fullPath}'");

                if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                    // Store file info with WEB-RELATIVE path (standardized)
                    $_SESSION['customize_uploads'][$fieldName] = [
                        'stored_filename' => $storedFilename,
                        'file_path' => $webPath,  // Web-relative, not absolute
                        'original_filename' => $file['name'],
                        'mime_type' => $file['type'],
                        'file_size' => $file['size']
                    ];
                    error_log("customize.php: SUCCESS - Saved upload for field '{$fieldName}' with web path {$webPath}");
                } else {
                    error_log("customize.php: FAILED - Could not move uploaded file for field '{$fieldName}'");
                    error_log("customize.php: Last PHP error: " . print_r(error_get_last(), true));
                }
            } else {
                if (empty($file['tmp_name'])) {
                    error_log("customize.php: Skipping '{$fieldName}' - no tmp_name");
                } else {
                    error_log("customize.php: Skipping '{$fieldName}' - error code: {$file['error']}");
                }
            }
        }

        error_log("customize.php: Session uploads after processing: " . json_encode(array_keys($_SESSION['customize_uploads'] ?? [])));

        // Store timezone
        if (!empty($_POST['user_timezone'])) {
            $_SESSION['user_timezone'] = $_POST['user_timezone'];
        }

        // Helper function to check if request is XHR
        $isXhrRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // ============================================
        // NEW: Handle Slide Editor Mode Submission
        // ============================================
        if ($isSlideEditorMode && isset($_POST['slide_index'])) {
            $submittedSlideIndex = intval($_POST['slide_index']);
            $action = $_POST['action'] ?? 'next';

            // Log slide submission
            error_log("customize.php: Slide editor POST - slide={$submittedSlideIndex}, action={$action}");

            // Track completed slides
            if (!isset($_SESSION['completed_slides'])) {
                $_SESSION['completed_slides'] = [];
            }
            $_SESSION['completed_slides'][$submittedSlideIndex] = true;

            if ($action === 'next') {
                // Go to next slide
                $nextSlideIndex = $submittedSlideIndex + 1;
                if ($nextSlideIndex < $totalSlides) {
                    $redirectUrl = '/template/' . $templateSlug . '?slide=' . $nextSlideIndex;
                } else {
                    // All slides completed - go to checkout or music
                    // Check if template has music field
                    $hasMusicField = !empty($templateDefinition['music']['fieldKey']);
                    if ($hasMusicField && empty($_SESSION['customize_data']['musicUrl'])) {
                        $redirectUrl = '/template/' . $templateSlug . '?music=1';
                    } else {
                        // Skip to checkout
                        $redirectUrl = '/template/' . $templateSlug . '?checkout=1';
                    }
                }

                if ($isXhrRequest) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                    exit;
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($action === 'previous' && $submittedSlideIndex > 0) {
                $redirectUrl = '/template/' . $templateSlug . '?slide=' . ($submittedSlideIndex - 1);

                if ($isXhrRequest) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                    exit;
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($action === 'checkout') {
                // Proceed to checkout/draft creation
                $redirectUrl = '/template/' . $templateSlug . '?checkout=1';

                if ($isXhrRequest) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                    exit;
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            // NEW: Handle 'save' action - saves data without redirecting
            // Used by AJAX when clicking slide thumbnails
            if ($action === 'save') {
                // Data is already saved to session at the top of POST handler
                // Just respond with success
                if ($isXhrRequest) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'saved' => true,
                        'message' => 'Slide data saved'
                    ]);
                    exit;
                }
                // For non-AJAX, just stay on current page
                header('Location: /template/' . $templateSlug . '?slide=' . $submittedSlideIndex);
                exit;
            }
        }

        // Handle dress step redirect - go to first regular step
        if ($isDressStep) {
            $redirectUrl = '/template/' . $templateSlug . '?step=' . ($availableSteps[0] ?? 1);

            if ($isXhrRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                exit;
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        // Determine next step
        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex < $totalSteps) {
            // Go to next step
            $nextStep = $availableSteps[$nextStepIndex];
            $redirectUrl = '/template/' . $templateSlug . '?step=' . $nextStep;

            // For XHR requests, return JSON with redirect URL
            if ($isXhrRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                exit;
            }

            header('Location: ' . $redirectUrl);
            exit;
        } else {
            // Final step - create draft order (not real order until payment succeeds)
            // Ensure user is logged in before creating draft
            if (empty($_SESSION['user_id'])) {
                // Store customization data and redirect to login
                $_SESSION['checkout_redirect'] = '/template/' . $templateSlug . '?step=' . $step;
                $_SESSION['pending_customization'] = true;
                header('Location: /login?redirect=' . urlencode('/template/' . $templateSlug . '?step=' . $step));
                exit;
            }

            $allData = $_SESSION['customize_data'];

            // Auto-detect country
            if (!isset($_SESSION['user_country'])) {
                $timezone = $_SESSION['user_timezone'] ?? '';
                if (strpos($timezone, 'Asia/Kolkata') !== false || strpos($timezone, 'Asia/Calcutta') !== false) {
                    $_SESSION['user_country'] = 'IN';
                } else {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                    if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
                        // Use timeout to prevent page hang if API is slow
                        $context = stream_context_create(['http' => ['timeout' => 2]]);
                        $geoData = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $context);
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

            // Determine currency and amount
            $userCountry = $_SESSION['user_country'] ?? 'US';
            $currency = ($userCountry === 'IN') ? 'INR' : 'USD';
            $amount = ($currency === 'INR') ? $template['price_inr'] : $template['price_usd'];

            // Use discounted price if available
            if ($currency === 'INR' && !empty($template['discounted_price_inr'])) {
                $amount = $template['discounted_price_inr'];
            } elseif ($currency === 'USD' && !empty($template['discounted_price_usd'])) {
                $amount = $template['discounted_price_usd'];
            }

            // Create draft order (real order will be created after payment success)
            require_once __DIR__ . '/../../src/Services/DraftOrderService.php';
            $draftService = new \InvitationVideos\Services\DraftOrderService();

            // Pass files directory via internal key (extracted inside createDraft)
            if (!empty($_SESSION['customize_draft_dir'])) {
                $allData['_files_directory'] = $_SESSION['customize_draft_dir'];
            }

            $draft = $draftService->createDraft(
                $templateId,
                $amount,
                $currency,
                $allData,
                $_SESSION['user_id'] ?? null
            );

            $draftId = $draft['id'];
            $draftToken = $draft['draft_token'];

            // Add all uploads from session (collected from all steps)
            $sessionUploads = $_SESSION['customize_uploads'] ?? [];
            error_log("customize.php: Final step - found " . count($sessionUploads) . " uploads in session");

            foreach ($sessionUploads as $fieldName => $uploadInfo) {
                $fileInfo = [
                    'name' => $uploadInfo['original_filename'],
                    'type' => $uploadInfo['mime_type'],
                    'size' => $uploadInfo['file_size']
                ];
                $draftService->addUpload(
                    $draftId,
                    $fieldName,
                    $fileInfo,
                    $uploadInfo['file_path']
                );
                error_log("customize.php: Added upload record for field '{$fieldName}'");
            }

            // Clear session data
            unset($_SESSION['customize_data']);
            unset($_SESSION['customize_template']);
            unset($_SESSION['customize_uploads']);

            // Redirect to checkout with draft token
            $checkoutUrl = '/checkout/' . $draftToken;

            // For XHR requests, return JSON with redirect URL
            if ($isXhrRequest) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => $checkoutUrl]);
                exit;
            }

            header('Location: ' . $checkoutUrl);
            exit;

        }
    }
}

// ============================================
// Checkout Mode Handler (for Slide Editor flow)
// ============================================
if ($isCheckoutMode && $hasSlideEditor) {
    // Ensure user is logged in before creating draft
    if (empty($_SESSION['user_id'])) {
        $_SESSION['checkout_redirect'] = '/template/' . $templateSlug . '?checkout=1';
        $_SESSION['pending_customization'] = true;
        header('Location: /login?redirect=' . urlencode('/template/' . $templateSlug . '?checkout=1'));
        exit;
    }

    $allData = $_SESSION['customize_data'] ?? [];

    // Auto-detect country
    if (!isset($_SESSION['user_country'])) {
        $timezone = $_SESSION['user_timezone'] ?? '';
        if (strpos($timezone, 'Asia/Kolkata') !== false || strpos($timezone, 'Asia/Calcutta') !== false) {
            $_SESSION['user_country'] = 'IN';
        } else {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
                $context = stream_context_create(['http' => ['timeout' => 2]]);
                $geoData = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $context);
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

    // Determine currency and amount
    $userCountry = $_SESSION['user_country'] ?? 'US';
    $currency = ($userCountry === 'IN') ? 'INR' : 'USD';
    $amount = ($currency === 'INR') ? $template['price_inr'] : $template['price_usd'];

    // Use discounted price if available
    if ($currency === 'INR' && !empty($template['discounted_price_inr'])) {
        $amount = $template['discounted_price_inr'];
    } elseif ($currency === 'USD' && !empty($template['discounted_price_usd'])) {
        $amount = $template['discounted_price_usd'];
    }

    // Create draft order
    require_once __DIR__ . '/../../src/Services/DraftOrderService.php';
    $draftService = new \InvitationVideos\Services\DraftOrderService();

    if (!empty($_SESSION['customize_draft_dir'])) {
        $allData['_files_directory'] = $_SESSION['customize_draft_dir'];
    }

    $draft = $draftService->createDraft(
        $templateId,
        $amount,
        $currency,
        $allData,
        $_SESSION['user_id'] ?? null
    );

    $draftId = $draft['id'];
    $draftToken = $draft['draft_token'];

    // Add all uploads from session
    $sessionUploads = $_SESSION['customize_uploads'] ?? [];
    foreach ($sessionUploads as $fieldName => $uploadInfo) {
        $fileInfo = [
            'name' => $uploadInfo['original_filename'],
            'type' => $uploadInfo['mime_type'],
            'size' => $uploadInfo['file_size']
        ];
        $draftService->addUpload(
            $draftId,
            $fieldName,
            $fileInfo,
            $uploadInfo['file_path']
        );
    }

    // Clear session data
    unset($_SESSION['customize_data']);
    unset($_SESSION['customize_template']);
    unset($_SESSION['customize_uploads']);
    unset($_SESSION['completed_slides']);

    // Redirect to checkout with draft token
    header('Location: /checkout/' . $draftToken);
    exit;
}

// Get stored values for current step
$storedValues = $_SESSION['customize_data'] ?? [];
$storedUploads = $_SESSION['customize_uploads'] ?? [];

// Calculate progress
$progressPercent = $totalSteps > 0 ? round((($currentStepIndex + 1) / $totalSteps) * 100) : 0;

// For mobile header: show back arrow instead of hamburger
$isTemplateDetailPage = true;
$templateBackUrl = ($step === 0)
    ? '/templates'  // Preview → Gallery
    : '/template/' . $templateSlug;  // Customize steps → Preview
$templateTitle = $template['title'];  // For header display on mobile

$pageTitle = ($step === 0 ? '' : 'Customize - ') . $template['title'];
?>

<?php ob_start(); ?>

<div class="max-w-7xl mx-auto px-4 md:px-8 py-6 sm:py-8">

    <?php if ($step === 0 && !$isSlideEditorMode): ?>
        <!-- ==================== PREVIEW PAGE ==================== -->

        <!-- Breadcrumb (hidden on mobile, header shows back arrow) -->
        <nav class="hidden lg:flex items-center gap-2 text-sm mb-6">
            <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
            <span class="text-slate-400">/</span>
            <a class="text-slate-500 hover:text-primary transition-colors" href="/templates">Templates</a>
            <span class="text-slate-400">/</span>
            <span class="font-medium text-slate-900"><?= Security::escape($template['title']) ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Left: Image Gallery -->
            <div class="lg:col-span-7 xl:col-span-7">
                <div class="sticky top-24">
                    <div class="flex gap-3">
                        <!-- Thumbnail Strip -->
                        <?php if (!empty($galleryImages) || !empty($template['thumbnail_url'])): ?>
                            <div class="hidden lg:flex flex-col gap-2 w-16 shrink-0">
                                <!-- Primary Thumbnail -->
                                <button type="button"
                                    class="gallery-thumb aspect-[9/16] rounded-lg overflow-hidden border-2 border-primary ring-2 ring-primary/20 bg-slate-100 transition-all"
                                    data-full-src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>">
                                    <img src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                                        alt="<?= Security::escape($template['title']) ?>" class="w-full h-full object-cover">
                                </button>

                                <!-- Gallery Thumbnails -->
                                <?php foreach ($galleryImages as $img): ?>
                                    <button type="button"
                                        class="gallery-thumb aspect-[9/16] rounded-lg overflow-hidden border-2 border-transparent hover:border-primary/50 bg-slate-100 transition-all"
                                        data-full-src="<?= Security::escape($img['image_url']) ?>">
                                        <img src="<?= Security::escape($img['image_url']) ?>" alt="Gallery preview"
                                            class="w-full h-full object-cover">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Main Preview Image -->
                        <div class="flex-1">
                            <div
                                class="relative aspect-[9/16] sm:aspect-[9/14] lg:aspect-[3/4] w-full lg:max-h-[65vh] rounded-2xl overflow-hidden shadow-2xl bg-slate-200">
                                <img id="main-preview"
                                    src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                                    alt="<?= Security::escape($template['title']) ?>"
                                    class="w-full h-full object-cover transition-opacity duration-300" width="512"
                                    height="640" loading="eager">

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

                            <!-- Mobile Thumbnail Strip -->
                            <?php if (!empty($galleryImages)): ?>
                                <div class="flex lg:hidden gap-2 mt-3 overflow-x-auto pb-2">
                                    <button type="button"
                                        class="gallery-thumb shrink-0 w-14 aspect-[9/16] rounded-lg overflow-hidden border-2 border-primary ring-2 ring-primary/20 bg-slate-100"
                                        data-full-src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>">
                                        <img src="<?= Security::escape($template['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                                            alt="<?= Security::escape($template['title']) ?>"
                                            class="w-full h-full object-cover">
                                    </button>
                                    <?php foreach ($galleryImages as $img): ?>
                                        <button type="button"
                                            class="gallery-thumb shrink-0 w-14 aspect-[9/16] rounded-lg overflow-hidden border-2 border-transparent hover:border-primary/50 bg-slate-100"
                                            data-full-src="<?= Security::escape($img['image_url']) ?>">
                                            <img src="<?= Security::escape($img['image_url']) ?>" alt="Gallery preview"
                                                class="w-full h-full object-cover">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- CTA Button below image (hidden on mobile, shown on lg+) -->
                            <div class="hidden lg:block mt-4">
                                <?php
                                // Use slide editor for templates with template_definition, otherwise legacy steps
                                $customizeUrl = $hasSlideEditor
                                    ? '/template/' . Security::escape($templateSlug) . '?slide=0'
                                    : '/template/' . Security::escape($templateSlug) . '?step=' . ($availableSteps[0] ?? 1);
                                ?>
                                <a href="<?= $customizeUrl ?>"
                                    class="w-full flex items-center justify-center gap-2 px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all text-lg">
                                    <span>Customize Now</span>
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Template Info -->
            <div class="lg:col-span-5 xl:col-span-5 space-y-6">

                <!-- Title & Category -->
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span
                            class="inline-flex items-center gap-1 text-xs font-bold text-primary uppercase tracking-wider mb-2">
                            <?= ucfirst(str_replace('_', ' ', $template['category'] ?? 'General')) ?>
                        </span>
                        <h1 class="heading-hero text-slate-900 leading-tight">
                            <?= Security::escape($template['title']) ?>
                        </h1>
                    </div>

                    <!-- Wishlist Button -->
                    <button type="button"
                        class="wishlist-btn size-11 shrink-0 rounded-full border-2 flex items-center justify-center transition-all <?= $isInWishlist ? 'border-rose-200 bg-rose-50' : 'border-slate-200 hover:border-rose-200 hover:bg-rose-50' ?>"
                        data-template-id="<?= $templateId ?>" data-in-wishlist="<?= $isInWishlist ? 'true' : 'false' ?>"
                        onclick="toggleWishlist(this);"
                        title="<?= $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                        <span
                            class="material-symbols-outlined text-xl <?= $isInWishlist ? 'text-rose-500' : 'text-slate-400' ?> wishlist-icon"
                            style="<?= $isInWishlist ? 'font-variation-settings: \"FILL\" 1;' : '' ?>">favorite</span>
                    </button>
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
                    <span class="text-3xl font-black text-primary template-price" data-usd="<?= $template['price_usd'] ?>"
                        data-inr="<?= $template['price_inr'] ?? 0 ?>">
                        ₹<?= number_format($template['price_inr'] ?? 0, 0) ?>
                    </span>
                </div>

                <!-- Description -->
                <p class="text-slate-600 leading-relaxed">
                    <?= Security::escape($template['description'] ?? 'Beautiful video invitation template perfect for your special occasion.') ?>
                </p>

                <!-- Features -->
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-slate-700">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Full HD 1080p Video Download</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-700">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Optimized for WhatsApp & Social Media</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-700">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Delivered in 24-48 Hours</span>
                    </li>
                    <li class="flex items-center gap-3 text-slate-700">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span>Free Revisions Included</span>
                    </li>
                </ul>

                <!-- Language Selector -->
                <div class="pt-4 border-t border-slate-200">
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">
                        Select Language
                    </h3>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2" id="language-selector">
                        <?php
                        // Fetch available languages
                        $languages = [
                            ['code' => 'en', 'name' => 'English', 'native' => 'English'],
                            ['code' => 'hi', 'name' => 'Hindi', 'native' => 'हिंदी'],
                            ['code' => 'mr', 'name' => 'Marathi', 'native' => 'मराठी'],
                            ['code' => 'ta', 'name' => 'Tamil', 'native' => 'தமிழ்'],
                            ['code' => 'te', 'name' => 'Telugu', 'native' => 'తెలుగు'],
                            ['code' => 'gu', 'name' => 'Gujarati', 'native' => 'ગુજરાતી'],
                            ['code' => 'bn', 'name' => 'Bengali', 'native' => 'বাংলা'],
                            ['code' => 'pa', 'name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ'],
                        ];
                        $selectedLang = $_SESSION['selected_language'] ?? 'en';
                        foreach ($languages as $lang):
                            ?>
                            <button type="button"
                                class="lang-btn px-3 py-2 rounded-lg border-2 text-center transition-all <?= $selectedLang === $lang['code'] ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-slate-200 hover:border-primary/50' ?>"
                                data-lang="<?= $lang['code'] ?>" data-name="<?= $lang['name'] ?>"
                                data-native="<?= $lang['native'] ?>">
                                <span
                                    class="block text-sm font-bold <?= $selectedLang === $lang['code'] ? 'text-primary' : 'text-slate-700' ?>"><?= $lang['native'] ?></span>
                                <span class="block text-xs text-slate-500"><?= $lang['name'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Translation Mode Indicator -->
                    <div id="translation-info" class="mt-3 hidden">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="material-symbols-outlined text-amber-500 text-lg">translate</span>
                            <span id="translation-mode-text" class="text-slate-600"></span>
                            <span id="translation-price-badge"
                                class="hidden px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">+₹99</span>
                        </div>
                    </div>
                </div>
                <!-- Trust badges -->
                <div class="flex items-center gap-4 pt-4 border-t border-slate-200">
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
        </div>

        <!-- Related Templates Section -->
        <?php if (!empty($relatedTemplates)): ?>
            <div class="mt-16 pt-8 border-t border-slate-200">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">Related Templates</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($relatedTemplates as $related): ?>
                        <a href="/template/<?= Security::escape($related['slug']) ?>" class="group block">
                            <div
                                class="relative aspect-[9/16] rounded-xl overflow-hidden bg-slate-100 shadow-md group-hover:shadow-xl transition-shadow">
                                <img src="<?= Security::escape($related['thumbnail_url'] ?? '/assets/images/placeholder.jpg') ?>"
                                    alt="<?= Security::escape($related['title']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy">
                                <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
                                    <p class="text-white font-semibold text-sm truncate"><?= Security::escape($related['title']) ?>
                                    </p>
                                    <p class="text-white/80 text-xs template-price"
                                        data-usd="<?= !empty($related['discounted_price_usd']) ? $related['discounted_price_usd'] : $related['price_usd'] ?>"
                                        data-inr="<?= !empty($related['discounted_price_inr']) ? $related['discounted_price_inr'] : ($related['price_inr'] ?? 0) ?>">
                                        ₹<?= number_format(!empty($related['discounted_price_inr']) ? $related['discounted_price_inr'] : ($related['price_inr'] ?? 0), 0) ?>
                                    </p>
                                </div>
                                <div
                                    class="absolute top-2 right-2 bg-black/60 backdrop-blur-sm text-white text-xs font-medium px-2 py-1 rounded">
                                    <?= $related['duration_seconds'] ?>s
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Fixed Bottom Bar for Mobile (up to lg breakpoint) -->
        <div
            class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-white border-t border-slate-200 p-4 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]">
            <div class="flex items-center justify-between gap-4 max-w-7xl mx-auto">
                <div class="flex-1">
                    <p class="text-xs text-slate-500">Starting at</p>
                    <p class="text-lg font-black text-slate-900 template-price" data-usd="<?= $template['price_usd'] ?>"
                        data-inr="<?= $template['price_inr'] ?? 0 ?>">
                        ₹<?= number_format($template['price_inr'] ?? 0, 0) ?>
                    </p>
                </div>
                <?php
                // For AI templates use dress step, for slide editor use ?slide=0, otherwise legacy steps
                if ($hasDressStep) {
                    $customizeUrl = '/template/' . Security::escape($templateSlug) . '?step=0&dress=1';
                } elseif ($hasSlideEditor) {
                    $customizeUrl = '/template/' . Security::escape($templateSlug) . '?slide=0';
                } else {
                    $customizeUrl = '/template/' . Security::escape($templateSlug) . '?step=' . ($availableSteps[0] ?? 1);
                }
                ?>
                <a href="<?= $customizeUrl ?>"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/30 hover:bg-primary/90 transition-all">
                    <span>Customize</span>
                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Spacer for fixed bottom bar on mobile -->
        <div class="h-24 lg:hidden"></div>

    <?php elseif ($isSlideEditorMode): ?>
        <!-- ==================== SLIDE-BASED VISUAL EDITOR ==================== -->
        <link rel="stylesheet" href="/assets/css/slide-editor.css">

        <div class="slide-editor">

            <!-- Preview Canvas - Now Interactive! -->
            <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-2xl mb-4">
                <div id="slide-preview-canvas" class="slide-preview-canvas" data-slide-preview="true"
                    data-slide-config='<?= Security::escape(json_encode($currentSlideConfig)) ?>'
                    data-user-values='<?= Security::escape(json_encode($storedValues)) ?>'
                    data-editable-fields='<?= Security::escape(json_encode($editableFields)) ?>'>
                    <!-- JavaScript renders interactive preview here -->
                </div>
            </div>

            <!-- Slide Thumbnails Navigation Strip -->
            <div class="flex gap-2 overflow-x-auto pb-4 px-1 -mx-1 scrollbar-hide">
                <?php foreach ($slides as $index => $slide): ?>
                    <?php
                    $isActive = $index === $currentSlideIndex;
                    $isCompleted = isset($_SESSION['completed_slides'][$index]);
                    $bgColor = $slide['background']['color'] ?? '#6b7280';
                    $slideName = $slide['name'] ?? 'Slide ' . ($index + 1);
                    ?>
                    <button type="button" onclick="saveAndNavigate(<?= $index ?>)"
                        class="flex-shrink-0 relative group transition-all duration-200 <?= $isActive ? 'scale-105 z-10' : 'hover:scale-105' ?>">
                        <!-- Thumbnail -->
                        <div class="w-16 h-28 rounded-lg overflow-hidden shadow-md border-2 transition-all duration-200
                            <?= $isActive ? 'border-primary shadow-lg shadow-primary/25' : ($isCompleted ? 'border-green-500' : 'border-transparent hover:border-slate-300') ?>"
                            style="background: <?= Security::escape($bgColor) ?>;">
                            <!-- Slide number -->
                            <div
                                class="absolute top-1 left-1 w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center
                                <?= $isActive ? 'bg-primary text-white' : ($isCompleted ? 'bg-green-500 text-white' : 'bg-white/80 text-slate-700') ?>">
                                <?= $isCompleted && !$isActive ? '✓' : ($index + 1) ?>
                            </div>
                        </div>
                        <!-- Slide name tooltip on hover/active -->
                        <span class="absolute -bottom-5 left-1/2 -translate-x-1/2 text-[10px] font-medium text-slate-600 whitespace-nowrap
                            <?= $isActive ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' ?> transition-opacity">
                            <?= Security::escape(strlen($slideName) > 10 ? substr($slideName, 0, 10) . '...' : $slideName) ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Hidden Form for Data Submission -->
            <form id="slide-editor-form" method="POST" enctype="multipart/form-data" class="hidden">
                <?= Security::csrfField() ?>
                <input type="hidden" name="slide_index" value="<?= $currentSlideIndex ?>">
                <input type="hidden" name="user_timezone" id="user_timezone" value="">

                <?php foreach ($editableFields as $field): ?>
                    <?php if ($field['type'] === 'text'): ?>
                        <input type="hidden" id="field-<?= $field['fieldKey'] ?>" name="<?= $field['fieldKey'] ?>"
                            value="<?= Security::escape($storedValues[$field['fieldKey']] ?? $field['defaultValue']) ?>">
                    <?php elseif ($field['type'] === 'image'): ?>
                        <input type="file" id="field-<?= $field['fieldKey'] ?>" name="<?= $field['fieldKey'] ?>" accept="image/*"
                            class="hidden">
                    <?php endif; ?>
                <?php endforeach; ?>
            </form>

            <!-- Navigation Buttons (Desktop) -->
            <div class="slide-editor-nav hidden md:flex">
                <?php if ($currentSlideIndex > 0): ?>
                    <button type="submit" form="slide-editor-form" name="action" value="previous"
                        class="slide-editor-btn slide-editor-btn-secondary">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Previous
                    </button>
                <?php endif; ?>

                <?php if ($currentSlideIndex < $totalSlides - 1): ?>
                    <button type="submit" form="slide-editor-form" name="action" value="next"
                        class="slide-editor-btn slide-editor-btn-primary">
                        Next Slide
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                <?php else: ?>
                    <button type="submit" form="slide-editor-form" name="action" value="checkout"
                        class="slide-editor-btn slide-editor-btn-primary">
                        Continue to Checkout
                        <span class="material-symbols-outlined">shopping_cart</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Fixed Bottom Bar for Mobile -->
            <div class="slide-editor-mobile-nav">
                <?php if ($currentSlideIndex < $totalSlides - 1): ?>
                    <button type="submit" form="slide-editor-form" name="action" value="next"
                        class="w-full flex items-center justify-center gap-2 px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/25">
                        Next Slide
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                <?php else: ?>
                    <button type="submit" form="slide-editor-form" name="action" value="checkout"
                        class="w-full flex items-center justify-center gap-2 px-8 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/25">
                        Continue to Checkout
                        <span class="material-symbols-outlined">shopping_cart</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Spacer for fixed bottom bar on mobile -->
            <div class="h-24 md:hidden"></div>
        </div>

        <!-- Bottom Sheet for Mobile Editing -->
        <div id="edit-sheet-backdrop"
            class="fixed inset-0 bg-black/50 z-[100] opacity-0 invisible transition-all duration-300"></div>
        <div id="edit-sheet"
            class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl p-5 pb-8 z-[101] translate-y-full transition-transform duration-500 shadow-[0_-10px_40px_rgba(0,0,0,0.15)] max-h-[80vh] overflow-y-auto"
            style="transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-5"></div>
            <label id="edit-sheet-label"
                class="block text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Field Name</label>
            <input type="text" id="edit-sheet-input"
                class="w-full p-4 border-2 border-slate-200 rounded-xl text-lg bg-slate-50 transition-all focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 focus:bg-white"
                placeholder="Enter value...">
            <input type="date" id="edit-sheet-date"
                class="hidden w-full p-4 border-2 border-slate-200 rounded-xl text-lg bg-slate-50">
            <input type="time" id="edit-sheet-time"
                class="hidden w-full p-4 border-2 border-slate-200 rounded-xl text-lg bg-slate-50">
            <button type="button" id="edit-sheet-done"
                class="w-full mt-4 p-4 bg-primary text-white border-none rounded-xl text-base font-bold cursor-pointer transition-all hover:bg-primary/90 active:scale-[0.98]">Done
                ✓</button>
        </div>

        <!-- Inline Input Container for Desktop -->
        <div id="inline-input-container"
            class="hidden fixed z-50 bg-white rounded-xl shadow-2xl p-4 min-w-[280px] max-w-[400px] animate-pop-in">
            <label id="inline-input-label"
                class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Field Name</label>
            <input type="text" id="inline-input"
                class="w-full p-3 border-2 border-slate-200 rounded-lg text-base transition-all focus:outline-none focus:border-primary"
                placeholder="Enter value...">
            <div class="flex gap-2 mt-3">
                <button type="button" id="inline-input-done"
                    class="flex-1 p-2.5 bg-primary text-white border-none rounded-lg font-semibold cursor-pointer">Done</button>
                <button type="button" id="inline-input-cancel"
                    class="px-4 py-2.5 bg-slate-100 text-slate-500 border-none rounded-lg font-semibold cursor-pointer">Cancel</button>
            </div>
        </div>

        <!-- Initialize Slide Preview with Inline Editing -->
        <script src="/assets/js/slide-preview.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Capture timezone
                const tzField = document.getElementById('user_timezone');
                if (tzField) {
                    tzField.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
                }

                // Initialize slide preview
                const canvas = document.getElementById('slide-preview-canvas');
                if (canvas) {
                    const slideConfig = JSON.parse(canvas.dataset.slideConfig || '{}');
                    const userValues = JSON.parse(canvas.dataset.userValues || '{}');
                    const editableFields = JSON.parse(canvas.dataset.editableFields || '[]');

                    window.slidePreview = new SlidePreview('slide-preview-canvas', slideConfig, userValues, {
                        editable: true,
                        editableFields: editableFields
                    });

                    // Setup inline editing
                    setupInlineEditing(editableFields);
                }
            });

            function setupInlineEditing(editableFields) {
                const isMobile = window.innerWidth < 768;
                const canvas = document.getElementById('slide-preview-canvas');
                const editSheet = document.getElementById('edit-sheet');
                const editSheetBackdrop = document.getElementById('edit-sheet-backdrop');
                const editSheetLabel = document.getElementById('edit-sheet-label');
                const editSheetInput = document.getElementById('edit-sheet-input');
                const editSheetDone = document.getElementById('edit-sheet-done');
                const inlineContainer = document.getElementById('inline-input-container');
                const inlineLabel = document.getElementById('inline-input-label');
                const inlineInput = document.getElementById('inline-input');
                const inlineDone = document.getElementById('inline-input-done');
                const inlineCancel = document.getElementById('inline-input-cancel');

                let currentFieldKey = null;
                let currentFieldType = null;

                // Create field lookup map
                const fieldMap = {};
                editableFields.forEach(f => { fieldMap[f.fieldKey] = f; });

                // Make editable layers clickable - delegate from canvas
                canvas.addEventListener('click', function (e) {
                    const layer = e.target.closest('[data-field-key]');
                    if (!layer) return;

                    const fieldKey = layer.dataset.fieldKey;
                    const field = fieldMap[fieldKey];
                    if (!field) return;

                    currentFieldKey = fieldKey;
                    currentFieldType = field.type;

                    if (field.type === 'image') {
                        // Trigger file input
                        const fileInput = document.getElementById('field-' + fieldKey);
                        if (fileInput) {
                            fileInput.click();
                        }
                    } else if (field.type === 'text') {
                        // Get current value
                        const hiddenInput = document.getElementById('field-' + fieldKey);
                        const currentValue = hiddenInput ? hiddenInput.value : '';

                        if (isMobile) {
                            // Mobile: Use contenteditable directly on the element
                            // This is less disruptive than a bottom sheet with keyboard
                            if (layer.contentEditable === 'true') {
                                // Already in edit mode, let user continue editing
                                return;
                            }

                            // Make the element editable
                            layer.contentEditable = 'true';
                            layer.classList.add('editing');

                            // Store original value for cancel
                            layer.dataset.originalValue = layer.textContent;

                            // Select all text for easy replacement
                            const range = document.createRange();
                            range.selectNodeContents(layer);
                            const sel = window.getSelection();
                            sel.removeAllRanges();
                            sel.addRange(range);

                            // Handle blur to save
                            const handleBlur = () => {
                                const newValue = layer.textContent.trim();
                                layer.contentEditable = 'false';
                                layer.classList.remove('editing');

                                if (newValue && newValue !== layer.dataset.originalValue) {
                                    updateFieldValue(fieldKey, newValue);
                                } else if (!newValue) {
                                    // Restore original if empty
                                    layer.textContent = layer.dataset.originalValue;
                                }

                                layer.removeEventListener('blur', handleBlur);
                                layer.removeEventListener('keydown', handleKeydown);
                            };

                            // Handle Enter to save, Escape to cancel
                            const handleKeydown = (e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    layer.blur();
                                } else if (e.key === 'Escape') {
                                    layer.textContent = layer.dataset.originalValue;
                                    layer.blur();
                                }
                            };

                            layer.addEventListener('blur', handleBlur);
                            layer.addEventListener('keydown', handleKeydown);

                            // Focus the element (this will show keyboard)
                            layer.focus();
                        } else {
                            // Desktop: Show inline input popup near the element
                            const rect = layer.getBoundingClientRect();
                            const canvasRect = canvas.getBoundingClientRect();

                            inlineLabel.textContent = field.label;
                            inlineInput.value = currentValue;
                            inlineInput.placeholder = field.defaultValue || 'Enter ' + field.label.toLowerCase();

                            // Show inline input popup
                            inlineContainer.classList.remove('hidden');
                            inlineContainer.style.left = (rect.left + rect.width / 2 - 160) + 'px';
                            inlineContainer.style.top = (rect.bottom + 10) + 'px';

                            // Ensure it's visible in viewport
                            const containerRect = inlineContainer.getBoundingClientRect();
                            if (containerRect.right > window.innerWidth) {
                                inlineContainer.style.left = (window.innerWidth - containerRect.width - 20) + 'px';
                            }
                            if (containerRect.left < 0) {
                                inlineContainer.style.left = '20px';
                            }

                            setTimeout(() => inlineInput.focus(), 100);
                        }
                    }
                });

                // Handle file input changes (for images)
                editableFields.forEach(field => {
                    if (field.type === 'image') {
                        const fileInput = document.getElementById('field-' + field.fieldKey);
                        if (fileInput) {
                            fileInput.addEventListener('change', function (e) {
                                const file = e.target.files[0];
                                if (file && window.slidePreview) {
                                    window.slidePreview.updateImageValue(field.fieldKey, file);
                                }
                            });
                        }
                    }
                });

                // Mobile: Handle bottom sheet done
                editSheetDone.addEventListener('click', function () {
                    if (currentFieldKey) {
                        const value = editSheetInput.value;
                        updateFieldValue(currentFieldKey, value);
                    }
                    closeEditSheet();
                });

                // Mobile: Close on backdrop click
                editSheetBackdrop.addEventListener('click', closeEditSheet);

                // Desktop: Handle inline input done
                inlineDone.addEventListener('click', function () {
                    if (currentFieldKey) {
                        const value = inlineInput.value;
                        updateFieldValue(currentFieldKey, value);
                    }
                    closeInlineInput();
                });

                // Desktop: Handle inline input cancel
                inlineCancel.addEventListener('click', closeInlineInput);

                // Handle Enter key
                editSheetInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        editSheetDone.click();
                    }
                });
                inlineInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        inlineDone.click();
                    } else if (e.key === 'Escape') {
                        closeInlineInput();
                    }
                });

                // Close inline input when clicking outside
                document.addEventListener('click', function (e) {
                    if (!inlineContainer.classList.contains('hidden') &&
                        !inlineContainer.contains(e.target) &&
                        !canvas.contains(e.target)) {
                        closeInlineInput();
                    }
                });

                function updateFieldValue(fieldKey, value) {
                    // Update hidden form field
                    const hiddenInput = document.getElementById('field-' + fieldKey);
                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }
                    // Update preview
                    if (window.slidePreview) {
                        window.slidePreview.updateValue(fieldKey, value);
                    }
                }

                function closeEditSheet() {
                    // Hide backdrop
                    editSheetBackdrop.classList.add('opacity-0', 'invisible');
                    editSheetBackdrop.classList.remove('opacity-100', 'visible');
                    // Slide down sheet
                    editSheet.classList.add('translate-y-full');
                    editSheet.classList.remove('translate-y-0');
                    currentFieldKey = null;
                }

                function closeInlineInput() {
                    inlineContainer.classList.add('hidden');
                    currentFieldKey = null;
                }
            }

            // Page context for AJAX navigation
            const slideEditorConfig = {
                templateSlug: '<?= Security::escape($templateSlug) ?>',
                currentSlideIndex: <?= $currentSlideIndex ?>
            };

            /**
             * Save current slide data and navigate to target slide
             * Used by slide thumbnail navigation to preserve edits
             */
            async function saveAndNavigate(targetSlideIndex) {
                // If clicking the current slide, do nothing
                if (targetSlideIndex === slideEditorConfig.currentSlideIndex) {
                    return;
                }

                // Collect current form data
                const form = document.getElementById('slide-editor-form');
                if (!form) {
                    // No form, just navigate directly
                    window.location.href = '/template/' + slideEditorConfig.templateSlug + '?slide=' + targetSlideIndex;
                    return;
                }

                // Create form data with 'save' action
                const formData = new FormData(form);
                formData.set('action', 'save');

                // Show saving indicator (optional visual feedback)
                const buttons = document.querySelectorAll('.slide-editor button');
                buttons.forEach(btn => btn.disabled = true);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Data saved, now navigate
                        window.location.href = '/template/' + slideEditorConfig.templateSlug + '?slide=' + targetSlideIndex;
                    } else {
                        console.error('Save failed:', result);
                        // Still navigate even if save failed
                        window.location.href = '/template/' + slideEditorConfig.templateSlug + '?slide=' + targetSlideIndex;
                    }
                } catch (error) {
                    console.error('Error saving slide data:', error);
                    // Navigate anyway on error
                    window.location.href = '/template/' + slideEditorConfig.templateSlug + '?slide=' + targetSlideIndex;
                }
            }
        </script>

    <?php else: ?>
        <!-- ==================== CUSTOMIZATION STEPS ==================== -->

        <div class="max-w-3xl mx-auto">

            <?php if ($isDressStep): ?>
                <!-- ==================== DRESS SELECTION STEP ==================== -->
                <form id="customize-form" method="POST" class="space-y-6">
                    <?= Security::csrfField() ?>

                    <!-- Include dress selection component -->
                    <?php include __DIR__ . '/../components/dress-selection.php'; ?>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between gap-4 pt-6 border-t border-slate-200">
                        <a href="/template/<?= Security::escape($templateSlug) ?>"
                            class="flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-200 font-bold hover:bg-slate-50:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined">arrow_back</span>
                            Back
                        </a>
                        <button type="submit"
                            class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-colors">
                            <span>Next Step</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </div>
                </form>

                <!-- Fixed Bottom Bar for Mobile (dress step) -->
                <div
                    class="fixed bottom-0 left-0 right-0 z-40 md:hidden bg-white border-t border-slate-200 p-4 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]">
                    <button type="submit" form="customize-form"
                        class="w-full flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-colors">
                        <span>Next Step</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </div>
                <div class="h-20 md:hidden"></div>

            <?php else: ?>
                <!-- Form Section -->

                <!-- Progress Indicator (only show if more than 1 step) -->
                <?php if ($totalSteps > 1): ?>
                    <div class="mb-6">
                        <!-- Step Circles with Connecting Lines -->
                        <div class="flex items-center justify-center py-4">
                            <?php foreach ($availableSteps as $idx => $s): ?>
                                <div class="flex items-center <?= $idx < count($availableSteps) - 1 ? 'flex-1' : '' ?>">
                                    <!-- Step Circle -->
                                    <div
                                        class="size-10 rounded-full flex items-center justify-center text-sm font-bold transition-all shrink-0
                                    <?= $s < $step ? 'bg-green-500 text-white' : ($s === $step ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500') ?>">
                                        <?php if ($s < $step): ?>
                                            <span class="material-symbols-outlined text-lg">check</span>
                                        <?php else: ?>
                                            <?= $idx + 1 ?>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Connecting Line -->
                                    <?php if ($idx < count($availableSteps) - 1): ?>
                                        <div class="flex-1 h-0.5 mx-2 <?= $s < $step ? 'bg-green-500' : 'bg-slate-200' ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Current Step Title -->
                        <div class="text-center">
                            <h2 class="font-bold text-slate-900 text-lg">
                                <?= $stepTitles[$step] ?? 'Details' ?>
                            </h2>
                        </div>
                    </div>
                <?php endif; ?>

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
                    <div class="flex items-center justify-between gap-4 pt-6 border-t border-slate-200">
                        <?php if ($currentStepIndex > 0): ?>
                            <a href="/template/<?= Security::escape($templateSlug) ?>?step=<?= $availableSteps[$currentStepIndex - 1] ?>"
                                class="hidden md:flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-200 font-bold hover:bg-slate-50:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Back
                            </a>
                        <?php else: ?>
                            <a href="/template/<?= Security::escape($templateSlug) ?>"
                                class="hidden md:flex items-center gap-2 px-6 py-3 rounded-lg border border-slate-200 font-bold hover:bg-slate-50:bg-slate-800 transition-colors">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Back
                            </a>
                        <?php endif; ?>

                        <button type="submit"
                            class="hidden md:flex flex-1 sm:flex-initial items-center justify-center gap-2 px-8 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-colors">
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

            <!-- Fixed Bottom Bar for Mobile (customize steps) -->
            <div
                class="fixed bottom-0 left-0 right-0 z-40 md:hidden bg-white border-t border-slate-200 p-4 shadow-[0_-4px_20px_rgba(0,0,0,0.1)]">
                <button type="submit" form="customize-form"
                    class="w-full flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 shadow-lg shadow-primary/25 transition-colors">
                    <?php if ($currentStepIndex < $totalSteps - 1): ?>
                        <span>Next Step</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    <?php else: ?>
                        <span>Continue to Checkout</span>
                        <span class="material-symbols-outlined">shopping_cart</span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Spacer for fixed bottom bar on mobile -->
            <div class="h-20 md:hidden"></div>

        <?php endif; ?> <!-- end if isDressStep else -->
    <?php endif; ?> <!-- end if step === 0 else -->

</div>

<script>
    // Capture user timezone for country detection
    document.addEventListener('DOMContentLoaded', function () {
        const tzField = document.getElementById('user_timezone');
        if (tzField) {
            tzField.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
        }

        // Currency detection (timezone-based) - INR is default, USD only for non-Indian users
        const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const isIndianUser = userTimezone.includes('Kolkata') || userTimezone.includes('Calcutta') || userTimezone.includes('Asia/');
        const userCurrency = isIndianUser ? 'INR' : 'USD';

        // Update prices based on detected currency (only change if USD)
        document.querySelectorAll('.template-price').forEach(el => {
            const usd = parseFloat(el.dataset.usd) || 0;
            const inr = parseFloat(el.dataset.inr) || 0;
            if (usd === 0) return; // Skip free items

            if (userCurrency === 'USD' && usd > 0) {
                el.textContent = '$' + Math.round(usd);
            }
            // INR is already shown by default in the HTML
        });
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
        modal.addEventListener('click', function (e) {
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
    document.addEventListener('DOMContentLoaded', function () {
        var playBtn = document.getElementById('play-video-btn');
        if (playBtn) {
            playBtn.addEventListener('click', function () {
                var videoUrl = this.getAttribute('data-video-url');
                if (videoUrl) {
                    openVideoModal(videoUrl);
                }
            });
        }

        // Gallery thumbnail click handlers
        var thumbs = document.querySelectorAll('.gallery-thumb');
        var mainPreview = document.getElementById('main-preview');

        if (thumbs.length && mainPreview) {
            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    var fullSrc = this.getAttribute('data-full-src');
                    if (fullSrc) {
                        // Fade transition
                        mainPreview.style.opacity = '0.5';
                        setTimeout(function () {
                            mainPreview.src = fullSrc;
                            mainPreview.style.opacity = '1';
                        }, 150);

                        // Update active state for all thumbs
                        thumbs.forEach(function (t) {
                            t.classList.remove('border-primary', 'ring-2', 'ring-primary/20');
                            t.classList.add('border-transparent');
                        });
                        this.classList.remove('border-transparent');
                        this.classList.add('border-primary', 'ring-2', 'ring-primary/20');
                    }
                });
            });
        }

        // Language selector handlers
        initLanguageSelector();
    });

    // Language Selection Logic
    function initLanguageSelector() {
        const langBtns = document.querySelectorAll('.lang-btn');
        const translationInfo = document.getElementById('translation-info');
        const translationModeText = document.getElementById('translation-mode-text');
        const translationPriceBadge = document.getElementById('translation-price-badge');

        let selectedLang = '<?= $_SESSION['selected_language'] ?? 'en' ?>';
        let translationMode = '<?= $_SESSION['translation_mode'] ?? 'self' ?>';

        langBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const langCode = this.dataset.lang;
                const langName = this.dataset.name;
                const langNative = this.dataset.native;

                // If selecting same language, do nothing
                if (langCode === selectedLang) return;

                // If English selected, just update - no modal needed
                if (langCode === 'en') {
                    updateLanguageSelection(langCode, 'self', langNative, langName);
                    saveLanguageToSession(langCode, 'self');
                    return;
                }

                // Show translation choice modal for non-English
                showTranslationModal(langCode, langName, langNative);
            });
        });

        function updateLanguageSelection(langCode, mode, nativeText, langName) {
            selectedLang = langCode;
            translationMode = mode;

            // Update button states
            langBtns.forEach(btn => {
                const isSelected = btn.dataset.lang === langCode;
                btn.classList.toggle('border-primary', isSelected);
                btn.classList.toggle('bg-primary/5', isSelected);
                btn.classList.toggle('ring-2', isSelected);
                btn.classList.toggle('ring-primary/20', isSelected);
                btn.classList.toggle('border-slate-200', !isSelected);
                btn.classList.toggle(!isSelected);
                btn.querySelector('span:first-child').classList.toggle('text-primary', isSelected);
                btn.querySelector('span:first-child').classList.toggle('text-slate-700', !isSelected);
            });

            // Update translation info display
            if (langCode === 'en') {
                translationInfo.classList.add('hidden');
            } else {
                translationInfo.classList.remove('hidden');
                if (mode === 'self') {
                    translationModeText.textContent = `You will type in ${langName}`;
                    translationPriceBadge.classList.add('hidden');
                } else {
                    translationModeText.textContent = `We will translate to ${langName}`;
                    translationPriceBadge.classList.remove('hidden');
                    updatePriceDisplay(true);
                }
            }

            // Update price if needed
            if (mode === 'self' || langCode === 'en') {
                updatePriceDisplay(false);
            }

            // Update thumbnail gallery (if language-specific thumbnails exist)
            updateThumbnailGallery(langCode);
        }

        function updatePriceDisplay(addTranslationFee) {
            const priceElements = document.querySelectorAll('.template-price');
            const translationFeeINR = 99;
            const translationFeeUSD = 1.99;

            priceElements.forEach(el => {
                let usd = parseFloat(el.dataset.usd) || 0;
                let inr = parseFloat(el.dataset.inr) || 0;

                if (addTranslationFee) {
                    usd += translationFeeUSD;
                    inr += translationFeeINR;
                }

                const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const isIndianUser = userTimezone.includes('Kolkata') || userTimezone.includes('Calcutta');

                if (isIndianUser && inr > 0) {
                    el.textContent = '₹' + Math.round(inr).toLocaleString('en-IN');
                } else {
                    el.textContent = '$' + Math.round(usd);
                }
            });
        }

        function updateThumbnailGallery(langCode) {
            // AJAX call to fetch thumbnails for selected language
            fetch(`/api/template/<?= $templateId ?>/thumbnails?lang=${langCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.thumbnails && data.thumbnails.length > 0) {
                        // Update gallery with new thumbnails
                        const mainPreview = document.getElementById('main-preview');
                        if (mainPreview && data.thumbnails[0]) {
                            mainPreview.src = data.thumbnails[0].thumbnail_url;
                        }
                    }
                })
                .catch(err => {
                    // Silently fail - keep existing thumbnails
                    console.log('No language-specific thumbnails found');
                });
        }

        function saveLanguageToSession(langCode, mode) {
            fetch('/api/set-language', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    language: langCode,
                    translation_mode: mode
                })
            });
        }

        window.showTranslationModal = function (langCode, langName, langNative) {
            const modal = document.createElement('div');
            modal.id = 'translation-modal';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-xl font-bold text-slate-900 mb-2">
                        You selected: ${langNative} (${langName})
                    </h3>
                    <p class="text-sm text-slate-500 mb-6">How would you like to enter your text?</p>
                    
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-4 rounded-xl border-2 border-slate-200 cursor-pointer hover:border-primary/50 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="translation_mode" value="self" class="mt-1 text-primary" checked>
                            <div class="flex-1">
                                <span class="block font-bold text-slate-900">I will type in ${langName}</span>
                                <span class="block text-sm text-slate-500">Enter your text directly in ${langName}</span>
                            </div>
                            <span class="text-green-600 font-bold">FREE</span>
                        </label>
                        
                        <label class="flex items-start gap-3 p-4 rounded-xl border-2 border-slate-200 cursor-pointer hover:border-primary/50 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="translation_mode" value="translate" class="mt-1 text-primary">
                            <div class="flex-1">
                                <span class="block font-bold text-slate-900">Translate my English to ${langName}</span>
                                <span class="block text-sm text-slate-500">Type in English, we'll translate for you</span>
                            </div>
                            <span class="text-amber-600 font-bold">+₹99</span>
                        </label>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeTranslationModal()" class="flex-1 px-4 py-3 rounded-xl border border-slate-200 font-bold hover:bg-slate-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button" onclick="confirmTranslationChoice('${langCode}', '${langName}', '${langNative}')" class="flex-1 px-4 py-3 rounded-xl bg-primary text-white font-bold hover:bg-primary/90 transition-colors">
                            Continue
                        </button>
                    </div>
                </div>
            `;

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeTranslationModal();
            });

            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
        };

        window.closeTranslationModal = function () {
            const modal = document.getElementById('translation-modal');
            if (modal) {
                modal.remove();
                document.body.style.overflow = '';
            }
        };

        window.confirmTranslationChoice = function (langCode, langName, langNative) {
            const modal = document.getElementById('translation-modal');
            const selectedMode = modal.querySelector('input[name="translation_mode"]:checked').value;

            updateLanguageSelection(langCode, selectedMode, langNative, langName);
            saveLanguageToSession(langCode, selectedMode);
            closeTranslationModal();
        };
    }

    // User login status for wishlist
    window.isUserLoggedIn = <?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>

<!-- Wishlist JavaScript -->
<script src="/assets/js/wishlist.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>