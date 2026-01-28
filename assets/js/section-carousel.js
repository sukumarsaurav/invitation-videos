/**
 * Section Carousel - Homepage Template Carousel
 * Horizontal scroll on mobile, arrow navigation on desktop
 */

(function () {
    'use strict';

    class SectionCarousel {
        constructor(container) {
            this.container = container;
            this.track = container.querySelector('.carousel-track');
            this.items = Array.from(container.querySelectorAll('.carousel-item'));
            this.prevBtn = container.querySelector('.carousel-prev');
            this.nextBtn = container.querySelector('.carousel-next');
            this.dotsContainer = container.querySelector('.carousel-dots');

            this.currentIndex = 0;
            this.visibleCounts = this.parseVisibleCounts();
            this.totalItems = this.items.length;

            this.init();
        }

        parseVisibleCounts() {
            try {
                return JSON.parse(this.container.dataset.visibleCounts || '{}');
            } catch (e) {
                return { xs: 2, sm: 3, md: 4, lg: 4, xl: 4 };
            }
        }

        getBreakpoint() {
            const w = window.innerWidth;
            if (w >= 1536) return 'xl';
            if (w >= 1280) return 'lg';
            if (w >= 1024) return 'md';
            if (w >= 768) return 'sm';
            return 'xs';
        }

        getItemsPerView() {
            const bp = this.getBreakpoint();
            return this.visibleCounts[bp] || 4;
        }

        getTotalSlides() {
            const itemsPerView = this.getItemsPerView();
            return Math.ceil(this.totalItems / itemsPerView);
        }

        init() {
            if (!this.track) return;

            // Setup arrow buttons
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.prev());
            }
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.next());
            }

            // Setup dots
            this.setupDots();

            // Update on resize
            window.addEventListener('resize', () => {
                this.updateLayout();
                this.updateDots();
            });

            // Initial update
            this.updateLayout();
            this.updateArrowVisibility();
        }

        setupDots() {
            if (!this.dotsContainer) return;

            this.dotsContainer.innerHTML = '';
            const totalSlides = this.getTotalSlides();

            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('button');
                dot.className = `carousel-dot w-2 h-2 rounded-full transition-all ${i === this.currentIndex ? 'bg-primary w-6' : 'bg-slate-300'}`;
                dot.addEventListener('click', () => this.goToSlide(i));
                this.dotsContainer.appendChild(dot);
            }
        }

        updateDots() {
            if (!this.dotsContainer) return;

            const dots = this.dotsContainer.querySelectorAll('.carousel-dot');
            const totalSlides = this.getTotalSlides();

            // Recreate dots if count changed
            if (dots.length !== totalSlides) {
                this.setupDots();
                return;
            }

            dots.forEach((dot, i) => {
                if (i === this.currentIndex) {
                    dot.classList.remove('bg-slate-300');
                    dot.classList.add('bg-primary', 'w-6');
                } else {
                    dot.classList.remove('bg-primary', 'w-6');
                    dot.classList.add('bg-slate-300');
                }
            });
        }

        updateLayout() {
            const bp = this.getBreakpoint();
            const itemsPerView = this.getItemsPerView();

            // On mobile (xs), use horizontal scroll
            if (bp === 'xs') {
                this.track.style.transform = '';
                this.items.forEach(item => {
                    item.style.width = `calc(${100 / itemsPerView}% - 12px)`;
                    item.style.flexShrink = '0';
                });
            } else {
                // On larger screens, use slide navigation
                const slideWidth = 100 / itemsPerView;
                const offset = this.currentIndex * slideWidth * itemsPerView;

                this.items.forEach(item => {
                    item.style.width = `calc(${slideWidth}% - 16px)`;
                    item.style.flexShrink = '0';
                });

                this.track.style.transform = `translateX(-${offset}%)`;
            }
        }

        updateArrowVisibility() {
            const totalSlides = this.getTotalSlides();

            if (this.prevBtn) {
                this.prevBtn.style.opacity = this.currentIndex === 0 ? '0.3' : '1';
                this.prevBtn.style.pointerEvents = this.currentIndex === 0 ? 'none' : 'auto';
            }

            if (this.nextBtn) {
                const isLast = this.currentIndex >= totalSlides - 1;
                this.nextBtn.style.opacity = isLast ? '0.3' : '1';
                this.nextBtn.style.pointerEvents = isLast ? 'none' : 'auto';
            }
        }

        next() {
            const totalSlides = this.getTotalSlides();
            if (this.currentIndex < totalSlides - 1) {
                this.currentIndex++;
                this.updateLayout();
                this.updateDots();
                this.updateArrowVisibility();
            }
        }

        prev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.updateLayout();
                this.updateDots();
                this.updateArrowVisibility();
            }
        }

        goToSlide(index) {
            this.currentIndex = index;
            this.updateLayout();
            this.updateDots();
            this.updateArrowVisibility();
        }
    }

    // Initialize all carousels
    function initCarousels() {
        document.querySelectorAll('.section-carousel').forEach(container => {
            new SectionCarousel(container);
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarousels);
    } else {
        initCarousels();
    }

    // Expose for external use
    window.SectionCarousel = SectionCarousel;

})();
