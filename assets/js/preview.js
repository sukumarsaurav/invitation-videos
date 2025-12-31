/**
 * Preview.js - Auto-playing Template Preview
 * Shows template with sample data, plays automatically on loop
 */

class TemplatePreview {
    constructor() {
        this.templateId = window.PREVIEW_DATA.templateId;
        this.template = window.PREVIEW_DATA.template;
        this.slides = window.PREVIEW_DATA.slides || [];
        this.fields = window.PREVIEW_DATA.fields || [];

        // Canvas
        this.canvas = document.getElementById('preview-canvas');
        this.ctx = this.canvas.getContext('2d');

        // Playback state
        this.isPlaying = true; // Auto-play by default
        this.currentTime = 0;
        this.animationFrame = null;
        this.totalDuration = this.calculateTotalDuration();

        // Initialize
        this.init();
    }

    init() {
        // Setup controls
        this.setupControls();
        this.setupProgressBar();

        // Preload images then start playing
        this.preloadAllImages().then(() => {
            this.play();
        });

        // Render first frame immediately
        this.renderFrame(0);
        this.updateTimeDisplay();
    }

    calculateTotalDuration() {
        return this.slides.reduce((sum, slide) => sum + (slide.duration_ms || 3000), 0);
    }

    setupControls() {
        // Play/pause button
        document.getElementById('btn-play')?.addEventListener('click', () => this.togglePlay());
        document.getElementById('btn-toggle-play')?.addEventListener('click', () => this.togglePlay());
    }

