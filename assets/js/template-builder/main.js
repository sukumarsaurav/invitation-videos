/**
 * Template Builder - Main JavaScript
 * Orchestrates all template builder functionality
 */

import { SlideManager } from './slideManager.js';
import { CanvasEditor } from './canvasEditor.js';
import { TextEditor } from './textEditor.js';
import { ShapeManager } from './shapeManager.js';
import { Animations } from './animations.js';
import { Exporter } from './exporter.js';

class TemplateBuilder {
    constructor() {
        this.templateId = window.TEMPLATE_DATA.templateId;
        this.csrfToken = window.TEMPLATE_DATA.csrfToken;
        this.slides = window.TEMPLATE_DATA.slides || [];
        this.fields = window.TEMPLATE_DATA.fields || [];

        this.currentSlideIndex = 0;
        this.selectedElement = null;
        this.isDirty = false;

        // Initialize modules
        this.slideManager = new SlideManager(this);
        this.canvasEditor = new CanvasEditor(this);
        this.textEditor = new TextEditor(this);
        this.shapeManager = new ShapeManager(this);
        this.animations = new Animations(this);
        this.exporter = new Exporter(this);

        this.init();
    }

    init() {
        this.bindEvents();
        this.setupSidebar();

        // Create default slide if none exist
        if (this.slides.length === 0) {
            this.slideManager.addSlide();
        }

        // Render first slide
        this.selectSlide(0);
    }

    setupSidebar() {
        const iconButtons = document.querySelectorAll('.icon-btn');
        const panels = document.querySelectorAll('.panel-view');
        const contentPanel = document.getElementById('content-panel');

        iconButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const panelId = btn.dataset.panel;

                // Toggle panel if clicking the same button
                if (btn.classList.contains('active')) {
                    contentPanel.classList.toggle('open');
                    return;
                }

                // Switch active button
                iconButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Switch panel
                panels.forEach(p => p.classList.remove('active'));
                const targetPanel = document.getElementById(`panel-${panelId}`);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }

