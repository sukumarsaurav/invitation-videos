/**
 * SlidePreview - Real-time slide preview for template customization
 * Renders a visual preview of the current slide with user's text/images
 */

class SlidePreview {
    constructor(canvasId, slideConfig, userValues = {}, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            console.error('SlidePreview: Canvas element not found:', canvasId);
            return;
        }

        this.slideConfig = slideConfig;
        this.userValues = userValues || {};
        this.options = {
            templateWidth: options.templateWidth || 1080,
            templateHeight: options.templateHeight || 1920,
            backgroundFallback: options.backgroundFallback || '#1a1a2e',
            ...options
        };

        // Calculate scale based on canvas size
        this.updateScale();

        // Initial render
        this.render();

        // Re-render on window resize
        window.addEventListener('resize', () => {
            this.updateScale();
            this.render();
        });
    }

    updateScale() {
        const canvasWidth = this.canvas.clientWidth;
        this.scale = canvasWidth / this.options.templateWidth;
    }

    updateValue(fieldKey, value) {
        this.userValues[fieldKey] = value;
        this.render();
    }

    updateImageValue(fieldKey, file) {
        if (file && file instanceof File) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.userValues[fieldKey] = e.target.result;
                this.render();
            };
            reader.readAsDataURL(file);
        }
    }

    render() {
        // Clear canvas
        this.canvas.innerHTML = '';

        // Set canvas positioning
        this.canvas.style.position = 'relative';
        this.canvas.style.overflow = 'hidden';
        this.canvas.style.backgroundColor = this.options.backgroundFallback;

        // Render background
        this.renderBackground();

        // Render layers in order (first layer = bottom)
        const layers = this.slideConfig.layers || [];
        for (const layer of layers) {
            if (layer.type === 'text') {
                this.renderTextLayer(layer);
            } else if (layer.type === 'image') {
                this.renderImageLayer(layer);
            }
        }
    }

    renderBackground() {
        const bg = this.slideConfig.background;
        if (!bg) return;

        const bgElement = document.createElement('div');
        bgElement.className = 'slide-preview-bg';
        bgElement.style.cssText = `
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        `;

        if (bg.type === 'video') {
            // For preview, show fallback image or color (can't preview video without playing)
            const src = this.resolveValue(bg.src) || bg.fallback;
            if (src && !src.startsWith('{{')) {
                // Use a gradient overlay to indicate video background
                bgElement.style.background = `linear-gradient(135deg, #2d2d44 0%, #1a1a2e 100%)`;

                // Add video icon indicator
                const videoIcon = document.createElement('div');
                videoIcon.innerHTML = `
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0.3;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="white">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </div>
                `;
                bgElement.appendChild(videoIcon.firstElementChild);
            }
        } else if (bg.type === 'image') {
            const src = this.resolveValue(bg.src) || bg.fallback;
            if (src && !src.startsWith('{{')) {
                bgElement.style.backgroundImage = `url(${src})`;
                bgElement.style.backgroundSize = 'cover';
                bgElement.style.backgroundPosition = 'center';
            }
        } else if (bg.type === 'color') {
            bgElement.style.backgroundColor = bg.src || '#000000';
        }

        // Add overlay if configured
        if (bg.overlay) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: absolute;
                inset: 0;
                opacity: ${bg.overlay.opacity || 0.5};
            `;

            if (bg.overlay.type === 'gradient') {
                overlay.style.background = bg.overlay.gradient ||
                    'linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7))';
            } else if (bg.overlay.type === 'solid') {
                overlay.style.backgroundColor = bg.overlay.color || 'rgba(0,0,0,0.5)';
            }

            bgElement.appendChild(overlay);
        }

        this.canvas.appendChild(bgElement);
    }

    renderTextLayer(layer) {
        const value = this.resolveLayerValue(layer);
        if (!value) return;

        const div = document.createElement('div');
        div.className = 'slide-preview-text';
        div.textContent = value;

        const pos = layer.position || { x: 540, y: 960 };
        const style = layer.style || {};
        const anchor = pos.anchor || 'center';

        // Calculate scaled position
        const x = pos.x * this.scale;
        const y = pos.y * this.scale;

        // Base styles
        let cssText = `
            position: absolute;
            font-size: ${(style.fontSize || 24) * this.scale}px;
            font-family: ${style.fontFamily || 'Inter, sans-serif'};
            color: ${this.resolveColor(style.color) || '#FFFFFF'};
            font-weight: ${style.fontWeight || 'normal'};
            text-align: ${style.textAlign || 'center'};
            white-space: ${style.maxWidth ? 'normal' : 'nowrap'};
            line-height: 1.3;
        `;

        // Add text shadow if specified
        if (style.textShadow) {
            cssText += `text-shadow: ${style.textShadow};`;
        }

        // Add max width if specified
        if (style.maxWidth) {
            cssText += `max-width: ${style.maxWidth * this.scale}px;`;
        }

        // Position based on anchor
        switch (anchor) {
            case 'center':
                cssText += `left: ${x}px; top: ${y}px; transform: translate(-50%, -50%);`;
                break;
            case 'top-left':
                cssText += `left: ${x}px; top: ${y}px;`;
                break;
            case 'top-right':
                cssText += `right: ${(this.options.templateWidth - pos.x) * this.scale}px; top: ${y}px;`;
                break;
            case 'bottom-left':
                cssText += `left: ${x}px; bottom: ${(this.options.templateHeight - pos.y) * this.scale}px;`;
                break;
            case 'bottom-right':
                cssText += `right: ${(this.options.templateWidth - pos.x) * this.scale}px; bottom: ${(this.options.templateHeight - pos.y) * this.scale}px;`;
                break;
            default:
                cssText += `left: ${x}px; top: ${y}px; transform: translate(-50%, -50%);`;
        }

        div.style.cssText = cssText;
        this.canvas.appendChild(div);
    }

    renderImageLayer(layer) {
        const src = this.resolveLayerValue(layer);
        if (!src || src.startsWith('{{')) return;

        const pos = layer.position || { x: 540, y: 960 };
        const size = layer.size || { width: 200, height: 200 };
        const style = layer.style || {};
        const anchor = pos.anchor || 'center';

        const container = document.createElement('div');
        container.className = 'slide-preview-image';

        // Calculate scaled values
        const x = pos.x * this.scale;
        const y = pos.y * this.scale;
        const width = size.width * this.scale;
        const height = size.height * this.scale;

        let cssText = `
            position: absolute;
            width: ${width}px;
            height: ${height}px;
            overflow: hidden;
            border-radius: ${typeof style.borderRadius === 'number' ? style.borderRadius * this.scale : style.borderRadius || 0}px;
        `;

        if (style.border) {
            cssText += `border: ${style.border};`;
        }
        if (style.boxShadow) {
            cssText += `box-shadow: ${style.boxShadow};`;
        }

        // Position based on anchor
        switch (anchor) {
            case 'center':
                cssText += `left: ${x - width / 2}px; top: ${y - height / 2}px;`;
                break;
            case 'top-left':
                cssText += `left: ${x}px; top: ${y}px;`;
                break;
            default:
                cssText += `left: ${x - width / 2}px; top: ${y - height / 2}px;`;
        }

        container.style.cssText = cssText;

        // Create image element
        const img = document.createElement('img');
        img.src = src;
        img.style.cssText = `
            width: 100%;
            height: 100%;
            object-fit: ${style.objectFit || 'cover'};
        `;
        img.onerror = () => {
            container.style.backgroundColor = '#3d3d5c';
            container.innerHTML = '<span style="color: #888; font-size: 12px;">Image</span>';
            container.style.display = 'flex';
            container.style.alignItems = 'center';
            container.style.justifyContent = 'center';
        };

        container.appendChild(img);
        this.canvas.appendChild(container);
    }

    resolveLayerValue(layer) {
        if (layer.fieldKey) {
            return this.userValues[layer.fieldKey] || layer.defaultValue || '';
        }
        return layer.defaultValue || '';
    }

    resolveValue(value) {
        if (!value) return value;

        // Check for {{fieldKey}} syntax
        const match = value.match(/^\{\{(\w+)\}\}$/);
        if (match) {
            return this.userValues[match[1]] || null;
        }
        return value;
    }

    resolveColor(color) {
        if (!color) return color;

        // Check for {{colorKey}} syntax
        const match = color.match(/^\{\{(\w+)\}\}$/);
        if (match) {
            return this.userValues[match[1]] || '#FFFFFF';
        }
        return color;
    }
}

// Auto-initialize previews with data attributes
document.addEventListener('DOMContentLoaded', () => {
    const canvases = document.querySelectorAll('[data-slide-preview]');
    canvases.forEach(canvas => {
        const config = JSON.parse(canvas.dataset.slideConfig || '{}');
        const values = JSON.parse(canvas.dataset.userValues || '{}');

        const preview = new SlidePreview(canvas.id, config, values);

        // Store instance on element for external access
        canvas._slidePreview = preview;
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SlidePreview;
}
