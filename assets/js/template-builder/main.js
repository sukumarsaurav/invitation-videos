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
        document.getElementById('btn-save').addEventListener('click', () => this.save());

        // Preview button
        document.getElementById('btn-preview').addEventListener('click', () => this.preview());

        // Add slide button
        document.getElementById('btn-add-slide').addEventListener('click', () => {
            this.slideManager.addSlide();
        });

        // Upload background button
        document.getElementById('btn-upload-bg').addEventListener('click', () => {
            document.getElementById('slide-bg-image').click();
        });

        document.getElementById('slide-bg-image').addEventListener('change', (e) => {
            this.uploadBackground(e.target.files[0]);
        });

        // Slide settings
        document.getElementById('slide-duration').addEventListener('change', (e) => {
            this.updateCurrentSlide({ duration_ms: parseInt(e.target.value) });
        });

        document.getElementById('slide-bg-color').addEventListener('input', (e) => {
            this.updateCurrentSlide({ background_color: e.target.value });
        });

        document.getElementById('slide-transition').addEventListener('change', (e) => {
            this.updateCurrentSlide({ transition_type: e.target.value });
        });

        // Text properties
        document.getElementById('text-sample').addEventListener('input', (e) => {
            this.textEditor.updateSelectedText({ sample_value: e.target.value });
        });

        document.getElementById('text-font-size').addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ font_size: parseInt(e.target.value) });
        });

        document.getElementById('text-font-family').addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ font_family: e.target.value });
        });

        document.getElementById('text-color').addEventListener('input', (e) => {
            this.textEditor.updateSelectedText({ font_color: e.target.value });
        });

        document.getElementById('text-animation').addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ animation_type: e.target.value });
        });

        document.getElementById('text-delay').addEventListener('change', (e) => {
            this.textEditor.updateSelectedText({ animation_delay_ms: parseInt(e.target.value) });
        });

        // Shape toolbar buttons
        this.setupShapeToolbar();

        // Shape properties
        this.setupShapeProperties();

        // Text presets (Add Heading/Subheading/Body)
        this.setupTextPresets();

        // Preset quick add button
        this.setupPresetQuickAdd();

        // Field drag and drop
        this.setupFieldDragDrop();

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
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });
        });

        canvasOverlays.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        canvasOverlays.addEventListener('drop', (e) => {
            e.preventDefault();
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
            }
        });
    }

    selectSlide(index) {
        if (index < 0 || index >= this.slides.length) return;

        this.currentSlideIndex = index;
        const slide = this.slides[index];

        // Update slide thumbnails
        document.querySelectorAll('.slide-thumb').forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });

        // Update properties panel
        document.getElementById('slide-duration').value = slide.duration_ms || 3000;
        document.getElementById('slide-bg-color').value = slide.background_color || '#ffffff';
        document.getElementById('slide-transition').value = slide.transition_type || 'fade';

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
        this.slideManager.updateThumbnail(this.currentSlideIndex, slide);
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
        return this.fields.find(f => f.id == fieldId);
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
    }

    showTextToolbar() {
        const toolbar = document.getElementById('text-toolbar');
        if (toolbar) {
            toolbar.classList.remove('hidden');
        }
    }

    hideTextToolbar() {
        const toolbar = document.getElementById('text-toolbar');
        if (toolbar) {
            toolbar.classList.add('hidden');
        }
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
        document.getElementById('preview-modal').classList.remove('hidden');
        this.exporter.playPreview();
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
