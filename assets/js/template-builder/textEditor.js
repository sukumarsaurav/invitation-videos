/**
 * Text Editor - Handles text elements on slides
 */

export class TextEditor {
    constructor(builder) {
        this.builder = builder;
        this.overlaysContainer = document.getElementById('canvas-overlays');
    }

    addTextToSlide(fieldId, fieldName, fieldLabel, sampleValue, x, y) {
        const currentSlide = this.builder.getCurrentSlide();
        if (!currentSlide) return;

        // Update field with slide assignment and position
        this.builder.updateField(fieldId, {
            slide_id: currentSlide.id,
            position_x: x,
            position_y: y,
            sample_value: sampleValue || fieldLabel
        });

        // Update the field badge in the left panel
        const fieldItem = document.querySelector(`.field-item[data-field-id="${fieldId}"]`);
        if (fieldItem) {
            const badge = fieldItem.querySelector('.field-slide-badge');
            const slideIndex = this.builder.slides.findIndex(s => s.id === currentSlide.id);
            badge.textContent = `S${slideIndex + 1}`;
            badge.dataset.slide = currentSlide.id;
        }

        // Render text element on canvas
        this.renderTextElement(fieldId);
        this.builder.isDirty = true;
    }

    renderTextsForSlide(slideId) {
        // Clear existing text elements
        this.overlaysContainer.innerHTML = '';

        // Get fields assigned to this slide
        const fieldsForSlide = this.builder.fields.filter(f => f.slide_id == slideId);

        fieldsForSlide.forEach(field => {
            this.renderTextElement(field.id);
        });
    }

    renderTextElement(fieldId) {
        const field = this.builder.getFieldById(fieldId);
        if (!field) return;

        // Check if element already exists
        let element = this.overlaysContainer.querySelector(`[data-field-id="${fieldId}"]`);

        if (!element) {
            element = document.createElement('div');
            element.className = 'canvas-text-element';
            element.dataset.fieldId = fieldId;
            this.overlaysContainer.appendChild(element);

            // Make draggable
            this.makeDraggable(element);

            // Click to select
            element.addEventListener('click', (e) => {
                e.stopPropagation();
                this.builder.selectElement(element);
            });
        }

        // Update styles
        element.style.left = `${field.position_x || 50}%`;
        element.style.top = `${field.position_y || 50}%`;
        element.style.transform = 'translate(-50%, -50%)';
        element.style.fontFamily = field.font_family || 'Inter';
        element.style.fontSize = `${(field.font_size || 24) / 4}px`; // Scale down for preview
        element.style.fontWeight = field.font_weight || 400;
        element.style.color = field.font_color || '#000000';
        element.style.textAlign = field.text_align || 'center';

        // Display sample value or placeholder
        element.textContent = field.sample_value || `{${field.field_name}}`;
    }

    makeDraggable(element) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;

        element.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseFloat(element.style.left);
            startTop = parseFloat(element.style.top);
            element.style.cursor = 'grabbing';
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const container = this.overlaysContainer.getBoundingClientRect();
            const deltaX = ((e.clientX - startX) / container.width) * 100;
            const deltaY = ((e.clientY - startY) / container.height) * 100;

            let newLeft = startLeft + deltaX;
            let newTop = startTop + deltaY;

            // Clamp to bounds
            newLeft = Math.max(5, Math.min(95, newLeft));
            newTop = Math.max(5, Math.min(95, newTop));

            element.style.left = `${newLeft}%`;
            element.style.top = `${newTop}%`;
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                element.style.cursor = 'move';

                // Update field position
                const fieldId = element.dataset.fieldId;
                const newX = Math.round(parseFloat(element.style.left));
                const newY = Math.round(parseFloat(element.style.top));

                this.builder.updateField(fieldId, {
                    position_x: newX,
                    position_y: newY
                });
            }
        });
    }

    updateSelectedText(updates) {
        const selected = this.builder.selectedElement;
        if (!selected) return;

        const fieldId = selected.dataset.fieldId;
        this.builder.updateField(fieldId, updates);

        // Re-render the element
        this.renderTextElement(fieldId);
    }

    removeTextFromSlide(fieldId) {
        this.builder.updateField(fieldId, {
            slide_id: null,
            position_x: 50,
            position_y: 50
        });

        // Remove from canvas
        const element = this.overlaysContainer.querySelector(`[data-field-id="${fieldId}"]`);
        if (element) element.remove();

        // Update badge
        const fieldItem = document.querySelector(`.field-item[data-field-id="${fieldId}"]`);
        if (fieldItem) {
            const badge = fieldItem.querySelector('.field-slide-badge');
            badge.textContent = '—';
            badge.dataset.slide = '';
        }

        this.builder.isDirty = true;
    }
}
