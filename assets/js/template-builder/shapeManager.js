/**
 * Shape Manager - Handles shape elements on slides
 * Supports rectangles, ellipses, lines, and images
 */

export class ShapeManager {
    constructor(builder) {
        this.builder = builder;
        this.overlaysContainer = document.getElementById('canvas-overlays');
        this.shapes = []; // Array of shape data for current slide
        this.selectedShape = null;
        this.resizeHandle = null;
        this.isDragging = false;
        this.isResizing = false;
    }

    /**
     * Add a new shape to the current slide
     * @param {string} type - 'rectangle' | 'ellipse' | 'line' | 'image'
     * @param {Object} options - Optional initial properties
     */
    addShape(type, options = {}) {
        const currentSlide = this.builder.getCurrentSlide();
        if (!currentSlide) return null;

        const shapeId = 'shape_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        const defaults = {
            rectangle: { width: 20, height: 15, fill: '#7c3aed', stroke: 'transparent', strokeWidth: 0, borderRadius: 0 },
            ellipse: { width: 15, height: 15, fill: '#ec4899', stroke: 'transparent', strokeWidth: 0 },
            line: { width: 30, height: 0.5, fill: '#f59e0b', stroke: 'transparent', strokeWidth: 2 },
            image: { width: 30, height: 30, fill: 'transparent', stroke: 'transparent', strokeWidth: 0, src: '' }
        };

        const shape = {
            id: shapeId,
            type: type,
            slideId: currentSlide.id,
            x: options.x ?? 40,
            y: options.y ?? 40,
            width: options.width ?? defaults[type]?.width ?? 20,
            height: options.height ?? defaults[type]?.height ?? 15,
            fill: options.fill ?? defaults[type]?.fill ?? '#7c3aed',
            stroke: options.stroke ?? defaults[type]?.stroke ?? 'transparent',
            strokeWidth: options.strokeWidth ?? defaults[type]?.strokeWidth ?? 0,
            borderRadius: options.borderRadius ?? defaults[type]?.borderRadius ?? 0,
            rotation: options.rotation ?? 0,
            opacity: options.opacity ?? 1,
            animation: options.animation ?? 'none',
            animationDelay: options.animationDelay ?? 0,
            animationDuration: options.animationDuration ?? 500,
            src: options.src ?? '', // For image shapes
            zIndex: this.shapes.length + 1
        };

        this.shapes.push(shape);
        this.renderShape(shape);
        this.builder.isDirty = true;

