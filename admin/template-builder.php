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
        <div class="header-left">
            <a href="/admin/templates.php?action=edit&id=<?= $templateId ?>" class="btn-icon" title="Back">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="header-title"><?= Security::escape($template['title']) ?></h1>
        </div>
        <div class="header-right">
            <button type="button" id="btn-preview" class="btn btn-secondary">
                <span class="material-symbols-outlined">play_arrow</span>
                Preview
            </button>
            <button type="button" id="btn-save" class="btn btn-primary">
                <span class="material-symbols-outlined">save</span>
                Save
            </button>
        </div>
    </div>

    <!-- Floating Text Toolbar (shown when text is selected) -->
    <div class="text-toolbar hidden" id="text-toolbar">
        <select id="toolbar-font" class="toolbar-select">
            <option value="Inter">Inter</option>
            <option value="Montserrat">Montserrat</option>
            <option value="Roboto">Roboto</option>
            <option value="Playfair Display">Playfair Display</option>
            <option value="Great Vibes">Great Vibes</option>
            <option value="Bebas Neue">Bebas Neue</option>
        </select>
        <input type="number" id="toolbar-size" class="toolbar-input" value="24" min="8" max="200" title="Font Size">
        <input type="color" id="toolbar-color" class="toolbar-color" value="#000000" title="Text Color">
        <div class="toolbar-divider"></div>
        <button type="button" class="toolbar-btn" data-action="bold" title="Bold">
            <span class="material-symbols-outlined">format_bold</span>
        </button>
        <button type="button" class="toolbar-btn" data-action="italic" title="Italic">
            <span class="material-symbols-outlined">format_italic</span>
        </button>
        <button type="button" class="toolbar-btn" data-action="underline" title="Underline">
            <span class="material-symbols-outlined">format_underlined</span>
        </button>
        <div class="toolbar-divider"></div>
        <button type="button" class="toolbar-btn" data-action="align-left" title="Align Left">
            <span class="material-symbols-outlined">format_align_left</span>
        </button>
        <button type="button" class="toolbar-btn" data-action="align-center" title="Align Center">
            <span class="material-symbols-outlined">format_align_center</span>
        </button>
        <button type="button" class="toolbar-btn" data-action="align-right" title="Align Right">
            <span class="material-symbols-outlined">format_align_right</span>
        </button>
        <div class="toolbar-divider"></div>
        <button type="button" class="toolbar-btn" id="toolbar-delete" title="Delete">
            <span class="material-symbols-outlined">delete</span>
        </button>
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
            <button class="icon-btn" data-panel="text" title="Text">
                <span class="material-symbols-outlined">title</span>
                <span class="icon-label">Text</span>
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
                    <!-- Category Tabs -->
                    <div class="elements-tabs">
                        <button type="button" class="element-tab active" data-category="shapes">Shapes</button>
                        <button type="button" class="element-tab" data-category="frames">Frames</button>
                        <button type="button" class="element-tab" data-category="graphics">Graphics</button>
                        <button type="button" class="element-tab" data-category="stickers">Stickers</button>
                    </div>

                    <!-- Elements Grid (loaded dynamically) -->
                    <div class="elements-grid" id="elements-grid">
                        <?php
                        // Fetch active elements from database
                        $designElements = Database::fetchAll(
                            "SELECT * FROM design_elements WHERE is_active = 1 ORDER BY category, display_order"
                        );

                        // Group by category
                        $elementsByCategory = [];
                        foreach ($designElements as $el) {
                            $elementsByCategory[$el['category']][] = $el;
                        }
                        ?>

                        <?php foreach (['shapes', 'frames', 'graphics', 'stickers'] as $cat): ?>
                            <div class="elements-category" data-category="<?= $cat ?>"
                                style="<?= $cat !== 'shapes' ? 'display: none;' : '' ?>">
                                <?php if (!empty($elementsByCategory[$cat])): ?>
                                    <?php foreach ($elementsByCategory[$cat] as $el): ?>
                                        <button type="button" class="element-item" data-element-id="<?= $el['id'] ?>"
                                            data-src="<?= Security::escape($el['file_path']) ?>" data-width="<?= $el['width'] ?>"
                                            data-height="<?= $el['height'] ?>" title="<?= Security::escape($el['name']) ?>">
                                            <img src="<?= Security::escape($el['file_path']) ?>"
                                                alt="<?= Security::escape($el['name']) ?>">
                                            <?php if ($el['is_premium']): ?>
                                                <span class="premium-badge">★</span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-elements">No <?= $cat ?> yet. <a
                                            href="/admin/elements.php?action=new&category=<?= $cat ?>" target="_blank">Add
                                            some</a></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Basic Shapes (fallback) -->
                    <span class="toolbar-label mt-3">Basic Shapes</span>
                    <div class="elements-grid basic-shapes">
                        <button type="button" id="btn-add-rectangle" class="element-btn" title="Rectangle">
                            <span class="material-symbols-outlined">rectangle</span>
                        </button>
                        <button type="button" id="btn-add-ellipse" class="element-btn" title="Ellipse">
                            <span class="material-symbols-outlined">circle</span>
                        </button>
                        <button type="button" id="btn-add-line" class="element-btn" title="Line">
                            <span class="material-symbols-outlined">horizontal_rule</span>
                        </button>
                        <button type="button" id="btn-add-triangle" class="element-btn" title="Triangle">
                            <span class="material-symbols-outlined">change_history</span>
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

            <!-- Text Panel -->
            <div class="panel-view" id="panel-text">
                <div class="panel-header">
                    <h3>Text</h3>
                </div>
                <div class="panel-body">
                    <span class="toolbar-label">Add Text</span>
                    <div class="text-presets">
                        <button type="button" id="btn-add-heading" class="text-preset-btn">
                            <span class="preset-preview heading">Add a heading</span>
                        </button>
                        <button type="button" id="btn-add-subheading" class="text-preset-btn">
                            <span class="preset-preview subheading">Add a subheading</span>
                        </button>
                        <button type="button" id="btn-add-body" class="text-preset-btn">
                            <span class="preset-preview body">Add body text</span>
                        </button>
                    </div>

                    <span class="toolbar-label mt-3">Font Styles</span>
                    <div class="font-styles-grid">
                        <button type="button" class="font-style-btn" data-font="Inter">
                            <span style="font-family: Inter">Aa</span>
                            <span>Inter</span>
                        </button>
                        <button type="button" class="font-style-btn" data-font="Playfair Display">
                            <span style="font-family: 'Playfair Display'">Aa</span>
                            <span>Playfair</span>
                        </button>
                        <button type="button" class="font-style-btn" data-font="Great Vibes">
                            <span style="font-family: 'Great Vibes'">Aa</span>
                            <span>Great Vibes</span>
                        </button>
                        <button type="button" class="font-style-btn" data-font="Bebas Neue">
                            <span style="font-family: 'Bebas Neue'">Aa</span>
                            <span>Bebas</span>
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
                    <?php
                    // Fetch backgrounds from database
                    $allBackgrounds = Database::fetchAll(
                        "SELECT * FROM backgrounds WHERE is_active = 1 ORDER BY type, display_order"
                    );

                    $bgColors = array_filter($allBackgrounds, fn($b) => $b['type'] === 'color');
                    $bgGradients = array_filter($allBackgrounds, fn($b) => $b['type'] === 'gradient');
                    $bgImages = array_filter($allBackgrounds, fn($b) => $b['type'] === 'image');
                    $bgVideos = array_filter($allBackgrounds, fn($b) => $b['type'] === 'video');
                    ?>

                    <!-- Background Type Tabs -->
                    <div class="bg-tabs">
                        <button type="button" class="bg-tab active" data-bg-type="colors">Colors</button>
                        <button type="button" class="bg-tab" data-bg-type="gradients">Gradients</button>
                        <button type="button" class="bg-tab" data-bg-type="images">Images</button>
                        <button type="button" class="bg-tab" data-bg-type="videos">Videos</button>
                    </div>

                    <!-- Colors Grid -->
                    <div class="bg-grid-container" id="bg-colors">
                        <div class="bg-grid scrollable">
                            <?php foreach ($bgColors as $bg): ?>
                                <div class="bg-item bg-color-item" data-type="color"
                                    data-value="<?= Security::escape($bg['color_value']) ?>"
                                    style="background: <?= Security::escape($bg['color_value']) ?>;"
                                    title="<?= Security::escape($bg['name']) ?>">
                                    <?php if ($bg['is_premium']): ?><span class="premium-badge">★</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Gradients Grid -->
                    <div class="bg-grid-container hidden" id="bg-gradients">
                        <div class="bg-grid scrollable">
                            <?php foreach ($bgGradients as $bg): ?>
                                <div class="bg-item bg-gradient-item" data-type="gradient"
                                    data-value="<?= Security::escape($bg['gradient_value']) ?>"
                                    style="background: <?= Security::escape($bg['gradient_value']) ?>;"
                                    title="<?= Security::escape($bg['name']) ?>">
                                    <?php if ($bg['is_premium']): ?><span class="premium-badge">★</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Images Grid -->
                    <div class="bg-grid-container hidden" id="bg-images">
                        <div class="bg-grid scrollable">
                            <?php foreach ($bgImages as $bg): ?>
                                <div class="bg-item bg-image-item" data-type="image"
                                    data-src="<?= Security::escape($bg['file_path']) ?>"
                                    title="<?= Security::escape($bg['name']) ?>">
                                    <img src="<?= Security::escape($bg['file_path']) ?>"
                                        alt="<?= Security::escape($bg['name']) ?>">
                                    <?php if ($bg['is_premium']): ?><span class="premium-badge">★</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Upload custom -->
                        <div class="bg-upload-section">
                            <input type="file" id="slide-bg-image" accept="image/*" class="hidden">
                            <button type="button" id="btn-upload-bg" class="upload-btn-sm">
                                <span class="material-symbols-outlined">add</span> Upload
                            </button>
                        </div>
                    </div>

                    <!-- Videos Grid -->
                    <div class="bg-grid-container hidden" id="bg-videos">
                        <div class="bg-grid scrollable">
                            <?php foreach ($bgVideos as $bg): ?>
                                <div class="bg-item bg-video-item" data-type="video"
                                    data-src="<?= Security::escape($bg['file_path']) ?>"
                                    title="<?= Security::escape($bg['name']) ?>">
                                    <?php if ($bg['thumbnail_path']): ?>
                                        <img src="<?= Security::escape($bg['thumbnail_path']) ?>"
                                            alt="<?= Security::escape($bg['name']) ?>">
                                    <?php else: ?>
                                        <video src="<?= Security::escape($bg['file_path']) ?>" muted></video>
                                    <?php endif; ?>
                                    <span class="video-indicator">▶</span>
                                    <?php if ($bg['is_premium']): ?><span class="premium-badge">★</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (empty($allBackgrounds)): ?>
                        <p class="no-items-msg">No backgrounds. <a href="/admin/backgrounds.php" target="_blank">Add
                                some</a></p>
                    <?php endif; ?>
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

        <!-- Canvas Area with Slides -->
        <div class="builder-canvas-wrapper">
            <div class="canvas-container" id="canvas-container">
                <canvas id="template-canvas" width="1080" height="1920"></canvas>
                <div id="canvas-overlays" class="canvas-overlays">
                    <!-- Text elements will be rendered here -->
                </div>
            </div>

            <!-- Zoom Controls -->
            <div class="canvas-controls">
                <div class="zoom-controls">
                    <button type="button" id="btn-zoom-out" class="zoom-btn" title="Zoom Out">
                        <span class="material-symbols-outlined">remove</span>
                    </button>
                    <input type="range" id="zoom-slider" min="25" max="200" value="100" class="zoom-slider">
                    <button type="button" id="btn-zoom-in" class="zoom-btn" title="Zoom In">
                        <span class="material-symbols-outlined">add</span>
                    </button>
                    <button type="button" id="btn-zoom-fit" class="zoom-btn" title="Fit to Screen">
                        <span class="material-symbols-outlined">fit_screen</span>
                    </button>
                    <span id="canvas-zoom" class="zoom-percent">100%</span>
                </div>
                <div class="canvas-info">
                    <span>1080 × 1920</span>
                    <span class="separator">•</span>
                    <span id="current-slide-info">Slide 1</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fixed Bottom Timeline -->
