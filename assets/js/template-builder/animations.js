/**
 * Animations - Animation definitions and preview
 */

export class Animations {
    constructor(builder) {
        this.builder = builder;
    }

    // Animation presets
    static presets = {
        none: {
            name: 'None',
            keyframes: []
        },
        fadeIn: {
            name: 'Fade In',
            keyframes: [
                { offset: 0, opacity: 0 },
                { offset: 1, opacity: 1 }
            ]
        },
        slideUp: {
            name: 'Slide Up',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateY(30px)' },
                { offset: 1, opacity: 1, transform: 'translateY(0)' }
            ]
        },
        slideDown: {
            name: 'Slide Down',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateY(-30px)' },
                { offset: 1, opacity: 1, transform: 'translateY(0)' }
            ]
        },
        slideLeft: {
            name: 'Slide Left',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateX(30px)' },
                { offset: 1, opacity: 1, transform: 'translateX(0)' }
            ]
        },
        slideRight: {
            name: 'Slide Right',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateX(-30px)' },
                { offset: 1, opacity: 1, transform: 'translateX(0)' }
            ]
        },
        zoomIn: {
            name: 'Zoom In',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'scale(0.5)' },
                { offset: 1, opacity: 1, transform: 'scale(1)' }
            ]
        },
        typewriter: {
            name: 'Typewriter',
            keyframes: [
                { offset: 0, opacity: 0, clipPath: 'inset(0 100% 0 0)' },
                { offset: 0.1, opacity: 1 },
                { offset: 1, opacity: 1, clipPath: 'inset(0 0 0 0)' }
            ]
        },
        bounce: {
            name: 'Bounce',
            keyframes: [
                { offset: 0, transform: 'translateY(0)' },
                { offset: 0.2, transform: 'translateY(-20px)' },
                { offset: 0.4, transform: 'translateY(0)' },
                { offset: 0.6, transform: 'translateY(-10px)' },
                { offset: 0.8, transform: 'translateY(0)' },
                { offset: 1, transform: 'translateY(0)' }
            ]
        }
    };

    // Preview animation on an element
    previewAnimation(element, animationType, duration = 500) {
        const preset = Animations.presets[animationType];
        if (!preset || preset.keyframes.length === 0) return;

        element.animate(preset.keyframes, {
            duration: duration,
            easing: 'ease-out',
            fill: 'forwards'
        });
    }

    // Apply animation CSS class
    applyAnimationClass(element, animationType, delay = 0, duration = 500) {
        element.style.animation = `${animationType} ${duration}ms ease-out ${delay}ms forwards`;
    }

    // Get animation values at specific progress (0-1)
    getAnimationValues(animationType, progress) {
        const preset = Animations.presets[animationType];
        if (!preset || preset.keyframes.length === 0) {
            return { opacity: 1, transform: 'none' };
        }

        // Find the two keyframes to interpolate between
        let prev = preset.keyframes[0];
        let next = preset.keyframes[preset.keyframes.length - 1];

        for (let i = 0; i < preset.keyframes.length - 1; i++) {
            if (progress >= preset.keyframes[i].offset && progress <= preset.keyframes[i + 1].offset) {
                prev = preset.keyframes[i];
                next = preset.keyframes[i + 1];
                break;
            }
        }

        // Calculate interpolation factor
        const range = next.offset - prev.offset;
        const t = range > 0 ? (progress - prev.offset) / range : 1;
        const eased = this.easeOutCubic(t);

        return {
            opacity: this.lerp(prev.opacity ?? 1, next.opacity ?? 1, eased),
            transform: this.interpolateTransform(prev.transform, next.transform, eased)
        };
    }

    lerp(a, b, t) {
        return a + (b - a) * t;
    }

    interpolateTransform(from, to, t) {
        // Simple interpolation for common transforms
        if (!from && !to) return 'none';
        if (!from) from = 'translateY(0) translateX(0) scale(1)';
        if (!to) to = 'translateY(0) translateX(0) scale(1)';

        // This is a simplified version - full implementation would parse transforms
        return t >= 0.5 ? to : from;
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
}
