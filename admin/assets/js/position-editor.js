/**
 * Position Editor for CMS Homepage Sections
 * Provides numeric inputs with live preview for SVG & Image positioning
 */

class PositionEditor {
    constructor(options = {}) {
        this.containerId = options.containerId || 'position-editor';
        this.breakpoints = ['xs', 'sm', 'md', 'lg', 'xl'];
        this.breakpointLabels = {
            'xs': '≤480px (Mobile)',
            'sm': '481-768px (Tablet)',
            'md': '769-1023px (Small Desktop)',
            'lg': '1024-1279px (Desktop)',
            'xl': '1280px+ (Large)'
        };
        this.breakpointWidths = {
            'xs': 320,
            'sm': 600,
            'md': 900,
            'lg': 1100,
            'xl': 1400
        };
        this.currentBreakpoint = 'xs';

        // Default positions
        this.defaultSvgPos = { x: 0, y: 0, scale: 1.5, rotation: 0, opacity: 30, zIndex: 1 };
        this.defaultImgPos = { x: 0, y: 0, scale: 1.0, rotation: 0, visible: true, zIndex: 2 };
        this.defaultHeight = { xs: '80', sm: '100', md: '120', lg: '140', xl: '160' };

        // Initialize state from hidden inputs or defaults
        this.svgPosition = this.parseJSON(options.svgPosition) || this.createDefaultPositions(this.defaultSvgPos);
        this.imagePosition = this.parseJSON(options.imagePosition) || this.createDefaultPositions(this.defaultImgPos);
        this.bannerHeights = this.parseJSON(options.bannerHeights) || { ...this.defaultHeight };
        this.svgAnimation = options.svgAnimation || 'none';
        this.imageAnimation = options.imageAnimation || 'none';
        this.imageOverflow = options.imageOverflow !== undefined ? options.imageOverflow : true;

        // Carousel visible counts
        this.visibleCounts = this.parseJSON(options.visibleCounts) || { xs: 2, sm: 3, md: 4, lg: 4, xl: 4 };

        // Preview elements
        this.bannerBgColor = options.bannerBgColor || '#a11045';
        this.titleColor = options.titleColor || '#d4a853';
        this.sectionTitle = options.sectionTitle || 'Section Title';
        this.svgUrl = options.svgUrl || '';
        this.imageUrl = options.imageUrl || '';

        this.container = null;
    }

    parseJSON(value) {
        if (!value) return null;
        if (typeof value === 'object') return value;
        try {
            return JSON.parse(value);
        } catch (e) {
            return null;
        }
    }

    createDefaultPositions(defaults) {
        const positions = {};
        this.breakpoints.forEach(bp => {
            positions[bp] = { ...defaults };
        });
        return positions;
    }

    init() {
        this.container = document.getElementById(this.containerId);
        if (!this.container) {
            console.error('Position editor container not found:', this.containerId);
            return;
        }

        this.render();
        this.setupEventListeners();
        this.updatePreview();
    }