<div class="builder-timeline" id="builder-timeline">
    <div class="timeline-header">
        <h4>TIMELINE</h4>
        <div class="timeline-controls">
            <span id="timeline-duration">3.0s</span>
        </div>
    </div>

    <div class="timeline-main">
        <!-- Slide Thumbnails Sidebar (compact vertical strip) -->
        <div class="timeline-slides-sidebar">
            <div id="slides-strip" class="slides-strip">
                <?php if (empty($slides)): ?>
                    <!-- Will be populated by JS if no slides exist -->
                <?php else: ?>
                    <?php foreach ($slides as $index => $slide): ?>
                        <div class="slide-thumb <?= $index === 0 ? 'active' : '' ?>" data-slide-id="<?= $slide['id'] ?>"
                            data-slide-order="<?= $slide['slide_order'] ?>"
                            style="background-color: <?= Security::escape($slide['background_color']) ?>;">
                            <span class="slide-duration"><?= $slide['duration_ms'] / 1000 ?>s</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="btn-add-slide" class="btn-add-slide-mini" title="Add Slide">
                <span class="material-symbols-outlined">add</span>
            </button>
        </div>

        <!-- Timeline Tracks Area -->
        <div class="timeline-tracks-area">
            <!-- Timeline Ruler at TOP with Playhead -->
            <div class="timeline-ruler-row">
                <div class="timeline-ruler" id="timeline-ruler">
                    <div class="playhead" id="timeline-playhead" style="left: 0%;">
                        <div class="playhead-head"></div>
                        <div class="playhead-line"></div>
                    </div>
                    <!-- Ruler marks will be generated by JS based on slide duration -->
                </div>
            </div>

            <!-- Element tracks (compact layers) -->
            <div id="element-tracks" class="element-tracks-container">
                <!-- Each element gets a compact track row - rendered by JS -->
            </div>
        </div>
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

<?php $content = ob_get_clean(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - VideoInvites Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Great+Vibes&family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/template-builder.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family: 'Inter', system-ui, sans-serif;
            color: #1e293b;
            overflow: hidden;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <?= $content ?>
</body>

</html>