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

// Get field presets for quick add
$presets = Database::fetchAll(
    "SELECT * FROM field_presets WHERE is_active = 1 ORDER BY category, display_order"
);

// Group presets by category
$presetsByCategory = [];
foreach ($presets as $preset) {
    $cat = $preset['category'] ?? 'general';
    $presetsByCategory[$cat][] = $preset;
}

$pendingTickets = 0;
$pageTitle = 'Template Builder: ' . $template['title'];
?>

<?php ob_start(); ?>

<link rel="stylesheet" href="/assets/css/template-builder.css">
<!-- Google Fonts for Canvas Text -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;600;700&family=Open+Sans:wght@400;600;700&family=Lato:wght@400;700&family=Raleway:wght@400;600;700&family=Nunito:wght@400;600;700&family=Playfair+Display:wght@400;600;700&family=Merriweather:wght@400;700&family=Lora:wght@400;600;700&family=Cormorant+Garamond:wght@400;600;700&family=Cinzel:wght@400;600;700&family=Great+Vibes&family=Dancing+Script:wght@400;600;700&family=Pacifico&family=Satisfy&family=Alex+Brush&family=Tangerine:wght@400;700&family=Abril+Fatface&family=Bebas+Neue&family=Anton&family=Oswald:wght@400;500;600;700&display=swap"
    rel="stylesheet">

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
        <!-- Icon Sidebar -->
        <div class="icon-sidebar">
            <button class="icon-btn active" data-panel="fields" title="Fields">
                <span class="material-symbols-outlined">text_fields</span>
                <span class="icon-label">Fields</span>
            </button>
            <button class="icon-btn" data-panel="elements" title="Elements">
                <span class="material-symbols-outlined">category</span>
                <span class="icon-label">Elements</span>
            </button>
            <button class="icon-btn" data-panel="uploads" title="Uploads">
                <span class="material-symbols-outlined">cloud_upload</span>
                <span class="icon-label">Uploads</span>
            </button>
            <button class="icon-btn" data-panel="background" title="Background">
                <span class="material-symbols-outlined">wallpaper</span>
                <span class="icon-label">Background</span>
            </button>
            <button class="icon-btn" data-panel="settings" title="Slide Settings">
                <span class="material-symbols-outlined">tune</span>
                <span class="icon-label">Settings</span>
            </button>
        </div>

        <!-- Content Panel (slides in/out based on selection) -->
        <div class="content-panel open" id="content-panel">
            <!-- Fields Panel -->
            <div class="panel-view active" id="panel-fields">
                <div class="panel-header">
                    <h3>Fields</h3>
                </div>
                <div class="panel-body">
                    <!-- Quick Add Preset -->
                    <?php if (!empty($presetsByCategory)): ?>
                        <div class="quick-add-preset">
                            <span class="toolbar-label">Quick Add Field</span>
                            <div class="preset-row">
                                <select id="preset-select" class="preset-dropdown">
                                    <option value="">Select preset...</option>
                                    <?php foreach ($presetsByCategory as $category => $categoryPresets): ?>
                                        <optgroup label="<?= ucfirst(str_replace('_', ' ', $category)) ?>">
                                            <?php foreach ($categoryPresets as $preset): ?>
                                                <option value="<?= $preset['id'] ?>"
                                                    data-name="<?= Security::escape($preset['name']) ?>"
                                                    data-field-name="<?= Security::escape($preset['field_name']) ?>"
                                                    data-type="<?= $preset['field_type'] ?>">
                                                    <?= Security::escape($preset['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="btn-add-preset" class="btn-icon-primary" title="Add">
                                    <span class="material-symbols-outlined">add</span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <p class="hint-text">Drag fields onto the canvas</p>
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
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($fields)): ?>
                            <p class="empty-hint">No fields yet. Add using presets above.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Text Properties (shown when text is selected) -->
                    <div class="context-properties" id="text-properties" style="display: none;">
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
                                <optgroup label="Sans Serif">
                                    <option value="Inter">Inter</option>
                                    <option value="Montserrat">Montserrat</option>
                                    <option value="Roboto">Roboto</option>
                                    <option value="Poppins">Poppins</option>
                                    <option value="Open Sans">Open Sans</option>
                                    <option value="Lato">Lato</option>
                                </optgroup>
                                <optgroup label="Serif">
                                    <option value="Playfair Display">Playfair Display</option>
                                    <option value="Merriweather">Merriweather</option>
                                    <option value="Lora">Lora</option>
                                </optgroup>
                                <optgroup label="Script">
                                    <option value="Great Vibes">Great Vibes</option>
                                    <option value="Dancing Script">Dancing Script</option>
                                    <option value="Pacifico">Pacifico</option>
                                </optgroup>
                                <optgroup label="Display">
                                    <option value="Abril Fatface">Abril Fatface</option>
                                    <option value="Bebas Neue">Bebas Neue</option>
                                    <option value="Oswald">Oswald</option>
                                </optgroup>
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
                                <option value="zoomIn">Zoom In</option>
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

            <!-- Elements Panel -->
            <div class="panel-view" id="panel-elements">
                <div class="panel-header">
                    <h3>Elements</h3>
                </div>
                <div class="panel-body">
                    <span class="toolbar-label">Shapes</span>
                    <div class="elements-grid">
                        <button type="button" id="btn-add-rectangle" class="element-btn" title="Rectangle">
                            <span class="material-symbols-outlined">rectangle</span>
                            <span>Rectangle</span>
                        </button>
                        <button type="button" id="btn-add-ellipse" class="element-btn" title="Ellipse">
                            <span class="material-symbols-outlined">circle</span>
                            <span>Ellipse</span>
                        </button>
                        <button type="button" id="btn-add-line" class="element-btn" title="Line">
                            <span class="material-symbols-outlined">horizontal_rule</span>
                            <span>Line</span>
                        </button>
                    </div>

                    <!-- Shape Properties (shown when shape is selected) -->
                    <div class="context-properties" id="shape-properties" style="display: none;">
                        <h4>Shape Properties</h4>
                        <label class="property-row">
                            <span>Fill Color</span>
                            <input type="color" id="shape-fill" value="#7c3aed">
                        </label>
                        <label class="property-row">
                            <span>Stroke</span>
                            <input type="color" id="shape-stroke" value="#000000">
                        </label>
                        <label class="property-row">
                            <span>Stroke Width</span>
                            <input type="number" id="shape-stroke-width" value="0" min="0" max="20">
                        </label>
                        <label class="property-row">
                            <span>Opacity (%)</span>
                            <input type="number" id="shape-opacity" value="100" min="0" max="100">
                        </label>
                        <label class="property-row">
                            <span>Border Radius</span>
                            <input type="number" id="shape-radius" value="0" min="0" max="100">
                        </label>
                        <button type="button" id="btn-delete-shape" class="btn btn-sm btn-danger">
                            <span class="material-symbols-outlined">delete</span>
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Uploads Panel -->
            <div class="panel-view" id="panel-uploads">
                <div class="panel-header">
                    <h3>Uploads</h3>
                </div>
                <div class="panel-body">
                    <span class="toolbar-label">Add Image</span>
                    <input type="file" id="shape-image-input" accept="image/*" class="hidden">
                    <button type="button" id="btn-add-image" class="upload-btn">
                        <span class="material-symbols-outlined">add_photo_alternate</span>
                        Upload Image
                    </button>
                    <p class="hint-text">Upload images to add to your canvas</p>
                </div>
            </div>

            <!-- Background Panel -->
            <div class="panel-view" id="panel-background">
                <div class="panel-header">
                    <h3>Background</h3>
                </div>
                <div class="panel-body">
                    <label class="property-row">
                        <span>Color</span>
                        <input type="color" id="slide-bg-color" value="#ffffff">
                    </label>
                    <span class="toolbar-label">Background Image</span>
                    <input type="file" id="slide-bg-image" accept="image/*" class="hidden">
                    <button type="button" id="btn-upload-bg" class="upload-btn">
                        <span class="material-symbols-outlined">add_photo_alternate</span>
                        Upload Image
                    </button>
                </div>
            </div>

            <!-- Settings Panel -->
            <div class="panel-view" id="panel-settings">
                <div class="panel-header">
                    <h3>Slide Settings</h3>
                </div>
                <div class="panel-body">
                    <label class="property-row">
                        <span>Duration (ms)</span>
                        <input type="number" id="slide-duration" value="3000" min="500" max="30000" step="100">
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
            </div>
        </div>

        <!-- Canvas Area -->
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