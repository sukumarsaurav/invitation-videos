<?php
/**
 * Admin - Template Builder
 * Visual slide editor for creating video invitation templates
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';

$templateId = intval($_GET['id'] ?? 0);

// Get all templates for the Design panel
$allTemplates = Database::fetchAll("SELECT id, title, slug, thumbnail_url, category, is_active FROM templates ORDER BY created_at DESC");

// Check if a template is selected
$template = null;
$hasSelectedTemplate = false;
if ($templateId) {
    $template = Database::fetchOne("SELECT * FROM templates WHERE id = ?", [$templateId]);
    $hasSelectedTemplate = !!$template;
}

// Get existing slides (only if template selected)
$slides = [];
$fields = [];
if ($hasSelectedTemplate) {
    $slides = Database::fetchAll(
        "SELECT * FROM template_slides WHERE template_id = ? ORDER BY slide_order",
        [$templateId]
    );

    // Get template fields with slide assignments
    $fields = Database::fetchAll(
        "SELECT * FROM template_fields WHERE template_id = ? ORDER BY display_order",
        [$templateId]
    );
}

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
$pageTitle = $hasSelectedTemplate ? 'Template Builder: ' . $template['title'] : 'Template Builder';
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
            <?php if ($hasSelectedTemplate): ?>
                <a href="/admin/templates.php?action=edit&id=<?= $templateId ?>" class="btn-icon" title="Back to Template">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <h1 class="header-title"><?= Security::escape($template['title']) ?></h1>
            <?php else: ?>
                <a href="/admin/dashboard.php" class="btn-icon" title="Back to Dashboard">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <h1 class="header-title">Template Builder</h1>
            <?php endif; ?>
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
            <button class="icon-btn <?= !$hasSelectedTemplate ? 'active' : '' ?>" data-panel="design" title="Templates">
                <span class="material-symbols-outlined">design_services</span>
                <span class="icon-label">Design</span>
            </button>
            <button class="icon-btn <?= $hasSelectedTemplate ? 'active' : '' ?>" data-panel="fields" title="Fields">
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
            <button class="icon-btn" data-panel="color" title="Color">
                <span class="material-symbols-outlined">palette</span>
                <span class="icon-label">Color</span>
            </button>
            <button class="icon-btn" data-panel="position" title="Layers">
                <span class="material-symbols-outlined">layers</span>
                <span class="icon-label">Layers</span>
            </button>
            <button class="icon-btn" data-panel="settings" title="Slide Settings">
                <span class="material-symbols-outlined">tune</span>
                <span class="icon-label">Settings</span>
            </button>
        </div>

        <!-- Content Panel (slides in/out based on selection) -->
        <div class="content-panel open" id="content-panel">
            <!-- Design Panel - Template Selection -->
            <div class="panel-view <?= !$hasSelectedTemplate ? 'active' : '' ?>" id="panel-design">
                <div class="panel-header">
                    <h3>Select Template</h3>
                </div>
                <div class="panel-body">
                    <?php if ($hasSelectedTemplate): ?>
                        <div class="current-template-info"
                            style="margin-bottom: 1rem; padding: 0.75rem; background: var(--primary-color); border-radius: 0.5rem; color: white;">
                            <p style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 0.25rem;">Currently Editing:</p>
                            <p style="font-weight: 600;"><?= Security::escape($template['title']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="template-grid"
                        style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                        <!-- New Template Card -->
                        <button type="button" id="btn-new-template" class="new-template-card"
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; border-radius: 0.5rem; overflow: hidden; border: 2px dashed var(--border-color); background: transparent; transition: all 0.2s; cursor: pointer; aspect-ratio: 9/16; color: #64748b;">
                            <span class="material-symbols-outlined"
                                style="font-size: 2.5rem; margin-bottom: 0.5rem;">add</span>
                            <span style="font-size: 0.75rem; font-weight: 600;">New Template</span>
                        </button>
                        <?php foreach ($allTemplates as $tpl): ?>
                            <a href="/admin/template-builder.php?id=<?= $tpl['id'] ?>"
                                class="template-card <?= $tpl['id'] == $templateId ? 'selected' : '' ?>"
                                style="display: block; text-decoration: none; border-radius: 0.5rem; overflow: hidden; border: 2px solid <?= $tpl['id'] == $templateId ? 'var(--primary-color)' : 'var(--border-color)' ?>; background: var(--surface-color); transition: all 0.2s;">
                                <div style="aspect-ratio: 9/16; background: #f1f5f9; overflow: hidden;">
                                    <?php if (!empty($tpl['thumbnail_url'])): ?>
                                        <img src="<?= Security::escape($tpl['thumbnail_url']) ?>"
                                            alt="<?= Security::escape($tpl['title']) ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div
                                            style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                                            <span class="material-symbols-outlined"
                                                style="font-size: 2rem;">video_library</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="padding: 0.5rem;">
                                    <p
                                        style="font-size: 0.75rem; font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= Security::escape($tpl['title']) ?>
                                    </p>
                                    <div
                                        style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.25rem;">
                                        <span
                                            style="font-size: 0.625rem; color: #64748b; text-transform: capitalize;"><?= $tpl['category'] ?></span>
                                        <span
                                            style="font-size: 0.625rem; padding: 0.125rem 0.375rem; border-radius: 9999px; background: <?= $tpl['is_active'] ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $tpl['is_active'] ? '#16a34a' : '#64748b' ?>;"><?= $tpl['is_active'] ? 'Active' : 'Draft' ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>

                        <?php if (empty($allTemplates)): ?>
                            <div style="grid-column: span 2; text-align: center; padding: 2rem; color: #64748b;">
                                <span class="material-symbols-outlined"
                                    style="font-size: 3rem; opacity: 0.5;">video_library</span>
                                <p style="margin-top: 0.5rem;">No templates yet</p>
                                <a href="/admin/templates.php?action=new"
                                    style="color: var(--primary-color); font-weight: 600;">Create Template</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Fields Panel -->
            <div class="panel-view <?= $hasSelectedTemplate ? 'active' : '' ?>" id="panel-fields">
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
                <div class="panel-body">
                    <?php
                    // Fetch backgrounds from database
                    $allBackgrounds = Database::fetchAll(
                        "SELECT * FROM backgrounds WHERE is_active = 1 ORDER BY type, display_order"
                    );

                    $bgImages = array_filter($allBackgrounds, fn($b) => $b['type'] === 'image');
                    $bgVideos = array_filter($allBackgrounds, fn($b) => $b['type'] === 'video');
                    ?>

                    <!-- Background Type Tabs (Images and Videos only) -->
                    <div class="bg-tabs">
                        <button type="button" class="bg-tab active" data-bg-type="images">Images</button>
                        <button type="button" class="bg-tab" data-bg-type="videos">Videos</button>
                    </div>

                    <!-- Images Grid -->
                    <div class="bg-grid-container" id="bg-images">
                        <div class="bg-grid bg-grid-2col scrollable">
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
                        <div class="bg-grid bg-grid-2col scrollable">
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

            <!-- Color Panel (in left sidebar) -->
            <div class="panel-view" id="panel-color">
                <div class="panel-header">
                    <h3>Background Color</h3>
                </div>
                <div class="panel-body">
                    <!-- Current Background Info -->
                    <div class="current-bg-section" id="current-bg-info">
                        <span class="toolbar-label">Current Background</span>
                        <div class="current-bg-preview" id="current-bg-preview">
                            <div class="bg-preview-swatch" id="bg-preview-swatch"></div>
                            <span id="bg-preview-text">Color: #ffffff</span>
                            <button type="button" class="btn-remove-bg hidden" id="btn-remove-bg"
                                title="Remove Background">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </div>

                    <!-- Default Colors -->
                    <div class="color-section">
                        <span class="toolbar-label">Solid Colors</span>
                        <div class="color-grid color-grid-panel" id="panel-default-colors">
                            <div class="color-swatch-panel" data-color="#000000" style="background: #000000"></div>
                            <div class="color-swatch-panel" data-color="#374151" style="background: #374151"></div>
                            <div class="color-swatch-panel" data-color="#6b7280" style="background: #6b7280"></div>
                            <div class="color-swatch-panel" data-color="#d1d5db" style="background: #d1d5db"></div>
                            <div class="color-swatch-panel" data-color="#f3f4f6" style="background: #f3f4f6"></div>
                            <div class="color-swatch-panel" data-color="#ffffff"
                                style="background: #ffffff; border: 1px solid #e5e7eb"></div>
                            <div class="color-swatch-panel" data-color="#ef4444" style="background: #ef4444"></div>
                            <div class="color-swatch-panel" data-color="#f97316" style="background: #f97316"></div>
                            <div class="color-swatch-panel" data-color="#eab308" style="background: #eab308"></div>
                            <div class="color-swatch-panel" data-color="#22c55e" style="background: #22c55e"></div>
                            <div class="color-swatch-panel" data-color="#06b6d4" style="background: #06b6d4"></div>
                            <div class="color-swatch-panel" data-color="#3b82f6" style="background: #3b82f6"></div>
                            <div class="color-swatch-panel" data-color="#8b5cf6" style="background: #8b5cf6"></div>
                            <div class="color-swatch-panel" data-color="#ec4899" style="background: #ec4899"></div>
                            <div class="color-swatch-panel" data-color="#14b8a6" style="background: #14b8a6"></div>
                            <div class="color-swatch-panel" data-color="#6366f1" style="background: #6366f1"></div>
                            <div class="color-swatch-panel" data-color="#a855f7" style="background: #a855f7"></div>
                            <div class="color-swatch-panel" data-color="#f43f5e" style="background: #f43f5e"></div>
                        </div>
                    </div>

                    <!-- Custom Color Picker -->
                    <div class="color-section">
                        <span class="toolbar-label">Custom Color</span>
                        <div class="custom-color-row">
                            <input type="color" id="panel-custom-color" class="panel-color-input" value="#7c3aed">
                            <input type="text" id="panel-color-hex" class="panel-hex-input" value="#7c3aed"
                                placeholder="#000000">
                            <button type="button" id="btn-apply-custom-color"
                                class="btn btn-sm btn-primary">Apply</button>
                        </div>
                    </div>

                    <!-- Gradients -->
                    <div class="color-section">
                        <span class="toolbar-label">Gradients</span>
                        <div class="gradient-grid" id="panel-gradients">
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"></div>
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(135deg, #f093fb 0%, #f5576c 100%)"
                                style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)"></div>
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)"
                                style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)"></div>
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)"
                                style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)"></div>
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(135deg, #fa709a 0%, #fee140 100%)"
                                style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)"></div>
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(135deg, #30cfd0 0%, #330867 100%)"
                                style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%)"></div>
                            <div class="gradient-swatch"
                                data-gradient="linear-gradient(to top, #0f0c29, #302b63, #24243e)"
                                style="background: linear-gradient(to top, #0f0c29, #302b63, #24243e)"></div>
                            <div class="gradient-swatch" data-gradient="linear-gradient(to right, #f12711, #f5af19)"
                                style="background: linear-gradient(to right, #f12711, #f5af19)"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Position/Layers Panel (in left sidebar) -->
            <div class="panel-view" id="panel-position">
                <div class="panel-header">
                    <h3>Layers</h3>
                </div>
                <div class="panel-body">
                    <!-- Tabs -->
                    <div class="layers-tabs-inline">
                        <button type="button" class="layers-tab-inline active" data-tab="layers">Layers</button>
                        <button type="button" class="layers-tab-inline" data-tab="arrange">Arrange</button>
                    </div>

                    <!-- Layers Content -->
                    <div class="layers-content-inline" id="panel-layers-content">
                        <div class="layers-list-inline" id="panel-layers-list">
                            <!-- Dynamically populated by JS -->
                            <p class="hint-text">Select elements on canvas to manage layers</p>
                        </div>
                    </div>

                    <!-- Arrange Content -->
                    <div class="arrange-content-inline hidden" id="panel-arrange-content">
                        <div class="arrange-buttons-inline">
                            <button type="button" class="arrange-btn-inline" id="panel-bring-to-front"
                                title="Bring to Front">
                                <span class="material-symbols-outlined">flip_to_front</span> Bring to front
                            </button>
                            <button type="button" class="arrange-btn-inline" id="panel-bring-forward"
                                title="Bring Forward">
                                <span class="material-symbols-outlined">move_up</span> Bring forward
                            </button>
                            <button type="button" class="arrange-btn-inline" id="panel-send-backward"
                                title="Send Backward">
                                <span class="material-symbols-outlined">move_down</span> Send backward
                            </button>
                            <button type="button" class="arrange-btn-inline" id="panel-send-to-back"
                                title="Send to Back">
                                <span class="material-symbols-outlined">flip_to_back</span> Send to back
                            </button>
                        </div>
                    </div>
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
            <!-- Canvas Toolbar (shows when canvas is selected) -->
            <div class="canvas-toolbar hidden" id="canvas-toolbar">
                <button type="button" class="toolbar-btn" id="btn-reset-canvas" title="Reset">
                    <span class="material-symbols-outlined">restart_alt</span>
                </button>
                <span class="toolbar-divider"></span>
                <button type="button" class="toolbar-btn" id="btn-delete-slide" title="Delete Slide">
                    <span class="material-symbols-outlined">delete</span>
                </button>
                <button type="button" class="toolbar-btn" id="btn-duplicate-slide" title="Duplicate Slide">
                    <span class="material-symbols-outlined">content_copy</span>
                </button>
                <span class="toolbar-divider"></span>
                <button type="button" class="toolbar-btn premium-feature" id="btn-magic-bg"
                    title="Magic Background (AI)">
                    Magic BG <span class="material-symbols-outlined premium-icon">crown</span>
                </button>
                <span class="toolbar-divider"></span>
                <input type="color" id="canvas-bg-color" class="toolbar-color-picker" value="#ffffff"
                    title="Background Color">
                <span class="toolbar-divider"></span>
                <button type="button" class="toolbar-btn" id="btn-position" title="Position">
                    Position
                </button>
                <button type="button" class="toolbar-btn" id="btn-more-options" title="More Options">
                    <span class="material-symbols-outlined">more_vert</span>
                </button>
            </div>

            <div class="canvas-container" id="canvas-container">
                <canvas id="template-canvas" width="1080" height="1920"></canvas>
                <div id="canvas-overlays" class="canvas-overlays">
                    <!-- Text elements will be rendered here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fixed Bottom Timeline -->
<div class="builder-timeline" id="builder-timeline">
    <!-- Top Row: Time Ruler with Playhead -->
    <div class="timeline-ruler-row">
        <div class="timeline-track-label"></div>
        <div class="timeline-ruler" id="timeline-ruler">
            <div class="playhead" id="timeline-playhead" style="left: 0%;">
                <div class="playhead-head"></div>
                <div class="playhead-line"></div>
            </div>
            <!-- Ruler marks will be generated by JS based on total duration -->
        </div>
    </div>

    <!-- Element Tracks (visual preview bars) -->
    <div class="timeline-tracks-wrapper">
        <div id="element-tracks" class="element-tracks-container">
            <!-- Each element gets a track row - rendered by JS -->
        </div>
    </div>

    <!-- Bottom Row: Slide Duration Bar -->
    <div class="timeline-slide-bar">
        <div class="slide-bar-wrapper" id="slide-bar-wrapper">
            <?php if (!empty($slides)): ?>
                <?php foreach ($slides as $index => $slide): ?>
                    <div class="slide-duration-bar <?= $index === 0 ? 'active' : '' ?>" data-slide-id="<?= $slide['id'] ?>"
                        data-slide-order="<?= $slide['slide_order'] ?>" data-duration="<?= $slide['duration_ms'] ?>">
                        <span class="slide-duration-label"><?= number_format($slide['duration_ms'] / 1000, 1) ?>s</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" id="btn-add-slide" class="btn-add-slide-inline" title="Add Slide">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
</div>


<!-- Removed separate right-side Color Panel and Layers Panel - they are now in left sidebar -->

<!-- Create New Template Modal -->
<div id="create-template-modal" class="modal hidden">
    <div class="modal-backdrop" onclick="closeCreateTemplateModal()"></div>
    <div class="modal-content" style="width: 400px; max-width: 90vw;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #334155;">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #f1f5f9;">Create New Template</h3>
            <button type="button" onclick="closeCreateTemplateModal()" class="btn-icon">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="create-template-form">
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.5rem;">Template Name *</label>
                    <input type="text" id="new-template-name" name="name" required
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 0.875rem;"
                        placeholder="e.g., Wedding Classic">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.5rem;">Description</label>
                    <textarea id="new-template-description" name="description" rows="3"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 0.875rem; resize: vertical;"
                        placeholder="Brief description of the template..."></textarea>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.5rem;">Category</label>
                    <select id="new-template-category" name="category"
                        style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #334155; background: #0f172a; color: #f1f5f9; font-size: 0.875rem;">
                        <option value="wedding">Wedding</option>
                        <option value="birthday">Birthday</option>
                        <option value="corporate">Corporate</option>
                        <option value="festival">Festival</option>
                        <option value="baby-shower">Baby Shower</option>
                        <option value="graduation">Graduation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid #334155;">
                <button type="button" onclick="closeCreateTemplateModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-create-template-submit">
                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">add</span>
                    Create Template
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="modal hidden">
    <div class="modal-backdrop" onclick="closePreviewModal()"></div>
    <div class="modal-content preview-modal-content">
        <div class="preview-player" id="preview-player">
            <!-- Close Button (top-right overlay) -->
            <button type="button" class="preview-close-btn" onclick="closePreviewModal()" title="Close">
                <span class="material-symbols-outlined">close</span>
            </button>

            <!-- Canvas -->
            <div id="preview-container" class="preview-container">
                <canvas id="preview-canvas" width="1080" height="1920"></canvas>
            </div>

            <!-- Center Play/Pause Button (overlay) -->
            <button type="button" id="btn-play-overlay" class="preview-play-overlay" title="Play/Pause">
                <span class="material-symbols-outlined play-icon">play_arrow</span>
                <span class="material-symbols-outlined pause-icon hidden">pause</span>
            </button>

            <!-- Bottom Controls (overlay) -->
            <div class="preview-controls-overlay" id="preview-controls-overlay">
                <div class="preview-time-display">
                    <span id="preview-time-current" class="preview-time">0:00</span>
                    <span class="preview-time-separator">/</span>
                    <span id="preview-time-total" class="preview-time">0:00</span>
                </div>
                <div class="preview-timeline" id="player-timeline">
                    <div class="preview-timeline-track">
                        <div class="preview-timeline-progress" id="timeline-progress"></div>
                        <div class="preview-timeline-playhead" id="timeline-playhead"></div>
                    </div>
                </div>
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