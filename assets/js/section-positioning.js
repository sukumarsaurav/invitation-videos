/**
 * Section Positioning JavaScript
 * Applies responsive positioning to SVG and Image elements based on current breakpoint
 */

(function () {
    'use strict';

    // Breakpoint detection matching Tailwind config
    function getBreakpoint() {
        const w = window.innerWidth;
        if (w >= 1536) return 'xl';
        if (w >= 1280) return 'lg';
        if (w >= 1024) return 'md';
        if (w >= 768) return 'sm';
        return 'xs';
    }

    // Apply positions to all section elements
    function applyPositions() {
        const bp = getBreakpoint();

        document.querySelectorAll('.section-element[data-positions]').forEach(el => {
            try {
                const positions = JSON.parse(el.dataset.positions);
                const pos = positions[bp] || positions.xs || {};

                // Build transform string
                const transforms = [];

                // Position
                const x = pos.x !== undefined ? pos.x : 0;
                const y = pos.y !== undefined ? pos.y : 0;
                transforms.push(`translate(${x}px, ${y}px)`);

                // Scale
                if (pos.scale !== undefined && pos.scale !== 1) {
                    transforms.push(`scale(${pos.scale})`);
                }

                // Rotation
                if (pos.rotation !== undefined && pos.rotation !== 0) {
                    transforms.push(`rotate(${pos.rotation}deg)`);
                }

                // Apply transform
                el.style.transform = transforms.join(' ');

                // Opacity (for SVG)
                if (pos.opacity !== undefined) {
                    el.style.opacity = pos.opacity / 100;
                }

                // Z-Index
                if (pos.zIndex !== undefined) {
                    el.style.zIndex = pos.zIndex;
                }

                // Visibility (for Image)
                if (pos.visible !== undefined) {
                    el.style.display = pos.visible ? '' : 'none';
                }

            } catch (e) {
                console.error('Error parsing section positions:', e);
            }
        });
    }

    // Intersection Observer for scroll animations
    function setupScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add visible class after a small delay for smoother effect
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                        // Reapply positions to ensure transform is correct after animation
                        applyPositions();
                    }, 100);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px' // Trigger slightly before element is fully visible
        });

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    }

    // Debounce function for resize events
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize
    function init() {
        applyPositions();
        setupScrollAnimations();

        // Reapply on resize (debounced)
        window.addEventListener('resize', debounce(applyPositions, 100));
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for external use if needed
    window.SectionPositioning = {
        applyPositions: applyPositions,
        getBreakpoint: getBreakpoint
    };

})();
