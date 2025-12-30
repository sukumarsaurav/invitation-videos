/**
 * Canvas Editor - Handles canvas rendering and interactions
 */

export class CanvasEditor {
    constructor(builder) {
        this.builder = builder;
        this.canvas = document.getElementById('template-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.container = document.getElementById('canvas-container');
    }

    renderSlide(slide) {
        const { width, height } = this.canvas;

        // Clear canvas
        this.ctx.clearRect(0, 0, width, height);

        // Draw background color
        this.ctx.fillStyle = slide.background_color || '#ffffff';
        this.ctx.fillRect(0, 0, width, height);

        // Draw background image if exists
        if (slide.background_image) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                this.drawImageCover(img, 0, 0, width, height);
            };
            img.src = slide.background_image;
        }
    }

    drawImageCover(img, x, y, w, h) {
        const imgRatio = img.width / img.height;
        const canvasRatio = w / h;

        let sx, sy, sw, sh;

        if (imgRatio > canvasRatio) {
            // Image is wider - crop sides
            sh = img.height;
            sw = img.height * canvasRatio;
            sx = (img.width - sw) / 2;
            sy = 0;
        } else {
            // Image is taller - crop top/bottom
            sw = img.width;
            sh = img.width / canvasRatio;
            sx = 0;
            sy = (img.height - sh) / 2;
        }

        this.ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
    }

    // Export current canvas as image data URL
    toDataURL() {
        return this.canvas.toDataURL('image/png');
    }

    // Render frame with texts for export
    renderFrameWithTexts(slide, textsForSlide, animationProgress) {
        const { width, height } = this.canvas;

        // Clear and draw background
        this.ctx.clearRect(0, 0, width, height);
        this.ctx.fillStyle = slide.background_color || '#ffffff';
        this.ctx.fillRect(0, 0, width, height);

        // Draw texts
        textsForSlide.forEach(field => {
            const text = field.sample_value || `{${field.field_name}}`;
            const x = (field.position_x / 100) * width;
            const y = (field.position_y / 100) * height;

            // Calculate animation state
            const delay = (field.animation_delay_ms || 0) / (slide.duration_ms || 3000);
            const duration = (field.animation_duration_ms || 500) / (slide.duration_ms || 3000);

            let opacity = 1;
            let offsetX = 0;
            let offsetY = 0;
            let scale = 1;

            if (animationProgress < delay) {
                opacity = 0;
            } else if (animationProgress < delay + duration) {
                const t = (animationProgress - delay) / duration;
                const eased = this.easeOutCubic(t);

                switch (field.animation_type) {
                    case 'fadeIn':
                        opacity = eased;
                        break;
                    case 'slideUp':
                        opacity = eased;
                        offsetY = (1 - eased) * 50;
                        break;
                    case 'slideDown':
                        opacity = eased;
                        offsetY = (eased - 1) * 50;
                        break;
                    case 'slideLeft':
                        opacity = eased;
                        offsetX = (1 - eased) * 50;
                        break;
                    case 'slideRight':
                        opacity = eased;
                        offsetX = (eased - 1) * 50;
                        break;
                    case 'zoomIn':
                        opacity = eased;
                        scale = 0.5 + (eased * 0.5);
                        break;
                    default:
                        opacity = 1;
                }
            }

            this.ctx.save();
            this.ctx.globalAlpha = opacity;
            this.ctx.font = `${field.font_weight || 400} ${field.font_size || 24}px ${field.font_family || 'Inter'}`;
            this.ctx.fillStyle = field.font_color || '#000000';
            this.ctx.textAlign = field.text_align || 'center';
            this.ctx.textBaseline = 'middle';

            if (scale !== 1) {
                this.ctx.translate(x, y);
                this.ctx.scale(scale, scale);
                this.ctx.fillText(text, offsetX, offsetY);
            } else {
                this.ctx.fillText(text, x + offsetX, y + offsetY);
            }

            this.ctx.restore();
        });
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
}
