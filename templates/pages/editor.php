<?php
/**
 * Simple Template Editor Page
 * Form fields on left, live canvas preview on right
 * Preview and Download buttons
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

// Get fields for this template
$fields = Database::fetchAll(
    "SELECT * FROM template_fields WHERE template_id = ? ORDER BY slide_id, id",
    [$templateId]
);

$pageTitle = 'Edit: ' . Security::escape($template['title']);
?>

<?php ob_start(); ?>

<div class="min-h-screen bg-slate-900">
    <!-- Header -->
    <header class="bg-slate-800 border-b border-slate-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="/templates" class="text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <h1 class="font-bold text-lg text-white">
                    <?= Security::escape($template['title']) ?>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" id="btn-preview"
                    class="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                    <span class="material-symbols-outlined">play_arrow</span>
                    <span class="hidden sm:inline">Preview</span>
                </button>
                <button type="button" id="btn-download"
                    class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg font-medium transition-colors shadow-lg shadow-primary/25">
                    <span class="material-symbols-outlined">download</span>
                    <span class="hidden sm:inline">Download</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex flex-col lg:flex-row h-[calc(100vh-60px)]">

        <!-- Left: Form Panel -->
        <div class="lg:w-96 xl:w-[400px] bg-slate-800 border-r border-slate-700 overflow-y-auto">
            <div class="p-5">
                <h2 class="font-bold text-lg mb-4 text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">edit</span>
                    Edit Your Details
                </h2>

                <form id="editor-form" class="space-y-4">
                    <?php foreach ($fields as $field): ?>
                        <div class="field-group">
                            <label for="field-<?= $field['id'] ?>" class="block text-sm font-medium text-slate-300 mb-1.5">
                                <?= Security::escape($field['field_label']) ?>
                            </label>

                            <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                    data-field-id="<?= $field['id'] ?>"
                                    class="field-input w-full px-4 py-2.5 border border-slate-600 rounded-lg bg-slate-700 text-white placeholder-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors"
                                    placeholder="Enter <?= Security::escape($field['field_label']) ?>"
                                    value="<?= Security::escape($field['sample_value'] ?? '') ?>">

                            <?php elseif ($field['field_type'] === 'date'): ?>
                                <input type="date" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                    data-field-id="<?= $field['id'] ?>"
                                    class="field-input w-full px-4 py-2.5 border border-slate-600 rounded-lg bg-slate-700 text-white focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors"
                                    value="<?= Security::escape($field['sample_value'] ?? '') ?>">

                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                    data-field-id="<?= $field['id'] ?>" rows="3"
                                    class="field-input w-full px-4 py-2.5 border border-slate-600 rounded-lg bg-slate-700 text-white placeholder-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors"
                                    placeholder="Enter <?= Security::escape($field['field_label']) ?>"><?= Security::escape($field['sample_value'] ?? '') ?></textarea>

                            <?php else: ?>
                                <input type="text" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                    data-field-id="<?= $field['id'] ?>"
                                    class="field-input w-full px-4 py-2.5 border border-slate-600 rounded-lg bg-slate-700 text-white placeholder-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors"
                                    placeholder="Enter <?= Security::escape($field['field_label']) ?>"
                                    value="<?= Security::escape($field['sample_value'] ?? '') ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($fields)): ?>
                        <p class="text-slate-400 text-center py-8">
                            No customizable fields available.
                        </p>
                    <?php endif; ?>
                </form>

                <!-- Mobile Preview/Download Buttons -->
                <div class="lg:hidden mt-6 flex gap-3">
                    <button type="button" id="btn-preview-mobile"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        <span class="material-symbols-outlined">play_arrow</span>
                        Preview
                    </button>
                    <button type="button" id="btn-download-mobile"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-primary hover:bg-primary/90 text-white rounded-lg font-medium transition-colors">
                        <span class="material-symbols-outlined">download</span>
                        Download
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: Canvas Preview -->
        <div class="flex-1 flex flex-col bg-slate-900">
            <!-- Canvas Area -->
            <div class="flex-1 flex items-center justify-center p-4 lg:p-8">
                <div id="canvas-wrapper" class="relative bg-white rounded-xl shadow-2xl overflow-hidden"
                    style="width: 270px; height: 480px;">
                    <canvas id="preview-canvas" width="1080" height="1920" class="w-full h-full"></canvas>

                    <!-- Text overlay container -->
                    <div id="canvas-text-overlay" class="absolute inset-0 pointer-events-none"></div>
                </div>
            </div>

            <!-- Video Player Controls -->
            <div class="bg-slate-800 border-t border-slate-700 p-4">
                <div class="max-w-xl mx-auto flex items-center gap-3">
                    <button type="button" id="btn-play"
                        class="w-10 h-10 rounded-full bg-primary hover:bg-primary/90 text-white flex items-center justify-center transition-colors">
                        <span class="material-symbols-outlined" id="play-icon">play_arrow</span>
                    </button>
                    <span id="time-current" class="text-sm text-slate-400 w-12 text-center font-mono">0:00</span>
                    <div id="progress-bar" class="flex-1 h-2 bg-slate-700 rounded-full cursor-pointer relative group">
                        <div id="progress-fill" class="h-full bg-primary rounded-full transition-all"
                            style="width: 0%;"></div>
                        <div id="progress-handle"
                            class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-white rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
                            style="left: 0%;"></div>
                    </div>
                    <span id="time-total" class="text-sm text-slate-400 w-12 text-center font-mono">0:00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Download Modal -->
<div id="download-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDownloadModal()"></div>
    <div class="absolute inset-4 flex items-center justify-center">
        <div class="bg-slate-800 rounded-2xl max-w-md w-full shadow-2xl border border-slate-700">
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                <h3 class="font-bold text-lg text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">download</span>
                    Export Video
                </h3>
                <button onclick="closeDownloadModal()" class="text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 space-y-5">
                <!-- Resolution Selection -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-3">Resolution</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="selectResolution('480p')"
                            class="resolution-btn px-4 py-3 rounded-lg border border-slate-600 text-slate-300 hover:border-primary hover:text-white transition-colors"
                            data-resolution="480p">
                            <div class="font-bold">480p</div>
                            <div class="text-xs text-slate-500">480 × 854</div>
                        </button>
                        <button type="button" onclick="selectResolution('720p')"
                            class="resolution-btn px-4 py-3 rounded-lg border border-slate-600 text-slate-300 hover:border-primary hover:text-white transition-colors"
                            data-resolution="720p">
                            <div class="font-bold">720p</div>
                            <div class="text-xs text-slate-500">720 × 1280</div>
                        </button>
                        <button type="button" onclick="selectResolution('1080p')"
                            class="resolution-btn px-4 py-3 rounded-lg border-2 border-primary bg-primary/10 text-white"
                            data-resolution="1080p">
                            <div class="font-bold">1080p</div>
                            <div class="text-xs text-slate-400">1080 × 1920</div>
                        </button>
                    </div>
                </div>

                <!-- Format Selection -->
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-3">Format</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectFormat('mp4')"
                            class="format-btn px-4 py-3 rounded-lg border-2 border-primary bg-primary/10 text-white"
                            data-format="mp4">
                            <div class="font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">videocam</span>
                                MP4
                            </div>
                            <div class="text-xs text-slate-400">Best compatibility</div>
                        </button>
                        <button type="button" onclick="selectFormat('webm')"
                            class="format-btn px-4 py-3 rounded-lg border border-slate-600 text-slate-300 hover:border-primary hover:text-white transition-colors"
                            data-format="webm">
                            <div class="font-bold flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">videocam</span>
                                WebM
                            </div>
                            <div class="text-xs text-slate-500">Smaller size</div>
                        </button>
                    </div>
                </div>

                <!-- Export Progress (hidden by default) -->
                <div id="export-progress" class="hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-slate-400">Generating video...</span>
                        <span id="export-percent" class="text-sm font-bold text-primary">0%</span>
                    </div>
                    <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                        <div id="export-bar" class="h-full bg-primary rounded-full transition-all" style="width: 0%;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-slate-700">
                <button type="button" id="btn-start-export" onclick="startExport()"
                    class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg transition-colors">
                    <span class="material-symbols-outlined">download</span>
                    Start Export
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Download modal state
    let selectedResolution = '1080p';
    let selectedFormat = 'mp4';

    function openDownloadModal() {
        document.getElementById('download-modal').classList.remove('hidden');
    }

    function closeDownloadModal() {
        document.getElementById('download-modal').classList.add('hidden');
        document.getElementById('export-progress').classList.add('hidden');
        document.getElementById('btn-start-export').disabled = false;
        document.getElementById('btn-start-export').innerHTML = '<span class="material-symbols-outlined">download</span> Start Export';
    }

    function selectResolution(res) {
        selectedResolution = res;
        document.querySelectorAll('.resolution-btn').forEach(btn => {
            if (btn.dataset.resolution === res) {
                btn.className = 'resolution-btn px-4 py-3 rounded-lg border-2 border-primary bg-primary/10 text-white';
            } else {
                btn.className = 'resolution-btn px-4 py-3 rounded-lg border border-slate-600 text-slate-300 hover:border-primary hover:text-white transition-colors';
            }
        });
    }

    function selectFormat(fmt) {
        selectedFormat = fmt;
        document.querySelectorAll('.format-btn').forEach(btn => {
            if (btn.dataset.format === fmt) {
                btn.className = 'format-btn px-4 py-3 rounded-lg border-2 border-primary bg-primary/10 text-white';
            } else {
                btn.className = 'format-btn px-4 py-3 rounded-lg border border-slate-600 text-slate-300 hover:border-primary hover:text-white transition-colors';
            }
        });
    }

    function startExport() {
        if (window.editor) {
            window.editor.downloadWithOptions(selectedResolution, selectedFormat);
        }
    }

    function updateExportProgress(percent) {
        document.getElementById('export-progress').classList.remove('hidden');
        document.getElementById('export-percent').textContent = percent + '%';
        document.getElementById('export-bar').style.width = percent + '%';
    }
</script>

<!-- Pass data to JavaScript -->
<script>
    window.EDITOR_DATA = {
        templateId: <?= $templateId ?>,
        template: <?= json_encode($template) ?>,
        slides: <?= json_encode($slides) ?>,
        fields: <?= json_encode($fields) ?>
    };
</script>
<script src="/assets/js/editor.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>