    render() {
        this.container.innerHTML = `
            <div class="position-editor">
                <!-- Breakpoint Tabs -->
                <div class="flex gap-1 mb-4 overflow-x-auto pb-2">
                    ${this.breakpoints.map(bp => `
                        <button type="button" 
                                class="breakpoint-tab px-3 py-2 text-xs font-medium rounded-lg whitespace-nowrap transition-colors ${bp === this.currentBreakpoint ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}"
                                data-breakpoint="${bp}">
                            ${bp.toUpperCase()}
                            <span class="hidden sm:inline text-[10px] opacity-75 ml-1">${this.breakpointLabels[bp].split(' ')[0]}</span>
                        </button>
                    `).join('')}
                </div>
                
                <!-- Live Preview -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Live Preview</label>
                    <div class="preview-wrapper border border-slate-200 rounded-lg overflow-hidden" style="max-width: 100%;">
                        <div class="preview-canvas relative transition-all duration-300" 
                             style="background-color: ${this.bannerBgColor}; height: ${this.bannerHeights[this.currentBreakpoint] || 100}px;">
                            <!-- SVG Preview -->
                            <div class="preview-svg absolute inset-0 pointer-events-none flex items-center justify-center overflow-visible">
                                ${this.svgUrl ? `<img src="${this.svgUrl}" class="h-full w-auto max-w-none" alt="SVG Pattern">` : '<span class="text-white/30 text-xs">No SVG</span>'}
                            </div>
                            <!-- Image Preview -->
                            <div class="preview-image absolute inset-0 pointer-events-none flex items-center justify-end pr-4 overflow-visible">
                                ${this.imageUrl ? `<img src="${this.imageUrl}" class="h-full w-auto max-h-[150%] object-contain" alt="Category Image">` : '<span class="text-white/30 text-xs">No Image</span>'}
                            </div>
                            <!-- Title Preview -->
                            <div class="relative z-20 h-full flex items-center px-4">
                                <span class="text-xl font-light italic" style="color: ${this.titleColor}; font-family: Georgia, serif;">
                                    ${this.sectionTitle}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Controls Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SVG Pattern Controls -->
                    <div class="bg-slate-50 rounded-lg p-4">
                        <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">pattern</span>
                            SVG Pattern
                        </h4>
                        
                        <div class="space-y-4">
                            ${this.renderSlider('svg', 'x', 'Position X', -500, 500, 'px')}
                            ${this.renderSlider('svg', 'y', 'Position Y', -200, 200, 'px')}
                            ${this.renderSlider('svg', 'scale', 'Scale', 0.5, 3, 'x', 0.1)}
                            ${this.renderSlider('svg', 'rotation', 'Rotation', 0, 360, '°')}
                            ${this.renderSlider('svg', 'opacity', 'Opacity', 0, 100, '%')}
                            ${this.renderSlider('svg', 'zIndex', 'Layer (Z-Index)', 1, 10, '')}
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Scroll Animation</label>
                            <select class="animation-select w-full px-3 py-2 text-sm rounded-lg border border-slate-300 bg-white" data-target="svg">
                                <option value="none" ${this.svgAnimation === 'none' ? 'selected' : ''}>None</option>
                                <option value="fade-in" ${this.svgAnimation === 'fade-in' ? 'selected' : ''}>Fade In</option>
                                <option value="slide-left" ${this.svgAnimation === 'slide-left' ? 'selected' : ''}>Slide from Left</option>
                                <option value="slide-right" ${this.svgAnimation === 'slide-right' ? 'selected' : ''}>Slide from Right</option>
                                <option value="slide-up" ${this.svgAnimation === 'slide-up' ? 'selected' : ''}>Slide Up</option>
                                <option value="scale-in" ${this.svgAnimation === 'scale-in' ? 'selected' : ''}>Scale In</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Category Image Controls -->
                    <div class="bg-slate-50 rounded-lg p-4">
                        <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">image</span>
                            Category Image
                        </h4>
                        
                        <div class="space-y-4">
                            ${this.renderSlider('image', 'x', 'Position X', -500, 500, 'px')}
                            ${this.renderSlider('image', 'y', 'Position Y', -200, 200, 'px')}
                            ${this.renderSlider('image', 'scale', 'Scale', 0.5, 2, 'x', 0.1)}
                            ${this.renderSlider('image', 'rotation', 'Rotation', 0, 360, '°')}
                            ${this.renderSlider('image', 'zIndex', 'Layer (Z-Index)', 1, 10, '')}
                            
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="visibility-checkbox w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary/20" 
                                           data-target="image" ${this.imagePosition[this.currentBreakpoint]?.visible !== false ? 'checked' : ''}>
                                    <span class="text-xs font-medium text-slate-600">Visible</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="overflow-checkbox w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary/20" 
                                           ${this.imageOverflow ? 'checked' : ''}>
                                    <span class="text-xs font-medium text-slate-600">Allow Overflow</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Scroll Animation</label>
                            <select class="animation-select w-full px-3 py-2 text-sm rounded-lg border border-slate-300 bg-white" data-target="image">
                                <option value="none" ${this.imageAnimation === 'none' ? 'selected' : ''}>None</option>
                                <option value="fade-in" ${this.imageAnimation === 'fade-in' ? 'selected' : ''}>Fade In</option>
                                <option value="slide-left" ${this.imageAnimation === 'slide-left' ? 'selected' : ''}>Slide from Left</option>
                                <option value="slide-right" ${this.imageAnimation === 'slide-right' ? 'selected' : ''}>Slide from Right</option>
                                <option value="slide-up" ${this.imageAnimation === 'slide-up' ? 'selected' : ''}>Slide Up</option>
                                <option value="scale-in" ${this.imageAnimation === 'scale-in' ? 'selected' : ''}>Scale In</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Banner Height -->
                <div class="mt-6 bg-slate-50 rounded-lg p-4">
                    <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">height</span>
                        Banner Height
                    </h4>
                    <div class="flex items-center gap-4">
                        <input type="range" class="height-slider flex-1" min="40" max="200" step="5" 
                               value="${parseInt(this.bannerHeights[this.currentBreakpoint]) || 100}">
                        <div class="flex items-center gap-1">
                            <input type="number" class="height-input w-16 px-2 py-1 text-sm text-center rounded border border-slate-300" 
                                   min="40" max="200" step="5" value="${parseInt(this.bannerHeights[this.currentBreakpoint]) || 100}">
                            <span class="text-xs text-slate-500">px</span>
                        </div>
                    </div>
                </div>
                
                <!-- Carousel: Templates Visible -->
                <div class="mt-6 bg-slate-50 rounded-lg p-4">
                    <h4 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">view_carousel</span>
                        Templates Visible (Carousel)
                    </h4>
                    <p class="text-xs text-slate-500 mb-3">How many template cards to show at this breakpoint. Users can scroll/navigate to see more.</p>
                    <div class="flex items-center gap-4">
                        <input type="range" class="visible-count-slider flex-1" min="1" max="6" step="1" 
                               value="${this.visibleCounts[this.currentBreakpoint] || 4}">
                        <div class="flex items-center gap-1">
                            <input type="number" class="visible-count-input w-16 px-2 py-1 text-sm text-center rounded border border-slate-300" 
                                   min="1" max="6" step="1" value="${this.visibleCounts[this.currentBreakpoint] || 4}">
                            <span class="text-xs text-slate-500">cards</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" class="copy-to-all-btn flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors">
                        <span class="material-symbols-outlined text-lg">content_copy</span>
                        Copy ${this.currentBreakpoint.toUpperCase()} → All Breakpoints
                    </button>
                    <button type="button" class="reset-btn flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors">
                        <span class="material-symbols-outlined text-lg">restart_alt</span>
                        Reset Current
                    </button>
                </div>
                
                <!-- Hidden Inputs for Form Submission -->
                <input type="hidden" name="svg_position" id="svg_position_input" value='${JSON.stringify(this.svgPosition)}'>
                <input type="hidden" name="image_position" id="image_position_input" value='${JSON.stringify(this.imagePosition)}'>
                <input type="hidden" name="banner_heights" id="banner_heights_input" value='${JSON.stringify(this.bannerHeights)}'>
                <input type="hidden" name="svg_animation" id="svg_animation_input" value="${this.svgAnimation}">
                <input type="hidden" name="image_animation" id="image_animation_input" value="${this.imageAnimation}">
                <input type="hidden" name="image_overflow" id="image_overflow_input" value="${this.imageOverflow ? '1' : '0'}">
                <input type="hidden" name="visible_counts" id="visible_counts_input" value='${JSON.stringify(this.visibleCounts)}'>
            </div>
        `;
    }

