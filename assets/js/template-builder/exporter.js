/**
 * Exporter - Preview playback and video export
 */

export class Exporter {
    constructor(builder) {
        this.builder = builder;
        this.previewCanvas = document.getElementById('preview-canvas');
        this.previewCtx = this.previewCanvas.getContext('2d');
        this.isPlaying = false;
        this.animationFrame = null;
    }

    playPreview() {
        if (this.isPlaying) return;
        this.isPlaying = true;
        this.isPaused = false;

        const slides = this.builder.slides;
        const fields = this.builder.fields;

        // Calculate total duration
        this.totalDuration = slides.reduce((sum, s) => sum + (s.duration_ms || 3000), 0);

        // If resuming from pause, use saved offset
        const startOffset = this.pausedAt || 0;
        this.startTime = performance.now() - startOffset;

        document.getElementById('btn-play-preview').innerHTML =
            '<span class="material-symbols-outlined">pause</span>';

        const animate = (currentTime) => {
            if (!this.isPlaying) return;

            const elapsed = currentTime - this.startTime;
            const progress = (elapsed % this.totalDuration) / this.totalDuration;

            // Store current progress for pause/resume
            this.currentProgress = progress;
            this.currentElapsed = elapsed % this.totalDuration;

            // Find current slide
            let accumulatedTime = 0;
            let currentSlide = slides[0];
            let slideProgress = 0;

            for (const slide of slides) {
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

            // Get fields for current slide
            const slideFields = fields.filter(f => f.slide_id == currentSlide.id);

            // Render frame
            this.renderPreviewFrame(currentSlide, slideFields, slideProgress);

            // Update progress bar
            document.getElementById('preview-progress-bar').style.width = `${progress * 100}%`;

            // Update time display
            const elapsedSec = Math.floor((elapsed % this.totalDuration) / 1000);
            const totalSec = Math.floor(this.totalDuration / 1000);
            document.getElementById('preview-time').textContent =
                `${Math.floor(elapsedSec / 60)}:${String(elapsedSec % 60).padStart(2, '0')} / ${Math.floor(totalSec / 60)}:${String(totalSec % 60).padStart(2, '0')}`;

            this.animationFrame = requestAnimationFrame(animate);
        };

        this.animationFrame = requestAnimationFrame(animate);
    }

    pausePreview() {
        if (!this.isPlaying) return;
        this.isPlaying = false;
        this.isPaused = true;
        this.pausedAt = this.currentElapsed || 0;

        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
        document.getElementById('btn-play-preview').innerHTML =
            '<span class="material-symbols-outlined">play_arrow</span>';
    }

    togglePreview() {
        if (this.isPlaying) {
            this.pausePreview();
        } else {
            this.playPreview();
        }
    }

    stopPreview() {
        this.isPlaying = false;
        this.isPaused = false;
        this.pausedAt = 0;
        this.currentElapsed = 0;
        this.currentProgress = 0;

        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
        document.getElementById('btn-play-preview').innerHTML =
            '<span class="material-symbols-outlined">play_arrow</span>';
    }

    seekPreview(progressPercent) {
        const seekTime = progressPercent * this.totalDuration;
        this.pausedAt = seekTime;

        if (this.isPlaying) {
            // If playing, restart from new position
            this.isPlaying = false;
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
            }
            this.playPreview();
        } else {
            // If paused, just render the frame at this position
            this.currentElapsed = seekTime;
            this.renderPreviewAtProgress(progressPercent);
        }

        // Update progress bar
        document.getElementById('preview-progress-bar').style.width = `${progressPercent * 100}%`;
    }

