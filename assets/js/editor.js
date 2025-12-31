/**
 * Editor.js - Live Preview Editor for Template Customization
 * Handles real-time canvas preview as users type in form fields
 */

class TemplateEditor {
    constructor() {
        this.templateId = window.EDITOR_DATA.templateId;
        this.template = window.EDITOR_DATA.template;
        this.slides = window.EDITOR_DATA.slides || [];
        this.fields = window.EDITOR_DATA.fields || [];

        // Canvas elements
        this.canvas = document.getElementById('preview-canvas');
        this.ctx = this.canvas.getContext('2d');

        // Playback state
        this.isPlaying = false;
        this.currentTime = 0;
        this.animationFrame = null;
        this.totalDuration = this.calculateTotalDuration();

        // Field values (user input)
        this.fieldValues = {};

        // Initialize
        this.init();
    }

    init() {
        // Load initial field values from inputs
        this.loadFieldValues();

        // Setup event listeners
        this.setupFieldListeners();
        this.setupPlayerControls();
        this.setupProgressBar();

        // Render initial frame
        this.renderFrame(0);

        // Update time display
        this.updateTimeDisplay();
    }

    calculateTotalDuration() {
        return this.slides.reduce((sum, slide) => sum + (slide.duration_ms || 3000), 0);
    }

    loadFieldValues() {
        document.querySelectorAll('.field-input').forEach(input => {
            const fieldId = input.dataset.fieldId;
            this.fieldValues[fieldId] = input.value || '';
        });
    }

    setupFieldListeners() {
        document.querySelectorAll('.field-input').forEach(input => {
            input.addEventListener('input', (e) => {
                const fieldId = e.target.dataset.fieldId;
                this.fieldValues[fieldId] = e.target.value;

                // Re-render current frame with new values
                if (!this.isPlaying) {
                    this.renderFrame(this.currentTime / this.totalDuration);
                }
            });
        });
    }

    setupPlayerControls() {
        // Play button
        const playBtn = document.getElementById('btn-play');
        playBtn?.addEventListener('click', () => this.togglePlay());

        // Preview button
        document.getElementById('btn-preview')?.addEventListener('click', () => {
            this.currentTime = 0;
            this.play();
        });
        document.getElementById('btn-preview-mobile')?.addEventListener('click', () => {
            this.currentTime = 0;
            this.play();
        });

        // Download button
        document.getElementById('btn-download')?.addEventListener('click', () => this.download());
        document.getElementById('btn-download-mobile')?.addEventListener('click', () => this.download());
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
        if (this.isPlaying) return;
        this.isPlaying = true;

        document.getElementById('play-icon').textContent = 'pause';

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

        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }

    updateProgressUI(progress) {
        document.getElementById('progress-fill').style.width = `${progress * 100}%`;
        document.getElementById('progress-handle').style.left = `${progress * 100}%`;

        const currentSec = Math.floor(this.currentTime / 1000);
        document.getElementById('time-current').textContent =
            `${Math.floor(currentSec / 60)}:${String(currentSec % 60).padStart(2, '0')}`;
    }