    renderSlider(target, property, label, min, max, unit, step = 1) {
        const positions = target === 'svg' ? this.svgPosition : this.imagePosition;
        const value = positions[this.currentBreakpoint]?.[property] ?? (target === 'svg' ? this.defaultSvgPos[property] : this.defaultImgPos[property]);

        return `
            <div class="slider-group">
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-medium text-slate-600">${label}</label>
                    <div class="flex items-center gap-1">
                        <input type="number" 
                               class="position-input w-16 px-2 py-0.5 text-xs text-center rounded border border-slate-300"
                               data-target="${target}" data-property="${property}"
                               min="${min}" max="${max}" step="${step}" value="${value}">
                        <span class="text-xs text-slate-400">${unit}</span>
                    </div>
                </div>
                <input type="range" 
                       class="position-slider w-full"
                       data-target="${target}" data-property="${property}"
                       min="${min}" max="${max}" step="${step}" value="${value}">
            </div>
        `;
    }

    setupEventListeners() {
        // Breakpoint tabs
        this.container.querySelectorAll('.breakpoint-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.setBreakpoint(e.target.closest('.breakpoint-tab').dataset.breakpoint);
            });
        });

        // Position sliders and inputs
        this.container.querySelectorAll('.position-slider').forEach(slider => {
            slider.addEventListener('input', (e) => {
                const { target, property } = e.target.dataset;
                const value = parseFloat(e.target.value);
                this.updatePosition(target, property, value);
            });
        });

        this.container.querySelectorAll('.position-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const { target, property } = e.target.dataset;
                const value = parseFloat(e.target.value);
                this.updatePosition(target, property, value);
            });
        });

        // Visibility checkbox
        this.container.querySelectorAll('.visibility-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const target = e.target.dataset.target;
                const positions = target === 'svg' ? this.svgPosition : this.imagePosition;
                positions[this.currentBreakpoint].visible = e.target.checked;
                this.updateHiddenInputs();
                this.updatePreview();
            });
        });

        // Overflow checkbox
        this.container.querySelector('.overflow-checkbox')?.addEventListener('change', (e) => {
            this.imageOverflow = e.target.checked;
            this.updateHiddenInputs();
            this.updatePreview();
        });

        // Animation selects
        this.container.querySelectorAll('.animation-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const target = e.target.dataset.target;
                if (target === 'svg') {
                    this.svgAnimation = e.target.value;
                } else {
                    this.imageAnimation = e.target.value;
                }
                this.updateHiddenInputs();
            });
        });

        // Height slider and input
        const heightSlider = this.container.querySelector('.height-slider');
        const heightInput = this.container.querySelector('.height-input');

        if (heightSlider) {
            heightSlider.addEventListener('input', (e) => {
                this.bannerHeights[this.currentBreakpoint] = e.target.value + 'px';
                if (heightInput) heightInput.value = e.target.value;
                this.updateHiddenInputs();
                this.updatePreview();
            });
        }

        if (heightInput) {
            heightInput.addEventListener('change', (e) => {
                this.bannerHeights[this.currentBreakpoint] = e.target.value + 'px';
                if (heightSlider) heightSlider.value = e.target.value;
                this.updateHiddenInputs();
                this.updatePreview();
            });
        }

        // Visible count slider and input
        const visibleSlider = this.container.querySelector('.visible-count-slider');
        const visibleInput = this.container.querySelector('.visible-count-input');

        if (visibleSlider) {
            visibleSlider.addEventListener('input', (e) => {
                this.visibleCounts[this.currentBreakpoint] = parseInt(e.target.value);
                if (visibleInput) visibleInput.value = e.target.value;
                this.updateHiddenInputs();
            });
        }

        if (visibleInput) {
            visibleInput.addEventListener('change', (e) => {
                this.visibleCounts[this.currentBreakpoint] = parseInt(e.target.value);
                if (visibleSlider) visibleSlider.value = e.target.value;
                this.updateHiddenInputs();
            });
        }

        // Copy to all button
        this.container.querySelector('.copy-to-all-btn')?.addEventListener('click', () => {
            this.copyToAllBreakpoints();
        });

        // Reset button
        this.container.querySelector('.reset-btn')?.addEventListener('click', () => {
            this.resetCurrentBreakpoint();
        });
    }

    setBreakpoint(bp) {
        this.currentBreakpoint = bp;

        // Update tab styles
        this.container.querySelectorAll('.breakpoint-tab').forEach(tab => {
            if (tab.dataset.breakpoint === bp) {
                tab.classList.remove('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
                tab.classList.add('bg-primary', 'text-white');
            } else {
                tab.classList.remove('bg-primary', 'text-white');
                tab.classList.add('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
            }
        });

        // Update copy button text
        const copyBtn = this.container.querySelector('.copy-to-all-btn');
        if (copyBtn) {
            copyBtn.innerHTML = `
                <span class="material-symbols-outlined text-lg">content_copy</span>
                Copy ${bp.toUpperCase()} → All Breakpoints
            `;
        }

        // Update all inputs with current breakpoint values
        this.updateInputsFromState();
        this.updatePreview();
    }

    updatePosition(target, property, value) {
        const positions = target === 'svg' ? this.svgPosition : this.imagePosition;

        if (!positions[this.currentBreakpoint]) {
            positions[this.currentBreakpoint] = target === 'svg' ? { ...this.defaultSvgPos } : { ...this.defaultImgPos };
        }

        positions[this.currentBreakpoint][property] = value;

        // Sync slider and input
        const slider = this.container.querySelector(`.position-slider[data-target="${target}"][data-property="${property}"]`);
        const input = this.container.querySelector(`.position-input[data-target="${target}"][data-property="${property}"]`);

        if (slider) slider.value = value;
        if (input) input.value = value;

        this.updateHiddenInputs();
        this.updatePreview();
    }

    updateInputsFromState() {
        // Update SVG inputs
        ['x', 'y', 'scale', 'rotation', 'opacity', 'zIndex'].forEach(prop => {
            const value = this.svgPosition[this.currentBreakpoint]?.[prop] ?? this.defaultSvgPos[prop];
            const slider = this.container.querySelector(`.position-slider[data-target="svg"][data-property="${prop}"]`);
            const input = this.container.querySelector(`.position-input[data-target="svg"][data-property="${prop}"]`);
            if (slider) slider.value = value;
            if (input) input.value = value;
        });

        // Update Image inputs
        ['x', 'y', 'scale', 'rotation', 'zIndex'].forEach(prop => {
            const value = this.imagePosition[this.currentBreakpoint]?.[prop] ?? this.defaultImgPos[prop];
            const slider = this.container.querySelector(`.position-slider[data-target="image"][data-property="${prop}"]`);
            const input = this.container.querySelector(`.position-input[data-target="image"][data-property="${prop}"]`);
            if (slider) slider.value = value;
            if (input) input.value = value;
        });

        // Update visibility checkbox
        const visCheckbox = this.container.querySelector('.visibility-checkbox[data-target="image"]');
        if (visCheckbox) {
            visCheckbox.checked = this.imagePosition[this.currentBreakpoint]?.visible !== false;
        }

        // Update height
        const height = parseInt(this.bannerHeights[this.currentBreakpoint]) || 100;
        const heightSlider = this.container.querySelector('.height-slider');
        const heightInput = this.container.querySelector('.height-input');
        if (heightSlider) heightSlider.value = height;
        if (heightInput) heightInput.value = height;

        // Update visible counts
        const visibleCount = this.visibleCounts[this.currentBreakpoint] || 4;
        const visibleSlider = this.container.querySelector('.visible-count-slider');
        const visibleInput = this.container.querySelector('.visible-count-input');
        if (visibleSlider) visibleSlider.value = visibleCount;
        if (visibleInput) visibleInput.value = visibleCount;
    }

    updatePreview() {
        const canvas = this.container.querySelector('.preview-canvas');
        if (!canvas) return;

        const bp = this.currentBreakpoint;
        const svgPos = this.svgPosition[bp] || this.defaultSvgPos;
        const imgPos = this.imagePosition[bp] || this.defaultImgPos;

        // Update canvas height
        canvas.style.height = this.bannerHeights[bp] || '100px';

        // Update SVG
        const svgEl = canvas.querySelector('.preview-svg');
        if (svgEl) {
            const transforms = [];
            transforms.push(`translate(${svgPos.x || 0}px, ${svgPos.y || 0}px)`);
            transforms.push(`scale(${svgPos.scale || 1})`);
            if (svgPos.rotation) transforms.push(`rotate(${svgPos.rotation}deg)`);

            svgEl.style.transform = transforms.join(' ');
            svgEl.style.opacity = (svgPos.opacity || 30) / 100;
            svgEl.style.zIndex = svgPos.zIndex || 1;
        }

        // Update Image
        const imgEl = canvas.querySelector('.preview-image');
        if (imgEl) {
            const transforms = [];
            transforms.push(`translate(${imgPos.x || 0}px, ${imgPos.y || 0}px)`);
            transforms.push(`scale(${imgPos.scale || 1})`);
            if (imgPos.rotation) transforms.push(`rotate(${imgPos.rotation}deg)`);

            imgEl.style.transform = transforms.join(' ');
            imgEl.style.zIndex = imgPos.zIndex || 2;
            imgEl.style.display = imgPos.visible !== false ? '' : 'none';
        }
    }

    updateHiddenInputs() {
        const svgInput = document.getElementById('svg_position_input');
        const imgInput = document.getElementById('image_position_input');
        const heightsInput = document.getElementById('banner_heights_input');
        const svgAnimInput = document.getElementById('svg_animation_input');
        const imgAnimInput = document.getElementById('image_animation_input');
        const overflowInput = document.getElementById('image_overflow_input');

        if (svgInput) svgInput.value = JSON.stringify(this.svgPosition);
        if (imgInput) imgInput.value = JSON.stringify(this.imagePosition);
        if (heightsInput) heightsInput.value = JSON.stringify(this.bannerHeights);
        if (svgAnimInput) svgAnimInput.value = this.svgAnimation;
        if (imgAnimInput) imgAnimInput.value = this.imageAnimation;
        if (overflowInput) overflowInput.value = this.imageOverflow ? '1' : '0';

        const visibleCountsInput = document.getElementById('visible_counts_input');
        if (visibleCountsInput) visibleCountsInput.value = JSON.stringify(this.visibleCounts);
    }

    copyToAllBreakpoints() {
        const current = this.currentBreakpoint;
        this.breakpoints.forEach(bp => {
            if (bp !== current) {
                this.svgPosition[bp] = { ...this.svgPosition[current] };
                this.imagePosition[bp] = { ...this.imagePosition[current] };
                this.bannerHeights[bp] = this.bannerHeights[current];
                this.visibleCounts[bp] = this.visibleCounts[current];
            }
        });
        this.updateHiddenInputs();

        // Show confirmation
        alert(`Positions copied from ${current.toUpperCase()} to all breakpoints!`);
    }

    resetCurrentBreakpoint() {
        const bp = this.currentBreakpoint;
        this.svgPosition[bp] = { ...this.defaultSvgPos };
        this.imagePosition[bp] = { ...this.defaultImgPos };
        this.bannerHeights[bp] = this.defaultHeight[bp];

        this.updateInputsFromState();
        this.updateHiddenInputs();
        this.updatePreview();
    }

    // Static method to initialize from existing data
    static create(containerId, options = {}) {
        const editor = new PositionEditor({
            containerId,
            ...options
        });
        editor.init();
        return editor;
    }
}

// Export for use
window.PositionEditor = PositionEditor;
