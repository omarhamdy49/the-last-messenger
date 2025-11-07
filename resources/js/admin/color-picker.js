/**
 * Color Picker Component
 *
 * A color picker component with preset palette, custom color input, and recently used colors.
 *
 * Features:
 * - Preset palette (blue-green variations)
 * - Custom color input (HEX format)
 * - Live preview
 * - Recently used colors (localStorage, max 10)
 * - Copy hex to clipboard
 * - Validation
 * - Keyboard navigation
 * - RTL support
 *
 * @example
 * // Initialize in your Blade template
 * <input type="text" name="color" id="category-color" value="#0056b3">
 * <button type="button" id="color-picker-trigger" class="btn">Choose Color</button>
 * <div id="color-preview"></div>
 *
 * <script>
 *   const picker = new ColorPicker(document.getElementById('category-color'), {
 *     trigger: document.getElementById('color-picker-trigger'),
 *     preview: document.getElementById('color-preview'),
 *     onSelect: (color) => console.log('Selected:', color)
 *   });
 * </script>
 */

class ColorPicker {
    /**
     * Create a new ColorPicker instance
     *
     * @param {HTMLElement} inputElement - Input to store color value
     * @param {Object} options - Configuration options
     * @param {HTMLElement} options.trigger - Button to open picker
     * @param {HTMLElement} options.preview - Element to show color preview
     * @param {Array<string>} options.presets - Array of preset colors
     * @param {Function} options.onSelect - Callback when color is selected
     * @param {string} options.dropdownId - Custom dropdown ID
     */
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            trigger: options.trigger,
            preview: options.preview,
            presets: options.presets || this.getDefaultPresets(),
            onSelect: options.onSelect || (() => {}),
            dropdownId: options.dropdownId || 'color-picker-dropdown',
            maxRecentColors: options.maxRecentColors || 10,
            ...options
        };

        this.selectedColor = this.input?.value || '#0056b3';
        this.recentColors = this.getRecentColors();
        this.dropdown = null;
        this.customInput = null;
        this.isOpen = false;

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        if (!this.input) {
            console.error('ColorPicker: Input element not found');
            return;
        }

        // Validate and normalize initial color
        this.selectedColor = this.normalizeColor(this.selectedColor);
        this.input.value = this.selectedColor;

        // Create dropdown
        this.createDropdown();

        // Setup trigger
        if (this.options.trigger) {
            this.options.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        }

        // Update preview
        this.updatePreview();

        // Sync input changes
        this.input.addEventListener('change', () => {
            this.selectedColor = this.normalizeColor(this.input.value);
            this.updatePreview();
        });
    }

    /**
     * Get default preset colors (blue-green theme)
     *
     * @returns {Array<Object>} Array of color objects
     */
    getDefaultPresets() {
        return [
            // Primary colors
            { hex: '#0056b3', name: 'Primary Blue' },
            { hex: '#218838', name: 'Primary Green' },
            { hex: '#64CCC5', name: 'Accent Teal' },

            // Blue variations
            { hex: '#003d82', name: 'Dark Blue' },
            { hex: '#0069d9', name: 'Medium Blue' },
            { hex: '#4A90E2', name: 'Light Blue' },
            { hex: '#ADD8E6', name: 'Sky Blue' },

            // Green variations
            { hex: '#155724', name: 'Dark Green' },
            { hex: '#28a745', name: 'Medium Green' },
            { hex: '#5CB85C', name: 'Light Green' },
            { hex: '#90EE90', name: 'Pale Green' },

            // Teal/Cyan variations
            { hex: '#17a2b8', name: 'Cyan' },
            { hex: '#20C997', name: 'Teal' },
            { hex: '#40E0D0', name: 'Turquoise' },

            // Accent colors
            { hex: '#6610f2', name: 'Purple' },
            { hex: '#E83E8C', name: 'Pink' },
            { hex: '#fd7e14', name: 'Orange' },
            { hex: '#ffc107', name: 'Yellow' },
            { hex: '#dc3545', name: 'Red' },

            // Neutral colors
            { hex: '#6c757d', name: 'Gray' },
            { hex: '#343a40', name: 'Dark Gray' },
            { hex: '#f8f9fa', name: 'Light Gray' },
        ];
    }

    /**
     * Create dropdown HTML
     */
    createDropdown() {
        // Check if dropdown already exists
        if (document.getElementById(this.options.dropdownId)) {
            this.dropdown = document.getElementById(this.options.dropdownId);
            return;
        }

        // Create dropdown element
        const dropdownHtml = `
            <div id="${this.options.dropdownId}" class="color-picker-dropdown absolute z-50 hidden mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl p-4 w-80">
                <!-- Custom Color Input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Custom Color
                    </label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-mono text-sm">#</span>
                            <input
                                type="text"
                                class="color-picker-custom-input w-full pl-8 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-gray-900 dark:text-white font-mono placeholder-gray-500 focus:ring-2 focus:ring-blue-600 transition-shadow"
                                placeholder="0056b3"
                                maxlength="6"
                                autocomplete="off"
                            >
                        </div>
                        <button
                            type="button"
                            class="color-picker-copy px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                            title="Copy to clipboard"
                        >
                            <i data-lucide="copy" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
                        </button>
                    </div>
                </div>

                <!-- Preset Colors -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Preset Colors
                    </label>
                    <div class="grid grid-cols-7 gap-2">
                        ${this.options.presets.map(color => `
                            <button
                                type="button"
                                class="color-option w-full aspect-square rounded-lg border-2 transition-all hover:scale-110 hover:shadow-lg ${
                                    color.hex === this.selectedColor ? 'border-gray-900 dark:border-white ring-2 ring-offset-2 ring-blue-600' : 'border-transparent'
                                }"
                                style="background-color: ${color.hex};"
                                data-color="${color.hex}"
                                title="${color.name}"
                            ></button>
                        `).join('')}
                    </div>
                </div>

                <!-- Recent Colors -->
                <div class="recent-colors-container ${this.recentColors.length === 0 ? 'hidden' : ''}">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Recent Colors
                    </label>
                    <div class="grid grid-cols-10 gap-2">
                        ${this.renderRecentColors()}
                    </div>
                    <button
                        type="button"
                        class="clear-recent-colors mt-2 text-xs text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                    >
                        Clear recent colors
                    </button>
                </div>

                <!-- Preview -->
                <div class="mt-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="color-picker-preview-swatch w-12 h-12 rounded-lg border-2 border-gray-200 dark:border-gray-700 transition-colors" style="background-color: ${this.selectedColor};"></div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Selected Color</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">${this.selectedColor}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append dropdown after input or trigger
        const referenceElement = this.options.trigger || this.input;
        referenceElement.parentElement.style.position = 'relative';
        referenceElement.insertAdjacentHTML('afterend', dropdownHtml);

        this.dropdown = document.getElementById(this.options.dropdownId);
        this.customInput = this.dropdown.querySelector('.color-picker-custom-input');

        // Setup dropdown event listeners
        this.setupDropdownListeners();

        // Initialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Render recent colors HTML
     *
     * @returns {string} HTML string
     */
    renderRecentColors() {
        return this.recentColors.map(color => `
            <button
                type="button"
                class="color-option w-full aspect-square rounded-lg border-2 transition-all hover:scale-110 hover:shadow-lg ${
                    color === this.selectedColor ? 'border-gray-900 dark:border-white ring-2 ring-offset-2 ring-blue-600' : 'border-transparent'
                }"
                style="background-color: ${color};"
                data-color="${color}"
                title="${color}"
            ></button>
        `).join('');
    }

    /**
     * Setup dropdown event listeners
     */
    setupDropdownListeners() {
        // Custom color input
        this.customInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^0-9a-fA-F]/g, '').slice(0, 6);
            e.target.value = value;

            if (value.length === 6) {
                this.selectColor('#' + value);
            }
        });

        this.customInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && this.customInput.value.length === 6) {
                this.selectColor('#' + this.customInput.value);
                this.close();
            }
        });

        // Preset color buttons
        this.dropdown.addEventListener('click', (e) => {
            const colorBtn = e.target.closest('.color-option');
            if (colorBtn) {
                const color = colorBtn.dataset.color;
                this.selectColor(color);
                this.close();
            }
        });

        // Copy button
        const copyBtn = this.dropdown.querySelector('.color-picker-copy');
        copyBtn?.addEventListener('click', () => {
            this.copyToClipboard(this.selectedColor);
        });

        // Clear recent colors
        const clearBtn = this.dropdown.querySelector('.clear-recent-colors');
        clearBtn?.addEventListener('click', () => {
            this.clearRecentColors();
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.dropdown.contains(e.target) && !this.options.trigger?.contains(e.target)) {
                this.close();
            }
        });

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }

    /**
     * Select a color
     *
     * @param {string} color - Hex color value
     */
    selectColor(color) {
        // Normalize and validate
        color = this.normalizeColor(color);

        if (!this.isValidColor(color)) {
            console.warn('Invalid color:', color);
            return;
        }

        this.selectedColor = color;
        this.input.value = color;

        // Trigger change event
        const event = new Event('change', { bubbles: true });
        this.input.dispatchEvent(event);

        // Update preview
        this.updatePreview();

        // Update dropdown preview
        if (this.dropdown) {
            const previewSwatch = this.dropdown.querySelector('.color-picker-preview-swatch');
            if (previewSwatch) {
                previewSwatch.style.backgroundColor = color;
            }

            const previewText = this.dropdown.querySelector('.color-picker-preview-swatch + div .text-xs');
            if (previewText) {
                previewText.textContent = color;
            }

            // Update custom input
            if (this.customInput) {
                this.customInput.value = color.substring(1);
            }
        }

        // Add to recent colors
        this.saveRecentColor(color);

        // Callback
        this.options.onSelect(color);
    }

    /**
     * Update color preview
     */
    updatePreview() {
        if (!this.options.preview) return;

        this.options.preview.innerHTML = `
            <div class="inline-flex items-center gap-2">
                <div class="w-10 h-10 rounded-lg border-2 border-gray-200 dark:border-gray-700 shadow-sm" style="background-color: ${this.selectedColor};"></div>
                <span class="text-sm font-mono text-gray-600 dark:text-gray-400">${this.selectedColor}</span>
            </div>
        `;
    }

    /**
     * Normalize color to hex format
     *
     * @param {string} color - Color value
     * @returns {string} Normalized hex color
     */
    normalizeColor(color) {
        if (!color) return '#0056b3';

        // Remove spaces and convert to lowercase
        color = color.trim().toLowerCase();

        // Add # if missing
        if (!color.startsWith('#')) {
            color = '#' + color;
        }

        // Expand shorthand hex (#abc -> #aabbcc)
        if (color.length === 4) {
            color = '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
        }

        return color;
    }

    /**
     * Validate hex color
     *
     * @param {string} color - Hex color value
     * @returns {boolean} Is valid
     */
    isValidColor(color) {
        return /^#[0-9A-Fa-f]{6}$/.test(color);
    }

    /**
     * Get recent colors from localStorage
     *
     * @returns {Array<string>} Array of hex colors
     */
    getRecentColors() {
        try {
            const stored = localStorage.getItem('colorPickerRecentColors');
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading recent colors:', error);
            return [];
        }
    }

    /**
     * Save color to recent colors
     *
     * @param {string} color - Hex color value
     */
    saveRecentColor(color) {
        // Remove if already exists
        this.recentColors = this.recentColors.filter(c => c !== color);

        // Add to beginning
        this.recentColors.unshift(color);

        // Keep only max recent colors
        this.recentColors = this.recentColors.slice(0, this.options.maxRecentColors);

        // Save to localStorage
        try {
            localStorage.setItem('colorPickerRecentColors', JSON.stringify(this.recentColors));
        } catch (error) {
            console.error('Error saving recent colors:', error);
        }

        // Update UI
        this.updateRecentColorsUI();
    }

    /**
     * Update recent colors UI
     */
    updateRecentColorsUI() {
        if (!this.dropdown) return;

        const container = this.dropdown.querySelector('.recent-colors-container');
        if (!container) return;

        if (this.recentColors.length === 0) {
            container.classList.add('hidden');
        } else {
            container.classList.remove('hidden');
            const grid = container.querySelector('.grid');
            if (grid) {
                grid.innerHTML = this.renderRecentColors();
            }
        }
    }

    /**
     * Clear recent colors
     */
    clearRecentColors() {
        this.recentColors = [];
        localStorage.removeItem('colorPickerRecentColors');
        this.updateRecentColorsUI();

        if (window.Alpine && window.Alpine.store('admin')) {
            window.Alpine.store('admin').success('Recent colors cleared');
        }
    }

    /**
     * Copy color to clipboard
     *
     * @param {string} color - Hex color value
     */
    async copyToClipboard(color) {
        try {
            await navigator.clipboard.writeText(color);

            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').success('Color copied to clipboard');
            }
        } catch (error) {
            console.error('Error copying to clipboard:', error);

            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = color;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').success('Color copied to clipboard');
            }
        }
    }

    /**
     * Toggle dropdown
     */
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    /**
     * Open dropdown
     */
    open() {
        if (!this.dropdown) return;

        this.dropdown.classList.remove('hidden');
        this.isOpen = true;

        // Update custom input
        if (this.customInput) {
            this.customInput.value = this.selectedColor.substring(1);
        }

        // Focus custom input
        setTimeout(() => {
            this.customInput?.focus();
        }, 100);
    }

    /**
     * Close dropdown
     */
    close() {
        if (!this.dropdown) return;

        this.dropdown.classList.add('hidden');
        this.isOpen = false;
    }

    /**
     * Destroy the component
     */
    destroy() {
        if (this.dropdown) {
            this.dropdown.remove();
        }
    }
}

// Make available globally
window.ColorPicker = ColorPicker;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ColorPicker;
}