    setupProgressBar() {
        const progressBar = document.getElementById('progress-bar');
        if (!progressBar) return;

        let isDragging = false;

        const seek = (e) => {
            const rect = progressBar.getBoundingClientRect();
            const progress = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            this.currentTime = progress * this.totalDuration;
            this.renderFrame(progress);
            this.updateProgressUI(progress);
        };

        progressBar.addEventListener('mousedown', (e) => {
            isDragging = true;
            seek(e);
        });

        document.addEventListener('mousemove', (e) => {
            if (isDragging) seek(e);
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });
    }

    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }

    play() {
        if (this.isPlaying && this.animationFrame) return;
        this.isPlaying = true;

        document.getElementById('play-icon').textContent = 'pause';
        document.getElementById('play-overlay')?.classList.add('hidden');

        const startTime = performance.now() - this.currentTime;

        const animate = (now) => {
            if (!this.isPlaying) return;

            this.currentTime = (now - startTime) % this.totalDuration;
            const progress = this.currentTime / this.totalDuration;

            this.renderFrame(progress);
            this.updateProgressUI(progress);

            this.animationFrame = requestAnimationFrame(animate);
        };

        this.animationFrame = requestAnimationFrame(animate);
    }

    pause() {
        this.isPlaying = false;
        document.getElementById('play-icon').textContent = 'play_arrow';
        document.getElementById('play-overlay')?.classList.remove('hidden');

        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }

    updateProgressUI(progress) {
        document.getElementById('progress-fill').style.width = `${progress * 100}%`;

        const currentSec = Math.floor(this.currentTime / 1000);
        document.getElementById('time-current').textContent =
            `${Math.floor(currentSec / 60)}:${String(currentSec % 60).padStart(2, '0')}`;
    }

    updateTimeDisplay() {
        const totalSec = Math.floor(this.totalDuration / 1000);
        document.getElementById('time-total').textContent =
            `${Math.floor(totalSec / 60)}:${String(totalSec % 60).padStart(2, '0')}`;
    }

    async preloadAllImages() {
        const promises = this.slides.map(slide => {
            if (slide.background_image && !slide._bgImageLoaded) {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => {
                        slide._bgImage = img;
                        slide._bgImageLoaded = true;
                        resolve();
                    };
                    img.onerror = () => resolve();
                    img.src = slide.background_image;
                });
            }
            return Promise.resolve();
        });
        await Promise.all(promises);
    }

    renderFrame(progress) {
        const { width, height } = this.canvas;

        // Find current slide
        let accumulatedTime = 0;
        let currentSlide = this.slides[0];
        let slideProgress = 0;

        for (const slide of this.slides) {
            const slideDuration = slide.duration_ms || 3000;
            const slideStart = accumulatedTime / this.totalDuration;
            const slideEnd = (accumulatedTime + slideDuration) / this.totalDuration;

            if (progress >= slideStart && progress < slideEnd) {
                currentSlide = slide;
                slideProgress = (progress - slideStart) / (slideEnd - slideStart);
                break;
            }
            accumulatedTime += slideDuration;
        }

        if (!currentSlide) {
            currentSlide = this.slides[0];
            slideProgress = 0;
        }

        // Clear canvas
        this.ctx.clearRect(0, 0, width, height);

        // Draw background
        this.drawBackground(currentSlide, width, height);

        // Draw text fields with sample values
        const slideFields = this.fields.filter(f => String(f.slide_id) === String(currentSlide?.id));
        slideFields.forEach(field => {
            this.drawTextField(field, slideProgress, currentSlide?.duration_ms || 3000, width, height);
        });
    }

    drawBackground(slide, width, height) {
        if (!slide) {
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillRect(0, 0, width, height);
            return;
        }

        // Gradient background
        if (slide.background_gradient) {
            this.ctx.fillStyle = slide.background_gradient;
            this.ctx.fillRect(0, 0, width, height);
            return;
        }

        // Color background
        if (slide.background_color) {
            this.ctx.fillStyle = slide.background_color;
            this.ctx.fillRect(0, 0, width, height);
        }

        // Image background
        if (slide._bgImage) {
            this.drawImageCover(slide._bgImage, 0, 0, width, height);
        }
    }

    drawImageCover(img, x, y, w, h) {
        const imgRatio = img.width / img.height;
        const canvasRatio = w / h;

        let sx, sy, sw, sh;

        if (imgRatio > canvasRatio) {
            sh = img.height;
            sw = img.height * canvasRatio;
            sx = (img.width - sw) / 2;
            sy = 0;
        } else {
            sw = img.width;
            sh = img.width / canvasRatio;
            sx = 0;
            sy = (img.height - sh) / 2;
        }

        this.ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
    }

    drawTextField(field, slideProgress, slideDuration, width, height) {
        // Use sample_value for preview
        const text = field.sample_value || `{${field.field_name}}`;

        const x = (field.position_x / 100) * width;
        const y = (field.position_y / 100) * height;

        // Animation calculation
        const delayRatio = (field.animation_delay_ms || 0) / slideDuration;
        const durationRatio = (field.animation_duration_ms || 500) / slideDuration;

        let opacity = 1;
        let offsetX = 0;
        let offsetY = 0;
        let scale = 1;

        if (slideProgress < delayRatio) {
            opacity = 0;
        } else if (slideProgress < delayRatio + durationRatio) {
            const t = (slideProgress - delayRatio) / durationRatio;
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
                case 'bounce':
                    offsetY = -20 * Math.abs(Math.sin(t * 5)) * (1 - t);
                    break;
                default:
                    opacity = 1;
            }
        }

        this.ctx.save();
        this.ctx.globalAlpha = opacity;
        this.ctx.font = `${field.font_weight || 400} ${field.font_size || 48}px ${field.font_family || 'Inter'}`;
        this.ctx.fillStyle = field.font_color || '#000000';
        this.ctx.textAlign = field.text_align || 'center';
        this.ctx.textBaseline = 'middle';

        this.ctx.translate(x, y);
        if (scale !== 1) {
            this.ctx.scale(scale, scale);
        }
        this.ctx.fillText(text, offsetX, offsetY);

        this.ctx.restore();
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.preview = new TemplatePreview();
});