    updateTimeDisplay() {
        const totalSec = Math.floor(this.totalDuration / 1000);
        document.getElementById('time-total').textContent =
            `${Math.floor(totalSec / 60)}:${String(totalSec % 60).padStart(2, '0')}`;
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

        // Draw text fields
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
        if (slide.background_image) {
            if (!slide._bgImageLoaded) {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    slide._bgImage = img;
                    slide._bgImageLoaded = true;
                    this.renderFrame(this.currentTime / this.totalDuration);
                };
                img.src = slide.background_image;
                slide._bgImageLoaded = 'loading';
            } else if (slide._bgImage) {
                this.drawImageCover(slide._bgImage, 0, 0, width, height);
            }
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
        // Get user value or sample value
        const text = this.fieldValues[field.id] || field.sample_value || `{${field.field_name}}`;

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

    async download() {
        // Get buttons
        const downloadBtn = document.getElementById('btn-download');
        const downloadBtnMobile = document.getElementById('btn-download-mobile');

        // Show loading state
        const originalText = downloadBtn?.innerHTML || '';
        const originalTextMobile = downloadBtnMobile?.innerHTML || '';

        if (downloadBtn) {
            downloadBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> <span class="hidden sm:inline">Recording...</span>';
            downloadBtn.disabled = true;
        }
        if (downloadBtnMobile) {
            downloadBtnMobile.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Recording...';
            downloadBtnMobile.disabled = true;
        }

        try {
            // Create a high-resolution export canvas
            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = 1080;
            exportCanvas.height = 1920;
            const exportCtx = exportCanvas.getContext('2d');

            // Setup MediaRecorder
            const stream = exportCanvas.captureStream(30); // 30 FPS
            const mediaRecorder = new MediaRecorder(stream, {
                mimeType: this.getSupportedMimeType(),
                videoBitsPerSecond: 8000000 // 8 Mbps for good quality
            });

            const chunks = [];
            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) {
                    chunks.push(e.data);
                }
            };

            // Promise to wait for recording to finish
            const recordingComplete = new Promise((resolve) => {
                mediaRecorder.onstop = () => {
                    const blob = new Blob(chunks, { type: this.getSupportedMimeType() });
                    resolve(blob);
                };
            });

            // Start recording
            mediaRecorder.start();

            // Pre-load all background images
            await this.preloadAllImages();

            // Render each frame
            const fps = 30;
            const frameDuration = 1000 / fps;
            const totalFrames = Math.ceil(this.totalDuration / frameDuration);

            // Update button to show progress
            const updateProgress = (percent) => {
                if (downloadBtn) {
                    downloadBtn.innerHTML = `<span class="material-symbols-outlined animate-spin">progress_activity</span> <span class="hidden sm:inline">${percent}%</span>`;
                }
                if (downloadBtnMobile) {
                    downloadBtnMobile.innerHTML = `<span class="material-symbols-outlined animate-spin">progress_activity</span> ${percent}%`;
                }
            };

            for (let frame = 0; frame < totalFrames; frame++) {
                const progress = frame / totalFrames;
                const currentTimeMs = progress * this.totalDuration;

                // Render frame to export canvas
                this.renderFrameToCanvas(exportCtx, exportCanvas.width, exportCanvas.height, progress);

                // Update progress every 10%
                if (frame % Math.ceil(totalFrames / 10) === 0) {
                    updateProgress(Math.floor(progress * 100));
                }

                // Small delay to allow MediaRecorder to capture the frame
                await new Promise(r => setTimeout(r, frameDuration / 2));
            }

            // Stop recording
            mediaRecorder.stop();

            // Wait for blob
            const blob = await recordingComplete;

            // Download the video
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${this.template.title || 'invitation'}_${Date.now()}.webm`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Show success message
            this.showNotification('Video downloaded successfully!', 'success');

        } catch (error) {
            console.error('Export error:', error);
            this.showNotification('Failed to export video: ' + error.message, 'error');
        } finally {
            // Restore buttons
            if (downloadBtn) {
                downloadBtn.innerHTML = originalText;
                downloadBtn.disabled = false;
            }
            if (downloadBtnMobile) {
                downloadBtnMobile.innerHTML = originalTextMobile;
                downloadBtnMobile.disabled = false;
            }
        }
    }

    getSupportedMimeType() {
        const types = [
            'video/webm;codecs=vp9',
            'video/webm;codecs=vp8',
            'video/webm',
            'video/mp4'
        ];
        for (const type of types) {
            if (MediaRecorder.isTypeSupported(type)) {
                return type;
            }
        }
        return 'video/webm';
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

    renderFrameToCanvas(ctx, width, height, progress) {
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
        ctx.clearRect(0, 0, width, height);

        // Draw background
        this.drawBackgroundToCanvas(ctx, currentSlide, width, height);

        // Draw text fields
        const slideFields = this.fields.filter(f => String(f.slide_id) === String(currentSlide?.id));
        slideFields.forEach(field => {
            this.drawTextFieldToCanvas(ctx, field, slideProgress, currentSlide?.duration_ms || 3000, width, height);
        });
    }

    drawBackgroundToCanvas(ctx, slide, width, height) {
        if (!slide) {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);
            return;
        }

        // Gradient background
        if (slide.background_gradient) {
            ctx.fillStyle = slide.background_gradient;
            ctx.fillRect(0, 0, width, height);
            return;
        }

        // Color background
        if (slide.background_color) {
            ctx.fillStyle = slide.background_color;
            ctx.fillRect(0, 0, width, height);
        }

        // Image background
        if (slide._bgImage) {
            this.drawImageCoverToCanvas(ctx, slide._bgImage, 0, 0, width, height);
        }
    }

    drawImageCoverToCanvas(ctx, img, x, y, w, h) {
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

    drawTextFieldToCanvas(ctx, field, slideProgress, slideDuration, width, height) {
        // Get user value or sample value
        const text = this.fieldValues[field.id] || field.sample_value || `{${field.field_name}}`;

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

        ctx.save();
        ctx.globalAlpha = opacity;
        ctx.font = `${field.font_weight || 400} ${field.font_size || 48}px ${field.font_family || 'Inter'}`;
        ctx.fillStyle = field.font_color || '#000000';
        ctx.textAlign = field.text_align || 'center';
        ctx.textBaseline = 'middle';

        ctx.translate(x, y);
        if (scale !== 1) {
            ctx.scale(scale, scale);
        }
        ctx.fillText(text, offsetX, offsetY);

        ctx.restore();
    }

    showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium animate-pulse ${type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.editor = new TemplateEditor();
});
