<?php
/**
 * Admin - Visual Template Editor
 * Creates and edits template definitions (slides, layers, animations) for GenericTemplate
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$templateId = intval($_GET['id'] ?? 0);
$error = null;
$success = null;

// Fetch template
$template = null;
if ($templateId > 0) {
    $template = Database::fetchOne("SELECT * FROM templates WHERE id = ?", [$templateId]);
    if (!$template) {
        header('Location: /admin/templates.php');
        exit;
    }
}

// Get existing template definition or create empty structure
$templateDefinition = json_decode($template['template_definition'] ?? 'null', true) ?: [
    'version' => '1.0',
    'fps' => 30,
    'width' => 1080,
    'height' => 1920,
    'slides' => [],
    'music' => [
        'fieldKey' => 'musicUrl',
        'fallback' => null
    ]
];

// Get field presets for the dropdown
$fieldPresets = Database::fetchAll("
    SELECT id, name, field_name, field_type, category, placeholder, default_value
    FROM field_presets
    WHERE is_active = 1
    ORDER BY category, display_order
");

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_definition'])) {
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $newDefinition = json_decode($_POST['template_definition'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Invalid JSON: ' . json_last_error_msg();
        } else {
            try {
                Database::query(
                    "UPDATE templates SET template_definition = ?, remotion_composition_id = 'GenericTemplate' WHERE id = ?",
                    [json_encode($newDefinition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $templateId]
                );
                $success = 'Template definition saved successfully!';
                $templateDefinition = $newDefinition;
            } catch (Exception $e) {
                $error = 'Save failed: ' . $e->getMessage();
            }
        }
    }
}

// Animation presets for the dropdown
$animationPresets = [
    'none' => 'None',
    'fade-in' => 'Fade In',
    'fade-out' => 'Fade Out',
    'slide-up' => 'Slide Up',
    'slide-down' => 'Slide Down',
    'slide-left' => 'Slide Left',
    'slide-right' => 'Slide Right',
    'zoom-in' => 'Zoom In',
    'zoom-out' => 'Zoom Out',
    'bounce' => 'Bounce',
    'rotate' => 'Rotate',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Editor -
        <?= htmlspecialchars($template['title'] ?? 'New') ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .editor-container {
            display: grid;
            grid-template-columns: 280px 1fr 350px;
            gap: 0;
            height: calc(100vh - 64px);
        }

        .editor-sidebar {
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 16px;
            overflow-y: auto;
        }

        .editor-canvas {
            background: #f1f5f9;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .preview-frame {
            width: 270px;
            height: 480px;
            background: #1e293b;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .editor-properties {
            background: white;
            border-left: 1px solid #e2e8f0;
            padding: 16px;
            overflow-y: auto;
        }

        .slide-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .slide-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .slide-item:hover,
        .slide-item.active {
            border-color: #970747;
            background: rgba(151, 7, 71, 0.05);
        }

        .slide-item.active {
            box-shadow: 0 0 0 2px #970747;
        }

        .slide-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .slide-name {
            font-weight: 600;
            font-size: 14px;
        }

        .slide-duration {
            font-size: 12px;
            color: #64748b;
        }

        .layer-list {
            margin-top: 10px;
            padding-left: 12px;
            border-left: 2px solid #e2e8f0;
        }

        .layer-item {
            padding: 6px 8px;
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-radius: 4px;
            margin-bottom: 2px;
        }

        .layer-item:hover {
            background: rgba(151, 7, 71, 0.05);
        }

        .layer-item.active {
            background: rgba(151, 7, 71, 0.1);
            color: #1e293b;
        }

        .layer-item i {
            width: 16px;
        }

        .slide-actions,
        .layer-actions {
            display: flex;
            gap: 2px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .slide-item:hover .slide-actions,
        .slide-item.active .slide-actions,
        .layer-item:hover .layer-actions {
            opacity: 1;
        }

        .btn-icon {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 6px;
            cursor: pointer;
            color: #64748b;
            font-size: 10px;
            transition: all 0.2s;
        }

        .btn-icon:hover:not(:disabled) {
            background: #970747;
            border-color: #970747;
            color: white;
        }

        .btn-icon:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .btn-icon-sm {
            background: transparent;
            border: none;
            padding: 2px 4px;
            cursor: pointer;
            color: #64748b;
            font-size: 9px;
            opacity: 0.6;
            transition: all 0.2s;
        }

        .btn-icon-sm:hover:not(:disabled) {
            color: #970747;
            opacity: 1;
        }

        .btn-icon-sm:disabled {
            opacity: 0.2;
            cursor: not-allowed;
        }

        .slide-header {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .slide-name {
            font-weight: 600;
            font-size: 14px;
            flex: 1;
        }

        .btn-add-slide {
            width: 100%;
            padding: 12px;
            background: rgba(151, 7, 71, 0.05);
            border: 2px dashed #970747;
            border-radius: 8px;
            color: #970747;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-add-slide:hover {
            background: rgba(151, 7, 71, 0.1);
        }

        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .property-group {
            margin-bottom: 20px;
        }

        .property-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
            color: #64748b;
        }

        .property-group input,
        .property-group select,
        .property-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
            color: #1e293b;
            font-size: 14px;
        }

        .property-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .json-editor {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            line-height: 1.5;
            min-height: 300px;
            resize: vertical;
        }

        .timeline-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 4px;
        }

        .timeline-segment {
            height: 30px;
            background: linear-gradient(135deg, #970747, #7a053a);
            border-radius: 4px;
            flex-shrink: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: white;
            transition: opacity 0.2s;
        }

        .timeline-segment:hover {
            opacity: 0.8;
        }

        .preview-layer {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-layer.text {
            color: white;
            font-family: 'Inter', sans-serif;
        }

        .preview-layer.image {
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 2px dashed rgba(255, 255, 255, 0.5);
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            height: 64px;
        }

        .editor-header h1 {
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1e293b;
        }

        .editor-header h1 a {
            color: #64748b;
            transition: color 0.2s;
        }

        .editor-header h1 a:hover {
            color: #970747;
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #970747;
            color: white;
        }

        .btn-primary:hover {
            background: #7a053a;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .btn-outline-danger {
            background: transparent;
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .btn-outline-danger:hover {
            background: #fef2f2;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-900" style="margin: 0; min-height: 100vh;">
    <div class="editor-header">
        <h1>
            <a href="/admin/templates.php"><i class="fas fa-arrow-left"></i></a>
            Template Editor:
            <?= htmlspecialchars($template['title'] ?? 'New Template') ?>
        </h1>
        <div class="header-actions">
            <button type="button" class="btn btn-secondary" onclick="toggleJsonMode()">
                <i class="fas fa-code"></i> JSON Mode
            </button>
            <button type="button" class="btn btn-primary" onclick="saveTemplate()">
                <i class="fas fa-save"></i> Save Template
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="editor-container">
        <!-- Left Sidebar: Slides -->
        <div class="editor-sidebar">
            <div class="section-title">Slides</div>
            <ul class="slide-list" id="slideList">
                <!-- Populated by JavaScript -->
            </ul>
            <button type="button" class="btn-add-slide" onclick="addSlide()">
                <i class="fas fa-plus"></i> Add Slide
            </button>
        </div>

        <!-- Center: Preview Canvas -->
        <div class="editor-canvas">
            <div class="preview-header"
                style="display: flex; justify-content: space-between; align-items: center; padding: 8px 16px; background: white; border-bottom: 1px solid #e2e8f0; position: absolute; top: 0; left: 0; right: 0; z-index: 10;">
                <span style="font-size: 12px; font-weight: 600;">Preview</span>
                <div style="display: flex; gap: 4px;">
                    <button type="button" id="btnCssPreview" class="btn btn-sm btn-primary"
                        onclick="setPreviewMode('css')">Simple</button>
                    <button type="button" id="btnRemotionPreview" class="btn btn-sm btn-secondary"
                        onclick="setPreviewMode('remotion')">Remotion</button>
                </div>
            </div>
            <div class="preview-frame" id="previewFrame">
                <!-- CSS Preview content rendered here -->
            </div>
            <iframe id="remotionPreviewFrame" src=""
                style="display: none; width: 100%; height: 100%; border: none; background: #1a1a2e;" allow="autoplay">
            </iframe>
            <div class="timeline-bar" id="timelineBar">
                <!-- Timeline segments -->
            </div>
        </div>

        <!-- Right Sidebar: Properties -->
        <div class="editor-properties" id="propertiesPanel">
            <div class="section-title">Properties</div>
            <p style="color: #64748b; font-size: 13px;">
                Select a slide or layer to edit its properties.
            </p>
        </div>
    </div>

    <!-- Hidden file input for asset uploads -->
    <input type="file" id="assetFileInput" style="display: none;"
        accept="video/mp4,video/webm,image/jpeg,image/png,image/webp">

    <!-- Store CSRF token for JavaScript -->
    <meta name="csrf-token" content="<?= Security::generateCSRFToken() ?>">


    <!-- JSON Editor Modal -->
    <div id="jsonModal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; padding: 40px;">
        <div style="max-width: 900px; margin: 0 auto; height: 100%;">
            <form method="POST" style="height: 100%; display: flex; flex-direction: column;">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Security::generateCSRFToken() ?>">
                <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                    <h2 style="color: white; margin: 0;">Edit JSON</h2>
                    <div>
                        <button type="button" class="btn btn-secondary" onclick="closeJsonModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
                <textarea name="template_definition" class="json-editor" id="jsonEditor"
                    style="flex: 1;"><?= htmlspecialchars(json_encode($templateDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
            </form>
        </div>
    </div>

    <!-- Hidden data containers for safe JSON parsing -->
    <script type="application/json" id="templateDefData">
<?php
$jsonDef = json_encode($templateDefinition, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
echo $jsonDef !== false ? $jsonDef : '{}';
?>
    </script>
    <script type="application/json" id="animationsData">
<?php
$jsonAnim = json_encode($animationPresets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
echo $jsonAnim !== false ? $jsonAnim : '{}';
?>
    </script>
    <script type="application/json" id="fieldPresetsData">
<?php
$jsonPresets = json_encode($fieldPresets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
echo $jsonPresets !== false ? $jsonPresets : '[]';
?>
    </script>

    <script>
        // ========== GLOBAL STATE ==========
        // Initialize these first so functions always have something to work with
        let templateDef = { version: '1.0', fps: 30, width: 1080, height: 1920, slides: [], music: { fieldKey: 'musicUrl', fallback: null } };
        let selectedSlideIndex = null;
        let selectedLayerIndex = null;
        let animations = {};
        let fieldPresets = [];
        let previewMode = 'css';
        const REMOTION_STUDIO_URL = 'http://localhost:3000';

        // ========== CORE FUNCTIONS (defined first for onclick handlers) ==========

        function addSlide() {
            const lastSlide = templateDef.slides[templateDef.slides.length - 1];
            const startFrame = lastSlide ? lastSlide.startFrame + lastSlide.durationFrames : 0;

            templateDef.slides.push({
                id: 'slide_' + Date.now(),
                name: 'Slide ' + (templateDef.slides.length + 1),
                startFrame: startFrame,
                durationFrames: 90,
                background: { type: 'color', src: '#1a1a2e' },
                layers: []
            });

            renderSlideList();
            renderTimeline();
            selectSlide(templateDef.slides.length - 1);
        }

        function toggleJsonMode() {
            const modal = document.getElementById('jsonModal');
            document.getElementById('jsonEditor').value = JSON.stringify(templateDef, null, 2);
            modal.style.display = 'block';
        }

        function closeJsonModal() {
            document.getElementById('jsonModal').style.display = 'none';
        }

        function saveTemplate() {
            document.getElementById('jsonEditor').value = JSON.stringify(templateDef, null, 2);
            document.querySelector('#jsonModal form').submit();
        }

        function setPreviewMode(mode) {
            previewMode = mode;
            const cssPreviewEl = document.getElementById('previewFrame');
            const remotionEl = document.getElementById('remotionPreviewFrame');
            const btnCss = document.getElementById('btnCssPreview');
            const btnRemotion = document.getElementById('btnRemotionPreview');

            if (mode === 'remotion') {
                cssPreviewEl.style.display = 'none';
                remotionEl.style.display = 'block';
                btnCss.className = 'btn btn-sm btn-secondary';
                btnRemotion.className = 'btn btn-sm btn-primary';

                if (!remotionEl.src || remotionEl.src === '' || remotionEl.src === 'about:blank') {
                    const previewUrl = REMOTION_STUDIO_URL + '/?composition=GenericTemplate';
                    remotionEl.src = previewUrl;
                }
                setTimeout(() => updateRemotionPreview(), 500);
            } else {
                cssPreviewEl.style.display = 'block';
                remotionEl.style.display = 'none';
                btnCss.className = 'btn btn-sm btn-primary';
                btnRemotion.className = 'btn btn-sm btn-secondary';
                renderPreview();
            }
        }

        function updateRemotionPreview() {
            const remotionEl = document.getElementById('remotionPreviewFrame');
            if (remotionEl && remotionEl.contentWindow && previewMode === 'remotion') {
                remotionEl.contentWindow.postMessage({
                    type: 'UPDATE_TEMPLATE',
                    data: { template: templateDef }
                }, '*');
            }
        }

        // ========== ASSET UPLOAD ==========
        let isUploading = false;

        function uploadBackgroundAsset(assetType) {
            if (isUploading) return;

            const input = document.getElementById('assetFileInput');
            input.accept = assetType === 'video' ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp';

            input.onchange = async function () {
                if (!this.files || !this.files[0]) return;

                const file = this.files[0];
                const maxSize = assetType === 'video' ? 50 * 1024 * 1024 : 10 * 1024 * 1024;

                if (file.size > maxSize) {
                    alert(`File too large. Maximum size is ${maxSize / 1024 / 1024}MB`);
                    return;
                }

                isUploading = true;
                const uploadBtn = document.getElementById('uploadAssetBtn');
                const originalText = uploadBtn.innerHTML;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                uploadBtn.disabled = true;

                try {
                    const formData = new FormData();
                    formData.append('asset_file', file);
                    formData.append('asset_type', assetType);
                    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                    const response = await fetch('/api/admin/upload-asset.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        updateSlideBackground('src', result.url);
                        renderSlideProperties(); // Refresh to show new URL
                        renderPreview();
                    } else {
                        alert('Upload failed: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('Upload failed: ' + error.message);
                } finally {
                    isUploading = false;
                    uploadBtn.innerHTML = originalText;
                    uploadBtn.disabled = false;
                    input.value = ''; // Reset for next upload
                }
            };

            input.click();
        }


        // ========== DATA INITIALIZATION (wrapped in try-catch) ==========
        try {
            const rawData = document.getElementById('templateDefData');
            if (rawData && rawData.textContent) {
                const parsed = JSON.parse(rawData.textContent.trim());
                if (parsed && typeof parsed === 'object') {
                    templateDef = parsed;
                    if (!Array.isArray(templateDef.slides)) {
                        templateDef.slides = [];
                    }
                }
            }
        } catch (e) {
            console.error('Failed to parse templateDef:', e);
        }

        try {
            const animData = document.getElementById('animationsData');
            if (animData && animData.textContent) {
                animations = JSON.parse(animData.textContent.trim()) || {};
            }
        } catch (e) {
            console.error('Failed to parse animations:', e);
        }

        try {
            const presetsData = document.getElementById('fieldPresetsData');
            if (presetsData && presetsData.textContent) {
                fieldPresets = JSON.parse(presetsData.textContent.trim()) || [];
            }
        } catch (e) {
            console.error('Failed to parse fieldPresets:', e);
        }

        // ========== INITIALIZATION ==========
        document.addEventListener('DOMContentLoaded', () => {
            renderSlideList();
            renderTimeline();
            if (templateDef.slides.length > 0) {
                selectSlide(0);
            }
        });

        function renderSlideList() {
            const list = document.getElementById('slideList');
            list.innerHTML = templateDef.slides.map((slide, index) => `
                <li class="slide-item ${selectedSlideIndex === index ? 'active' : ''}" 
                    onclick="selectSlide(${index})"
                    data-index="${index}">
                    <div class="slide-header">
                        <span class="slide-name">${slide.name || 'Slide ' + (index + 1)}</span>
                        <span class="slide-duration">${(slide.durationFrames / 30).toFixed(1)}s</span>
                        <div class="slide-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn-icon" onclick="moveSlide(${index}, -1)" title="Move Up" ${index === 0 ? 'disabled' : ''}>
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button type="button" class="btn-icon" onclick="moveSlide(${index}, 1)" title="Move Down" ${index === templateDef.slides.length - 1 ? 'disabled' : ''}>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <button type="button" class="btn-icon" onclick="duplicateSlide(${index})" title="Duplicate">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="layer-list">
                        ${slide.layers.map((layer, li) => `
                            <div class="layer-item ${selectedSlideIndex === index && selectedLayerIndex === li ? 'active' : ''}" onclick="event.stopPropagation(); selectLayer(${index}, ${li})">
                                <span>
                                    <i class="fas fa-${layer.type === 'text' ? 'font' : 'image'}"></i>
                                    ${layer.fieldKey || layer.id}
                                </span>
                                <div class="layer-actions" onclick="event.stopPropagation()">
                                    <button type="button" class="btn-icon-sm" onclick="moveLayer(${index}, ${li}, -1)" title="Move Up" ${li === 0 ? 'disabled' : ''}>
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                    <button type="button" class="btn-icon-sm" onclick="moveLayer(${index}, ${li}, 1)" title="Move Down" ${li === slide.layers.length - 1 ? 'disabled' : ''}>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <button type="button" class="btn-icon-sm" onclick="duplicateLayer(${index}, ${li})" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </li>
            `).join('');
        }

        function renderTimeline() {
            const bar = document.getElementById('timelineBar');
            const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0);

            bar.innerHTML = templateDef.slides.map((slide, index) => {
                const widthPercent = (slide.durationFrames / totalFrames) * 100;
                return `
                    <div class="timeline-segment" 
                         style="width: ${widthPercent}%"
                         onclick="selectSlide(${index})">
                        ${slide.name || 'S' + (index + 1)}
                    </div>
                `;
            }).join('');
        }

        function renderPreview() {
            const frame = document.getElementById('previewFrame');
            if (selectedSlideIndex === null || !templateDef.slides[selectedSlideIndex]) {
                frame.innerHTML = '<p style="color: #666;">No slide selected</p>';
                return;
            }

            const slide = templateDef.slides[selectedSlideIndex];
            const bgColor = slide.background?.type === 'color' ? slide.background.src : '#2d2d44';

            let layersHtml = slide.layers.map(layer => {
                const scale = 0.25; // Preview scale
                const x = (layer.position?.x || 540) * scale;
                const y = (layer.position?.y || 500) * scale;

                if (layer.type === 'text') {
                    const fontSize = (layer.style?.fontSize || 48) * scale;
                    return `
                        <div class="preview-layer text" style="
                            left: ${x}px;
                            top: ${y}px;
                            transform: translate(-50%, -50%);
                            font-size: ${fontSize}px;
                            color: ${layer.style?.color || '#FFFFFF'};
                            font-weight: ${layer.style?.fontWeight || 'normal'};
                        ">
                            ${layer.defaultValue || layer.fieldKey}
                        </div>
                    `;
                } else if (layer.type === 'image') {
                    const w = (layer.size?.width || 200) * scale;
                    const h = (layer.size?.height || 200) * scale;
                    return `
                        <div class="preview-layer image" style="
                            left: ${x}px;
                            top: ${y}px;
                            transform: translate(-50%, -50%);
                            width: ${w}px;
                            height: ${h}px;
                        ">
                            <i class="fas fa-image"></i>
                        </div>
                    `;
                }
                return '';
            }).join('');

            frame.innerHTML = `
                <div style="position: absolute; inset: 0; background: ${bgColor};">
                    ${layersHtml}
                </div>
            `;
        }

        function selectSlide(index) {
            selectedSlideIndex = index;
            selectedLayerIndex = null;
            renderSlideList();
            renderPreview();
            renderSlideProperties();
        }

        function selectLayer(slideIndex, layerIndex) {
            selectedSlideIndex = slideIndex;
            selectedLayerIndex = layerIndex;
            renderSlideList();
            renderPreview();
            renderLayerProperties();
        }

        function renderSlideProperties() {
            const panel = document.getElementById('propertiesPanel');
            const slide = templateDef.slides[selectedSlideIndex];

            panel.innerHTML = `
                <div class="section-title">Slide Properties</div>
                <div class="property-group">
                    <label>Name</label>
                    <input type="text" value="${slide.name || ''}" onchange="updateSlide('name', this.value)">
                </div>
                <div class="property-row">
                    <div class="property-group">
                        <label>Start (frames)</label>
                        <input type="number" value="${slide.startFrame}" onchange="updateSlide('startFrame', parseInt(this.value))">
                    </div>
                    <div class="property-group">
                        <label>Duration (frames)</label>
                        <input type="number" value="${slide.durationFrames}" onchange="updateSlide('durationFrames', parseInt(this.value))">
                    </div>
                </div>
                <div class="property-group">
                    <label>Background Type</label>
                    <select onchange="updateSlideBackground('type', this.value)">
                        <option value="color" ${slide.background?.type === 'color' ? 'selected' : ''}>Solid Color</option>
                        <option value="video" ${slide.background?.type === 'video' ? 'selected' : ''}>Video</option>
                        <option value="image" ${slide.background?.type === 'image' ? 'selected' : ''}>Image</option>
                    </select>
                </div>
                <div class="property-group">
                    <label>Background Source</label>
                    ${slide.background?.type !== 'color' ? `
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <button type="button" id="uploadAssetBtn" class="btn btn-secondary btn-sm" 
                                    onclick="uploadBackgroundAsset('${slide.background?.type || 'video'}')" style="flex-shrink: 0;">
                                <i class="fas fa-upload"></i> Upload ${slide.background?.type === 'image' ? 'Image' : 'Video'}
                            </button>
                            <span style="font-size: 11px; color: #64748b; display: flex; align-items: center;">
                                Max: ${slide.background?.type === 'image' ? '10MB' : '50MB'}
                            </span>
                        </div>
                    ` : ''}
                    <input type="text" value="${slide.background?.src || ''}" 
                           placeholder="${slide.background?.type === 'color' ? '#1a1a2e' : 'S3 URL or {{fieldKey}}'}"
                           onchange="updateSlideBackground('src', this.value)"
                           style="${slide.background?.src && slide.background?.type !== 'color' ? 'font-size: 11px;' : ''}">
                    ${slide.background?.src && slide.background?.type !== 'color' ? `
                        <p style="font-size: 11px; color: #16a34a; margin-top: 4px;">
                            <i class="fas fa-check-circle"></i> Asset uploaded
                        </p>
                    ` : ''}
                </div>
                
                <div class="section-title" style="margin-top: 24px;">Layers</div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addLayer('text')">
                    <i class="fas fa-font"></i> Add Text
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addLayer('image')">
                    <i class="fas fa-image"></i> Add Image
                </button>
                
                <div style="margin-top: 20px;">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteSlide()">
                        <i class="fas fa-trash"></i> Delete Slide
                    </button>
                </div>
            `;
        }

        function renderLayerProperties() {
            const panel = document.getElementById('propertiesPanel');
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];

            let typeSpecificHtml = '';
            if (layer.type === 'text') {
                typeSpecificHtml = `
                    <div class="property-group">
                        <label>Default Text</label>
                        <input type="text" value="${layer.defaultValue || ''}" onchange="updateLayer('defaultValue', this.value)">
                    </div>
                    <div class="property-group">
                        <label>Font Family (Google Fonts)</label>
                        <select onchange="updateLayerStyle('fontFamily', this.value)">
                            <optgroup label="Sans-Serif">
                                <option value="Inter" ${layer.style?.fontFamily === 'Inter' ? 'selected' : ''}>Inter</option>
                                <option value="Poppins" ${layer.style?.fontFamily === 'Poppins' ? 'selected' : ''}>Poppins</option>
                                <option value="Montserrat" ${layer.style?.fontFamily === 'Montserrat' ? 'selected' : ''}>Montserrat</option>
                                <option value="Open Sans" ${layer.style?.fontFamily === 'Open Sans' ? 'selected' : ''}>Open Sans</option>
                                <option value="Raleway" ${layer.style?.fontFamily === 'Raleway' ? 'selected' : ''}>Raleway</option>
                                <option value="Outfit" ${layer.style?.fontFamily === 'Outfit' ? 'selected' : ''}>Outfit</option>
                            </optgroup>
                            <optgroup label="Serif">
                                <option value="Playfair Display" ${layer.style?.fontFamily === 'Playfair Display' ? 'selected' : ''}>Playfair Display</option>
                                <option value="Cormorant Garamond" ${layer.style?.fontFamily === 'Cormorant Garamond' ? 'selected' : ''}>Cormorant Garamond</option>
                                <option value="Libre Baskerville" ${layer.style?.fontFamily === 'Libre Baskerville' ? 'selected' : ''}>Libre Baskerville</option>
                                <option value="Merriweather" ${layer.style?.fontFamily === 'Merriweather' ? 'selected' : ''}>Merriweather</option>
                                <option value="Lora" ${layer.style?.fontFamily === 'Lora' ? 'selected' : ''}>Lora</option>
                            </optgroup>
                            <optgroup label="Script/Decorative">
                                <option value="Great Vibes" ${layer.style?.fontFamily === 'Great Vibes' ? 'selected' : ''}>Great Vibes (Script)</option>
                                <option value="Dancing Script" ${layer.style?.fontFamily === 'Dancing Script' ? 'selected' : ''}>Dancing Script</option>
                                <option value="Parisienne" ${layer.style?.fontFamily === 'Parisienne' ? 'selected' : ''}>Parisienne</option>
                                <option value="Alex Brush" ${layer.style?.fontFamily === 'Alex Brush' ? 'selected' : ''}>Alex Brush</option>
                                <option value="Allura" ${layer.style?.fontFamily === 'Allura' ? 'selected' : ''}>Allura</option>
                                <option value="Tangerine" ${layer.style?.fontFamily === 'Tangerine' ? 'selected' : ''}>Tangerine</option>
                            </optgroup>
                            <optgroup label="Indian/Hindi Fonts">
                                <option value="Hind" ${layer.style?.fontFamily === 'Hind' ? 'selected' : ''}>Hind (Devanagari)</option>
                                <option value="Noto Sans Devanagari" ${layer.style?.fontFamily === 'Noto Sans Devanagari' ? 'selected' : ''}>Noto Sans Devanagari</option>
                                <option value="Poppins" ${layer.style?.fontFamily === 'Poppins' ? 'selected' : ''}>Poppins (Hindi+English)</option>
                            </optgroup>
                        </select>
                        <p style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            Only selected fonts are loaded during render (optimized)
                        </p>
                    </div>
                    <div class="property-row">
                        <div class="property-group">
                            <label>Font Size</label>
                            <input type="number" value="${layer.style?.fontSize || 48}" onchange="updateLayerStyle('fontSize', parseInt(this.value))">
                        </div>
                        <div class="property-group">
                            <label>Color</label>
                            <input type="color" value="${layer.style?.color || '#FFFFFF'}" onchange="updateLayerStyle('color', this.value)">
                        </div>
                    </div>
                    <div class="property-group">
                        <label>Font Weight</label>
                        <select onchange="updateLayerStyle('fontWeight', this.value)">
                            <option value="normal" ${layer.style?.fontWeight === 'normal' ? 'selected' : ''}>Normal</option>
                            <option value="500" ${layer.style?.fontWeight == '500' ? 'selected' : ''}>Medium (500)</option>
                            <option value="600" ${layer.style?.fontWeight == '600' ? 'selected' : ''}>Semi-Bold (600)</option>
                            <option value="bold" ${layer.style?.fontWeight === 'bold' ? 'selected' : ''}>Bold (700)</option>
                        </select>
                    </div>
                    <div class="property-group">
                        <label>Text Align</label>
                        <select onchange="updateLayerStyle('textAlign', this.value)">
                            <option value="left" ${layer.style?.textAlign === 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${layer.style?.textAlign === 'center' || !layer.style?.textAlign ? 'selected' : ''}>Center</option>
                            <option value="right" ${layer.style?.textAlign === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    <div class="property-row">
                        <div class="property-group">
                            <label>Max Width (px)</label>
                            <input type="number" value="${layer.style?.maxWidth || ''}" placeholder="Auto" 
                                   onchange="updateLayerStyle('maxWidth', this.value ? parseInt(this.value) : null)">
                        </div>
                        <div class="property-group">
                            <label>Line Height</label>
                            <input type="number" step="0.1" value="${layer.style?.lineHeight || 1.2}" 
                                   onchange="updateLayerStyle('lineHeight', parseFloat(this.value))">
                        </div>
                    </div>
                    <div class="property-row">
                        <div class="property-group">
                            <label>Letter Spacing</label>
                            <input type="number" value="${layer.style?.letterSpacing || 0}" 
                                   onchange="updateLayerStyle('letterSpacing', parseInt(this.value))">
                        </div>
                    </div>
                    <div class="property-group">
                        <label>Text Shadow</label>
                        <input type="text" value="${layer.style?.textShadow || ''}" placeholder="e.g. 2px 2px 4px rgba(0,0,0,0.5)"
                               onchange="updateLayerStyle('textShadow', this.value)">
                        <p style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            CSS text-shadow format: x y blur color
                        </p>
                    </div>
                `;
            } else if (layer.type === 'image') {
                typeSpecificHtml = `
                    <div class="property-row">
                        <div class="property-group">
                            <label>Width</label>
                            <input type="number" value="${layer.size?.width || 400}" onchange="updateLayerSize('width', parseInt(this.value))">
                        </div>
                        <div class="property-group">
                            <label>Height</label>
                            <input type="number" value="${layer.size?.height || 400}" onchange="updateLayerSize('height', parseInt(this.value))">
                        </div>
                    </div>
                    <div class="property-group">
                        <label>Border Radius</label>
                        <input type="number" value="${layer.style?.borderRadius || 0}" onchange="updateLayerStyle('borderRadius', parseInt(this.value))">
                    </div>
                    <div class="property-group">
                        <label>Object Fit</label>
                        <select onchange="updateLayerStyle('objectFit', this.value)">
                            <option value="cover" ${layer.style?.objectFit === 'cover' || !layer.style?.objectFit ? 'selected' : ''}>Cover (fill area, crop if needed)</option>
                            <option value="contain" ${layer.style?.objectFit === 'contain' ? 'selected' : ''}>Contain (fit inside, may have gaps)</option>
                            <option value="fill" ${layer.style?.objectFit === 'fill' ? 'selected' : ''}>Fill (stretch to fit)</option>
                        </select>
                    </div>
                    <div class="property-group">
                        <label>Border</label>
                        <input type="text" value="${layer.style?.border || ''}" placeholder="e.g. 3px solid #fff"
                               onchange="updateLayerStyle('border', this.value)">
                    </div>
                    <div class="property-group">
                        <label>Box Shadow</label>
                        <input type="text" value="${layer.style?.boxShadow || ''}" placeholder="e.g. 0 4px 20px rgba(0,0,0,0.3)"
                               onchange="updateLayerStyle('boxShadow', this.value)">
                    </div>
                `;
            }

            panel.innerHTML = `
                <div class="section-title">${layer.type === 'text' ? 'Text' : 'Image'} Layer</div>
                <div class="property-group">
                    <label>Field Key</label>
                    <select onchange="updateLayer('fieldKey', this.value)">
                        <option value="">-- Select Field --</option>
                        ${fieldPresets.map(f => `
                            <option value="${f.field_name}" ${layer.fieldKey === f.field_name ? 'selected' : ''}>
                                ${f.name} (${f.field_name})
                            </option>
                        `).join('')}
                        <option value="${layer.fieldKey}" ${!fieldPresets.find(f => f.field_name === layer.fieldKey) ? 'selected' : ''}>
                            Custom: ${layer.fieldKey}
                        </option>
                    </select>
                </div>
                
                <div class="property-row">
                    <div class="property-group">
                        <label>X Position</label>
                        <input type="number" value="${layer.position?.x || 540}" onchange="updateLayerPosition('x', parseInt(this.value))">
                    </div>
                    <div class="property-group">
                        <label>Y Position</label>
                        <input type="number" value="${layer.position?.y || 500}" onchange="updateLayerPosition('y', parseInt(this.value))">
                    </div>
                </div>
                
                ${typeSpecificHtml}
                
                <div class="property-group">
                    <label>Enter Animation</label>
                    <select onchange="updateLayerAnimation('enter', 'type', this.value)">
                        ${Object.entries(animations).map(([key, label]) => `
                            <option value="${key}" ${layer.animation?.enter?.type === key ? 'selected' : ''}>
                                ${label}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="property-row">
                    <div class="property-group">
                        <label>Duration (frames)</label>
                        <input type="number" value="${layer.animation?.enter?.durationFrames || 30}" 
                               onchange="updateLayerAnimation('enter', 'durationFrames', parseInt(this.value))">
                    </div>
                    <div class="property-group">
                        <label>Delay (frames)</label>
                        <input type="number" value="${layer.animation?.enter?.delay || 0}" 
                               onchange="updateLayerAnimation('enter', 'delay', parseInt(this.value))">
                    </div>
                </div>
                
                <div class="property-group" style="margin-top: 15px;">
                    <label>Exit Animation</label>
                    <select onchange="updateLayerAnimation('exit', 'type', this.value)">
                        <option value="" ${!layer.animation?.exit?.type ? 'selected' : ''}>None (stays visible)</option>
                        ${Object.entries(animations).map(([key, label]) => `
                            <option value="${key}" ${layer.animation?.exit?.type === key ? 'selected' : ''}>
                                ${label}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="property-row">
                    <div class="property-group">
                        <label>Exit Duration (frames)</label>
                        <input type="number" value="${layer.animation?.exit?.durationFrames || 30}" 
                               onchange="updateLayerAnimation('exit', 'durationFrames', parseInt(this.value))">
                    </div>
                    <div class="property-group">
                        <label>Exit Delay (frames before end)</label>
                        <input type="number" value="${layer.animation?.exit?.delay || 0}" 
                               onchange="updateLayerAnimation('exit', 'delay', parseInt(this.value))">
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteLayer()">
                        <i class="fas fa-trash"></i> Delete Layer
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="selectSlide(${selectedSlideIndex})">
                        <i class="fas fa-arrow-left"></i> Back to Slide
                    </button>
                </div>
            `;
        }



        function addLayer(type) {
            const slide = templateDef.slides[selectedSlideIndex];
            const newLayer = {
                id: type + '_' + Date.now(),
                type: type,
                fieldKey: type === 'text' ? 'title' : 'couplePhoto',
                position: { x: 540, y: 500, anchor: 'center' },
                animation: { enter: { type: 'fade-in', durationFrames: 30 } }
            };

            if (type === 'text') {
                newLayer.defaultValue = 'Sample Text';
                newLayer.style = { fontSize: 48, color: '#FFFFFF', fontWeight: 'bold' };
            } else {
                newLayer.size = { width: 400, height: 400 };
                newLayer.style = { borderRadius: 200 };
            }

            slide.layers.push(newLayer);
            renderSlideList();
            renderPreview();
            selectLayer(selectedSlideIndex, slide.layers.length - 1);
        }

        function updateSlide(key, value) {
            templateDef.slides[selectedSlideIndex][key] = value;
            renderSlideList();
            renderTimeline();
        }

        function updateSlideBackground(key, value) {
            if (!templateDef.slides[selectedSlideIndex].background) {
                templateDef.slides[selectedSlideIndex].background = { type: 'color', src: '#1a1a2e' };
            }
            templateDef.slides[selectedSlideIndex].background[key] = value;
            renderPreview();
        }

        function updateLayer(key, value) {
            templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex][key] = value;
            renderSlideList();
            renderPreview();
        }

        function updateLayerPosition(key, value) {
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
            if (!layer.position) layer.position = { x: 540, y: 500, anchor: 'center' };
            layer.position[key] = value;
            renderPreview();
        }

        function updateLayerStyle(key, value) {
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
            if (!layer.style) layer.style = {};
            layer.style[key] = value;
            renderPreview();
        }

        function updateLayerSize(key, value) {
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
            if (!layer.size) layer.size = { width: 400, height: 400 };
            layer.size[key] = value;
            renderPreview();
        }

        function updateLayerAnimation(phase, key, value) {
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
            if (!layer.animation) layer.animation = {};
            if (!layer.animation[phase]) layer.animation[phase] = { type: 'none' };
            layer.animation[phase][key] = value;
        }

        function deleteSlide() {
            if (!confirm('Delete this slide?')) return;
            templateDef.slides.splice(selectedSlideIndex, 1);
            selectedSlideIndex = null;
            selectedLayerIndex = null;
            renderSlideList();
            renderTimeline();
            renderPreview();
            document.getElementById('propertiesPanel').innerHTML = `
                <div class="section-title">Properties</div>
                <p style="color: #64748b; font-size: 13px;">Select a slide or layer to edit its properties.</p>
            `;
        }

        function deleteLayer() {
            if (!confirm('Delete this layer?')) return;
            templateDef.slides[selectedSlideIndex].layers.splice(selectedLayerIndex, 1);
            selectedLayerIndex = null;
            renderSlideList();
            renderPreview();
            renderSlideProperties();
        }



        // ========== Phase 3: Ordering & Duplication ==========

        function moveSlide(index, direction) {
            event.stopPropagation();
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= templateDef.slides.length) return;

            // Swap slides
            const temp = templateDef.slides[index];
            templateDef.slides[index] = templateDef.slides[newIndex];
            templateDef.slides[newIndex] = temp;

            // Recalculate startFrames
            let currentFrame = 0;
            templateDef.slides.forEach(slide => {
                slide.startFrame = currentFrame;
                currentFrame += slide.durationFrames;
            });

            // Update selection
            selectedSlideIndex = newIndex;

            renderSlideList();
            renderTimeline();
            renderSlideProperties();
        }

        function moveLayer(slideIndex, layerIndex, direction) {
            event.stopPropagation();
            const layers = templateDef.slides[slideIndex].layers;
            const newIndex = layerIndex + direction;
            if (newIndex < 0 || newIndex >= layers.length) return;

            // Swap layers
            const temp = layers[layerIndex];
            layers[layerIndex] = layers[newIndex];
            layers[newIndex] = temp;

            // Update selection
            selectedLayerIndex = newIndex;

            renderSlideList();
            renderPreview();
        }

        function duplicateSlide(index) {
            event.stopPropagation();
            const original = templateDef.slides[index];
            const clone = JSON.parse(JSON.stringify(original));

            // Generate new ID and update name
            clone.id = 'slide_' + Date.now();
            clone.name = (clone.name || 'Slide') + ' (copy)';

            // Generate new IDs for layers
            clone.layers.forEach(layer => {
                layer.id = layer.type + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            });

            // Insert after current slide
            templateDef.slides.splice(index + 1, 0, clone);

            // Recalculate startFrames
            let currentFrame = 0;
            templateDef.slides.forEach(slide => {
                slide.startFrame = currentFrame;
                currentFrame += slide.durationFrames;
            });

            renderSlideList();
            renderTimeline();
            selectSlide(index + 1);
        }

        function duplicateLayer(slideIndex, layerIndex) {
            event.stopPropagation();
            const original = templateDef.slides[slideIndex].layers[layerIndex];
            const clone = JSON.parse(JSON.stringify(original));

            // Generate new ID
            clone.id = clone.type + '_' + Date.now();

            // Offset position slightly so it's visible
            if (clone.position) {
                clone.position.x = (clone.position.x || 540) + 20;
                clone.position.y = (clone.position.y || 500) + 20;
            }

            // Insert after current layer
            templateDef.slides[slideIndex].layers.splice(layerIndex + 1, 0, clone);

            renderSlideList();
            renderPreview();
            selectLayer(slideIndex, layerIndex + 1);
        }



        // Also sync template when it changes (call this from updateLayer, etc.)
        const originalUpdateLayer = updateLayer;
        updateLayer = function (key, value) {
            originalUpdateLayer(key, value);
            if (previewMode === 'remotion') updateRemotionPreview();
        };

        const originalUpdateLayerStyle = updateLayerStyle;
        updateLayerStyle = function (key, value) {
            originalUpdateLayerStyle(key, value);
            if (previewMode === 'remotion') updateRemotionPreview();
        };
    </script>
</body>

</html>