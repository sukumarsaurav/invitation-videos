<?php
/**
 * Template Preview Page
 * Shows animated preview of template with sample data
 * Users can watch the preview then click "Customize" to edit
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

// Get template ID from URL
$templateId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$templateId) {
    header('Location: /templates');
    exit;
}

// Get template data
$template = Database::fetchOne(
    "SELECT * FROM templates WHERE id = ? AND is_active = 1",
    [$templateId]
);

if (!$template) {
    header('Location: /templates');
    exit;
}

// Get slides for this template
$slides = Database::fetchAll(
    "SELECT * FROM template_slides WHERE template_id = ? ORDER BY slide_order",
    [$templateId]
);

// Get fields for this template (with sample values)
$fields = Database::fetchAll(
    "SELECT * FROM template_fields WHERE template_id = ? ORDER BY slide_id, id",
    [$templateId]
);

// Calculate total duration
$totalDuration = array_reduce($slides, function ($sum, $slide) {
    return $sum + ($slide['duration_ms'] ?? 3000);
}, 0);
$totalSeconds = round($totalDuration / 1000);

$pageTitle = 'Preview: ' . Security::escape($template['title']);
?>

<?php ob_start(); ?>

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <!-- Header -->
    <header class="absolute top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/templates" class="flex items-center gap-2 text-white/80 hover:text-white transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                <span class="hidden sm:inline">Back to Templates</span>
            </a>
            <a href="/editor/<?= $templateId ?>"
                class="flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-lg font-medium transition-colors shadow-lg shadow-primary/25">
                <span class="material-symbols-outlined">edit</span>
                Customize
            </a>
        </div>
    </header>

    <!-- Main Preview Area -->
    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Left: Canvas Preview -->
        <div class="flex-1 flex items-center justify-center p-4 pt-20 lg:pt-4">
            <div class="relative">
                <!-- Phone Frame -->
                <div class="relative bg-black rounded-[3rem] p-3 shadow-2xl">
                    <div class="absolute top-6 left-1/2 -translate-x-1/2 w-24 h-6 bg-black rounded-full z-10"></div>
                    <div id="canvas-wrapper" class="relative bg-white rounded-[2.5rem] overflow-hidden"
                        style="width: 280px; height: 560px;">
                        <canvas id="preview-canvas" width="1080" height="1920" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- Play/Pause Overlay -->
                <button id="btn-toggle-play"
                    class="absolute inset-0 flex items-center justify-center bg-black/0 hover:bg-black/20 transition-colors rounded-[3rem] group">
                    <div id="play-overlay"
                        class="hidden size-20 rounded-full bg-white/30 backdrop-blur-md flex items-center justify-center text-white border border-white/50 shadow-lg group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-4xl">play_arrow</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Right: Template Info -->
        <div class="lg:w-96 xl:w-[450px] bg-white/5 backdrop-blur-sm lg:min-h-screen p-6 lg:p-8 flex flex-col">
            <div class="flex-1 space-y-6">
                <!-- Category Badge -->
                <span
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-primary uppercase tracking-wider bg-primary/10 px-3 py-1.5 rounded-full">
                    <span class="material-symbols-outlined text-sm">favorite</span>
                    <?= ucfirst(str_replace('_', ' ', $template['category'] ?? 'General')) ?>
                </span>

                <!-- Title -->
                <h1 class="text-3xl lg:text-4xl font-black text-white leading-tight">
                    <?= Security::escape($template['title']) ?>
                </h1>

                <!-- Rating -->
                <div class="flex items-center gap-3">
                    <div class="flex gap-0.5 text-yellow-400">
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star</span>
                        <span class="material-symbols-outlined text-lg">star_half</span>
                    </div>
                    <span class="text-sm text-white/60"><?= number_format(rand(40, 50) / 10, 1) ?> (<?= rand(50, 200) ?>
                        reviews)</span>
                </div>

                <!-- Price -->
                <div class="flex items-baseline gap-3">
                    <span class="text-4xl font-black text-white">
                        $<?= number_format($template['price_usd'], 0) ?>
                    </span>
                    <?php if ($template['price_inr']): ?>
                        <span class="text-lg text-white/50">/ ₹<?= number_format($template['price_inr'], 0) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <p class="text-white/70 leading-relaxed">
                    <?= Security::escape($template['description'] ?? 'Beautiful animated video invitation template. Customize with your details and share with your loved ones.') ?>
                </p>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 py-4 border-y border-white/10">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white"><?= count($slides) ?></div>
                        <div class="text-xs text-white/50 uppercase">Slides</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white"><?= $totalSeconds ?>s</div>
                        <div class="text-xs text-white/50 uppercase">Duration</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white"><?= count($fields) ?></div>
                        <div class="text-xs text-white/50 uppercase">Fields</div>
                    </div>
                </div>

                <!-- Features -->
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-white/80">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span>Full HD 1080p Video</span>
                    </li>
                    <li class="flex items-center gap-3 text-white/80">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span>Instant Download</span>
                    </li>
                    <li class="flex items-center gap-3 text-white/80">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span>WhatsApp Ready</span>
                    </li>
                    <li class="flex items-center gap-3 text-white/80">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span>Unlimited Revisions</span>
                    </li>
                </ul>
            </div>

            <!-- CTA Button -->
            <div class="pt-6">
                <a href="/editor/<?= $templateId ?>"
                    class="flex items-center justify-center gap-2 w-full px-8 py-4 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl shadow-lg shadow-primary/30 transition-all text-lg">
                    <span>Customize Now</span>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
                <p class="text-center text-white/40 text-sm mt-3">
                    Edit your details • Preview instantly • Download video
                </p>
            </div>
        </div>
    </div>

    <!-- Progress Bar (bottom) -->
    <div
        class="fixed bottom-0 left-0 right-0 lg:right-auto lg:left-0 lg:w-[calc(100%-24rem)] xl:w-[calc(100%-28rem)] bg-black/50 backdrop-blur-sm p-4 z-40">
        <div class="max-w-md mx-auto flex items-center gap-3">
            <button type="button" id="btn-play"
                class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined" id="play-icon">pause</span>
            </button>
            <span id="time-current" class="text-sm text-white/60 w-10 text-center font-mono">0:00</span>
            <div id="progress-bar" class="flex-1 h-1.5 bg-white/20 rounded-full cursor-pointer relative group">
                <div id="progress-fill" class="h-full bg-primary rounded-full transition-all" style="width: 0%;"></div>
            </div>
            <span id="time-total"
                class="text-sm text-white/60 w-10 text-center font-mono"><?= floor($totalSeconds / 60) ?>:<?= str_pad($totalSeconds % 60, 2, '0', STR_PAD_LEFT) ?></span>
        </div>
    </div>
</div>

<!-- Pass data to JavaScript -->
<script>
    window.PREVIEW_DATA = {
        templateId: <?= $templateId ?>,
        template: <?= json_encode($template) ?>,
        slides: <?= json_encode($slides) ?>,
        fields: <?= json_encode($fields) ?>
    };
</script>
<script src="/assets/js/preview.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>