                // Ensure panel is open
                contentPanel.classList.add('open');
            });
        });
    }

    bindEvents() {
        // Save button
        document.getElementById('btn-save')?.addEventListener('click', () => this.save());

        // Preview button
        document.getElementById('btn-preview')?.addEventListener('click', () => this.preview());

        // Add slide button
        document.getElementById('btn-add-slide')?.addEventListener('click', () => {
            this.slideManager.addSlide();
        });

        // Upload background button
        document.getElementById('btn-upload-bg')?.addEventListener('click', () => {
            document.getElementById('slide-bg-image')?.click();
        });

        document.getElementById('slide-bg-image')?.addEventListener('change', (e) => {
            this.uploadBackground(e.target.files[0]);
        });

        // Slide settings
        document.getElementById('slide-duration')?.addEventListener('change', (e) => {
            this.updateCurrentSlide({ duration_ms: parseInt(e.target.value) });
        });

        document.getElementById('slide-bg-color')?.addEventListener('input', (e) => {
            this.updateCurrentSlide({ background_color: e.target.value });
        });

        document.getElementById('slide-transition')?.addEventListener('change', (e) => {
            this.updateCurrentSlide({ transition_type: e.target.value });
        });

        // Text properties
        document.getElementById('text-sample')?.addEventListener('input', (e) => {
            this.textEditor.updateSelectedText({ sample_value: e.target.value });
        });

        document.getElementById('text-font-size')?.addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ font_size: parseInt(e.target.value) });
        });

        document.getElementById('text-font-family')?.addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ font_family: e.target.value });
        });

        document.getElementById('text-color')?.addEventListener('input', (e) => {
            this.textEditor.updateSelectedText({ font_color: e.target.value });
        });

        document.getElementById('text-animation')?.addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ animation_type: e.target.value });
        });

        document.getElementById('text-delay')?.addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ animation_delay_ms: parseInt(e.target.value) });
        });

        // Shape toolbar buttons
        this.setupShapeToolbar();

        // Shape properties
        this.setupShapeProperties();

        // Text presets (Add Heading/Subheading/Body)
        this.setupTextPresets();

        // Text toolbar (floating toolbar handlers)
        this.setupTextToolbar();

        // Preset quick add button
        this.setupPresetQuickAdd();

        // Field drag and drop
        this.setupFieldDragDrop();

        // Timeline setup
        this.setupTimeline();

        // Background panel tabs and items
        this.setupBackgroundPanel();

        // Zoom and Pan controls
        this.setupZoomPan();

        // Color and Layers panels
        this.setupPanels();

        // Warn before leaving if unsaved
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    setupFieldDragDrop() {
        const fieldItems = document.querySelectorAll('.field-item');
        const canvasOverlays = document.getElementById('canvas-overlays');

        fieldItems.forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('field-id', item.dataset.fieldId);
                e.dataTransfer.setData('field-name', item.dataset.fieldName);
                e.dataTransfer.setData('field-label', item.dataset.fieldLabel);
                e.dataTransfer.setData('sample-value', item.dataset.sampleValue);
                item.classList.add('dragging');
                // Enable pointer-events on overlays during drag
                canvasOverlays.style.pointerEvents = 'auto';
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                // Reset pointer-events after drag ends
                canvasOverlays.style.pointerEvents = '';
            });
        });

        canvasOverlays.addEventListener('dragover', (e) => {
            e.preventDefault();
            canvasOverlays.classList.add('drag-over');
        });

        canvasOverlays.addEventListener('dragleave', () => {
            canvasOverlays.classList.remove('drag-over');
        });

        canvasOverlays.addEventListener('drop', (e) => {
            e.preventDefault();
            canvasOverlays.classList.remove('drag-over');

            const fieldId = e.dataTransfer.getData('field-id');
            const fieldName = e.dataTransfer.getData('field-name');
            const fieldLabel = e.dataTransfer.getData('field-label');
            const sampleValue = e.dataTransfer.getData('sample-value');

            if (fieldId) {
                const rect = canvasOverlays.getBoundingClientRect();
                const x = Math.round(((e.clientX - rect.left) / rect.width) * 100);
                const y = Math.round(((e.clientY - rect.top) / rect.height) * 100);

                this.shapeManager.deselectShape();

                this.textEditor.addTextToSlide(fieldId, fieldName, fieldLabel, sampleValue, x, y);

                // Refresh timeline to show the new element
                this.refreshTimeline();
            }
        });
    }

    selectSlide(index) {
        if (index < 0 || index >= this.slides.length) return;

        this.currentSlideIndex = index;
        const slide = this.slides[index];

        // Update slide bars (horizontal layout)
        document.querySelectorAll('.slide-bar').forEach((bar, i) => {
            bar.classList.toggle('active', i === index);
        });

        // Update properties panel
        const durationEl = document.getElementById('slide-duration');
        const transitionEl = document.getElementById('slide-transition');
        if (durationEl) durationEl.value = slide.duration_ms || 3000;
        if (transitionEl) transitionEl.value = slide.transition_type || 'fade';

        // Update canvas background based on slide
        const canvasContainer = document.getElementById('canvas-container');
        if (canvasContainer) {
            if (slide.background_gradient) {
                canvasContainer.style.background = slide.background_gradient;
            } else if (slide.background_image) {
                canvasContainer.style.background = `url(${slide.background_image}) center/cover no-repeat`;
            } else {
                canvasContainer.style.background = slide.background_color || '#ffffff';
            }
        }

        // Update slide info
        document.getElementById('current-slide-info').textContent = `Slide ${index + 1} of ${this.slides.length}`;

        // Render canvas
        this.canvasEditor.renderSlide(slide);

        // Render text elements for this slide
        this.textEditor.renderTextsForSlide(slide.id);

        // Render shapes for this slide
        this.shapeManager.renderShapesForSlide(slide.id);

        // Clear selection
        this.deselectElement();

        // Refresh timeline for this slide
        this.refreshTimeline();
    }

    getCurrentSlide() {
        return this.slides[this.currentSlideIndex];
    }

    updateCurrentSlide(updates) {
        const slide = this.getCurrentSlide();
        if (!slide) return;

        Object.assign(slide, updates);
        this.isDirty = true;

        // Re-render
        this.canvasEditor.renderSlide(slide);

        // If duration changed, re-render all thumbnails to recalculate widths
        if (updates.duration_ms) {
            this.slideManager.renderAllThumbnails();
            this.refreshTimeline();
        } else {
            this.slideManager.updateThumbnail(this.currentSlideIndex, slide);
        }
    }

    selectElement(element) {
        this.deselectElement();
        this.selectedElement = element;
        element.classList.add('selected');

        // Show text properties
        document.getElementById('text-properties').style.display = 'block';

        // Populate properties
        const field = this.getFieldById(element.dataset.fieldId);
        if (field) {
            document.getElementById('text-sample').value = field.sample_value || '';
            document.getElementById('text-font-size').value = field.font_size || 24;
            document.getElementById('text-font-family').value = field.font_family || 'Inter';
            document.getElementById('text-color').value = field.font_color || '#000000';
            document.getElementById('text-animation').value = field.animation_type || 'fadeIn';
            document.getElementById('text-delay').value = field.animation_delay_ms || 0;
        }
    }

    deselectElement() {
        if (this.selectedElement) {
            this.selectedElement.classList.remove('selected');
            this.selectedElement = null;
        }
        document.getElementById('text-properties').style.display = 'none';
    }

    getFieldById(fieldId) {
        // Use String() conversion for consistent comparison (field.id might be number, fieldId might be string)
        return this.fields.find(f => String(f.id) === String(fieldId));
    }

    updateField(fieldId, updates) {
        const field = this.getFieldById(fieldId);
        if (field) {
            Object.assign(field, updates);
            this.isDirty = true;
        }
    }

    async uploadBackground(file) {
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'upload_background');
        formData.append('template_id', this.templateId);
        formData.append('slide_id', this.getCurrentSlide()?.id || 'new');
        formData.append('image', file);
        formData.append('csrf_token', this.csrfToken);

        try {
            const response = await fetch('/api/template-builder.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.updateCurrentSlide({ background_image: result.url });
            } else {
                alert('Upload failed: ' + result.error);
            }
        } catch (error) {
            console.error('Background upload error:', error);
            alert('Upload failed');
        }
    }

    async save() {
        const btn = document.getElementById('btn-save');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin">sync</span> Saving...';

        try {
            const response = await fetch('/api/template-builder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save',
                    template_id: this.templateId,
                    csrf_token: this.csrfToken,
                    slides: this.slides,
                    shapes: this.shapeManager.getShapesData(),
                    fields: this.fields
                })
            });

            const result = await response.json();
            if (result.success) {
                this.isDirty = false;
                // Update slide IDs if they were newly created
                if (result.slides) {
                    this.slides = result.slides;
                }
                btn.innerHTML = '<span class="material-symbols-outlined">check</span> Saved!';
                setTimeout(() => {
                    btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save Changes';
                }, 2000);
            } else {
                throw new Error(result.error || 'Save failed');
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save: ' + error.message);
            btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save Changes';
        } finally {
            btn.disabled = false;
        }
    }

    setupShapeToolbar() {
        // Rectangle button
        const btnRect = document.getElementById('btn-add-rectangle');
        if (btnRect) {
            btnRect.addEventListener('click', () => {
                this.shapeManager.addShape('rectangle');
            });
        }

        // Ellipse button
        const btnEllipse = document.getElementById('btn-add-ellipse');
        if (btnEllipse) {
            btnEllipse.addEventListener('click', () => {
                this.shapeManager.addShape('ellipse');
            });
        }

        // Line button
        const btnLine = document.getElementById('btn-add-line');
        if (btnLine) {
            btnLine.addEventListener('click', () => {
                this.shapeManager.addShape('line');
            });
        }

        // Image upload button
        const btnImage = document.getElementById('btn-add-image');
        const imageInput = document.getElementById('shape-image-input');
        if (btnImage && imageInput) {
            btnImage.addEventListener('click', () => {
                imageInput.click();
            });
            imageInput.addEventListener('change', (e) => {
                if (e.target.files[0]) {
                    this.shapeManager.addImageShape(e.target.files[0]);
                    e.target.value = ''; // Reset input
                }
            });
        }

        // Delete shape button
        const btnDelete = document.getElementById('btn-delete-shape');
        if (btnDelete) {
            btnDelete.addEventListener('click', () => {
                this.shapeManager.deleteSelectedShape();
            });
        }

        // Click on canvas background to deselect
        document.getElementById('canvas-overlays').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                this.shapeManager.deselectShape();
                this.deselectElement();
            }
        });
    }

    setupShapeProperties() {
        // Fill color
        const fillInput = document.getElementById('shape-fill');
        if (fillInput) {
            fillInput.addEventListener('input', (e) => {
                this.shapeManager.updateShape({ fill: e.target.value });
            });
        }

        // Stroke color
        const strokeInput = document.getElementById('shape-stroke');
        if (strokeInput) {
            strokeInput.addEventListener('input', (e) => {
                this.shapeManager.updateShape({ stroke: e.target.value });
            });
        }

        // Stroke width
        const strokeWidthInput = document.getElementById('shape-stroke-width');
        if (strokeWidthInput) {
            strokeWidthInput.addEventListener('change', (e) => {
                this.shapeManager.updateShape({ strokeWidth: parseInt(e.target.value) });
            });
        }

        // Opacity
        const opacityInput = document.getElementById('shape-opacity');
        if (opacityInput) {
            opacityInput.addEventListener('input', (e) => {
                this.shapeManager.updateShape({ opacity: parseInt(e.target.value) / 100 });
            });
        }

        // Border radius
        const radiusInput = document.getElementById('shape-radius');
        if (radiusInput) {
            radiusInput.addEventListener('change', (e) => {
                this.shapeManager.updateShape({ borderRadius: parseInt(e.target.value) });
            });
        }

        // Animation
        const animationInput = document.getElementById('shape-animation');
        if (animationInput) {
            animationInput.addEventListener('change', (e) => {
                this.shapeManager.updateShape({ animation: e.target.value });
            });
        }
    }

    setupTextPresets() {
        // Add Heading button
        const addHeadingBtn = document.getElementById('btn-add-heading');
        if (addHeadingBtn) {
            addHeadingBtn.addEventListener('click', () => {
                this.addTextElement('heading', 'Add a heading', 48, 700);
            });
        }

        // Add Subheading button
        const addSubheadingBtn = document.getElementById('btn-add-subheading');
        if (addSubheadingBtn) {
            addSubheadingBtn.addEventListener('click', () => {
                this.addTextElement('subheading', 'Add a subheading', 32, 600);
            });
        }

        // Add Body text button
        const addBodyBtn = document.getElementById('btn-add-body');
        if (addBodyBtn) {
            addBodyBtn.addEventListener('click', () => {
                this.addTextElement('body', 'Add body text', 24, 400);
            });
        }

        // Font style buttons
        const fontStyleBtns = document.querySelectorAll('.font-style-btn');
        fontStyleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const fontFamily = btn.dataset.font;
                this.addTextElement('custom', 'Your text here', 32, 400, fontFamily);
            });
        });
    }

    addTextElement(type, text, fontSize, fontWeight, fontFamily = 'Inter') {
        const currentSlide = this.slides[this.currentSlideIndex];
        if (!currentSlide) return;

        // Create a pseudo-field for the text element
        const textField = {
            id: 'text_' + Date.now(),
            field_name: type + '_' + Date.now(),
            field_label: text,
            sample_value: text,
            slide_id: currentSlide.id,
            position_x: 50, // Center-ish position
            position_y: 50,
            font_family: fontFamily,
            font_size: fontSize,
            font_color: '#000000',
            animation: 'fadeIn',
            animation_delay: 0,
            font_weight: fontWeight,
            text_type: type
        };

        // Add to slide fields
        if (!currentSlide.fields) {
            currentSlide.fields = [];
        }
        currentSlide.fields.push(textField);

        // Add to global fields array
        this.fields.push(textField);

        // Render on canvas
        this.textEditor.renderTextElement(textField.id);

        // Mark as dirty
        this.isDirty = true;

        // Show toolbar
        this.showTextToolbar();

        // Refresh timeline to show new element
        this.refreshTimeline();
    }

    setupTextToolbar() {
        // Font family change
        const fontSelect = document.getElementById('toolbar-font');
        if (fontSelect) {
            fontSelect.addEventListener('change', (e) => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    this.updateField(fieldId, { font_family: e.target.value });
                    this.textEditor.renderTextElement(fieldId);
                }
            });
        }

        // Font size change
        const sizeInput = document.getElementById('toolbar-size');
        if (sizeInput) {
            sizeInput.addEventListener('change', (e) => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    this.updateField(fieldId, { font_size: parseInt(e.target.value) });
                    this.textEditor.renderTextElement(fieldId);
                }
            });
        }

        // Color change
        const colorInput = document.getElementById('toolbar-color');
        if (colorInput) {
            colorInput.addEventListener('input', (e) => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    this.updateField(fieldId, { font_color: e.target.value });
                    this.textEditor.renderTextElement(fieldId);
                }
            });
        }

        // Bold button
        const boldBtn = document.querySelector('.toolbar-btn[data-action="bold"]');
        if (boldBtn) {
            boldBtn.addEventListener('click', () => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    const field = this.getFieldById(fieldId);
                    const currentWeight = field?.font_weight || 400;
                    const newWeight = currentWeight >= 700 ? 400 : 700;
                    this.updateField(fieldId, { font_weight: newWeight });
                    this.textEditor.renderTextElement(fieldId);
                    boldBtn.classList.toggle('active', newWeight >= 700);
                }
            });
        }

        // Italic button
        const italicBtn = document.querySelector('.toolbar-btn[data-action="italic"]');
        if (italicBtn) {
            italicBtn.addEventListener('click', () => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    const field = this.getFieldById(fieldId);
                    const isItalic = field?.font_style === 'italic';
                    this.updateField(fieldId, { font_style: isItalic ? 'normal' : 'italic' });
                    this.textEditor.renderTextElement(fieldId);
                    italicBtn.classList.toggle('active', !isItalic);
                }
            });
        }

        // Underline button
        const underlineBtn = document.querySelector('.toolbar-btn[data-action="underline"]');
        if (underlineBtn) {
            underlineBtn.addEventListener('click', () => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    const field = this.getFieldById(fieldId);
                    const isUnderline = field?.text_decoration === 'underline';
                    this.updateField(fieldId, { text_decoration: isUnderline ? 'none' : 'underline' });
                    this.textEditor.renderTextElement(fieldId);
                    underlineBtn.classList.toggle('active', !isUnderline);
                }
            });
        }

        // Alignment buttons
        ['left', 'center', 'right'].forEach(align => {
            const btn = document.querySelector(`.toolbar-btn[data-action="align-${align}"]`);
            if (btn) {
                btn.addEventListener('click', () => {
                    if (this.selectedElement) {
                        const fieldId = this.selectedElement.dataset.fieldId;
                        this.updateField(fieldId, { text_align: align });
                        this.textEditor.renderTextElement(fieldId);
                        // Update active state
                        document.querySelectorAll('.toolbar-btn[data-action^="align-"]').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    }
                });
            }
        });

        // Delete button
        const deleteBtn = document.getElementById('toolbar-delete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                if (this.selectedElement) {
                    const fieldId = this.selectedElement.dataset.fieldId;
                    this.textEditor.removeTextFromSlide(fieldId);
                    this.selectedElement = null;
                    this.hideTextToolbar();
                }
            });
        }

        // Click outside to deselect and hide toolbar
        document.getElementById('canvas-container')?.addEventListener('click', (e) => {
            if (e.target.id === 'canvas-container' || e.target.id === 'template-canvas') {
                if (this.selectedElement) {
                    this.selectedElement.classList.remove('selected');
                    this.selectedElement = null;
                    this.hideTextToolbar();
                }
            }
        });
    }

    showTextToolbar() {
        const toolbar = document.getElementById('text-toolbar');
        if (toolbar) {
            toolbar.classList.remove('hidden');

            // Sync toolbar values with selected element
            if (this.selectedElement) {
                const fieldId = this.selectedElement.dataset.fieldId;
                const field = this.getFieldById(fieldId);
                if (field) {
                    const fontSelect = document.getElementById('toolbar-font');
                    const sizeInput = document.getElementById('toolbar-size');
                    const colorInput = document.getElementById('toolbar-color');

                    if (fontSelect) fontSelect.value = field.font_family || 'Inter';
                    if (sizeInput) sizeInput.value = field.font_size || 24;
                    if (colorInput) colorInput.value = field.font_color || '#000000';

                    // Update button states
                    document.querySelector('.toolbar-btn[data-action="bold"]')?.classList.toggle('active', field.font_weight >= 700);
                    document.querySelector('.toolbar-btn[data-action="italic"]')?.classList.toggle('active', field.font_style === 'italic');
                    document.querySelector('.toolbar-btn[data-action="underline"]')?.classList.toggle('active', field.text_decoration === 'underline');

                    document.querySelectorAll('.toolbar-btn[data-action^="align-"]').forEach(b => b.classList.remove('active'));
                    document.querySelector(`.toolbar-btn[data-action="align-${field.text_align || 'center'}"]`)?.classList.add('active');
                }
            }
        }
    }

    hideTextToolbar() {
        const toolbar = document.getElementById('text-toolbar');
        if (toolbar) {
            toolbar.classList.add('hidden');
        }
    }

    setupTimeline() {
        this.renderTimelineRuler();
        this.renderTimelineTracks();
        this.setupPlayhead();
    }

    renderTimelineRuler() {
        const ruler = document.getElementById('timeline-ruler');
        const durationSpan = document.getElementById('timeline-duration');
        if (!ruler) return;

        // Calculate total duration of all slides
        const totalDuration = this.slides.reduce((total, slide) => total + (slide.duration_ms || 3000), 0);

        if (durationSpan) {
            durationSpan.textContent = (totalDuration / 1000).toFixed(1) + 's';
        }

        // Clear existing marks (keep playhead)
        const playhead = ruler.querySelector('.playhead');
        ruler.innerHTML = '';
        if (playhead) ruler.appendChild(playhead);

        // Generate time marks based on total duration
        const stepMs = totalDuration <= 5000 ? 1000 : (totalDuration <= 15000 ? 2000 : 5000);
        for (let t = 0; t <= totalDuration; t += stepMs) {
            const mark = document.createElement('span');
            mark.className = 'ruler-mark';
            mark.style.left = `${(t / totalDuration) * 100}%`;
            mark.textContent = (t / 1000).toFixed(0) + 's';
            ruler.appendChild(mark);
        }
    }

    renderTimelineTracks() {
        const container = document.getElementById('element-tracks');
        if (!container) return;

        container.innerHTML = '';

        // Get current slide
        const currentSlide = this.slides[this.currentSlideIndex];
        if (!currentSlide) return;

        const duration = currentSlide.duration_ms || 3000;

        // Get fields assigned to this slide (from this.fields, not currentSlide.fields)
        const slideFields = this.fields.filter(f => f.slide_id == currentSlide.id);

        slideFields.forEach(field => {
            const animStart = field.animation_start || 0;
            const animEnd = field.animation_end || duration;

            const startPercent = (animStart / duration) * 100;
            const widthPercent = ((animEnd - animStart) / duration) * 100;

            const track = document.createElement('div');
            track.className = 'timeline-track';
            track.dataset.fieldId = field.id;

            // Determine icon based on field type
            let icon = 'title';
            let trackClass = 'text-track';
            if (field.field_type === 'image') {
                icon = 'image';
                trackClass = 'image-track';
            } else if (field.field_type === 'shape') {
                icon = 'shapes';
                trackClass = '';
            }

            // Get the display text (sample value or field label)
            const displayText = field.sample_value || field.field_label || field.field_name || 'Element';
            const shortText = displayText.length > 20 ? displayText.substring(0, 18) + '...' : displayText;

            track.innerHTML = `
                <span class="track-label">
                    <span class="track-icon material-symbols-outlined">${icon}</span>
                    ${field.field_name || 'Text'}
                </span>
                <div class="track-bar-container">
                    <div class="track-bar ${trackClass}" 
                         data-start="${animStart}" 
                         data-end="${animEnd}"
                         style="left: ${startPercent}%; width: ${widthPercent}%;">
                        <span class="track-bar-text">${shortText}</span>
                        <div class="track-handle track-handle-left"></div>
                        <div class="track-handle track-handle-right"></div>
                    </div>
                </div>
            `;

            container.appendChild(track);
            this.makeTrackDraggable(track, field, duration);
        });
    }

    makeTrackDraggable(track, field, duration) {
        const bar = track.querySelector('.track-bar');
        const container = track.querySelector('.track-bar-container');
        const leftHandle = bar.querySelector('.track-handle-left');
        const rightHandle = bar.querySelector('.track-handle-right');

        let isDragging = false;
        let dragType = null; // 'move', 'left', 'right'
        let startX, startLeft, startWidth;

        const onMouseDown = (e, type) => {
            e.preventDefault();
            e.stopPropagation();
            isDragging = true;
            dragType = type;
            startX = e.clientX;
            startLeft = parseFloat(bar.style.left) || 0;
            startWidth = parseFloat(bar.style.width) || 100;
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
            bar.style.cursor = 'grabbing';
        };

        const onMouseMove = (e) => {
            if (!isDragging) return;

            const containerRect = container.getBoundingClientRect();
            const deltaX = e.clientX - startX;
            const deltaPercent = (deltaX / containerRect.width) * 100;

            if (dragType === 'move') {
                let newLeft = startLeft + deltaPercent;
                newLeft = Math.max(0, Math.min(newLeft, 100 - startWidth));
                bar.style.left = newLeft + '%';
            } else if (dragType === 'left') {
                let newLeft = startLeft + deltaPercent;
                let newWidth = startWidth - deltaPercent;
                if (newLeft >= 0 && newWidth >= 5) {
                    bar.style.left = newLeft + '%';
                    bar.style.width = newWidth + '%';
                }
            } else if (dragType === 'right') {
                let newWidth = startWidth + deltaPercent;
                if (newWidth >= 5 && startLeft + newWidth <= 100) {
                    bar.style.width = newWidth + '%';
                }
            }
        };

        const onMouseUp = () => {
            if (!isDragging) return;
            isDragging = false;
            bar.style.cursor = 'grab';
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);

            // Update field data
            const left = parseFloat(bar.style.left) || 0;
            const width = parseFloat(bar.style.width) || 100;
            const newStart = Math.round((left / 100) * duration);
            const newEnd = Math.round(((left + width) / 100) * duration);

            bar.dataset.start = newStart;
            bar.dataset.end = newEnd;

            this.updateField(field.id, {
                animation_start: newStart,
                animation_end: newEnd
            });
        };

        bar.addEventListener('mousedown', (e) => {
            if (e.target === leftHandle) return;
            if (e.target === rightHandle) return;
            onMouseDown(e, 'move');
        });
        leftHandle.addEventListener('mousedown', (e) => onMouseDown(e, 'left'));
        rightHandle.addEventListener('mousedown', (e) => onMouseDown(e, 'right'));
    }

    setupPlayhead() {
        const playhead = document.getElementById('timeline-playhead');
        const ruler = document.getElementById('timeline-ruler');
        if (!playhead || !ruler) return;

        const head = playhead.querySelector('.playhead-head');
        let isDragging = false;

        head.addEventListener('mousedown', (e) => {
            isDragging = true;
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });

        const onMove = (e) => {
            if (!isDragging) return;
            const rect = ruler.getBoundingClientRect();
            let percent = ((e.clientX - rect.left) / rect.width) * 100;
            percent = Math.max(0, Math.min(100, percent));
            playhead.style.left = percent + '%';

            // Update canvas preview at this time position
            this.updateCanvasAtTime(percent);
        };

        const onUp = () => {
            isDragging = false;
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };

        // Also allow clicking on ruler to jump playhead
        ruler.addEventListener('click', (e) => {
            if (e.target.classList.contains('playhead-head')) return;
            const rect = ruler.getBoundingClientRect();
            let percent = ((e.clientX - rect.left) / rect.width) * 100;
            percent = Math.max(0, Math.min(100, percent));
            playhead.style.left = percent + '%';
            this.updateCanvasAtTime(percent);
        });
    }

    /**
     * Update canvas preview based on playhead position
     * Shows/hides elements based on their animation start/end times
     * @param {number} percent - Playhead position as percentage (0-100)
     */
    updateCanvasAtTime(percent) {
        const currentSlide = this.slides[this.currentSlideIndex];
        if (!currentSlide) return;

        const duration = currentSlide.duration_ms || 3000;
        const currentTimeMs = (percent / 100) * duration;

        // Get all text elements on canvas
        const textElements = document.querySelectorAll('.canvas-text-element');
        textElements.forEach(element => {
            const fieldId = element.dataset.fieldId;
            const field = this.getFieldById(fieldId);
            if (!field) return;

            const animStart = field.animation_start || 0;
            const animEnd = field.animation_end || duration;

            // Show element if current time is within its animation range
            if (currentTimeMs >= animStart && currentTimeMs <= animEnd) {
                element.style.opacity = '1';
                element.style.visibility = 'visible';
            } else {
                element.style.opacity = '0.3';
                element.style.visibility = 'visible';
            }
        });

        // Get all shape elements on canvas
        const shapeElements = document.querySelectorAll('.canvas-shape-element');
        shapeElements.forEach(element => {
            const shapeId = element.dataset.shapeId;
            const shape = this.shapeManager.getShapeById(shapeId);
            if (!shape) return;

            const animStart = shape.animation_start || 0;
            const animEnd = shape.animation_end || duration;

            if (currentTimeMs >= animStart && currentTimeMs <= animEnd) {
                element.style.opacity = shape.opacity || '1';
                element.style.visibility = 'visible';
            } else {
                element.style.opacity = '0.3';
                element.style.visibility = 'visible';
            }
        });
    }


    refreshTimeline() {
        this.renderTimelineRuler();
        this.renderTimelineTracks();
    }

    setupZoomPan() {
        this.zoom = 100;
        this.panX = 0;
        this.panY = 0;

        const canvasWrapper = document.querySelector('.builder-canvas-wrapper');
        const canvasContainer = document.getElementById('canvas-container');
        const zoomSlider = document.getElementById('zoom-slider');
        const zoomDisplay = document.getElementById('canvas-zoom');
        const btnZoomIn = document.getElementById('btn-zoom-in');
        const btnZoomOut = document.getElementById('btn-zoom-out');
        const btnZoomFit = document.getElementById('btn-zoom-fit');
        const canvasToolbar = document.getElementById('canvas-toolbar');

        if (!canvasContainer) return;

        // Canvas selection logic
        let canvasSelected = false;

        const selectCanvas = () => {
            canvasSelected = true;
            canvasContainer.classList.add('selected');
            if (canvasToolbar) canvasToolbar.classList.remove('hidden');
        };

        const deselectCanvas = () => {
            canvasSelected = false;
            canvasContainer.classList.remove('selected');
            if (canvasToolbar) canvasToolbar.classList.add('hidden');
        };

        // Click on canvas to select
        canvasContainer.addEventListener('click', (e) => {
            // Only select if click is directly on canvas/overlays (not on elements)
            if (e.target === canvasContainer || e.target.id === 'template-canvas' || e.target.id === 'canvas-overlays') {
                selectCanvas();
                e.stopPropagation();
            }
        });

        // Click outside canvas to deselect
        document.addEventListener('click', (e) => {
            if (canvasSelected && !canvasContainer.contains(e.target) && !canvasToolbar?.contains(e.target)) {
                deselectCanvas();
            }
        });

        // Canvas dimensions
        const canvasBaseWidth = 270;
        const canvasBaseHeight = 480;

        // Apply pan boundaries to prevent empty space - equal gaps at top and bottom
        const clampPan = () => {
            const wrapperRect = canvasWrapper.getBoundingClientRect();
            const scaledWidth = canvasBaseWidth * (this.zoom / 100);
            const scaledHeight = canvasBaseHeight * (this.zoom / 100);
            const zoomFactor = this.zoom / 100;

            // Gap to maintain at edges
            const gapMargin = 32;
            const timelineReserve = 160; // Fixed timeline at bottom

            // The canvas is centered in the wrapper initially
            // Calculate how far it can pan while maintaining the gap

            // Horizontal: canvas can pan until edge + gap touches container edge
            const containerWidth = wrapperRect.width;
            if (scaledWidth > containerWidth) {
                // Canvas is wider than container - can pan
                const maxPanX = ((scaledWidth - containerWidth) / 2 + gapMargin) / zoomFactor;
                this.panX = Math.max(-maxPanX, Math.min(maxPanX, this.panX));
            } else {
                // Canvas fits - center it (no pan)
                this.panX = 0;
            }

            // Vertical: account for timeline and maintain equal gaps
            const visibleHeight = wrapperRect.height - timelineReserve;
            if (scaledHeight > visibleHeight) {
                // Canvas is taller than visible area - can pan
                // Max pan up: until top edge has gap from header
                // Max pan down: until bottom edge has gap from timeline
                const maxPanY = ((scaledHeight - visibleHeight) / 2 + gapMargin) / zoomFactor;
                this.panY = Math.max(-maxPanY, Math.min(maxPanY, this.panY));
            } else {
                // Canvas fits - center it (no pan)
                this.panY = 0;
            }
        };

        // Apply transform to canvas
        const applyTransform = () => {
            clampPan();
            canvasContainer.style.transform = `scale(${this.zoom / 100}) translate(${this.panX}px, ${this.panY}px)`;
        };

        const updateZoom = (newZoom) => {
            this.zoom = Math.max(25, Math.min(500, newZoom)); // Increased to 500%
            applyTransform();
            if (zoomSlider) zoomSlider.value = Math.min(200, this.zoom); // Slider max is 200
            if (zoomDisplay) zoomDisplay.textContent = Math.round(this.zoom) + '%';
        };

        // Zoom In/Out buttons
        if (btnZoomIn) {
            btnZoomIn.addEventListener('click', () => updateZoom(this.zoom + 10));
        }
        if (btnZoomOut) {
            btnZoomOut.addEventListener('click', () => updateZoom(this.zoom - 10));
        }

        // Zoom Slider
        if (zoomSlider) {
            zoomSlider.addEventListener('input', (e) => updateZoom(parseInt(e.target.value)));
        }

        // Fit to Width - fill container width
        if (btnZoomFit) {
            btnZoomFit.addEventListener('click', () => {
                this.panX = 0;
                this.panY = 0;
                // Calculate zoom to fit width
                const wrapperRect = canvasWrapper.getBoundingClientRect();
                const fitZoom = (wrapperRect.width / canvasBaseWidth) * 100;
                updateZoom(Math.round(fitZoom));
            });
        }

        // Mouse wheel zoom
        canvasWrapper.addEventListener('wheel', (e) => {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -10 : 10;
                updateZoom(this.zoom + delta);
            }
        }, { passive: false });

        // Panning with space + drag or middle mouse button
        let isPanning = false;
        let startX, startY;

        canvasWrapper.addEventListener('mousedown', (e) => {
            if (e.button === 1 || (e.button === 0 && e.altKey)) {
                isPanning = true;
                startX = e.clientX - this.panX;
                startY = e.clientY - this.panY;
                canvasWrapper.classList.add('panning');
                e.preventDefault();
            }
        });

        document.addEventListener('mousemove', (e) => {
            if (!isPanning) return;
            this.panX = e.clientX - startX;
            this.panY = e.clientY - startY;
            applyTransform(); // Apply with bounds checking
        });

        document.addEventListener('mouseup', () => {
            if (isPanning) {
                isPanning = false;
                canvasWrapper.classList.remove('panning');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + 0: Reset zoom
            if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                e.preventDefault();
                this.panX = 0;
                this.panY = 0;
                updateZoom(100);
            }
            // Ctrl/Cmd + +: Zoom in
            if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                updateZoom(this.zoom + 10);
            }
            // Ctrl/Cmd + -: Zoom out
            if ((e.ctrlKey || e.metaKey) && e.key === '-') {
                e.preventDefault();
                updateZoom(this.zoom - 10);
            }
        });

        // ============================================
        // TOUCH GESTURES (Mobile/Tablet)
        // ============================================

        let lastTouchDistance = 0;
        let lastTouchCenter = { x: 0, y: 0 };
        let isTouchPanning = false;

        // Calculate distance between two touch points
        const getTouchDistance = (touches) => {
            if (touches.length < 2) return 0;
            const dx = touches[0].clientX - touches[1].clientX;
            const dy = touches[0].clientY - touches[1].clientY;
            return Math.sqrt(dx * dx + dy * dy);
        };

        // Get center point between two touches
        const getTouchCenter = (touches) => {
            if (touches.length < 2) {
                return { x: touches[0].clientX, y: touches[0].clientY };
            }
            return {
                x: (touches[0].clientX + touches[1].clientX) / 2,
                y: (touches[0].clientY + touches[1].clientY) / 2
            };
        };

        canvasWrapper.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                // Pinch gesture start
                e.preventDefault();
                lastTouchDistance = getTouchDistance(e.touches);
                lastTouchCenter = getTouchCenter(e.touches);
            } else if (e.touches.length === 1) {
                // Single finger pan start
                isTouchPanning = true;
                startX = e.touches[0].clientX - this.panX;
                startY = e.touches[0].clientY - this.panY;
            }
        }, { passive: false });

        canvasWrapper.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                // Pinch zoom
                e.preventDefault();
                const currentDistance = getTouchDistance(e.touches);
                const currentCenter = getTouchCenter(e.touches);

                if (lastTouchDistance > 0) {
                    const scale = currentDistance / lastTouchDistance;
                    const newZoom = this.zoom * scale;
                    updateZoom(newZoom);
                }

                // Pan while pinching
                this.panX += (currentCenter.x - lastTouchCenter.x) / (this.zoom / 100);
                this.panY += (currentCenter.y - lastTouchCenter.y) / (this.zoom / 100);
                applyTransform();

                lastTouchDistance = currentDistance;
                lastTouchCenter = currentCenter;
            } else if (e.touches.length === 1 && isTouchPanning) {
                // Single finger pan
                e.preventDefault();
                this.panX = e.touches[0].clientX - startX;
                this.panY = e.touches[0].clientY - startY;
                applyTransform();
            }
        }, { passive: false });

        canvasWrapper.addEventListener('touchend', (e) => {
            if (e.touches.length < 2) {
                lastTouchDistance = 0;
            }
            if (e.touches.length === 0) {
                isTouchPanning = false;
            }
        });

        // ============================================
        // SCROLL TO PAN (without Ctrl)
        // ============================================

        canvasWrapper.addEventListener('wheel', (e) => {
            // Already handling Ctrl+wheel for zoom above
            if (!e.ctrlKey && !e.metaKey) {
                // Regular scroll = pan
                e.preventDefault();
                if (e.shiftKey) {
                    // Shift + scroll = horizontal pan
                    this.panX -= e.deltaY / 2;
                } else {
                    // Normal scroll = vertical pan
                    this.panY -= e.deltaY / 2;
                }
                applyTransform();
            }
        }, { passive: false });
    }

    setupBackgroundPanel() {
        // Tab switching
        const bgTabs = document.querySelectorAll('.bg-tab');
        const bgContainers = document.querySelectorAll('.bg-grid-container');

        bgTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                bgTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const targetType = tab.dataset.bgType;
                bgContainers.forEach(c => c.classList.add('hidden'));
                document.getElementById(`bg-${targetType}`)?.classList.remove('hidden');
            });
        });

        // Click on background items to apply
        document.querySelectorAll('.bg-item').forEach(item => {
            item.addEventListener('click', () => {
                const type = item.dataset.type;
                const canvasContainer = document.getElementById('canvas-container');

                // Remove selected from all
                document.querySelectorAll('.bg-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');

                // Get current slide using index directly (currentSlideIndex IS the index)
                const slideIndex = this.currentSlideIndex;
                const currentSlide = this.slides[slideIndex];
                if (!currentSlide) return;

                // Clear all background properties first
                currentSlide.background_color = null;
                currentSlide.background_image = null;
                currentSlide.background_gradient = null;
                currentSlide.background_video = null;

                if (type === 'color') {
                    const color = item.dataset.value;
                    currentSlide.background_color = color;

                    // Update canvas background
                    if (canvasContainer) {
                        canvasContainer.style.background = color;
                    }
                } else if (type === 'gradient') {
                    const gradient = item.dataset.value;
                    currentSlide.background_gradient = gradient;

                    if (canvasContainer) {
                        canvasContainer.style.background = gradient;
                    }
                } else if (type === 'image') {
                    const src = item.dataset.src;
                    currentSlide.background_image = src;

                    if (canvasContainer) {
                        canvasContainer.style.background = `url(${src}) center/cover no-repeat`;
                    }
                } else if (type === 'video') {
                    const src = item.dataset.src;
                    currentSlide.background_video = src;
                    // For videos, show a placeholder or first frame
                    if (canvasContainer) {
                        canvasContainer.style.background = '#1e293b';
                    }
                }

                // Update the slide thumbnail
                this.slideManager.updateThumbnail(slideIndex >= 0 ? slideIndex : 0, currentSlide);
                this.isDirty = true;
            });
        });
    }

    setupPresetQuickAdd() {
        const presetSelect = document.getElementById('preset-select');
        const addPresetBtn = document.getElementById('btn-add-preset');

        if (!presetSelect || !addPresetBtn) return;

        addPresetBtn.addEventListener('click', async () => {
            const presetId = presetSelect.value;
            if (!presetId) {
                alert('Please select a preset first');
                return;
            }

            addPresetBtn.disabled = true;

            try {
                const response = await fetch('/api/template-builder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_preset_field',
                        template_id: this.templateId,
                        preset_id: parseInt(presetId),
                        csrf_token: this.csrfToken
                    })
                });

                const result = await response.json();

                if (result.success && result.field) {
                    // Add to our fields array
                    this.fields.push(result.field);

                    // Add to the fields list UI
                    const fieldsList = document.getElementById('fields-list');
                    const emptyMsg = fieldsList.querySelector('p.text-center');
                    if (emptyMsg) emptyMsg.remove();

                    const fieldItem = document.createElement('div');
                    fieldItem.className = 'field-item';
                    fieldItem.draggable = true;
                    fieldItem.dataset.fieldId = result.field.id;
                    fieldItem.dataset.fieldName = result.field.field_name;
                    fieldItem.dataset.fieldLabel = result.field.field_label;
                    fieldItem.dataset.sampleValue = result.field.sample_value || '';
                    fieldItem.innerHTML = `
                        <span class="material-symbols-outlined drag-handle">drag_indicator</span>
                        <div class="field-info">
                            <span class="field-label">${result.field.field_label}</span>
                            <span class="field-type">${result.field.field_type}</span>
                        </div>
                        <span class="field-slide-badge" data-slide="">—</span>
                    `;
                    fieldsList.appendChild(fieldItem);

                    // Re-setup drag and drop
                    this.setupFieldDragDrop();

                    // Reset select
                    presetSelect.value = '';
                    this.showNotification('Field added successfully!', 'success');
                } else {
                    throw new Error(result.error || 'Failed to add preset');
                }
            } catch (err) {
                console.error('Add preset error:', err);
                this.showNotification('Failed to add field: ' + err.message, 'error');
            } finally {
                addPresetBtn.disabled = false;
            }
        });
    }

    showNotification(message, type = 'success') {
        // Simple notification - could be enhanced later
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'error' ? '#dc2626' : '#10b981'};
            color: white;
            border-radius: 8px;
            font-weight: 500;
            z-index: 10000;
            animation: slideUp 0.3s ease-out;
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    preview() {
        const modal = document.getElementById('preview-modal');
        modal.classList.remove('hidden');

        // Calculate total duration for display
        const totalDuration = this.slides.reduce((sum, s) => sum + (s.duration_ms || 3000), 0);
        this.exporter.totalDuration = totalDuration;

        // Update initial time display
        const totalSec = Math.floor(totalDuration / 1000);
        const currentTimeEl = document.getElementById('preview-time-current');
        const totalTimeEl = document.getElementById('preview-time-total');
        if (currentTimeEl) currentTimeEl.textContent = '0:00';
        if (totalTimeEl) totalTimeEl.textContent = `${Math.floor(totalSec / 60)}:${String(totalSec % 60).padStart(2, '0')}`;

        // Reset progress bar
        const progressEl = document.getElementById('timeline-progress');
        const playheadEl = document.getElementById('timeline-playhead');
        if (progressEl) progressEl.style.width = '0%';
        if (playheadEl) playheadEl.style.left = '0%';

        // Setup play button toggle
        const playBtn = document.getElementById('btn-play-preview');
        playBtn.onclick = () => this.exporter.togglePreview();

        // Setup timeline click and drag to seek
        const timeline = document.getElementById('player-timeline');
        if (timeline) {
            let isDragging = false;

            const seekToPosition = (e) => {
                const rect = timeline.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const progress = Math.max(0, Math.min(1, clickX / rect.width));
                this.exporter.seekPreview(progress);
            };

            // Click to seek
            timeline.addEventListener('mousedown', (e) => {
                isDragging = true;
                seekToPosition(e);
            });

            // Drag to seek
            document.addEventListener('mousemove', (e) => {
                if (isDragging) {
                    seekToPosition(e);
                }
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
            });
        }

        // Render first frame
        if (this.slides.length > 0) {
            this.exporter.renderPreviewAtProgress(0);
        }
    }

    // Setup Color and Layers Panels (now in left sidebar)
    setupPanels() {
        const btnColorPicker = document.getElementById('canvas-bg-color');
        const btnPosition = document.getElementById('btn-position');

        // When color picker in toolbar is clicked, switch to Color panel in left sidebar
        if (btnColorPicker) {
            btnColorPicker.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchToPanel('color');
            });
        }

        // When Position button in toolbar is clicked, switch to Layers panel in left sidebar
        if (btnPosition) {
            btnPosition.addEventListener('click', () => {
                this.switchToPanel('position');
                this.populateLayersPanel();
            });
        }

        // Color swatches click (panel version)
        document.querySelectorAll('.color-swatch-panel').forEach(swatch => {
            swatch.addEventListener('click', () => {
                const color = swatch.dataset.color;
                this.applyBackgroundColor(color);
            });
        });

        // Gradient swatches click
        document.querySelectorAll('.gradient-swatch').forEach(swatch => {
            swatch.addEventListener('click', () => {
                const gradient = swatch.dataset.gradient;
                this.applyBackgroundGradient(gradient);
            });
        });

        // Custom color picker (panel version)
        const panelCustomColor = document.getElementById('panel-custom-color');
        const panelColorHex = document.getElementById('panel-color-hex');
        const btnApplyCustomColor = document.getElementById('btn-apply-custom-color');

        panelCustomColor?.addEventListener('input', (e) => {
            if (panelColorHex) panelColorHex.value = e.target.value;
        });

        panelColorHex?.addEventListener('input', (e) => {
            const hex = e.target.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(hex) && panelCustomColor) {
                panelCustomColor.value = hex;
            }
        });

        btnApplyCustomColor?.addEventListener('click', () => {
            const color = panelCustomColor?.value || '#7c3aed';
            this.applyBackgroundColor(color);
        });

        // Remove background button
        const btnRemoveBg = document.getElementById('btn-remove-bg');
        btnRemoveBg?.addEventListener('click', () => {
            this.removeBackground();
        });

        // Layers tabs (inline version)
        document.querySelectorAll('.layers-tab-inline').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.layers-tab-inline').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const tabName = tab.dataset.tab;
                document.getElementById('panel-layers-content')?.classList.toggle('hidden', tabName !== 'layers');
                document.getElementById('panel-arrange-content')?.classList.toggle('hidden', tabName !== 'arrange');
            });
        });
    }

    // Switch to a specific panel in the left sidebar
    switchToPanel(panelName) {
        const iconButtons = document.querySelectorAll('.icon-btn');
        const panels = document.querySelectorAll('.panel-view');
        const contentPanel = document.getElementById('content-panel');

        // Switch active button
        iconButtons.forEach(b => b.classList.remove('active'));
        const targetBtn = document.querySelector(`.icon-btn[data-panel="${panelName}"]`);
        if (targetBtn) targetBtn.classList.add('active');

        // Switch panel
        panels.forEach(p => p.classList.remove('active'));
        const targetPanel = document.getElementById(`panel-${panelName}`);
        if (targetPanel) targetPanel.classList.add('active');

        // Ensure content panel is open
        contentPanel?.classList.add('open');

        // Update background info if switching to color panel
        if (panelName === 'color') {
            this.updateBackgroundInfo();
        }
    }

    // Update the current background info display in color panel
    updateBackgroundInfo() {
        const currentSlide = this.getCurrentSlide();
        if (!currentSlide) return;

        const previewSwatch = document.getElementById('bg-preview-swatch');
        const previewText = document.getElementById('bg-preview-text');
        const removeBtn = document.getElementById('btn-remove-bg');

        if (currentSlide.background_image) {
            if (previewSwatch) previewSwatch.style.background = `url(${currentSlide.background_image}) center/cover`;
            if (previewText) previewText.textContent = 'Image';
            removeBtn?.classList.remove('hidden');
        } else if (currentSlide.background_gradient) {
            if (previewSwatch) previewSwatch.style.background = currentSlide.background_gradient;
            if (previewText) previewText.textContent = 'Gradient';
            removeBtn?.classList.remove('hidden');
        } else {
            const color = currentSlide.background_color || '#ffffff';
            if (previewSwatch) previewSwatch.style.background = color;
            if (previewText) previewText.textContent = `Color: ${color}`;
            removeBtn?.classList.add('hidden');
        }
    }

    // Remove background image/gradient (reset to white)
    removeBackground() {
        const currentSlide = this.getCurrentSlide();
        if (!currentSlide) return;

        currentSlide.background_image = null;
        currentSlide.background_gradient = null;
        currentSlide.background_video = null;
        currentSlide.background_color = '#ffffff';

        // Update canvas
        const canvasContainer = document.getElementById('canvas-container');
        if (canvasContainer) {
            canvasContainer.style.background = '#ffffff';
        }

        // Update thumbnail
        this.slideManager.updateThumbnail(this.currentSlideIndex, currentSlide);
        this.isDirty = true;

        // Update the info display
        this.updateBackgroundInfo();

        this.showNotification('Background removed', 'success');
    }

    // Apply background gradient to current slide
    applyBackgroundGradient(gradient) {
        const currentSlide = this.getCurrentSlide();
        if (!currentSlide) return;

        currentSlide.background_gradient = gradient;
        currentSlide.background_color = null;
        currentSlide.background_image = null;

        // Update canvas
        const canvasContainer = document.getElementById('canvas-container');
        if (canvasContainer) {
            canvasContainer.style.background = gradient;
        }

        // Update thumbnail
        this.slideManager.updateThumbnail(this.currentSlideIndex, currentSlide);
        this.isDirty = true;

        // Update the info display
        this.updateBackgroundInfo();
    }

    // Populate layers list (inline panel version)
    populateLayersPanel() {
        const layersList = document.getElementById('panel-layers-list');
        if (!layersList) return;

        const currentSlide = this.getCurrentSlide();
        if (!currentSlide) {
            layersList.innerHTML = '<div class="no-items-msg">No slide selected</div>';
            return;
        }

        // Get fields for this slide
        const slideFields = this.fields.filter(f => f.slide_id == currentSlide.id);

        // Get shapes for this slide
        const slideShapes = this.shapeManager?.shapes?.filter(s => s.slide_id == currentSlide.id) || [];

        const allElements = [
            ...slideFields.map(f => ({ type: 'text', id: f.id, label: f.sample_value || f.field_label || 'Text', zIndex: f.z_index || 0 })),
            ...slideShapes.map(s => ({ type: s.shapeType || 'shape', id: s.id, label: s.shapeType || 'Shape', zIndex: s.z_index || 0 }))
        ];

        if (allElements.length === 0) {
            layersList.innerHTML = '<p class="hint-text">No elements on this slide</p>';
            return;
        }

        // Sort by z_index (highest first)
        allElements.sort((a, b) => (b.zIndex || 0) - (a.zIndex || 0));

        layersList.innerHTML = allElements.map(el => `
            <div class="layer-item-inline" data-element-id="${el.id}" data-element-type="${el.type}">
                <span class="material-symbols-outlined layer-drag-handle">drag_indicator</span>
                <span class="material-symbols-outlined layer-icon">${el.type === 'text' ? 'title' : 'shapes'}</span>
                <span class="layer-label">${el.label.substring(0, 25)}${el.label.length > 25 ? '...' : ''}</span>
            </div>
        `).join('');
    }

    // Populate layers list
    populateLayers() {
        const layersList = document.getElementById('layers-list');
        if (!layersList) return;

        const slide = this.slides.find(s => s.id == this.currentSlideId);
        if (!slide || !slide.elements) {
            layersList.innerHTML = '<div class="no-items-msg">No elements on this slide</div>';
            return;
        }

        // Sort by z_index (highest first)
        const sortedElements = [...slide.elements].sort((a, b) => (b.z_index || 0) - (a.z_index || 0));

        layersList.innerHTML = sortedElements.map(el => `
            <div class="layer-item" data-element-id="${el.id}">
                <span class="material-symbols-outlined layer-drag-handle">drag_indicator</span>
                <div class="layer-preview">
                    ${el.type === 'image' ? `<img src="${el.content}" alt="">` : el.content?.substring(0, 20) || el.type}
                </div>
                <div class="layer-info">${el.type === 'text' ? (el.content || 'Text') : el.type}</div>
            </div>
        `).join('');
    }

    // Apply background color to current slide
    applyBackgroundColor(color) {
        const currentSlide = this.getCurrentSlide();
        if (!currentSlide) return;

        currentSlide.background_color = color;
        currentSlide.background_gradient = null;
        currentSlide.background_image = null;

        // Update canvas
        const canvasContainer = document.getElementById('canvas-container');
        if (canvasContainer) {
            canvasContainer.style.background = color;
        }

        // Update thumbnail
        this.slideManager.updateThumbnail(this.currentSlideIndex, currentSlide);
        this.isDirty = true;

        // Update the info display
        this.updateBackgroundInfo();
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.templateBuilder = new TemplateBuilder();
});

// Close preview modal
window.closePreviewModal = function () {
    document.getElementById('preview-modal').classList.add('hidden');
    window.templateBuilder.exporter.stopPreview();
};