    renderPreviewAtProgress(progress) {
        const slides = this.builder.slides;
        const fields = this.builder.fields;

        // Find current slide at this progress
        let accumulatedTime = 0;
        let currentSlide = slides[0];
        let slideProgress = 0;

        for (const slide of slides) {
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

        const slideFields = fields.filter(f => f.slide_id == currentSlide.id);
        this.renderPreviewFrame(currentSlide, slideFields, slideProgress);

        // Update time display
        const elapsedSec = Math.floor((progress * this.totalDuration) / 1000);
        const totalSec = Math.floor(this.totalDuration / 1000);
        document.getElementById('preview-time').textContent =
            `${Math.floor(elapsedSec / 60)}:${String(elapsedSec % 60).padStart(2, '0')} / ${Math.floor(totalSec / 60)}:${String(totalSec % 60).padStart(2, '0')}`;
    }

    renderPreviewFrame(slide, fieldsForSlide, progress) {
        const { width, height } = this.previewCanvas;

        // Clear canvas
        this.previewCtx.clearRect(0, 0, width, height);

        // Draw background
        this.previewCtx.fillStyle = slide.background_color || '#ffffff';
        this.previewCtx.fillRect(0, 0, width, height);

        // Draw background image if exists (cached)
        if (slide.background_image && slide._bgImage) {
            this.drawImageCover(slide._bgImage, 0, 0, width, height);
        } else if (slide.background_image) {
            // Load and cache image
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                slide._bgImage = img;
            };
            img.src = slide.background_image;
        }

        // Draw shapes
        const shapesForSlide = this.builder.shapeManager?.getShapesForSlide(slide.id) || [];
        shapesForSlide.forEach(shape => {
            this.renderShape(shape, progress, slide.duration_ms || 3000);
        });

        // Draw texts with animations
        fieldsForSlide.forEach(field => {
            this.renderAnimatedText(field, slide.duration_ms || 3000, progress);
        });
    }

    renderShape(shape, progress, slideDuration) {
        const { width, height } = this.previewCanvas;
        const x = (shape.x / 100) * width;
        const y = (shape.y / 100) * height;
        const w = (shape.width / 100) * width;
        const h = (shape.height / 100) * height;

        // Calculate animation
        const delayRatio = (shape.animationDelay || 0) / slideDuration;
        const durationRatio = (shape.animationDuration || 500) / slideDuration;

        let opacity = shape.opacity ?? 1;
        let scale = 1;
        let offsetY = 0;

        if (shape.animation && shape.animation !== 'none') {
            if (progress < delayRatio) {
                opacity = 0;
            } else if (progress < delayRatio + durationRatio) {
                const t = (progress - delayRatio) / durationRatio;
                const eased = this.easeOutCubic(t);

                switch (shape.animation) {
                    case 'fadeIn':
                        opacity = eased * (shape.opacity ?? 1);
                        break;
                    case 'slideUp':
                        opacity = eased * (shape.opacity ?? 1);
                        offsetY = (1 - eased) * 30;
                        break;
                    case 'slideDown':
                        opacity = eased * (shape.opacity ?? 1);
                        offsetY = (eased - 1) * 30;
                        break;
                    case 'zoomIn':
                        opacity = eased * (shape.opacity ?? 1);
                        scale = 0.5 + (eased * 0.5);
                        break;
                    case 'pulse':
                        const pulseT = t * Math.PI * 2;
                        scale = 1 + 0.05 * Math.sin(pulseT);
                        break;
                    case 'bounce':
                        const bounceT = t * 5;
                        offsetY = -20 * Math.abs(Math.sin(bounceT)) * (1 - t);
                        break;
                }
            }
        }

        this.previewCtx.save();
        this.previewCtx.globalAlpha = opacity;

        const centerX = x;
        const centerY = y + offsetY;

        if (scale !== 1) {
            this.previewCtx.translate(centerX, centerY);
            this.previewCtx.scale(scale, scale);
            this.previewCtx.translate(-centerX, -centerY);
        }

        if (shape.rotation) {
            this.previewCtx.translate(centerX, centerY);
            this.previewCtx.rotate((shape.rotation * Math.PI) / 180);
            this.previewCtx.translate(-centerX, -centerY);
        }

        if (shape.type === 'rectangle') {
            this.previewCtx.fillStyle = shape.fill || '#7c3aed';
            this.previewCtx.beginPath();
            const radius = shape.borderRadius || 0;
            this.roundRect(x - w / 2 + offsetY, centerY - h / 2, w, h, radius);
            this.previewCtx.fill();
            if (shape.strokeWidth > 0) {
                this.previewCtx.strokeStyle = shape.stroke || '#000';
                this.previewCtx.lineWidth = shape.strokeWidth;
                this.previewCtx.stroke();
            }
        } else if (shape.type === 'ellipse') {
            this.previewCtx.fillStyle = shape.fill || '#ec4899';
            this.previewCtx.beginPath();
            this.previewCtx.ellipse(centerX, centerY, w / 2, h / 2, 0, 0, Math.PI * 2);
            this.previewCtx.fill();
            if (shape.strokeWidth > 0) {
                this.previewCtx.strokeStyle = shape.stroke || '#000';
                this.previewCtx.lineWidth = shape.strokeWidth;
                this.previewCtx.stroke();
            }
        } else if (shape.type === 'line') {
            this.previewCtx.strokeStyle = shape.fill || '#f59e0b';
            this.previewCtx.lineWidth = shape.strokeWidth || 2;
            this.previewCtx.beginPath();
            this.previewCtx.moveTo(centerX - w / 2, centerY);
            this.previewCtx.lineTo(centerX + w / 2, centerY);
            this.previewCtx.stroke();
        } else if (shape.type === 'image' && shape.src) {
            // Cache image
            if (!shape._img) {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => { shape._img = img; };
                img.src = shape.src;
            } else {
                this.previewCtx.drawImage(shape._img, centerX - w / 2, centerY - h / 2, w, h);
            }
        }

        this.previewCtx.restore();
    }

