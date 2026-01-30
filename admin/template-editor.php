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
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .editor-container {
            display: grid;
            grid-template-columns: 280px 1fr 350px;
            gap: 20px;
            height: calc(100vh - 120px);
            margin: -20px;
        }

        .editor-sidebar {
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            padding: 16px;
            overflow-y: auto;
        }

        .editor-canvas {
            background: #1a1a2e;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .preview-frame {
            width: 270px;
            height: 480px;
            background: #2d2d44;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .editor-properties {
            background: var(--card-bg);
            border-left: 1px solid var(--border-color);
            padding: 16px;
            overflow-y: auto;
        }

        .slide-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .slide-item {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .slide-item:hover,
        .slide-item.active {
            border-color: var(--primary-color);
            background: rgba(127, 19, 236, 0.1);
        }

        .slide-item.active {
            box-shadow: 0 0 0 2px var(--primary-color);
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
            color: var(--text-muted);
        }

        .layer-list {
            margin-top: 10px;
            padding-left: 12px;
            border-left: 2px solid var(--border-color);
        }

        .layer-item {
            padding: 6px 8px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .layer-item i {
            width: 16px;
        }

        .btn-add-slide {
            width: 100%;
            padding: 12px;
            background: rgba(127, 19, 236, 0.1);
            border: 2px dashed var(--primary-color);
            border-radius: 8px;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-add-slide:hover {
            background: rgba(127, 19, 236, 0.2);
        }

        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
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
            color: var(--text-muted);
        }

        .property-group input,
        .property-group select,
        .property-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-color);
            color: var(--text-color);
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
            background: linear-gradient(135deg, #7f13ec, #5b0fb5);
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
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .editor-header h1 {
            font-size: 18px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .editor-header h1 a {
            color: var(--text-muted);
        }
    </style>
</head>

<body class="admin-body">
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
            <div class="preview-frame" id="previewFrame">
                <!-- Preview content rendered here -->
            </div>
            <div class="timeline-bar" id="timelineBar">
                <!-- Timeline segments -->
            </div>
        </div>

        <!-- Right Sidebar: Properties -->
        <div class="editor-properties" id="propertiesPanel">
            <div class="section-title">Properties</div>
            <p style="color: var(--text-muted); font-size: 13px;">
                Select a slide or layer to edit its properties.
            </p>
        </div>
    </div>

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

    <script>
        // Template definition state
        let templateDef = <?= json_encode($templateDefinition) ?>;
        let selectedSlideIndex = null;
        let selectedLayerIndex = null;

        // Animation presets
        const animations = <?= json_encode($animationPresets) ?>;

        // Field presets for dropdown
        const fieldPresets = <?= json_encode($fieldPresets) ?>;

        // Initialize
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
                    </div>
                    <div class="layer-list">
                        ${slide.layers.map((layer, li) => `
                            <div class="layer-item" onclick="event.stopPropagation(); selectLayer(${index}, ${li})">
                                <i class="fas fa-${layer.type === 'text' ? 'font' : 'image'}"></i>
                                ${layer.fieldKey || layer.id}
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
                    <input type="text" value="${slide.background?.src || ''}" 
                           placeholder="${slide.background?.type === 'color' ? '#1a1a2e' : 'URL or {{fieldKey}}'}"
                           onchange="updateSlideBackground('src', this.value)">
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
                            <option value="bold" ${layer.style?.fontWeight === 'bold' ? 'selected' : ''}>Bold</option>
                            <option value="600" ${layer.style?.fontWeight == '600' ? 'selected' : ''}>Semi-Bold</option>
                        </select>
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
                <p style="color: var(--text-muted); font-size: 13px;">Select a slide or layer to edit its properties.</p>
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

        function saveTemplate() {
            document.getElementById('jsonEditor').value = JSON.stringify(templateDef, null, 2);
            document.querySelector('#jsonModal form').submit();
        }

        function toggleJsonMode() {
            const modal = document.getElementById('jsonModal');
            document.getElementById('jsonEditor').value = JSON.stringify(templateDef, null, 2);
            modal.style.display = 'block';
        }

        function closeJsonModal() {
            document.getElementById('jsonModal').style.display = 'none';
        }
    </script>
</body>

</html>