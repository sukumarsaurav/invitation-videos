<?php
/**
 * Admin - Template Builder
 * Visual slide editor for creating video invitation templates
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$templateId = intval($_GET['id'] ?? 0);

if (!$templateId) {
    header('Location: /admin/templates.php');
    exit;
}

// Get template info
$template = Database::fetchOne("SELECT * FROM templates WHERE id = ?", [$templateId]);
if (!$template) {
    header('Location: /admin/templates.php');
    exit;
}

// Get existing slides
$slides = Database::fetchAll(
    "SELECT * FROM template_slides WHERE template_id = ? ORDER BY slide_order",
    [$templateId]
);

// Get template fields with slide assignments
$fields = Database::fetchAll(
    "SELECT * FROM template_fields WHERE template_id = ? ORDER BY display_order",
    [$templateId]
);

$pendingTickets = 0;
$pageTitle = 'Template Builder: ' . $template['title'];
?>

<?php ob_start(); ?>

<link rel="stylesheet" href="/assets/css/template-builder.css">

<div class="template-builder">
    <!-- Header -->
    <div class="builder-header">
        <div class="flex items-center gap-4">
            <a href="/admin/templates.php?action=edit&id=<?= $templateId ?>" class="btn-icon" title="Back">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold"><?= Security::escape($template['title']) ?></h1>
                <p class="text-sm text-slate-500">Template Builder</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="btn-preview" class="btn btn-secondary">
                <span class="material-symbols-outlined">play_arrow</span>
                Preview
            </button>
            <button type="button" id="btn-save" class="btn btn-primary">
                <span class="material-symbols-outlined">save</span>
                Save Changes
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="builder-content">
        <!-- Left Panel - Fields -->
        <div class="builder-panel panel-left">
            <div class="panel-header">
                <h3>Fields</h3>
                <button type="button" id="btn-add-field" class="btn-icon-sm" title="Add Field">
                    <span class="material-symbols-outlined">add</span>
                </button>
            </div>
            <div class="panel-content">
                <p class="text-xs text-slate-400 mb-3">Drag fields onto the canvas</p>
                <div id="fields-list" class="fields-list">
                    <?php foreach ($fields as $field): ?>
                        <div class="field-item" data-field-id="<?= $field['id'] ?>"
                            data-field-name="<?= Security::escape($field['field_name']) ?>"
                            data-field-label="<?= Security::escape($field['field_label']) ?>"
                            data-sample-value="<?= Security::escape($field['sample_value'] ?? '') ?>" draggable="true">
                            <span class="material-symbols-outlined drag-handle">drag_indicator</span>
                            <div class="field-info">
                                <span class="field-label"><?= Security::escape($field['field_label']) ?></span>
                                <span class="field-type"><?= $field['field_type'] ?></span>
                            </div>
                            <span class="field-slide-badge" data-slide="<?= $field['slide_id'] ?? '' ?>">
                                <?= $field['slide_id'] ? 'S' . $field['slide_id'] : '—' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($fields)): ?>
                        <p class="text-center text-slate-400 py-4 text-sm">
                            No fields yet.<br>
                            <a href="/admin/templates.php?action=edit&id=<?= $templateId ?>" class="text-primary">Add fields
                                first</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Center - Canvas -->
        <div class="builder-canvas-wrapper">
            <div class="canvas-container" id="canvas-container">
                <canvas id="template-canvas" width="1080" height="1920"></canvas>
                <div id="canvas-overlays" class="canvas-overlays">
                    <!-- Text elements will be rendered here -->
                </div>
            </div>
            <div class="canvas-info">
                <span id="canvas-zoom">100%</span>
                <span class="separator">•</span>
                <span>1080 × 1920</span>
                <span class="separator">•</span>
                <span id="current-slide-info">Slide 1</span>
            </div>
        </div>

        <!-- Right Panel - Properties -->
        <div class="builder-panel panel-right">
            <div class="panel-header">
                <h3>Properties</h3>
            </div>
            <div class="panel-content" id="properties-panel">
                <div class="property-section">
                    <h4>Slide Settings</h4>
                    <label class="property-row">
                        <span>Duration (ms)</span>
                        <input type="number" id="slide-duration" value="3000" min="500" max="30000" step="100">
                    </label>
                    <label class="property-row">
                        <span>Background</span>
                        <input type="color" id="slide-bg-color" value="#ffffff">
                    </label>
                    <label class="property-row">
                        <span>Image</span>
                        <input type="file" id="slide-bg-image" accept="image/*" class="hidden">
                        <button type="button" id="btn-upload-bg" class="btn btn-sm btn-secondary">Upload</button>
                    </label>
                    <label class="property-row">
                        <span>Transition</span>
                        <select id="slide-transition">
                            <option value="none">None</option>
                            <option value="fade" selected>Fade</option>
                            <option value="slide">Slide</option>
                            <option value="zoom">Zoom</option>
                        </select>
                    </label>
                </div>

                <div class="property-section" id="text-properties" style="display: none;">
                    <h4>Text Properties</h4>
                    <label class="property-row">
                        <span>Sample Text</span>
                        <input type="text" id="text-sample" placeholder="Preview text...">
                    </label>
                    <label class="property-row">
                        <span>Font Size</span>
                        <input type="number" id="text-font-size" value="24" min="8" max="200">
                    </label>
                    <label class="property-row">
                        <span>Font Family</span>
                        <select id="text-font-family">
                            <option value="Inter">Inter</option>
                            <option value="Playfair Display">Playfair Display</option>
                            <option value="Great Vibes">Great Vibes</option>
                            <option value="Montserrat">Montserrat</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Poppins">Poppins</option>
                        </select>
                    </label>
                    <label class="property-row">
                        <span>Color</span>
                        <input type="color" id="text-color" value="#000000">
                    </label>
                    <label class="property-row">
                        <span>Animation</span>
                        <select id="text-animation">
                            <option value="none">None</option>
                            <option value="fadeIn" selected>Fade In</option>
                            <option value="slideUp">Slide Up</option>
                            <option value="slideDown">Slide Down</option>
                            <option value="slideLeft">Slide Left</option>
                            <option value="slideRight">Slide Right</option>
                            <option value="zoomIn">Zoom In</option>
                            <option value="typewriter">Typewriter</option>
                            <option value="bounce">Bounce</option>
                        </select>
                    </label>
                    <label class="property-row">
                        <span>Delay (ms)</span>
                        <input type="number" id="text-delay" value="0" min="0" max="10000" step="100">
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom - Slide Strip -->
    <div class="builder-slides">
        <div id="slides-strip" class="slides-strip">
            <?php if (empty($slides)): ?>
                <!-- Will be populated by JS if no slides exist -->
            <?php else: ?>
                <?php foreach ($slides as $index => $slide): ?>
                    <div class="slide-thumb <?= $index === 0 ? 'active' : '' ?>" data-slide-id="<?= $slide['id'] ?>"
                        data-slide-order="<?= $slide['slide_order'] ?>"
                        style="background-color: <?= Security::escape($slide['background_color']) ?>;">
                        <span class="slide-number"><?= $index + 1 ?></span>
                        <span class="slide-duration"><?= $slide['duration_ms'] / 1000 ?>s</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" id="btn-add-slide" class="btn-add-slide" title="Add Slide">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="modal hidden">
    <div class="modal-backdrop" onclick="closePreviewModal()"></div>
    <div class="modal-content preview-modal-content">
        <div class="modal-header">
            <h3>Template Preview</h3>
            <button type="button" onclick="closePreviewModal()" class="btn-icon">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div id="preview-container" class="preview-container">
                <canvas id="preview-canvas" width="1080" height="1920"></canvas>
            </div>
            <div class="preview-controls">
                <button type="button" id="btn-play-preview" class="btn btn-primary">
                    <span class="material-symbols-outlined">play_arrow</span>
                    Play
                </button>
                <div class="preview-progress">
                    <div id="preview-progress-bar" class="progress-bar"></div>
                </div>
                <span id="preview-time">0:00 / 0:00</span>
            </div>
        </div>
    </div>
</div>

<!-- Pass data to JavaScript -->
<script>
    window.TEMPLATE_DATA = {
        templateId: <?= $templateId ?>,
        csrfToken: '<?= Security::generateCSRFToken() ?>',
        slides: <?= json_encode($slides) ?>,
        fields: <?= json_encode($fields) ?>
    };
</script>
<script src="/assets/js/template-builder/main.js" type="module"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>