    roundRect(x, y, w, h, r) {
        if (r > w / 2) r = w / 2;
        if (r > h / 2) r = h / 2;
        this.previewCtx.moveTo(x + r, y);
        this.previewCtx.arcTo(x + w, y, x + w, y + h, r);
        this.previewCtx.arcTo(x + w, y + h, x, y + h, r);
        this.previewCtx.arcTo(x, y + h, x, y, r);
        this.previewCtx.arcTo(x, y, x + w, y, r);
        this.previewCtx.closePath();
    }

    renderAnimatedText(field, slideDuration, progress) {
        const { width, height } = this.previewCanvas;
        const text = field.sample_value || `{${field.field_name}}`;
        const x = (field.position_x / 100) * width;
        const y = (field.position_y / 100) * height;

        // Calculate animation progress
        const delayRatio = (field.animation_delay_ms || 0) / slideDuration;
        const durationRatio = (field.animation_duration_ms || 500) / slideDuration;

        let opacity = 1;
        let offsetX = 0;
        let offsetY = 0;
        let scale = 1;
        let rotation = 0;

        if (progress < delayRatio) {
            opacity = 0;
        } else if (progress < delayRatio + durationRatio) {
            const t = (progress - delayRatio) / durationRatio;
            const eased = this.easeOutCubic(t);

            switch (field.animation_type) {
                case 'fadeIn':
                    opacity = eased;
                    break;
                case 'fadeOut':
                    opacity = 1 - eased;
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
                case 'zoomOut':
                    opacity = 1 - eased;
                    scale = 1 - (eased * 0.5);
                    break;
                case 'pulse':
                    const pulseT = t * Math.PI * 2;
                    scale = 1 + 0.05 * Math.sin(pulseT);
                    break;
                case 'shake':
                    const shakeT = t * 10;
                    offsetX = 5 * Math.sin(shakeT * Math.PI) * (1 - t);
                    break;
                case 'flip':
                    opacity = eased;
                    // Simplified flip
                    break;
                case 'rotate':
                    opacity = eased;
                    rotation = -180 * (1 - eased);
                    break;
                case 'bounce':
                    const bounceT = t * 5;
                    offsetY = -20 * Math.abs(Math.sin(bounceT)) * (1 - t);
                    break;
                case 'none':
                default:
                    opacity = 1;
            }
        }

        this.previewCtx.save();
        this.previewCtx.globalAlpha = opacity;
        this.previewCtx.font = `${field.font_weight || 400} ${field.font_size || 24}px ${field.font_family || 'Inter'}`;
        this.previewCtx.fillStyle = field.font_color || '#000000';
        this.previewCtx.textAlign = field.text_align || 'center';
        this.previewCtx.textBaseline = 'middle';

        this.previewCtx.translate(x, y);
        if (rotation !== 0) {
            this.previewCtx.rotate((rotation * Math.PI) / 180);
        }
        if (scale !== 1) {
            this.previewCtx.scale(scale, scale);
        }
        this.previewCtx.fillText(text, offsetX, offsetY);

        this.previewCtx.restore();
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

        this.previewCtx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    // Export video using MediaRecorder
    async exportVideo(resolution = '1080p') {
        const resolutions = {
            '720p': { width: 720, height: 1280 },
            '1080p': { width: 1080, height: 1920 },
            '4k': { width: 2160, height: 3840 }
        };

        const { width, height } = resolutions[resolution] || resolutions['1080p'];

        // Create offscreen canvas
        const offCanvas = new OffscreenCanvas(width, height);
        const offCtx = offCanvas.getContext('2d');

        const slides = this.builder.slides;
        const fields = this.builder.fields;
        const totalDuration = slides.reduce((sum, s) => sum + (s.duration_ms || 3000), 0);
        const fps = 30;
        const totalFrames = Math.ceil((totalDuration / 1000) * fps);

        // Collect frames
        const frames = [];

        for (let frame = 0; frame < totalFrames; frame++) {
            const elapsed = (frame / fps) * 1000;
            const progress = elapsed / totalDuration;

            // Find current slide
            let accumulatedTime = 0;
            let currentSlide = slides[0];
            let slideProgress = 0;

            for (const slide of slides) {
                const slideDuration = slide.duration_ms || 3000;
                const slideStart = accumulatedTime;
                const slideEnd = accumulatedTime + slideDuration;

                if (elapsed >= slideStart && elapsed < slideEnd) {
                    currentSlide = slide;
                    slideProgress = (elapsed - slideStart) / slideDuration;
                    break;
                }
                accumulatedTime += slideDuration;
            }

            // Render frame
            const slideFields = fields.filter(f => f.slide_id == currentSlide.id);
            await this.renderExportFrame(offCtx, currentSlide, slideFields, slideProgress, width, height);

            // Capture frame
            const blob = await offCanvas.convertToBlob({ type: 'image/webp', quality: 0.9 });
            frames.push(blob);
        }

        // Note: Full video encoding would require WebCodecs API or server-side processing
        console.log(`Captured ${frames.length} frames`);
        alert('Video export captured. Server-side processing needed for final video.');
    }

    async renderExportFrame(ctx, slide, fieldsForSlide, progress, width, height) {
        // Clear
        ctx.clearRect(0, 0, width, height);

        // Background
        ctx.fillStyle = slide.background_color || '#ffffff';
        ctx.fillRect(0, 0, width, height);

        // Background image
        if (slide.background_image) {
            await new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    this.drawImageCoverCtx(ctx, img, 0, 0, width, height);
                    resolve();
                };
                img.onerror = resolve;
                img.src = slide.background_image;
            });
        }

        // Texts
        fieldsForSlide.forEach(field => {
            this.renderAnimatedTextCtx(ctx, field, slide.duration_ms || 3000, progress, width, height);
        });
    }

    drawImageCoverCtx(ctx, img, x, y, w, h) {
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
        ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
    }

    renderAnimatedTextCtx(ctx, field, slideDuration, progress, width, height) {
        const text = field.sample_value || `{${field.field_name}}`;
        const x = (field.position_x / 100) * width;
        const y = (field.position_y / 100) * height;

        const delayRatio = (field.animation_delay_ms || 0) / slideDuration;
        const durationRatio = (field.animation_duration_ms || 500) / slideDuration;

        let opacity = 1;
        let offsetY = 0;

        if (progress < delayRatio) {
            opacity = 0;
        } else if (progress < delayRatio + durationRatio) {
            const t = (progress - delayRatio) / durationRatio;
            const eased = this.easeOutCubic(t);
            opacity = eased;
            if (field.animation_type === 'slideUp') offsetY = (1 - eased) * 50;
        }

        ctx.save();
        ctx.globalAlpha = opacity;
        ctx.font = `${field.font_weight || 400} ${field.font_size || 24}px ${field.font_family || 'Inter'}`;
        ctx.fillStyle = field.font_color || '#000000';
        ctx.textAlign = field.text_align || 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, x, y + offsetY);
        ctx.restore();
    }
}