        return shape;
    }

    /**
     * Upload and add an image shape
     * @param {File} file - Image file to upload
     */
    async addImageShape(file) {
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'upload_shape_image');
        formData.append('template_id', this.builder.templateId);
        formData.append('image', file);
        formData.append('csrf_token', this.builder.csrfToken);

        try {
            const response = await fetch('/api/template-builder.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.addShape('image', { src: result.url, x: 35, y: 35 });
            } else {
                console.error('Image upload failed:', result.error);
                alert('Failed to upload image: ' + result.error);
            }
        } catch (error) {
            console.error('Image upload error:', error);
            alert('Failed to upload image');
        }
    }

    /**
     * Render a shape element on the canvas
     */
    renderShape(shape) {
        let element = this.overlaysContainer.querySelector(`[data-shape-id="${shape.id}"]`);

        if (!element) {
            element = document.createElement('div');
            element.className = 'canvas-shape-element';
            element.dataset.shapeId = shape.id;
            element.dataset.shapeType = shape.type;
            this.overlaysContainer.appendChild(element);

            this.makeInteractive(element, shape);
        }

        // Apply styles
        element.style.left = `${shape.x}%`;
        element.style.top = `${shape.y}%`;
        element.style.width = `${shape.width}%`;
        element.style.height = `${shape.height}%`;
        element.style.opacity = shape.opacity;
        element.style.transform = `translate(-50%, -50%) rotate(${shape.rotation}deg)`;
        element.style.zIndex = shape.zIndex;

        if (shape.type === 'rectangle') {
            element.style.backgroundColor = shape.fill;
            element.style.borderRadius = `${shape.borderRadius}px`;
            element.style.border = shape.strokeWidth > 0 ? `${shape.strokeWidth}px solid ${shape.stroke}` : 'none';
        } else if (shape.type === 'ellipse') {
            element.style.backgroundColor = shape.fill;
            element.style.borderRadius = '50%';
            element.style.border = shape.strokeWidth > 0 ? `${shape.strokeWidth}px solid ${shape.stroke}` : 'none';
        } else if (shape.type === 'line') {
            element.style.backgroundColor = shape.fill;
            element.style.height = '2px';
            element.style.borderRadius = '1px';
        } else if (shape.type === 'image' && shape.src) {
            element.style.backgroundImage = `url(${shape.src})`;
            element.style.backgroundSize = 'cover';
            element.style.backgroundPosition = 'center';
            element.style.backgroundColor = 'transparent';
        }

        return element;
    }

    /**
     * Make a shape element interactive (draggable, resizable)
     */
    makeInteractive(element, shape) {
        let startX, startY, startLeft, startTop, startWidth, startHeight;

        // Click to select
        element.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            this.selectShape(element, shape);

            // Check if clicking on resize handle
            const rect = element.getBoundingClientRect();
            const handleSize = 10;
            const isBottomRight = (e.clientX > rect.right - handleSize && e.clientY > rect.bottom - handleSize);

            if (isBottomRight && element.classList.contains('selected')) {
                this.isResizing = true;
                this.resizeHandle = 'se';
            } else {
                this.isDragging = true;
            }

            startX = e.clientX;
            startY = e.clientY;
            startLeft = shape.x;
            startTop = shape.y;
            startWidth = shape.width;
            startHeight = shape.height;

            element.style.cursor = this.isResizing ? 'se-resize' : 'grabbing';
            e.preventDefault();
        });

        const onMouseMove = (e) => {
            if (!this.isDragging && !this.isResizing) return;
            if (this.selectedShape?.id !== shape.id) return;

            const container = this.overlaysContainer.getBoundingClientRect();
            const deltaX = ((e.clientX - startX) / container.width) * 100;
            const deltaY = ((e.clientY - startY) / container.height) * 100;

            if (this.isDragging) {
                shape.x = Math.max(2, Math.min(98, startLeft + deltaX));
                shape.y = Math.max(2, Math.min(98, startTop + deltaY));
            } else if (this.isResizing) {
                shape.width = Math.max(5, startWidth + deltaX);
                shape.height = Math.max(5, startHeight + deltaY);
            }

            this.renderShape(shape);
        };

        const onMouseUp = () => {
            if (this.isDragging || this.isResizing) {
                this.isDragging = false;
                this.isResizing = false;
                element.style.cursor = 'move';
                this.builder.isDirty = true;
            }
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }

    /**
     * Select a shape
     */
    selectShape(element, shape) {
        this.deselectShape();
        this.selectedShape = shape;
        element.classList.add('selected');

        // Show shape properties panel
        this.showShapeProperties(shape);
    }

    /**
     * Deselect current shape
     */
    deselectShape() {
        if (this.selectedShape) {
            const element = this.overlaysContainer.querySelector(`[data-shape-id="${this.selectedShape.id}"]`);
            if (element) {
                element.classList.remove('selected');
            }
            this.selectedShape = null;
        }
        this.hideShapeProperties();
    }

    /**
     * Update selected shape properties
     */
    updateShape(updates) {
        if (!this.selectedShape) return;

        Object.assign(this.selectedShape, updates);
        this.renderShape(this.selectedShape);
        this.builder.isDirty = true;
    }

    /**
     * Delete selected shape
     */
    deleteSelectedShape() {
        if (!this.selectedShape) return;

        const element = this.overlaysContainer.querySelector(`[data-shape-id="${this.selectedShape.id}"]`);
        if (element) element.remove();

        this.shapes = this.shapes.filter(s => s.id !== this.selectedShape.id);
        this.selectedShape = null;
        this.hideShapeProperties();
        this.builder.isDirty = true;
    }

    /**
     * Show shape properties in the panel
     */
    showShapeProperties(shape) {
        const panel = document.getElementById('shape-properties');
        if (!panel) return;

        panel.style.display = 'block';

        // Populate values
        const fillInput = document.getElementById('shape-fill');
        const strokeInput = document.getElementById('shape-stroke');
        const strokeWidthInput = document.getElementById('shape-stroke-width');
        const opacityInput = document.getElementById('shape-opacity');
        const radiusInput = document.getElementById('shape-radius');
        const animationInput = document.getElementById('shape-animation');

        if (fillInput) fillInput.value = shape.fill || '#7c3aed';
        if (strokeInput) strokeInput.value = shape.stroke || '#000000';
        if (strokeWidthInput) strokeWidthInput.value = shape.strokeWidth || 0;
        if (opacityInput) opacityInput.value = Math.round(shape.opacity * 100);
        if (radiusInput) radiusInput.value = shape.borderRadius || 0;
        if (animationInput) animationInput.value = shape.animation || 'none';
    }

    /**
     * Hide shape properties panel
     */
    hideShapeProperties() {
        const panel = document.getElementById('shape-properties');
        if (panel) panel.style.display = 'none';
    }

    /**
     * Render all shapes for a slide
     */
    renderShapesForSlide(slideId) {
        // Clear existing shapes from DOM
        this.overlaysContainer.querySelectorAll('.canvas-shape-element').forEach(el => el.remove());

        // Filter shapes for this slide
        const shapesForSlide = this.shapes.filter(s => s.slideId == slideId);
        shapesForSlide.forEach(shape => this.renderShape(shape));
    }

    /**
     * Load shapes from saved data
     */
    loadShapes(shapesData) {
        this.shapes = shapesData || [];
    }

    /**
     * Get shapes data for saving
     */
    getShapesData() {
        return this.shapes;
    }

    /**
     * Get shapes for a specific slide
     */
    getShapesForSlide(slideId) {
        return this.shapes.filter(s => s.slideId == slideId);
    }
}
