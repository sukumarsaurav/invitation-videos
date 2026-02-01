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
            grid-template-columns: var(--sidebar-width, 280px) 1fr var(--properties-width, 350px);
            gap: 0;
            height: calc(100vh - 64px);
            --sidebar-width: 280px;
            --properties-width: 350px;
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

        .layer-item.hidden-layer {
            opacity: 0.5;
        }

        .layer-item.locked-layer {
            background: #fef3c7;
        }

        .btn-icon-sm.inactive,
        .btn-icon-sm.inactive i {
            color: #94a3b8;
        }

        .btn-icon-sm.active {
            color: #970747;
        }

        .btn-playback.active {
            background: rgba(151, 7, 71, 0.2);
            color: #970747;
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

        /* Enhanced Timeline Container */
        .timeline-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 140px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            border-top: 1px solid #334155;
            display: flex;
            flex-direction: column;
        }

        .timeline-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid #334155;
        }

        .timeline-controls .btn-playback {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #970747;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .timeline-controls .btn-playback:hover {
            background: #b80856;
            transform: scale(1.05);
        }

        .timeline-controls .btn-playback.active {
            background: #22c55e;
        }

        .timeline-info {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            gap: 16px;
        }

        .timeline-info span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .timeline-info .current-frame {
            color: #f1f5f9;
            font-weight: 600;
            min-width: 80px;
        }

        .timeline-zoom {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-zoom input[type="range"] {
            width: 80px;
            accent-color: #970747;
        }

        /* Timeline Track Area */
        .timeline-tracks {
            flex: 1;
            position: relative;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 8px 16px;
        }

        .timeline-ruler {
            height: 20px;
            position: relative;
            border-bottom: 1px solid #334155;
            margin-bottom: 8px;
        }

        .timeline-ruler-mark {
            position: absolute;
            color: #64748b;
            font-size: 9px;
            transform: translateX(-50%);
        }

        .timeline-ruler-mark::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 12px;
            width: 1px;
            height: 6px;
            background: #475569;
        }

        .timeline-slide-tracks {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
        }

        .timeline-slide-row {
            display: flex;
            align-items: center;
            gap: 2px;
            height: 28px;
        }

        .timeline-segment {
            height: 24px;
            background: linear-gradient(135deg, #970747, #7a053a);
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 0 8px;
            font-size: 10px;
            color: white;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .timeline-segment:hover {
            filter: brightness(1.1);
        }

        .timeline-segment.active {
            outline: 2px solid #22c55e;
            outline-offset: 1px;
        }

        .timeline-segment .slide-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        /* Layer timing bars inside slide */
        .timeline-layer-bars {
            position: absolute;
            bottom: 2px;
            left: 4px;
            right: 4px;
            height: 6px;
            display: flex;
            gap: 1px;
        }

        .timeline-layer-bar {
            height: 100%;
            border-radius: 2px;
            opacity: 0.7;
        }

        .timeline-layer-bar.text {
            background: #3b82f6;
        }

        .timeline-layer-bar.image {
            background: #22c55e;
        }

        /* Playhead */
        .timeline-playhead {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ef4444;
            z-index: 10;
            cursor: ew-resize;
            transition: left 0.05s linear;
        }

        .timeline-playhead::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -5px;
            width: 12px;
            height: 12px;
            background: #ef4444;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .timeline-playhead::after {
            content: '';
            position: absolute;
            top: 8px;
            left: -5px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 8px solid #ef4444;
        }

        /* Layer-level timeline tracks */
        .timeline-layer-row {
            height: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-left: 8px;
            border-left: 2px solid #334155;
            margin-left: 4px;
        }

        .timeline-layer-label {
            width: 70px;
            min-width: 70px;
            font-size: 10px;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .timeline-layer-track {
            flex: 1;
            height: 16px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
            position: relative;
        }

        .timeline-layer-segment {
            position: absolute;
            height: 100%;
            border-radius: 4px;
            cursor: pointer;
            transition: filter 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: rgba(255, 255, 255, 0.8);
        }

        .timeline-layer-segment:hover {
            filter: brightness(1.2);
        }

        .timeline-layer-segment.active {
            outline: 2px solid #22c55e;
            outline-offset: 1px;
        }

        .timeline-layer-segment.text {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .timeline-layer-segment.image {
            background: linear-gradient(90deg, #22c55e, #4ade80);
        }

        /* Drag handles for resizing */
        .timeline-resize-handle {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 8px;
            cursor: ew-resize;
            background: rgba(255, 255, 255, 0.4);
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 5;
        }

        .timeline-layer-segment:hover .timeline-resize-handle {
            opacity: 1;
        }

        .timeline-resize-handle.left {
            left: 0;
            border-radius: 4px 0 0 4px;
        }

        .timeline-resize-handle.right {
            right: 0;
            border-radius: 0 4px 4px 0;
        }

        .timeline-resize-handle:hover {
            background: rgba(255, 255, 255, 0.7);
        }

        /* Keyframe markers */
        .timeline-keyframe {
            position: absolute;
            width: 6px;
            height: 6px;
            background: #f59e0b;
            transform: rotate(45deg);
            top: 50%;
            margin-top: -3px;
            cursor: pointer;
            z-index: 6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .timeline-keyframe.enter {
            background: #22c55e;
        }

        .timeline-keyframe.exit {
            background: #ef4444;
        }

        /* Video Timeline Track */
        .timeline-video-track {
            display: none;
            background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 50%, #1e3a5f 100%);
            height: 32px;
            border-radius: 6px;
            margin: 8px 16px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .timeline-video-track.visible {
            display: block;
        }

        .timeline-video-progress {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: rgba(34, 197, 94, 0.6);
            pointer-events: none;
            transition: width 0.1s linear;
        }

        .timeline-video-playhead {
            position: absolute;
            top: 0;
            height: 100%;
            width: 3px;
            background: #ef4444;
            pointer-events: none;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
        }

        .timeline-video-duration {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            color: #f1f5f9;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .timeline-video-label {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .timeline-video-label i {
            font-size: 14px;
        }

        .timeline-video-track.duration-mismatch {
            border: 2px solid #f59e0b;
        }

        .timeline-video-track.duration-mismatch::after {
            content: '⚠ Duration mismatch';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            color: #f59e0b;
            font-weight: 600;
        }

        .preview-layer {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            transition: box-shadow 0.15s ease, outline 0.15s ease;
            user-select: none;
        }

        .preview-layer:hover {
            outline: 2px dashed rgba(151, 7, 71, 0.6);
            outline-offset: 4px;
        }

        .preview-layer.selected {
            outline: 2px solid #970747;
            outline-offset: 4px;
            box-shadow: 0 0 0 1px rgba(151, 7, 71, 0.3);
        }

        .preview-layer.dragging {
            cursor: grabbing;
            opacity: 0.9;
            outline: 2px solid #22c55e;
            outline-offset: 4px;
        }

        .preview-layer.text {
            color: white;
            text-align: center;
            white-space: nowrap;
        }

        .preview-layer.image {
            background: rgba(255, 255, 255, 0.2);
            border: 2px dashed rgba(255, 255, 255, 0.5);
        }

        .layer-label {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 9px;
            background: #970747;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
        }

        .preview-layer:hover .layer-label,
        .preview-layer.selected .layer-label {
            opacity: 1;
        }

        .preview-canvas-container {
            position: absolute;
            inset: 0;
            overflow: hidden;
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

        /* ========== RESPONSIVE DESIGN ========== */

        /* Tablet breakpoint */
        @media (max-width: 1200px) {
            .editor-container {
                --sidebar-width: 240px;
                --properties-width: 300px;
            }

            .preview-frame {
                width: 240px;
                height: 426px;
            }
        }

        /* Mobile breakpoint - collapsible sidebars */
        @media (max-width: 900px) {
            .editor-container {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr;
            }

            .editor-sidebar,
            .editor-properties {
                position: fixed;
                top: 64px;
                bottom: 0;
                z-index: 200;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .editor-sidebar {
                left: 0;
                width: 280px;
                transform: translateX(-100%);
            }

            .editor-sidebar.open {
                transform: translateX(0);
            }

            .editor-properties {
                right: 0;
                width: 320px;
                transform: translateX(100%);
                border-left: none;
            }

            .editor-properties.open {
                transform: translateX(0);
            }

            .mobile-toggle {
                display: flex !important;
            }

            .sidebar-overlay {
                display: block;
            }

            .sidebar-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }

            .preview-frame {
                width: 200px;
                height: 356px;
            }

            .timeline-container {
                height: 100px;
            }
        }

        /* Extra small screens */
        @media (max-width: 480px) {
            .editor-header h1 {
                font-size: 14px;
            }

            .header-actions .btn span {
                display: none;
            }

            .preview-frame {
                width: 160px;
                height: 284px;
            }
        }

        /* Mobile toggle buttons */
        .mobile-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .mobile-toggle:hover {
            background: #f1f5f9;
            color: #970747;
        }

        .mobile-toggle.active {
            background: #970747;
            border-color: #970747;
            color: white;
        }

        /* Overlay for mobile sidebars */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            top: 64px;
            background: rgba(0, 0, 0, 0.5);
            z-index: 150;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        /* Close button for mobile panels */
        .mobile-close-btn {
            display: none;
            position: absolute;
            top: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #f1f5f9;
            color: #64748b;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        @media (max-width: 900px) {
            .mobile-close-btn {
                display: flex;
            }
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
            <button type="button" class="mobile-toggle" id="toggleSlides" onclick="toggleMobileSidebar('slides')"
                title="Toggle Slides Panel">
                <i class="fas fa-layer-group"></i>
            </button>
            <button type="button" class="btn btn-secondary" onclick="toggleJsonMode()">
                <i class="fas fa-code"></i> <span>JSON Mode</span>
            </button>
            <button type="button" class="btn btn-primary" onclick="saveTemplate()">
                <i class="fas fa-save"></i> <span>Save</span>
            </button>
            <button type="button" class="mobile-toggle" id="toggleProperties"
                onclick="toggleMobileSidebar('properties')" title="Toggle Properties Panel">
                <i class="fas fa-sliders-h"></i>
            </button>
        </div>
    </div>

    <!-- Overlay for mobile sidebars -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebars()"></div>

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

    <div class="editor-container" id="editorContainer">
        <!-- Left Sidebar: Slides -->
        <div class="editor-sidebar" id="slidesSidebar">
            <button class="mobile-close-btn" onclick="closeMobileSidebars()">
                <i class="fas fa-times"></i>
            </button>
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
                <div style="display: flex; gap: 8px; align-items: center;">
                    <div style="display: flex; gap: 4px; align-items: center;" title="Preview Zoom">
                        <i class="fas fa-search-minus" style="font-size: 10px; color: #64748b;"></i>
                        <input type="range" id="previewZoom" min="50" max="150" value="100"
                            onchange="updatePreviewZoom(this.value)" style="width: 60px; height: 4px;">
                        <span id="previewZoomLabel"
                            style="font-size: 10px; color: #64748b; min-width: 32px;">100%</span>
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="button" id="btnCssPreview" class="btn btn-sm btn-primary"
                            onclick="setPreviewMode('css')" title="Simple CSS Preview">Simple</button>
                        <button type="button" id="btnRemotionPreview" class="btn btn-sm btn-secondary"
                            onclick="setPreviewMode('remotion')" title="Remotion Studio Preview">Remotion</button>
                    </div>
                </div>
            </div>
            <div class="preview-frame" id="previewFrame">
                <!-- CSS Preview content rendered here -->
            </div>
            <iframe id="remotionPreviewFrame" src=""
                style="display: none; width: 100%; height: 100%; border: none; background: #1a1a2e;" allow="autoplay">
            </iframe>
            <!-- Enhanced Timeline Container -->
            <div class="timeline-container" id="timelineContainer">
                <div class="timeline-controls">
                    <button type="button" class="btn-playback" id="btnPlayPause" onclick="togglePlayback()"
                        title="Play/Pause (Space)">
                        <i class="fas fa-play" id="playIcon"></i>
                    </button>
                    <button type="button" class="btn-playback" onclick="resetPlayhead()" title="Reset">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" class="btn-playback" onclick="stepFrame(-1)" title="Previous Frame (←)">
                        <i class="fas fa-step-backward"></i>
                    </button>
                    <button type="button" class="btn-playback" onclick="stepFrame(1)" title="Next Frame (→)">
                        <i class="fas fa-step-forward"></i>
                    </button>
                    <div class="timeline-info">
                        <span class="current-frame" id="currentFrameDisplay">Frame: 0</span>
                        <span id="totalDurationDisplay">Total: 0s</span>
                    </div>
                    <div class="timeline-zoom">
                        <i class="fas fa-search-minus" style="color: #64748b; font-size: 10px;"></i>
                        <input type="range" id="timelineZoom" min="50" max="200" value="100"
                            onchange="updateTimelineZoom(this.value)" title="Timeline Zoom">
                        <i class="fas fa-search-plus" style="color: #64748b; font-size: 10px;"></i>
                        <span id="timelineZoomIndicator"
                            style="font-size: 10px; color: #64748b; min-width: 32px;">100%</span>
                    </div>
                    <button type="button" id="btnSnapGrid" class="btn-playback" onclick="toggleSnapToGrid()"
                        title="Snap to Grid (G)" style="margin-left: 8px;">
                        <i class="fas fa-th"></i>
                    </button>
                </div>
                <!-- Video Background Timeline Track -->
                <div class="timeline-video-track" id="videoTimelineTrack">
                    <span class="timeline-video-label">
                        <i class="fas fa-film"></i>
                        <span id="videoTrackName">Video</span>
                    </span>
                    <div class="timeline-video-progress" id="videoProgress"></div>
                    <div class="timeline-video-playhead" id="videoPlayhead"></div>
                    <span class="timeline-video-duration" id="videoTrackDuration">0:00 / 0:00</span>
                </div>
                <div class="timeline-tracks" id="timelineTracks">
                    <div class="timeline-ruler" id="timelineRuler"></div>
                    <div class="timeline-slide-tracks" id="timelineSlideArea">
                        <!-- Populated by JavaScript -->
                    </div>
                    <div class="timeline-playhead" id="timelinePlayhead" style="left: 0;"></div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Properties -->
        <div class="editor-properties" id="propertiesPanel">
            <button class="mobile-close-btn" onclick="closeMobileSidebars()">
                <i class="fas fa-times"></i>
            </button>
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

        // ========== MOBILE SIDEBAR TOGGLE ==========
        function toggleMobileSidebar(panel) {
            const slidesSidebar = document.getElementById('slidesSidebar');
            const propertiesPanel = document.getElementById('propertiesPanel');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleSlides = document.getElementById('toggleSlides');
            const toggleProperties = document.getElementById('toggleProperties');

            if (panel === 'slides') {
                const isOpen = slidesSidebar.classList.toggle('open');
                propertiesPanel.classList.remove('open');
                toggleSlides.classList.toggle('active', isOpen);
                toggleProperties.classList.remove('active');
                overlay.classList.toggle('active', isOpen);
            } else if (panel === 'properties') {
                const isOpen = propertiesPanel.classList.toggle('open');
                slidesSidebar.classList.remove('open');
                toggleProperties.classList.toggle('active', isOpen);
                toggleSlides.classList.remove('active');
                overlay.classList.toggle('active', isOpen);
            }
        }

        function closeMobileSidebars() {
            const slidesSidebar = document.getElementById('slidesSidebar');
            const propertiesPanel = document.getElementById('propertiesPanel');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleSlides = document.getElementById('toggleSlides');
            const toggleProperties = document.getElementById('toggleProperties');

            slidesSidebar?.classList.remove('open');
            propertiesPanel?.classList.remove('open');
            overlay?.classList.remove('active');
            toggleSlides?.classList.remove('active');
            toggleProperties?.classList.remove('active');
        }

        // Close sidebars on window resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                closeMobileSidebars();
            }
        });

        // ========== UNDO/REDO SYSTEM ==========
        const historyStack = [];
        const redoStack = [];
        const MAX_HISTORY = 50;

        function saveState(action = 'edit') {
            const state = JSON.stringify(templateDef);
            historyStack.push({ action, state, slideIndex: selectedSlideIndex, layerIndex: selectedLayerIndex });
            if (historyStack.length > MAX_HISTORY) {
                historyStack.shift();
            }
            redoStack.length = 0; // Clear redo on new action
            console.log(`State saved: ${action} (${historyStack.length} in history)`);
        }

        function undo() {
            if (historyStack.length === 0) {
                console.log('Nothing to undo');
                return;
            }
            const current = JSON.stringify(templateDef);
            redoStack.push({ action: 'undo', state: current, slideIndex: selectedSlideIndex, layerIndex: selectedLayerIndex });

            const prev = historyStack.pop();
            templateDef = JSON.parse(prev.state);
            selectedSlideIndex = prev.slideIndex;
            selectedLayerIndex = prev.layerIndex;

            refreshAll();
            console.log('Undo performed');
        }

        function redo() {
            if (redoStack.length === 0) {
                console.log('Nothing to redo');
                return;
            }
            const current = JSON.stringify(templateDef);
            historyStack.push({ action: 'redo', state: current, slideIndex: selectedSlideIndex, layerIndex: selectedLayerIndex });

            const next = redoStack.pop();
            templateDef = JSON.parse(next.state);
            selectedSlideIndex = next.slideIndex;
            selectedLayerIndex = next.layerIndex;

            refreshAll();
            console.log('Redo performed');
        }

        function refreshAll() {
            renderSlideList();
            renderTimeline();
            renderPreview();
            if (selectedLayerIndex !== null && selectedSlideIndex !== null) {
                renderLayerProperties();
            } else if (selectedSlideIndex !== null) {
                renderSlideProperties();
            } else {
                document.getElementById('propertiesPanel').innerHTML = `
                    <div class="section-title">Properties</div>
                    <p style="color: #64748b; font-size: 13px;">Select a slide or layer to edit its properties.</p>
                `;
            }
        }

        // ========== KEYBOARD SHORTCUTS ==========
        document.addEventListener('keydown', (e) => {
            // Ignore if typing in input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

            // Undo: Cmd/Ctrl + Z
            if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                undo();
                return;
            }
            // Redo: Cmd/Ctrl + Shift + Z or Cmd/Ctrl + Y
            if ((e.metaKey || e.ctrlKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                e.preventDefault();
                redo();
                return;
            }
            // Save: Cmd/Ctrl + S
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                saveTemplate();
                return;
            }
            // Delete: Backspace or Delete
            if (e.key === 'Backspace' || e.key === 'Delete') {
                if (selectedLayerIndex !== null) {
                    e.preventDefault();
                    deleteLayer();
                }
                return;
            }
            // Play/Pause: Space
            if (e.key === ' ') {
                e.preventDefault();
                togglePlayback();
                return;
            }
            // Arrow keys: Move layer position
            if (selectedLayerIndex !== null && ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                e.preventDefault();
                saveState('nudge layer');
                const delta = e.shiftKey ? 10 : 1;
                const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
                if (!layer.position) layer.position = { x: 540, y: 500, anchor: 'center' };

                switch (e.key) {
                    case 'ArrowUp': layer.position.y -= delta; break;
                    case 'ArrowDown': layer.position.y += delta; break;
                    case 'ArrowLeft': layer.position.x -= delta; break;
                    case 'ArrowRight': layer.position.x += delta; break;
                }
                renderPreview();
                renderLayerProperties();
            }
            // Escape: Deselect
            if (e.key === 'Escape') {
                if (selectedLayerIndex !== null) {
                    selectedLayerIndex = null;
                    renderSlideList();
                    renderPreview();
                    if (selectedSlideIndex !== null) {
                        renderSlideProperties();
                    }
                }
            }
            // Copy: Cmd/Ctrl + C
            if ((e.metaKey || e.ctrlKey) && e.key === 'c') {
                if (selectedLayerIndex !== null) {
                    e.preventDefault();
                    copyLayer();
                }
                return;
            }
            // Paste: Cmd/Ctrl + V
            if ((e.metaKey || e.ctrlKey) && e.key === 'v') {
                if (clipboardLayer) {
                    e.preventDefault();
                    pasteLayer();
                }
                return;
            }
            // Duplicate: Cmd/Ctrl + D
            if ((e.metaKey || e.ctrlKey) && e.key === 'd') {
                if (selectedLayerIndex !== null) {
                    e.preventDefault();
                    duplicateLayer();
                }
                return;
            }
            // Toggle Grid: G
            if (e.key === 'g' || e.key === 'G') {
                e.preventDefault();
                toggleSnapToGrid();
                return;
            }
        });

        // ========== HELPER: FORMAT FIELD KEY FOR PREVIEW ==========
        function formatFieldKeyForPreview(fieldKey) {
            if (!fieldKey) return '{{Text}}';
            // Convert camelCase/snake_case to Title Case
            const formatted = fieldKey
                .replace(/([A-Z])/g, ' $1') // camelCase to spaces
                .replace(/_/g, ' ') // snake_case to spaces
                .replace(/\b\w/g, l => l.toUpperCase()) // Capitalize first letter of each word
                .trim();
            return '{{' + formatted + '}}';
        }

        // ========== GOOGLE FONTS LOADER ==========
        const loadedFonts = new Set();

        function loadGoogleFont(fontFamily) {
            if (!fontFamily || loadedFonts.has(fontFamily)) return;

            loadedFonts.add(fontFamily);
            const formattedName = fontFamily.replace(/\s+/g, '+');
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `https://fonts.googleapis.com/css2?family=${formattedName}:wght@400;500;600;700&display=swap`;
            document.head.appendChild(link);
            console.log('Loaded font:', fontFamily);
        }

        // Load all fonts from template on init
        function loadAllTemplateFonts() {
            if (!templateDef.slides) return;
            templateDef.slides.forEach(slide => {
                slide.layers?.forEach(layer => {
                    if (layer.type === 'text' && layer.style?.fontFamily) {
                        loadGoogleFont(layer.style.fontFamily);
                    }
                });
            });
        }

        // ========== DRAG-AND-DROP STATE ==========
        let isDragging = false;
        let dragLayerIndex = null;
        let dragStartX = 0;
        let dragStartY = 0;
        let dragLayerStartX = 0;
        let dragLayerStartY = 0;
        const PREVIEW_SCALE = 0.25;

        // ========== TIMELINE PLAYBACK STATE ==========
        let isPlaying = false;
        let currentFrame = 0;
        let playbackInterval = null;
        let timelineZoom = 1;
        let isPlayheadDragging = false;

        // ========== TIMELINE PLAYBACK FUNCTIONS ==========
        function togglePlayback() {
            if (isPlaying) {
                pausePlayback();
            } else {
                startPlayback();
            }
        }

        function startPlayback() {
            const fps = templateDef.fps || 30;
            const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0) || 90;

            isPlaying = true;
            updatePlayButtonState();

            // Start video playback if present
            const video = document.getElementById('bgVideo');
            if (video) {
                video.play().catch(() => { }); // Ignore autoplay policy errors
            }

            playbackInterval = setInterval(() => {
                currentFrame++;
                if (currentFrame >= totalFrames) {
                    currentFrame = 0; // Loop
                    // Reset video to beginning
                    if (video) video.currentTime = 0;
                }
                updatePlayheadPosition();
                updateCurrentFrameDisplay();

                // Frame-accurate animation update
                updatePreviewAtFrame(currentFrame);

                // Auto-select slide based on current frame
                const slideIndex = getSlideAtFrame(currentFrame);
                if (slideIndex !== selectedSlideIndex) {
                    selectSlide(slideIndex);
                }
            }, 1000 / fps);
        }

        function pausePlayback() {
            isPlaying = false;
            if (playbackInterval) {
                clearInterval(playbackInterval);
                playbackInterval = null;
            }
            updatePlayButtonState();

            // Pause video if present
            const video = document.getElementById('bgVideo');
            if (video) {
                video.pause();
            }
        }

        function resetPlayhead() {
            pausePlayback();
            currentFrame = 0;
            updatePlayheadPosition();
            updateCurrentFrameDisplay();
            if (templateDef.slides.length > 0) {
                selectSlide(0);
            }
        }

        function updatePlayButtonState() {
            const btn = document.getElementById('btnPlayPause');
            const icon = document.getElementById('playIcon');
            if (btn && icon) {
                if (isPlaying) {
                    btn.classList.add('active');
                    icon.className = 'fas fa-pause';
                } else {
                    btn.classList.remove('active');
                    icon.className = 'fas fa-play';
                }
            }
        }

        function updateTimelineZoom(value) {
            timelineZoom = parseInt(value) / 100;
            renderTimeline();
        }

        function updatePlayheadPosition() {
            const playhead = document.getElementById('timelinePlayhead');
            const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0) || 90;

            if (playhead) {
                const position = currentFrame * timelineZoom;
                playhead.style.left = position + 'px';
            }
        }

        function updateCurrentFrameDisplay() {
            const display = document.getElementById('currentFrameDisplay');
            const fps = templateDef.fps || 30;
            if (display) {
                const seconds = (currentFrame / fps).toFixed(1);
                display.textContent = `Frame: ${currentFrame} (${seconds}s)`;
            }
        }

        function getSlideAtFrame(frame) {
            for (let i = 0; i < templateDef.slides.length; i++) {
                const slide = templateDef.slides[i];
                if (frame >= slide.startFrame && frame < slide.startFrame + slide.durationFrames) {
                    return i;
                }
            }
            return 0;
        }

        function attachPlayheadDragHandler() {
            const playhead = document.getElementById('timelinePlayhead');
            const tracksArea = document.getElementById('timelineTracks');

            if (!playhead || !tracksArea) return;

            playhead.addEventListener('mousedown', (e) => {
                isPlayheadDragging = true;
                pausePlayback();
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isPlayheadDragging) return;

                const tracksRect = tracksArea.getBoundingClientRect();
                const x = e.clientX - tracksRect.left - 16; // Account for padding
                const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0) || 90;

                currentFrame = Math.max(0, Math.min(totalFrames - 1, Math.round(x / timelineZoom)));
                updatePlayheadPosition();
                updateCurrentFrameDisplay();

                // Update selected slide
                const slideIndex = getSlideAtFrame(currentFrame);
                if (slideIndex !== selectedSlideIndex) {
                    selectSlide(slideIndex);
                }
            });

            document.addEventListener('mouseup', () => {
                isPlayheadDragging = false;
            });
        }

        // ========== ANIMATION PREVIEW FUNCTIONS ==========
        let animationPreviewTimeout = null;

        function playLayerAnimation(layerIndex, phase = 'enter') {
            if (selectedSlideIndex === null) return;

            const layer = templateDef.slides[selectedSlideIndex].layers[layerIndex];
            if (!layer) return;

            const animConfig = layer.animation?.[phase];
            if (!animConfig || animConfig.type === 'none') return;

            const layerEl = document.querySelector(`.preview-layer[data-layer-index="${layerIndex}"]`);
            if (!layerEl) return;

            // Clear any existing animation
            stopLayerAnimation(layerIndex);

            // Get animation keyframes
            const keyframes = getAnimationKeyframes(animConfig.type, phase);
            const duration = (animConfig.durationFrames || 30) / (templateDef.fps || 30) * 1000;

            // Apply animation
            layerEl.style.animation = `${animConfig.type}-${phase} ${duration}ms ease-out forwards`;

            // Create dynamic keyframes if not exists
            createDynamicKeyframes(animConfig.type, phase);

            // Clear animation after completion
            animationPreviewTimeout = setTimeout(() => {
                layerEl.style.animation = '';
            }, duration + 100);
        }

        function stopLayerAnimation(layerIndex) {
            if (animationPreviewTimeout) {
                clearTimeout(animationPreviewTimeout);
                animationPreviewTimeout = null;
            }

            const layerEl = document.querySelector(`.preview-layer[data-layer-index="${layerIndex}"]`);
            if (layerEl) {
                layerEl.style.animation = '';
            }
        }

        function getAnimationKeyframes(type, phase) {
            const keyframeMap = {
                'fade-in': { from: { opacity: 0 }, to: { opacity: 1 } },
                'fade-out': { from: { opacity: 1 }, to: { opacity: 0 } },
                'slide-up': { from: { transform: 'translateY(50px)', opacity: 0 }, to: { transform: 'translateY(0)', opacity: 1 } },
                'slide-down': { from: { transform: 'translateY(-50px)', opacity: 0 }, to: { transform: 'translateY(0)', opacity: 1 } },
                'slide-left': { from: { transform: 'translateX(50px)', opacity: 0 }, to: { transform: 'translateX(0)', opacity: 1 } },
                'slide-right': { from: { transform: 'translateX(-50px)', opacity: 0 }, to: { transform: 'translateX(0)', opacity: 1 } },
                'zoom-in': { from: { transform: 'scale(0.5)', opacity: 0 }, to: { transform: 'scale(1)', opacity: 1 } },
                'zoom-out': { from: { transform: 'scale(1.5)', opacity: 0 }, to: { transform: 'scale(1)', opacity: 1 } },
                'bounce': { from: { transform: 'translateY(-30px)', opacity: 0 }, to: { transform: 'translateY(0)', opacity: 1 } },
                'rotate': { from: { transform: 'rotate(-180deg)', opacity: 0 }, to: { transform: 'rotate(0)', opacity: 1 } }
            };
            return keyframeMap[type] || keyframeMap['fade-in'];
        }

        function createDynamicKeyframes(type, phase) {
            const styleId = `anim-${type}-${phase}`;
            if (document.getElementById(styleId)) return;

            const keyframes = getAnimationKeyframes(type, phase);
            const fromStyle = Object.entries(keyframes.from).map(([k, v]) => `${k.replace(/([A-Z])/g, '-$1').toLowerCase()}: ${v}`).join('; ');
            const toStyle = Object.entries(keyframes.to).map(([k, v]) => `${k.replace(/([A-Z])/g, '-$1').toLowerCase()}: ${v}`).join('; ');

            const style = document.createElement('style');
            style.id = styleId;
            style.textContent = `
                @keyframes ${type}-${phase} {
                    from { ${fromStyle}; }
                    to { ${toStyle}; }
                }
            `;
            document.head.appendChild(style);
        }

        // ========== VIDEO DURATION DETECTION ==========
        function detectVideoDuration(videoEl) {
            if (!videoEl || !videoEl.duration) return;

            const durationSeconds = videoEl.duration;
            const fps = templateDef.fps || 30;
            const durationFrames = Math.ceil(durationSeconds * fps);

            // Store duration in background object
            if (selectedSlideIndex !== null) {
                const slide = templateDef.slides[selectedSlideIndex];
                if (slide.background) {
                    slide.background.duration = durationSeconds;
                    slide.background.durationFrames = durationFrames;
                }

                // Show duration notification
                console.log(`Video duration detected: ${durationSeconds.toFixed(1)}s (${durationFrames} frames)`);

                // Update properties panel if visible
                renderSlideProperties();
            }
        }

        // ========== PROPERTIES VIDEO PREVIEW CONTROLS ==========
        function togglePropertiesVideoPlayback() {
            const video = document.getElementById('propertiesVideoPreview');
            const icon = document.getElementById('propertiesVideoPlayIcon');
            if (!video) return;

            if (video.paused) {
                video.play();
                if (icon) icon.className = 'fas fa-pause';
            } else {
                video.pause();
                if (icon) icon.className = 'fas fa-play';
            }
        }

        // ========== STEP FRAME CONTROLS ==========
        function stepFrame(direction) {
            const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0) || 90;
            currentFrame = Math.max(0, Math.min(totalFrames - 1, currentFrame + direction));
            updatePlayheadPosition();
            updateCurrentFrameDisplay();
            updatePreviewAtFrame(currentFrame);

            // Update slide selection if needed
            const slideIndex = getSlideAtFrame(currentFrame);
            if (slideIndex !== selectedSlideIndex) {
                selectSlide(slideIndex);
            }
        }

        // ========== PREVIEW ZOOM CONTROLS ==========
        let previewZoomLevel = 100;

        function updatePreviewZoom(value) {
            previewZoomLevel = parseInt(value);
            const previewFrame = document.getElementById('previewFrame');
            const label = document.getElementById('previewZoomLabel');

            if (previewFrame) {
                previewFrame.style.transform = `scale(${previewZoomLevel / 100})`;
                previewFrame.style.transformOrigin = 'center center';
            }
            if (label) {
                label.textContent = `${previewZoomLevel}%`;
            }
        }

        // ========== LAYER VISIBILITY TOGGLE ==========
        function toggleLayerVisibility(slideIdx, layerIdx) {
            const layer = templateDef.slides[slideIdx].layers[layerIdx];
            layer.visible = !(layer.visible !== false); // Default to visible
            renderSlideList();
            renderPreview();
            renderTimeline();
        }

        function toggleLayerLock(slideIdx, layerIdx) {
            const layer = templateDef.slides[slideIdx].layers[layerIdx];
            layer.locked = !layer.locked;
            renderSlideList();
            renderPreview();
        }

        // ========== SNAP TO GRID ==========
        let snapToGrid = false;
        const GRID_SIZE = 20;

        function toggleSnapToGrid() {
            snapToGrid = !snapToGrid;
            const btn = document.getElementById('btnSnapGrid');
            if (btn) {
                btn.classList.toggle('active', snapToGrid);
            }
            console.log('Snap to grid:', snapToGrid);
        }

        function snapPosition(value) {
            if (!snapToGrid) return value;
            return Math.round(value / GRID_SIZE) * GRID_SIZE;
        }

        // ========== COPY/PASTE LAYERS ==========
        let clipboardLayer = null;

        function copyLayer() {
            if (selectedSlideIndex === null || selectedLayerIndex === null) return;
            clipboardLayer = JSON.parse(JSON.stringify(templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex]));
            clipboardLayer.id = null; // Will be regenerated on paste
            console.log('Layer copied to clipboard');
        }

        function pasteLayer() {
            if (!clipboardLayer || selectedSlideIndex === null) return;
            saveState('paste layer');

            const newLayer = JSON.parse(JSON.stringify(clipboardLayer));
            newLayer.id = newLayer.type + '_' + Date.now();
            // Offset position slightly to show paste
            if (newLayer.position) {
                newLayer.position.x += 20;
                newLayer.position.y += 20;
            }

            templateDef.slides[selectedSlideIndex].layers.push(newLayer);
            renderSlideList();
            renderPreview();
            renderTimeline();
            selectLayer(selectedSlideIndex, templateDef.slides[selectedSlideIndex].layers.length - 1);
        }

        function duplicateLayer() {
            if (selectedSlideIndex === null || selectedLayerIndex === null) return;
            copyLayer();
            pasteLayer();
        }

        // ========== TIMELINE SCRUBBING WITH ANIMATION ==========
        function scrubToFrame(frame) {
            const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0) || 90;
            currentFrame = Math.max(0, Math.min(totalFrames - 1, frame));
            updatePlayheadPosition();
            updateCurrentFrameDisplay();
            updatePreviewAtFrame(currentFrame);

            // Sync video background if present
            syncVideoToFrame(currentFrame);

            const slideIndex = getSlideAtFrame(currentFrame);
            if (slideIndex !== selectedSlideIndex) {
                selectSlide(slideIndex);
            }
        }

        function syncVideoToFrame(frame) {
            const video = document.getElementById('bgVideo');
            if (!video || !templateDef.slides[selectedSlideIndex]) return;

            const slide = templateDef.slides[selectedSlideIndex];
            const localFrame = frame - slide.startFrame;
            const fps = templateDef.fps || 30;
            video.currentTime = localFrame / fps;

            // Update video timeline UI
            if (video.duration) {
                updateVideoTimelinePosition(video.currentTime, video.duration);
            }
        }

        // ========== VIDEO TIMELINE CONTROL ==========
        let videoTimelineActive = false;

        function updateVideoTimelineVisibility() {
            const track = document.getElementById('videoTimelineTrack');
            if (!track) return;

            const slide = selectedSlideIndex !== null ? templateDef.slides[selectedSlideIndex] : null;
            const hasVideoBackground = slide?.background?.type === 'video' && slide?.background?.src;

            if (hasVideoBackground) {
                track.classList.add('visible');
                videoTimelineActive = true;
                updateVideoTrackInfo(slide);
            } else {
                track.classList.remove('visible');
                videoTimelineActive = false;
            }
        }

        function updateVideoTrackInfo(slide) {
            const nameEl = document.getElementById('videoTrackName');
            const durationEl = document.getElementById('videoTrackDuration');
            const track = document.getElementById('videoTimelineTrack');

            if (!slide?.background?.duration) {
                if (nameEl) nameEl.textContent = 'Loading...';
                if (durationEl) durationEl.textContent = '--:-- / --:--';
                return;
            }

            const videoDuration = slide.background.duration;
            const slideDuration = slide.durationFrames / (templateDef.fps || 30);

            if (nameEl) nameEl.textContent = 'Video BG';
            if (durationEl) durationEl.textContent = `${formatVideoTime(0)} / ${formatVideoTime(videoDuration)}`;

            // Check for duration mismatch
            if (track) {
                const mismatch = Math.abs(videoDuration - slideDuration) > 0.5;
                track.classList.toggle('duration-mismatch', mismatch);
            }
        }

        function formatVideoTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function updateVideoTimelinePosition(currentTime, duration) {
            const progress = document.getElementById('videoProgress');
            const playhead = document.getElementById('videoPlayhead');
            const durationEl = document.getElementById('videoTrackDuration');

            if (!duration) return;

            const percentage = (currentTime / duration) * 100;

            if (progress) progress.style.width = percentage + '%';
            if (playhead) playhead.style.left = percentage + '%';
            if (durationEl) durationEl.textContent = `${formatVideoTime(currentTime)} / ${formatVideoTime(duration)}`;
        }

        function attachVideoTimelineHandlers() {
            const track = document.getElementById('videoTimelineTrack');
            if (!track) return;

            let isScrubbing = false;

            const handleScrub = (e) => {
                const rect = track.getBoundingClientRect();
                const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
                const percentage = x / rect.width;

                const video = document.getElementById('bgVideo');
                if (video && video.duration) {
                    video.currentTime = percentage * video.duration;
                    updateVideoTimelinePosition(video.currentTime, video.duration);
                }
            };

            track.addEventListener('mousedown', (e) => {
                isScrubbing = true;
                pausePlayback(); // Pause timeline when manually scrubbing video
                handleScrub(e);
            });

            document.addEventListener('mousemove', (e) => {
                if (isScrubbing) handleScrub(e);
            });

            document.addEventListener('mouseup', () => {
                isScrubbing = false;
            });
        }

        // ========== TIMELINE ZOOM INDICATOR ==========
        function updateTimelineZoom(value) {
            const indicator = document.getElementById('timelineZoomIndicator');
            if (indicator) {
                indicator.textContent = `${value}%`;
            }
            renderTimeline();
        }

        function matchSlideDurationToVideo() {
            if (selectedSlideIndex === null) return;

            const slide = templateDef.slides[selectedSlideIndex];
            if (!slide.background?.durationFrames) {
                alert('No video duration available. Please wait for video to load.');
                return;
            }

            slide.durationFrames = slide.background.durationFrames;

            // Recalculate slide startFrames
            let currentFrame = 0;
            templateDef.slides.forEach(s => {
                s.startFrame = currentFrame;
                currentFrame += s.durationFrames;
            });

            renderSlideList();
            renderTimeline();
            renderSlideProperties();
        }

        // ========== CORE FUNCTIONS (defined first for onclick handlers) ==========

        function addSlide() {
            saveState('add slide');
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
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.error || `Server error: ${response.status}`);
                    }

                    if (result.success) {
                        updateSlideBackground('src', result.url);
                        renderSlideProperties(); // Refresh to show new URL
                        renderPreview();
                    } else {
                        alert('Upload failed: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    if (error.message.includes('Unexpected token')) {
                        alert('Upload failed: Server returned an invalid response. Please check if your session is still valid and try again.');
                    } else {
                        alert('Upload failed: ' + error.message);
                    }
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
            loadAllTemplateFonts(); // Pre-load all fonts from template
            renderSlideList();
            renderTimeline();
            if (templateDef.slides.length > 0) {
                selectSlide(0);
            }

            // Initialize video timeline handlers
            attachVideoTimelineHandlers();
            updateVideoTimelineVisibility();

            // Add timeline click-to-scrub
            const timelineTracks = document.getElementById('timelineTracks');
            if (timelineTracks) {
                let isScrubbing = false;

                const handleScrub = (e) => {
                    const rect = timelineTracks.getBoundingClientRect();
                    const x = e.clientX - rect.left + timelineTracks.scrollLeft;
                    const zoomLevel = parseInt(document.getElementById('timelineZoom')?.value || 100) / 100;
                    const frame = Math.round(x / zoomLevel);
                    scrubToFrame(frame);
                };

                timelineTracks.addEventListener('mousedown', (e) => {
                    // Only scrub if clicking on empty space or ruler
                    if (e.target.closest('.timeline-layer-segment') || e.target.closest('.timeline-slide-bar')) return;
                    isScrubbing = true;
                    handleScrub(e);
                });

                document.addEventListener('mousemove', (e) => {
                    if (isScrubbing) {
                        handleScrub(e);
                    }
                });

                document.addEventListener('mouseup', () => {
                    isScrubbing = false;
                });
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
                            <div class="layer-item ${selectedSlideIndex === index && selectedLayerIndex === li ? 'active' : ''} ${layer.visible === false ? 'hidden-layer' : ''} ${layer.locked ? 'locked-layer' : ''}" 
                                 onclick="event.stopPropagation(); selectLayer(${index}, ${li})">
                                <span>
                                    <i class="fas fa-${layer.type === 'text' ? 'font' : 'image'}"></i>
                                    ${layer.fieldKey || layer.id}
                                </span>
                                <div class="layer-actions" onclick="event.stopPropagation()">
                                    <button type="button" class="btn-icon-sm ${layer.visible === false ? 'inactive' : ''}" 
                                            onclick="toggleLayerVisibility(${index}, ${li})" 
                                            title="${layer.visible === false ? 'Show Layer' : 'Hide Layer'}">
                                        <i class="fas fa-${layer.visible === false ? 'eye-slash' : 'eye'}"></i>
                                    </button>
                                    <button type="button" class="btn-icon-sm ${layer.locked ? 'active' : ''}" 
                                            onclick="toggleLayerLock(${index}, ${li})" 
                                            title="${layer.locked ? 'Unlock Layer' : 'Lock Layer'}">
                                        <i class="fas fa-${layer.locked ? 'lock' : 'lock-open'}"></i>
                                    </button>
                                    <button type="button" class="btn-icon-sm" onclick="moveLayer(${index}, ${li}, -1)" title="Move Up" ${li === 0 ? 'disabled' : ''}>
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                    <button type="button" class="btn-icon-sm" onclick="moveLayer(${index}, ${li}, 1)" title="Move Down" ${li === slide.layers.length - 1 ? 'disabled' : ''}>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </li>
            `).join('');
        }

        function renderTimeline() {
            const totalFrames = templateDef.slides.reduce((sum, s) => Math.max(sum, s.startFrame + s.durationFrames), 0) || 90;
            const fps = templateDef.fps || 30;
            const zoomLevel = parseInt(document.getElementById('timelineZoom')?.value || 100) / 100;
            const trackWidth = totalFrames * zoomLevel;

            // Update duration display
            const durationDisplay = document.getElementById('totalDurationDisplay');
            if (durationDisplay) {
                durationDisplay.textContent = `Total: ${(totalFrames / fps).toFixed(1)}s (${totalFrames} frames)`;
            }

            // Render ruler with time marks
            const ruler = document.getElementById('timelineRuler');
            if (ruler) {
                ruler.style.width = trackWidth + 'px';
                let rulerHtml = '';
                for (let sec = 0; sec <= totalFrames / fps; sec++) {
                    const leftPos = sec * fps * zoomLevel;
                    rulerHtml += `<span class="timeline-ruler-mark" style="left: ${leftPos}px;">${sec}s</span>`;
                }
                ruler.innerHTML = rulerHtml;
            }

            // Render slide tracks with individual layer rows
            const slideArea = document.getElementById('timelineSlideArea');
            if (slideArea) {
                slideArea.style.width = trackWidth + 'px';

                let html = '';
                templateDef.slides.forEach((slide, slideIdx) => {
                    const slideLeft = slide.startFrame * zoomLevel;
                    const slideWidth = slide.durationFrames * zoomLevel;
                    const isSlideActive = selectedSlideIndex === slideIdx;

                    // Slide header row
                    html += `
                        <div class="timeline-slide-row" style="position: relative;">
                            <div class="timeline-segment ${isSlideActive ? 'active' : ''}" 
                                 style="margin-left: ${slideLeft}px; width: ${slideWidth}px; min-width: 40px;"
                                 onclick="selectSlide(${slideIdx})"
                                 title="${slide.name}: ${(slide.durationFrames / fps).toFixed(1)}s">
                                <span class="slide-label">${slide.name || 'Slide ' + (slideIdx + 1)}</span>
                            </div>
                        </div>
                    `;

                    // Layer rows (show for all slides, but highlight active)
                    if (slide.layers && slide.layers.length > 0) {
                        slide.layers.forEach((layer, layerIdx) => {
                            const timing = layer.timing || { startFrame: 0, endFrame: slide.durationFrames };
                            const layerLeft = (slide.startFrame + timing.startFrame) * zoomLevel;
                            const layerWidth = (timing.endFrame - timing.startFrame) * zoomLevel;
                            const isLayerActive = isSlideActive && selectedLayerIndex === layerIdx;

                            // Animation keyframe positions
                            const enterAnim = layer.animation?.enter;
                            const exitAnim = layer.animation?.exit;
                            let keyframesHtml = '';

                            if (enterAnim && enterAnim.type !== 'none') {
                                const enterEnd = (enterAnim.delay || 0) + (enterAnim.durationFrames || 30);
                                const keyframeLeft = enterEnd * zoomLevel / (timing.endFrame - timing.startFrame) * layerWidth;
                                keyframesHtml += `<div class="timeline-keyframe enter" style="left: ${Math.min(keyframeLeft, layerWidth - 6)}px;" title="Enter animation end"></div>`;
                            }

                            if (exitAnim && exitAnim.type && exitAnim.type !== 'none') {
                                const exitStart = (timing.endFrame - timing.startFrame) - (exitAnim.durationFrames || 30) - (exitAnim.delay || 0);
                                const keyframeLeft = exitStart * zoomLevel / (timing.endFrame - timing.startFrame) * layerWidth;
                                keyframesHtml += `<div class="timeline-keyframe exit" style="left: ${Math.max(0, keyframeLeft)}px;" title="Exit animation start"></div>`;
                            }

                            html += `
                                <div class="timeline-layer-row">
                                    <span class="timeline-layer-label" title="${layer.fieldKey || layer.id}">
                                        <i class="fas fa-${layer.type === 'text' ? 'font' : 'image'}" style="margin-right: 4px; font-size: 8px;"></i>
                                        ${layer.fieldKey || layer.type}
                                    </span>
                                    <div class="timeline-layer-track" style="position: relative;">
                                        <div class="timeline-layer-segment ${layer.type} ${isLayerActive ? 'active' : ''}"
                                             style="left: ${layerLeft}px; width: ${Math.max(8, layerWidth)}px;"
                                             data-slide="${slideIdx}" data-layer="${layerIdx}"
                                             onclick="event.stopPropagation(); selectLayer(${slideIdx}, ${layerIdx})"
                                             title="${layer.fieldKey}: ${(timing.startFrame / fps).toFixed(1)}s - ${(timing.endFrame / fps).toFixed(1)}s">
                                            <div class="timeline-resize-handle left" data-handle="start"></div>
                                            ${keyframesHtml}
                                            <div class="timeline-resize-handle right" data-handle="end"></div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                });

                slideArea.innerHTML = html;
                attachTimelineResizeHandlers();
            }

            // Attach playhead drag handler
            attachPlayheadDragHandler();
        }

        // ========== TIMELINE RESIZE HANDLERS ==========
        let isResizingTimeline = false;
        let resizeData = null;

        function attachTimelineResizeHandlers() {
            const handles = document.querySelectorAll('.timeline-resize-handle');
            handles.forEach(handle => {
                handle.addEventListener('mousedown', startTimelineResize);
            });
        }

        function startTimelineResize(e) {
            e.stopPropagation();
            e.preventDefault();

            const segment = e.target.closest('.timeline-layer-segment');
            if (!segment) return;

            const slideIdx = parseInt(segment.dataset.slide);
            const layerIdx = parseInt(segment.dataset.layer);
            const isStart = e.target.dataset.handle === 'start';
            const zoomLevel = parseInt(document.getElementById('timelineZoom')?.value || 100) / 100;

            isResizingTimeline = true;
            resizeData = { slideIdx, layerIdx, isStart, startX: e.clientX, zoomLevel };

            document.addEventListener('mousemove', handleTimelineResize);
            document.addEventListener('mouseup', endTimelineResize);
        }

        function handleTimelineResize(e) {
            if (!isResizingTimeline || !resizeData) return;

            const deltaX = e.clientX - resizeData.startX;
            const deltaFrames = Math.round(deltaX / resizeData.zoomLevel);

            if (deltaFrames === 0) return;

            const layer = templateDef.slides[resizeData.slideIdx].layers[resizeData.layerIdx];
            const slide = templateDef.slides[resizeData.slideIdx];

            if (!layer.timing) {
                layer.timing = { startFrame: 0, endFrame: slide.durationFrames };
            }

            if (resizeData.isStart) {
                const newStart = Math.max(0, Math.min(layer.timing.startFrame + deltaFrames, layer.timing.endFrame - 5));
                layer.timing.startFrame = newStart;
            } else {
                const newEnd = Math.min(slide.durationFrames, Math.max(layer.timing.endFrame + deltaFrames, layer.timing.startFrame + 5));
                layer.timing.endFrame = newEnd;
            }

            resizeData.startX = e.clientX;
            renderTimeline();
        }

        function endTimelineResize() {
            isResizingTimeline = false;
            resizeData = null;
            document.removeEventListener('mousemove', handleTimelineResize);
            document.removeEventListener('mouseup', endTimelineResize);

            // Update properties panel if a layer is selected
            if (selectedLayerIndex !== null) {
                renderLayerProperties();
            }
        }

        function renderPreview() {
            const frame = document.getElementById('previewFrame');
            if (selectedSlideIndex === null || !templateDef.slides[selectedSlideIndex]) {
                frame.innerHTML = '<p style="color: #666;">No slide selected</p>';
                return;
            }

            const slide = templateDef.slides[selectedSlideIndex];
            const bgColor = slide.background?.type === 'color' ? slide.background.src : '#2d2d44';
            let bgStyle = `background: ${bgColor};`;
            let bgMediaHtml = '';

            // Handle video/image backgrounds
            if (slide.background?.src && !slide.background.src.includes('{{')) {
                if (slide.background?.type === 'video') {
                    bgMediaHtml = `
                        <video id="bgVideo" muted playsinline
                               style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;"
                               onloadedmetadata="detectVideoDuration(this); updateVideoTimelineVisibility();"
                               ontimeupdate="updateVideoTimelinePosition(this.currentTime, this.duration)">
                            <source src="${slide.background.src}" type="video/mp4">
                        </video>
                    `;
                    bgStyle = 'background: #000;';
                } else if (slide.background?.type === 'image') {
                    bgMediaHtml = `
                        <img src="${slide.background.src}" 
                             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;"
                             alt="Background" />
                    `;
                    bgStyle = 'background: #000;';
                }
            }

            let layersHtml = slide.layers.map((layer, layerIdx) => {
                const x = (layer.position?.x || 540) * PREVIEW_SCALE;
                const y = (layer.position?.y || 500) * PREVIEW_SCALE;
                const isSelected = selectedLayerIndex === layerIdx;
                const selectedClass = isSelected ? 'selected' : '';

                if (layer.type === 'text') {
                    const fontSize = (layer.style?.fontSize || 48) * PREVIEW_SCALE;
                    const fontFamily = layer.style?.fontFamily || 'Inter';
                    // Load font if not already loaded
                    loadGoogleFont(fontFamily);

                    // Format display text: use defaultValue if set, otherwise show formatted fieldKey
                    const displayText = layer.defaultValue || formatFieldKeyForPreview(layer.fieldKey);

                    return `
                        <div class="preview-layer text ${selectedClass}" 
                             data-layer-index="${layerIdx}"
                             style="
                                left: ${x}px;
                                top: ${y}px;
                                transform: translate(-50%, -50%);
                                font-size: ${fontSize}px;
                                font-family: '${fontFamily}', sans-serif;
                                color: ${layer.style?.color || '#FFFFFF'};
                                font-weight: ${layer.style?.fontWeight || 'normal'};
                                letter-spacing: ${(layer.style?.letterSpacing || 0) * PREVIEW_SCALE}px;
                                text-shadow: ${layer.style?.textShadow || 'none'};
                                z-index: 1;
                            ">
                            <span class="layer-label">${layer.fieldKey || 'text'}</span>
                            ${displayText}
                        </div>
                    `;
                } else if (layer.type === 'image') {
                    const w = (layer.size?.width || 200) * PREVIEW_SCALE;
                    const h = (layer.size?.height || 200) * PREVIEW_SCALE;
                    const borderRadius = layer.style?.borderRadius ? (layer.style.borderRadius * PREVIEW_SCALE) + 'px' : '50%';

                    return `
                        <div class="preview-layer image ${selectedClass}" 
                             data-layer-index="${layerIdx}"
                             style="
                                left: ${x}px;
                                top: ${y}px;
                                transform: translate(-50%, -50%);
                                width: ${w}px;
                                height: ${h}px;
                                border-radius: ${borderRadius};
                                z-index: 1;
                            ">
                            <span class="layer-label">${layer.fieldKey || 'image'}</span>
                            <i class="fas fa-image" style="font-size: ${Math.min(w, h) * 0.4}px; color: rgba(255,255,255,0.5);"></i>
                        </div>
                    `;
                }
                return '';
            }).join('');

            frame.innerHTML = `
                <div class="preview-canvas-container" style="${bgStyle}" id="previewCanvas">
                    ${bgMediaHtml}
                    ${layersHtml}
                </div>
            `;

            // Attach event listeners for drag-and-drop
            attachPreviewEventListeners();
        }

        // ========== FRAME-ACCURATE PREVIEW ==========
        function updatePreviewAtFrame(globalFrame) {
            if (selectedSlideIndex === null) return;

            const slide = templateDef.slides[selectedSlideIndex];
            if (!slide) return;

            slide.layers.forEach((layer, idx) => {
                const layerEl = document.querySelector(`.preview-layer[data-layer-index="${idx}"]`);
                if (!layerEl) return;

                // Check layer visibility
                const isVisible = getLayerVisibilityAtFrame(layer, slide, globalFrame);

                if (!isVisible) {
                    layerEl.style.opacity = '0';
                    layerEl.style.pointerEvents = 'none';
                    return;
                }

                layerEl.style.pointerEvents = '';

                // Get animation state
                const animState = getLayerAnimationState(layer, slide, globalFrame);
                const styles = applyAnimationTransform(animState.type, animState.phase, animState.progress);

                layerEl.style.opacity = styles.opacity ?? 1;
                layerEl.style.transform = `translate(-50%, -50%) ${styles.transform || ''}`;
            });
        }

        function getLayerVisibilityAtFrame(layer, slide, globalFrame) {
            const slideStart = slide.startFrame;
            const timing = layer.timing || { startFrame: 0, endFrame: slide.durationFrames };
            const layerStart = slideStart + timing.startFrame;
            const layerEnd = slideStart + timing.endFrame;

            return globalFrame >= layerStart && globalFrame < layerEnd;
        }

        function getLayerAnimationState(layer, slide, globalFrame) {
            const timing = layer.timing || { startFrame: 0, endFrame: slide.durationFrames };
            const layerStart = slide.startFrame + timing.startFrame;
            const localFrame = globalFrame - layerStart;
            const localDuration = timing.endFrame - timing.startFrame;

            // Enter animation
            const enterAnim = layer.animation?.enter;
            if (enterAnim && enterAnim.type !== 'none') {
                const enterStart = enterAnim.delay || 0;
                const enterEnd = enterStart + (enterAnim.durationFrames || 30);

                if (localFrame >= enterStart && localFrame < enterEnd) {
                    const progress = (localFrame - enterStart) / (enterEnd - enterStart);
                    return { phase: 'enter', progress, type: enterAnim.type };
                }
            }

            // Exit animation
            const exitAnim = layer.animation?.exit;
            if (exitAnim && exitAnim.type && exitAnim.type !== 'none') {
                const exitDuration = exitAnim.durationFrames || 30;
                const exitStart = localDuration - exitDuration - (exitAnim.delay || 0);

                if (localFrame >= exitStart) {
                    const progress = (localFrame - exitStart) / exitDuration;
                    return { phase: 'exit', progress: Math.min(1, progress), type: exitAnim.type };
                }
            }

            return { phase: 'idle', progress: 1, type: 'none' };
        }

        function applyAnimationTransform(type, phase, progress) {
            const eased = easeOutQuad(phase === 'exit' ? 1 - progress : progress);

            const transforms = {
                'none': { opacity: 1, transform: '' },
                'fade-in': { opacity: eased, transform: '' },
                'fade-out': { opacity: 1 - eased, transform: '' },
                'slide-up': { opacity: eased, transform: `translateY(${(1 - eased) * 30}px)` },
                'slide-down': { opacity: eased, transform: `translateY(${(eased - 1) * 30}px)` },
                'slide-left': { opacity: eased, transform: `translateX(${(1 - eased) * 30}px)` },
                'slide-right': { opacity: eased, transform: `translateX(${(eased - 1) * 30}px)` },
                'zoom-in': { opacity: eased, transform: `scale(${0.5 + eased * 0.5})` },
                'zoom-out': { opacity: eased, transform: `scale(${1.5 - eased * 0.5})` },
                'bounce': { opacity: eased, transform: `translateY(${-20 * (1 - eased)}px)` },
                'rotate': { opacity: eased, transform: `rotate(${-180 * (1 - eased)}deg)` }
            };

            return transforms[type] || transforms['fade-in'];
        }

        function easeOutQuad(t) {
            return 1 - (1 - t) * (1 - t);
        }

        // ========== DRAG-AND-DROP HANDLERS ==========
        function attachPreviewEventListeners() {
            const canvas = document.getElementById('previewCanvas');
            if (!canvas) return;

            // Click to select layer
            canvas.addEventListener('click', (e) => {
                const layerEl = e.target.closest('.preview-layer');
                if (layerEl && !isDragging) {
                    const layerIdx = parseInt(layerEl.dataset.layerIndex);
                    selectLayer(selectedSlideIndex, layerIdx);
                }
            });

            // Mouse down to start drag
            canvas.addEventListener('mousedown', (e) => {
                const layerEl = e.target.closest('.preview-layer');
                if (!layerEl) return;

                const layerIdx = parseInt(layerEl.dataset.layerIndex);
                const layer = templateDef.slides[selectedSlideIndex].layers[layerIdx];

                isDragging = true;
                dragLayerIndex = layerIdx;
                dragStartX = e.clientX;
                dragStartY = e.clientY;
                dragLayerStartX = layer.position?.x || 540;
                dragLayerStartY = layer.position?.y || 500;

                layerEl.classList.add('dragging');
                e.preventDefault();
            });

            // Mouse move to drag
            document.addEventListener('mousemove', handleDragMove);
            document.addEventListener('mouseup', handleDragEnd);
        }

        function handleDragMove(e) {
            if (!isDragging || dragLayerIndex === null) return;

            const deltaX = (e.clientX - dragStartX) / PREVIEW_SCALE;
            const deltaY = (e.clientY - dragStartY) / PREVIEW_SCALE;

            const layer = templateDef.slides[selectedSlideIndex].layers[dragLayerIndex];
            layer.position = layer.position || { x: 540, y: 500, anchor: 'center' };
            layer.position.x = Math.round(dragLayerStartX + deltaX);
            layer.position.y = Math.round(dragLayerStartY + deltaY);

            // Update preview position visually without full re-render
            const layerEl = document.querySelector(`.preview-layer[data-layer-index="${dragLayerIndex}"]`);
            if (layerEl) {
                layerEl.style.left = (layer.position.x * PREVIEW_SCALE) + 'px';
                layerEl.style.top = (layer.position.y * PREVIEW_SCALE) + 'px';
            }
        }

        function handleDragEnd(e) {
            if (!isDragging) return;

            const layerEl = document.querySelector(`.preview-layer[data-layer-index="${dragLayerIndex}"]`);
            if (layerEl) {
                layerEl.classList.remove('dragging');
            }

            // Select the dragged layer and update properties panel
            if (dragLayerIndex !== null) {
                selectLayer(selectedSlideIndex, dragLayerIndex);
            }

            isDragging = false;
            dragLayerIndex = null;
        }

        function selectSlide(index) {
            selectedSlideIndex = index;
            selectedLayerIndex = null;
            renderSlideList();
            renderPreview();
            renderSlideProperties();
            updateVideoTimelineVisibility();
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
                    ${slide.background?.type === 'color' ? `
                        <!-- COLOR BACKGROUND: Native color picker + text input -->
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="color" 
                                   value="${slide.background?.src?.startsWith('#') ? slide.background.src : '#1a1a2e'}" 
                                   onchange="updateSlideBackground('src', this.value)"
                                   style="width: 50px; height: 36px; padding: 2px; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer;">
                            <input type="text" value="${slide.background?.src || '#1a1a2e'}" 
                                   placeholder="#1a1a2e"
                                   onchange="updateSlideBackground('src', this.value)"
                                   style="flex: 1;">
                        </div>
                        <div style="display: flex; gap: 4px; margin-top: 8px; flex-wrap: wrap;">
                            ${['#1a1a2e', '#0f172a', '#1e293b', '#2d2d44', '#1f2937', '#111827', '#000000', '#ffffff'].map(c => `
                                <button type="button" 
                                        onclick="updateSlideBackground('src', '${c}')" 
                                        style="width: 24px; height: 24px; background: ${c}; border: 2px solid ${slide.background?.src === c ? '#970747' : '#e2e8f0'}; border-radius: 4px; cursor: pointer;"
                                        title="${c}"></button>
                            `).join('')}
                        </div>
                    ` : `
                        <!-- VIDEO/IMAGE BACKGROUND: Upload + URL input -->
                        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <button type="button" id="uploadAssetBtn" class="btn btn-secondary btn-sm" 
                                    onclick="uploadBackgroundAsset('${slide.background?.type || 'video'}')" style="flex-shrink: 0;">
                                <i class="fas fa-upload"></i> Upload ${slide.background?.type === 'image' ? 'Image' : 'Video'}
                            </button>
                            <span style="font-size: 11px; color: #64748b; display: flex; align-items: center;">
                                Max: ${slide.background?.type === 'image' ? '10MB' : '50MB'}
                            </span>
                        </div>
                        <input type="text" value="${slide.background?.src || ''}" 
                               placeholder="S3 URL or {{fieldKey}}"
                               onchange="updateSlideBackground('src', this.value)"
                               style="${slide.background?.src ? 'font-size: 11px;' : ''}">
                        
                        ${slide.background?.src && !slide.background.src.includes('{{') ? `
                            <!-- MEDIA PREVIEW -->
                            <div style="margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; position: relative;">
                                ${slide.background?.type === 'video' ? `
                                    <video id="propertiesVideoPreview" 
                                           style="width: 100%; height: 100px; object-fit: cover; background: #000;"
                                           onloadeddata="this.parentElement.querySelector('.loading-spinner')?.remove()"
                                           onerror="this.parentElement.innerHTML='<p style=\\'color: #ef4444; padding: 12px; font-size: 12px;\\'><i class=\\'fas fa-exclamation-triangle\\'></i> Failed to load video</p>'">
                                        <source src="${slide.background.src}" type="video/mp4">
                                    </video>
                                    <div class="loading-spinner" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
                                        <i class="fas fa-spinner fa-spin" style="color: white; font-size: 20px;"></i>
                                    </div>
                                    <div style="position: absolute; bottom: 4px; right: 4px; display: flex; gap: 4px;">
                                        <button type="button" onclick="togglePropertiesVideoPlayback()" 
                                                style="background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 11px;">
                                            <i class="fas fa-play" id="propertiesVideoPlayIcon"></i>
                                        </button>
                                    </div>
                                ` : `
                                    <img src="${slide.background.src}" 
                                         style="width: 100%; height: 100px; object-fit: cover;"
                                         onload="this.parentElement.querySelector('.loading-spinner')?.remove()"
                                         onerror="this.parentElement.innerHTML='<p style=\\'color: #ef4444; padding: 12px; font-size: 12px;\\'><i class=\\'fas fa-exclamation-triangle\\'></i> Failed to load image</p>'">
                                    <div class="loading-spinner" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8);">
                                        <i class="fas fa-spinner fa-spin" style="color: #64748b; font-size: 20px;"></i>
                                    </div>
                                `}
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 8px; align-items: center;">
                                <span style="font-size: 11px; color: #16a34a;"><i class="fas fa-check-circle"></i> Asset loaded</span>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="updateSlideBackground('src', ''); renderSlideProperties();"
                                        style="margin-left: auto; font-size: 10px; padding: 2px 6px;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        ` : ''}
                    `}
                    ${slide.background?.type === 'video' && slide.background?.duration ? `
                        <div style="margin-top: 8px; padding: 8px; background: #f1f5f9; border-radius: 6px;">
                            <p style="font-size: 12px; color: #475569; margin: 0 0 6px 0;">
                                <i class="fas fa-clock"></i> Video Duration: <strong>${slide.background.duration.toFixed(1)}s</strong> 
                                (${slide.background.durationFrames} frames)
                            </p>
                            <button type="button" class="btn btn-primary btn-sm" onclick="matchSlideDurationToVideo()">
                                <i class="fas fa-sync"></i> Match Slide Duration to Video
                            </button>
                        </div>
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
                
                <div class="section-title" style="margin-top: 16px; font-size: 11px;">Layer Timing (within slide)</div>
                <div class="property-row">
                    <div class="property-group">
                        <label>Start Frame</label>
                        <input type="number" value="${layer.timing?.startFrame || 0}" 
                               min="0" max="${templateDef.slides[selectedSlideIndex].durationFrames}"
                               onchange="updateLayerTiming('startFrame', parseInt(this.value))">
                    </div>
                    <div class="property-group">
                        <label>End Frame</label>
                        <input type="number" value="${layer.timing?.endFrame || templateDef.slides[selectedSlideIndex].durationFrames}" 
                               min="0" max="${templateDef.slides[selectedSlideIndex].durationFrames}"
                               onchange="updateLayerTiming('endFrame', parseInt(this.value))">
                    </div>
                </div>
                <p style="font-size: 10px; color: #64748b; margin-top: 4px;">
                    Layer visible from frame ${layer.timing?.startFrame || 0} to ${layer.timing?.endFrame || templateDef.slides[selectedSlideIndex].durationFrames} 
                    (${(((layer.timing?.endFrame || templateDef.slides[selectedSlideIndex].durationFrames) - (layer.timing?.startFrame || 0)) / (templateDef.fps || 30)).toFixed(1)}s)
                </p>
                
                ${typeSpecificHtml}
                
                <div class="section-title" style="margin-top: 16px; font-size: 11px;">Animations</div>
                <div class="property-group">
                    <label>Enter Animation</label>
                    <div style="display: flex; gap: 8px;">
                        <select style="flex: 1;" onchange="updateLayerAnimation('enter', 'type', this.value)">
                            ${Object.entries(animations).map(([key, label]) => `
                                <option value="${key}" ${layer.animation?.enter?.type === key ? 'selected' : ''}>
                                    ${label}
                                </option>
                            `).join('')}
                        </select>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="playLayerAnimation(${selectedLayerIndex}, 'enter')" 
                                title="Preview Enter Animation" ${layer.animation?.enter?.type === 'none' ? 'disabled' : ''}>
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
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
                    <div style="display: flex; gap: 8px;">
                        <select style="flex: 1;" onchange="updateLayerAnimation('exit', 'type', this.value)">
                            <option value="" ${!layer.animation?.exit?.type ? 'selected' : ''}>None (stays visible)</option>
                            ${Object.entries(animations).map(([key, label]) => `
                                <option value="${key}" ${layer.animation?.exit?.type === key ? 'selected' : ''}>
                                    ${label}
                                </option>
                            `).join('')}
                        </select>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="playLayerAnimation(${selectedLayerIndex}, 'exit')" 
                                title="Preview Exit Animation" ${!layer.animation?.exit?.type || layer.animation?.exit?.type === 'none' ? 'disabled' : ''}>
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
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
            saveState('add layer');
            const slide = templateDef.slides[selectedSlideIndex];
            const newLayer = {
                id: type + '_' + Date.now(),
                type: type,
                fieldKey: type === 'text' ? 'title' : 'couplePhoto',
                position: { x: 540, y: 500, anchor: 'center' },
                timing: { startFrame: 0, endFrame: slide.durationFrames },
                animation: { enter: { type: 'fade-in', durationFrames: 30 } }
            };

            if (type === 'text') {
                newLayer.defaultValue = ''; // Empty - preview will show fieldKey
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

        function updateLayerTiming(key, value) {
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
            const slide = templateDef.slides[selectedSlideIndex];

            if (!layer.timing) {
                layer.timing = { startFrame: 0, endFrame: slide.durationFrames };
            }

            layer.timing[key] = Math.max(0, Math.min(value, slide.durationFrames));

            // Ensure startFrame <= endFrame
            if (layer.timing.startFrame > layer.timing.endFrame) {
                if (key === 'startFrame') {
                    layer.timing.endFrame = layer.timing.startFrame;
                } else {
                    layer.timing.startFrame = layer.timing.endFrame;
                }
            }

            renderTimeline();
            renderLayerProperties();
        }

        function updateLayerAnimation(phase, key, value) {
            const layer = templateDef.slides[selectedSlideIndex].layers[selectedLayerIndex];
            if (!layer.animation) layer.animation = {};
            if (!layer.animation[phase]) layer.animation[phase] = { type: 'none' };
            layer.animation[phase][key] = value;
        }

        function deleteSlide() {
            if (!confirm('Delete this slide?')) return;
            saveState('delete slide');
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
            saveState('delete layer');
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