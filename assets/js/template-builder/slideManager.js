/**
 * Slide Manager - Handles slide CRUD operations
 */

export class SlideManager {
    constructor(builder) {
        this.builder = builder;
        // Support both old and new container IDs
        this.slidesStrip = document.getElementById('slide-bar-wrapper') || document.getElementById('slides-strip');
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
        this.renderAllThumbnails();
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
        this.renderAllThumbnails();
        this.builder.isDirty = true;
    }

    /**
     * Get total duration of all slides in ms
     */
    getTotalDuration() {
        return this.builder.slides.reduce((total, slide) => total + (slide.duration_ms || 3000), 0);
    }

    /**
     * Render a single slide bar (horizontal, width proportional to duration)
     */
    renderSlideBar(slide, index, totalDuration) {
        const bar = document.createElement('div');
        bar.className = 'slide-duration-bar slide-bar'; // Both classes for compatibility
        bar.dataset.slideId = slide.id;
        bar.dataset.slideOrder = slide.slide_order;
        bar.dataset.duration = slide.duration_ms || 3000;

        // Calculate width based on duration percentage
        const duration = slide.duration_ms || 3000;
        const widthPercent = (duration / totalDuration) * 100;
        bar.style.flex = '1';
        bar.style.minWidth = '100px';

        // Apply background preview based on slide background
        if (slide.background_image) {
            bar.style.backgroundImage = `url(${slide.background_image})`;
            bar.style.backgroundSize = 'cover';
            bar.style.backgroundPosition = 'center';
            bar.style.backgroundColor = 'transparent';
        } else if (slide.background_gradient) {
            bar.style.background = slide.background_gradient;
        } else if (slide.background_color && slide.background_color !== '#ffffff') {
            bar.style.backgroundColor = slide.background_color;
        } else {
            bar.style.backgroundColor = '#fefce8'; // Default cream color
        }

        bar.innerHTML = `
            <span class="slide-duration-label">${(duration / 1000).toFixed(1)}s</span>
        `;

        bar.addEventListener('click', () => {
            const slideIndex = this.builder.slides.findIndex(s => s.id === slide.id);
            this.builder.selectSlide(slideIndex);
        });

        // Context menu for delete/duplicate
        bar.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            const slideIndex = this.builder.slides.findIndex(s => s.id === slide.id);
            this.showContextMenu(e.clientX, e.clientY, slideIndex);
        });

        return bar;
    }

    /**
     * Render/re-render all slide bars with proper widths
     */
    renderAllThumbnails() {
        this.slidesStrip.innerHTML = '';
        const totalDuration = this.getTotalDuration();

        this.builder.slides.forEach((slide, index) => {
            const bar = this.renderSlideBar(slide, index, totalDuration);
            if (index === this.builder.currentSlideIndex) {
                bar.classList.add('active');
            }
            this.slidesStrip.appendChild(bar);
        });

        // Update total duration display
        const durationSpan = document.getElementById('timeline-duration');
        if (durationSpan) {
            durationSpan.textContent = (totalDuration / 1000).toFixed(1) + 's';
        }

        // Refresh timeline ruler to show total duration
        if (this.builder.refreshTimeline) {
            this.builder.renderTimelineRuler();
        }
    }

    updateThumbnail(index, slide) {
        const bars = this.slidesStrip.querySelectorAll('.slide-duration-bar, .slide-bar');
        if (bars[index]) {
            const bar = bars[index];

            // Update duration label
            const durationEl = bar.querySelector('.slide-duration-label, .slide-label');
            if (durationEl) {
                durationEl.textContent = `${((slide.duration_ms || 3000) / 1000).toFixed(1)}s`;
            }

            // Update background preview
            if (slide.background_image) {
                bar.style.backgroundImage = `url(${slide.background_image})`;
                bar.style.backgroundSize = 'cover';
                bar.style.backgroundPosition = 'center';
                bar.style.backgroundColor = 'transparent';
            } else if (slide.background_gradient) {
                bar.style.backgroundImage = 'none';
                bar.style.background = slide.background_gradient;
            } else if (slide.background_color && slide.background_color !== '#ffffff') {
                bar.style.backgroundImage = 'none';
                bar.style.backgroundColor = slide.background_color;
            } else {
                bar.style.backgroundImage = 'none';
                bar.style.backgroundColor = '#fefce8'; // Default cream color
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
