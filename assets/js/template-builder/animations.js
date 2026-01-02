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
        fadeOut: {
            name: 'Fade Out',
            keyframes: [
                { offset: 0, opacity: 1 },
                { offset: 1, opacity: 0 }
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
        zoomOut: {
            name: 'Zoom Out',
            keyframes: [
                { offset: 0, opacity: 1, transform: 'scale(1)' },
                { offset: 1, opacity: 0, transform: 'scale(0.5)' }
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
        },
        pulse: {
            name: 'Pulse',
            keyframes: [
                { offset: 0, transform: 'scale(1)' },
                { offset: 0.5, transform: 'scale(1.05)' },
                { offset: 1, transform: 'scale(1)' }
            ]
        },
        shake: {
            name: 'Shake',
            keyframes: [
                { offset: 0, transform: 'translateX(0)' },
                { offset: 0.1, transform: 'translateX(-5px)' },
                { offset: 0.2, transform: 'translateX(5px)' },
                { offset: 0.3, transform: 'translateX(-5px)' },
                { offset: 0.4, transform: 'translateX(5px)' },
                { offset: 0.5, transform: 'translateX(-5px)' },
                { offset: 0.6, transform: 'translateX(5px)' },
                { offset: 0.7, transform: 'translateX(-5px)' },
                { offset: 0.8, transform: 'translateX(5px)' },
                { offset: 0.9, transform: 'translateX(-5px)' },
                { offset: 1, transform: 'translateX(0)' }
            ]
        },
        flip: {
            name: 'Flip',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'perspective(400px) rotateY(90deg)' },
                { offset: 0.4, transform: 'perspective(400px) rotateY(-20deg)' },
                { offset: 0.6, opacity: 1, transform: 'perspective(400px) rotateY(10deg)' },
                { offset: 0.8, transform: 'perspective(400px) rotateY(-5deg)' },
                { offset: 1, opacity: 1, transform: 'perspective(400px) rotateY(0)' }
            ]
        },
        rotate: {
            name: 'Rotate In',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'rotate(-180deg)' },
                { offset: 1, opacity: 1, transform: 'rotate(0)' }
            ]
        },
        rise: {
            name: 'Rise',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateY(50px)' },
                { offset: 1, opacity: 1, transform: 'translateY(0)' }
            ]
        },
        pan: {
            name: 'Pan',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateX(-50px)' },
                { offset: 1, opacity: 1, transform: 'translateX(0)' }
            ]
        },
        pop: {
            name: 'Pop',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'scale(0.8)' },
                { offset: 0.6, transform: 'scale(1.1)' },
                { offset: 1, opacity: 1, transform: 'scale(1)' }
            ]
        },
        wipe: {
            name: 'Wipe',
            keyframes: [
                { offset: 0, clipPath: 'inset(0 100% 0 0)' },
                { offset: 1, clipPath: 'inset(0 0 0 0)' }
            ]
        },
        blur: {
            name: 'Blur',
            keyframes: [
                { offset: 0, opacity: 0, filter: 'blur(10px)' },
                { offset: 1, opacity: 1, filter: 'blur(0)' }
            ]
        },
        neon: {
            name: 'Neon',
            keyframes: [
                { offset: 0, opacity: 0, filter: 'brightness(2) drop-shadow(0 0 10px currentColor)' },
                { offset: 0.5, opacity: 1, filter: 'brightness(1.5) drop-shadow(0 0 20px currentColor)' },
                { offset: 1, opacity: 1, filter: 'brightness(1) drop-shadow(0 0 5px currentColor)' }
            ]
        },
        stomp: {
            name: 'Stomp',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'scale(2)' },
                { offset: 0.7, opacity: 1, transform: 'scale(0.9)' },
                { offset: 1, opacity: 1, transform: 'scale(1)' }
            ]
        },
        scrapbook: {
            name: 'Scrapbook',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'rotate(-10deg) scale(0.8)' },
                { offset: 0.5, transform: 'rotate(5deg) scale(1.05)' },
                { offset: 1, opacity: 1, transform: 'rotate(0) scale(1)' }
            ]
        },
        shift: {
            name: 'Shift',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateX(-20px) skewX(-10deg)' },
                { offset: 1, opacity: 1, transform: 'translateX(0) skewX(0)' }
            ]
        },
        merge: {
            name: 'Merge',
            keyframes: [
                { offset: 0, opacity: 0, letterSpacing: '0.5em' },
                { offset: 1, opacity: 1, letterSpacing: 'normal' }
            ]
        },
        block: {
            name: 'Block',
            keyframes: [
                { offset: 0, clipPath: 'inset(0 0 100% 0)' },
                { offset: 1, clipPath: 'inset(0 0 0 0)' }
            ]
        },
        burst: {
            name: 'Burst',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'scale(0)' },
                { offset: 0.5, opacity: 1, transform: 'scale(1.3)' },
                { offset: 1, opacity: 1, transform: 'scale(1)' }
            ]
        },
        roll: {
            name: 'Roll',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateX(-100%) rotate(-360deg)' },
                { offset: 1, opacity: 1, transform: 'translateX(0) rotate(0)' }
            ]
        },
        skate: {
            name: 'Skate',
            keyframes: [
                { offset: 0, opacity: 0, transform: 'translateX(-50px) skewX(-20deg)' },
                { offset: 0.7, transform: 'translateX(10px) skewX(5deg)' },
                { offset: 1, opacity: 1, transform: 'translateX(0) skewX(0)' }
            ]
        },
        spread: {
            name: 'Spread',
            keyframes: [
                { offset: 0, opacity: 0, letterSpacing: '-0.5em' },
                { offset: 1, opacity: 1, letterSpacing: 'normal' }
            ]
        },
        clarify: {
            name: 'Clarify',
            keyframes: [
                { offset: 0, opacity: 0, filter: 'blur(20px)', transform: 'scale(0.9)' },
                { offset: 1, opacity: 1, filter: 'blur(0)', transform: 'scale(1)' }
            ]
        }
    };

    // Get list of animation names for dropdown menus
    static getAnimationList() {
        return Object.entries(Animations.presets).map(([key, preset]) => ({
            value: key,
            label: preset.name
        }));
    }

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

