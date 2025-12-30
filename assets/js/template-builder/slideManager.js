/**
 * Slide Manager - Handles slide CRUD operations
 */

export class SlideManager {
    constructor(builder) {
        this.builder = builder;
        this.slidesStrip = document.getElementById('slides-strip');
    }

    addSlide() {
        const slideOrder = this.builder.slides.length;
        const newSlide = {
            id: `new_${Date.now()}`,
            template_id: this.builder.templateId,
            slide_order: slideOrder,
            duration_ms: 3000,
            background_color: '#ffffff',
            background_image: null,
            transition_type: 'fade'
        };

        this.builder.slides.push(newSlide);
        this.renderThumbnail(newSlide, slideOrder);
        this.builder.isDirty = true;

        // Select the new slide
        this.builder.selectSlide(slideOrder);
    }

    removeSlide(index) {
        if (this.builder.slides.length <= 1) {
            alert('Cannot delete the only slide');
            return;
        }

        if (!confirm('Delete this slide?')) return;

        this.builder.slides.splice(index, 1);

        // Update slide orders
        this.builder.slides.forEach((slide, i) => {
            slide.slide_order = i;
        });

        // Re-render all thumbnails
        this.renderAllThumbnails();

        // Select previous slide or first
        const newIndex = Math.min(index, this.builder.slides.length - 1);
        this.builder.selectSlide(newIndex);
        this.builder.isDirty = true;
    }

    duplicateSlide(index) {
        const original = this.builder.slides[index];
        const duplicate = {
            ...original,
            id: `new_${Date.now()}`,
            slide_order: this.builder.slides.length
        };

        this.builder.slides.push(duplicate);
        this.renderThumbnail(duplicate, this.builder.slides.length - 1);
        this.builder.isDirty = true;
    }

    renderThumbnail(slide, index) {
        const thumb = document.createElement('div');
        thumb.className = 'slide-thumb';
        thumb.dataset.slideId = slide.id;
        thumb.dataset.slideOrder = slide.slide_order;

        // Apply background based on type (priority: gradient > image > color)
        if (slide.background_gradient) {
            thumb.style.background = slide.background_gradient;
        } else if (slide.background_image) {
            thumb.style.background = `url(${slide.background_image}) center/cover no-repeat`;
        } else {
            thumb.style.background = slide.background_color || '#ffffff';
        }

        thumb.innerHTML = `
            <span class="slide-duration">${(slide.duration_ms / 1000).toFixed(1)}s</span>
        `;

        thumb.addEventListener('click', () => {
            const slideIndex = this.builder.slides.findIndex(s => s.id === slide.id);
            this.builder.selectSlide(slideIndex);
        });

        // Context menu for delete/duplicate
        thumb.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            const slideIndex = this.builder.slides.findIndex(s => s.id === slide.id);
            this.showContextMenu(e.clientX, e.clientY, slideIndex);
        });

        this.slidesStrip.appendChild(thumb);
    }

    renderAllThumbnails() {
        this.slidesStrip.innerHTML = '';
        this.builder.slides.forEach((slide, index) => {
            this.renderThumbnail(slide, index);
        });
    }

    updateThumbnail(index, slide) {
        const thumbs = this.slidesStrip.querySelectorAll('.slide-thumb');
        if (thumbs[index]) {
            // Apply background based on type (priority: gradient > image > color)
            if (slide.background_gradient) {
                thumbs[index].style.background = slide.background_gradient;
            } else if (slide.background_image) {
                thumbs[index].style.background = `url(${slide.background_image}) center/cover no-repeat`;
            } else {
                thumbs[index].style.background = slide.background_color || '#ffffff';
            }

            const durationEl = thumbs[index].querySelector('.slide-duration');
            if (durationEl) {
                durationEl.textContent = `${(slide.duration_ms / 1000).toFixed(1)}s`;
            }
        }
    }

    showContextMenu(x, y, slideIndex) {
        // Remove existing context menu
        const existing = document.querySelector('.slide-context-menu');
        if (existing) existing.remove();

        const menu = document.createElement('div');
        menu.className = 'slide-context-menu';
        menu.style.cssText = `
            position: fixed;
            left: ${x}px;
            top: ${y}px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            padding: 0.5rem 0;
            z-index: 1000;
            min-width: 140px;
        `;

        menu.innerHTML = `
            <button class="context-btn" data-action="duplicate">
                <span class="material-symbols-outlined">content_copy</span>
                Duplicate
            </button>
            <button class="context-btn text-red-400" data-action="delete">
                <span class="material-symbols-outlined">delete</span>
                Delete
            </button>
        `;

        const style = document.createElement('style');
        style.textContent = `
            .context-btn {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                width: 100%;
                padding: 0.5rem 1rem;
                background: transparent;
                border: none;
                color: #f1f5f9;
                font-size: 0.875rem;
                cursor: pointer;
                text-align: left;
            }
            .context-btn:hover {
                background: #334155;
            }
            .context-btn .material-symbols-outlined {
                font-size: 1.125rem;
            }
        `;
        menu.appendChild(style);

        menu.querySelector('[data-action="duplicate"]').addEventListener('click', () => {
            this.duplicateSlide(slideIndex);
            menu.remove();
        });

        menu.querySelector('[data-action="delete"]').addEventListener('click', () => {
            this.removeSlide(slideIndex);
            menu.remove();
        });

        document.body.appendChild(menu);

        // Close on click outside
        setTimeout(() => {
            document.addEventListener('click', function closeMenu() {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            });
        }, 10);
    }
}
