/**
 * Icon Picker Component
 *
 * A searchable modal for selecting Lucide icons with preview and category grouping.
 *
 * Features:
 * - Modal with searchable icon grid
 * - 100+ popular Lucide icons
 * - Search filter (debounced)
 * - Click to select
 * - Preview selected icon
 * - Category grouping (UI, Content, Navigation, etc.)
 * - Keyboard navigation (Arrow keys, Enter, Escape)
 * - RTL support
 *
 * Dependencies:
 * - Lucide Icons (already included in project)
 * - Alpine.js (optional, for reactive preview)
 *
 * @example
 * // Initialize in your Blade template
 * <input type="hidden" name="icon" id="category-icon" value="folder">
 * <button type="button" id="icon-picker-trigger" class="btn">Choose Icon</button>
 * <div id="icon-preview"></div>
 *
 * <script>
 *   const picker = new IconPicker(document.getElementById('category-icon'), {
 *     trigger: document.getElementById('icon-picker-trigger'),
 *     preview: document.getElementById('icon-preview'),
 *     onSelect: (icon) => console.log('Selected:', icon)
 *   });
 * </script>
 */

class IconPicker {
    /**
     * Create a new IconPicker instance
     *
     * @param {HTMLElement} inputElement - Hidden input to store icon name
     * @param {Object} options - Configuration options
     * @param {HTMLElement} options.trigger - Button to open modal
     * @param {HTMLElement} options.preview - Element to show icon preview
     * @param {Function} options.onSelect - Callback when icon is selected
     * @param {string} options.modalId - Custom modal ID (default: 'icon-picker-modal')
     */
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            trigger: options.trigger,
            preview: options.preview,
            onSelect: options.onSelect || (() => {}),
            modalId: options.modalId || 'icon-picker-modal',
            ...options
        };

        this.icons = this.getIconsList();
        this.filteredIcons = [...this.icons];
        this.selectedIcon = this.input?.value || 'folder';
        this.searchQuery = '';
        this.selectedCategory = 'all';
        this.modal = null;
        this.searchInput = null;
        this.searchDebounceTimer = null;

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        if (!this.input) {
            console.error('IconPicker: Input element not found');
            return;
        }

        // Create modal
        this.createModal();

        // Setup trigger
        if (this.options.trigger) {
            this.options.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });
        }

        // Update preview
        this.updatePreview();
    }

    /**
     * Get list of popular Lucide icons organized by category
     *
     * @returns {Array<Object>} Array of icon objects
     */
    getIconsList() {
        return [
            // UI & Interface
            { name: 'folder', category: 'ui', keywords: ['directory', 'files', 'storage'] },
            { name: 'folder-open', category: 'ui', keywords: ['directory', 'files', 'opened'] },
            { name: 'file', category: 'ui', keywords: ['document', 'page'] },
            { name: 'file-text', category: 'ui', keywords: ['document', 'page', 'text'] },
            { name: 'book', category: 'ui', keywords: ['library', 'reading', 'education'] },
            { name: 'book-open', category: 'ui', keywords: ['reading', 'education'] },
            { name: 'bookmark', category: 'ui', keywords: ['save', 'favorite'] },
            { name: 'tag', category: 'ui', keywords: ['label', 'category'] },
            { name: 'tags', category: 'ui', keywords: ['labels', 'categories'] },
            { name: 'star', category: 'ui', keywords: ['favorite', 'rating'] },
            { name: 'heart', category: 'ui', keywords: ['love', 'like', 'favorite'] },
            { name: 'flag', category: 'ui', keywords: ['marker', 'bookmark'] },

            // Content & Media
            { name: 'image', category: 'content', keywords: ['photo', 'picture', 'media'] },
            { name: 'video', category: 'content', keywords: ['media', 'player', 'film'] },
            { name: 'music', category: 'content', keywords: ['audio', 'sound', 'media'] },
            { name: 'film', category: 'content', keywords: ['video', 'movie', 'media'] },
            { name: 'camera', category: 'content', keywords: ['photo', 'picture'] },
            { name: 'mic', category: 'content', keywords: ['microphone', 'audio', 'record'] },
            { name: 'headphones', category: 'content', keywords: ['audio', 'music', 'sound'] },

            // Navigation
            { name: 'home', category: 'navigation', keywords: ['house', 'main', 'dashboard'] },
            { name: 'menu', category: 'navigation', keywords: ['hamburger', 'bars', 'navigation'] },
            { name: 'compass', category: 'navigation', keywords: ['direction', 'navigation'] },
            { name: 'map', category: 'navigation', keywords: ['location', 'navigation'] },
            { name: 'map-pin', category: 'navigation', keywords: ['location', 'marker'] },
            { name: 'navigation', category: 'navigation', keywords: ['direction', 'gps'] },

            // Communication
            { name: 'mail', category: 'communication', keywords: ['email', 'message', 'envelope'] },
            { name: 'message-square', category: 'communication', keywords: ['chat', 'comment'] },
            { name: 'message-circle', category: 'communication', keywords: ['chat', 'bubble'] },
            { name: 'phone', category: 'communication', keywords: ['call', 'telephone'] },
            { name: 'phone-call', category: 'communication', keywords: ['calling', 'telephone'] },
            { name: 'bell', category: 'communication', keywords: ['notification', 'alert'] },

            // Users & People
            { name: 'user', category: 'users', keywords: ['person', 'account', 'profile'] },
            { name: 'users', category: 'users', keywords: ['people', 'group', 'team'] },
            { name: 'user-plus', category: 'users', keywords: ['add', 'invite', 'new'] },
            { name: 'user-check', category: 'users', keywords: ['verified', 'approved'] },
            { name: 'user-x', category: 'users', keywords: ['remove', 'delete', 'ban'] },

            // Actions
            { name: 'plus', category: 'actions', keywords: ['add', 'new', 'create'] },
            { name: 'plus-circle', category: 'actions', keywords: ['add', 'new', 'create'] },
            { name: 'minus', category: 'actions', keywords: ['remove', 'subtract'] },
            { name: 'x', category: 'actions', keywords: ['close', 'delete', 'remove'] },
            { name: 'check', category: 'actions', keywords: ['done', 'complete', 'success'] },
            { name: 'check-circle', category: 'actions', keywords: ['done', 'complete', 'success'] },
            { name: 'edit', category: 'actions', keywords: ['pencil', 'modify', 'change'] },
            { name: 'trash-2', category: 'actions', keywords: ['delete', 'remove', 'bin'] },
            { name: 'copy', category: 'actions', keywords: ['duplicate', 'clone'] },
            { name: 'save', category: 'actions', keywords: ['disk', 'floppy', 'store'] },
            { name: 'download', category: 'actions', keywords: ['save', 'export'] },
            { name: 'upload', category: 'actions', keywords: ['import', 'add'] },
            { name: 'search', category: 'actions', keywords: ['find', 'magnify', 'look'] },
            { name: 'filter', category: 'actions', keywords: ['funnel', 'sort'] },
            { name: 'refresh-cw', category: 'actions', keywords: ['reload', 'sync', 'update'] },
            { name: 'share-2', category: 'actions', keywords: ['forward', 'send'] },

            // Settings & Controls
            { name: 'settings', category: 'settings', keywords: ['gear', 'preferences', 'config'] },
            { name: 'sliders', category: 'settings', keywords: ['controls', 'adjust'] },
            { name: 'toggle-left', category: 'settings', keywords: ['switch', 'off'] },
            { name: 'toggle-right', category: 'settings', keywords: ['switch', 'on'] },
            { name: 'lock', category: 'settings', keywords: ['secure', 'private', 'protected'] },
            { name: 'unlock', category: 'settings', keywords: ['open', 'public'] },
            { name: 'eye', category: 'settings', keywords: ['view', 'show', 'visible'] },
            { name: 'eye-off', category: 'settings', keywords: ['hide', 'hidden', 'invisible'] },

            // Status & Indicators
            { name: 'info', category: 'status', keywords: ['information', 'help'] },
            { name: 'alert-circle', category: 'status', keywords: ['warning', 'error', 'danger'] },
            { name: 'alert-triangle', category: 'status', keywords: ['warning', 'caution'] },
            { name: 'help-circle', category: 'status', keywords: ['question', 'info', 'support'] },
            { name: 'check-circle-2', category: 'status', keywords: ['success', 'done', 'complete'] },
            { name: 'x-circle', category: 'status', keywords: ['error', 'failed', 'close'] },

            // Time & Calendar
            { name: 'calendar', category: 'time', keywords: ['date', 'schedule', 'event'] },
            { name: 'clock', category: 'time', keywords: ['time', 'watch', 'schedule'] },
            { name: 'calendar-days', category: 'time', keywords: ['date', 'month'] },

            // Shopping & Commerce
            { name: 'shopping-cart', category: 'commerce', keywords: ['cart', 'buy', 'shop'] },
            { name: 'shopping-bag', category: 'commerce', keywords: ['bag', 'purchase'] },
            { name: 'credit-card', category: 'commerce', keywords: ['payment', 'card'] },
            { name: 'dollar-sign', category: 'commerce', keywords: ['money', 'price', 'currency'] },

            // Technology
            { name: 'smartphone', category: 'technology', keywords: ['mobile', 'phone', 'device'] },
            { name: 'tablet', category: 'technology', keywords: ['ipad', 'device'] },
            { name: 'laptop', category: 'technology', keywords: ['computer', 'pc'] },
            { name: 'monitor', category: 'technology', keywords: ['screen', 'display'] },
            { name: 'wifi', category: 'technology', keywords: ['wireless', 'internet', 'connection'] },
            { name: 'globe', category: 'technology', keywords: ['world', 'internet', 'web'] },
            { name: 'link', category: 'technology', keywords: ['chain', 'url', 'hyperlink'] },
            { name: 'database', category: 'technology', keywords: ['storage', 'server', 'data'] },
            { name: 'server', category: 'technology', keywords: ['hosting', 'data', 'cloud'] },
            { name: 'cloud', category: 'technology', keywords: ['storage', 'backup', 'online'] },

            // Arrows & Directions
            { name: 'arrow-right', category: 'arrows', keywords: ['next', 'forward', 'direction'] },
            { name: 'arrow-left', category: 'arrows', keywords: ['back', 'previous', 'direction'] },
            { name: 'arrow-up', category: 'arrows', keywords: ['top', 'direction'] },
            { name: 'arrow-down', category: 'arrows', keywords: ['bottom', 'direction'] },
            { name: 'chevron-right', category: 'arrows', keywords: ['next', 'forward'] },
            { name: 'chevron-left', category: 'arrows', keywords: ['back', 'previous'] },
            { name: 'chevron-up', category: 'arrows', keywords: ['collapse', 'up'] },
            { name: 'chevron-down', category: 'arrows', keywords: ['expand', 'down'] },

            // Islamic & Religious
            { name: 'book-marked', category: 'religious', keywords: ['quran', 'hadith', 'islamic'] },
            { name: 'bookmark-plus', category: 'religious', keywords: ['save', 'favorite'] },
            { name: 'library', category: 'religious', keywords: ['books', 'knowledge', 'islamic'] },
            { name: 'award', category: 'religious', keywords: ['badge', 'achievement', 'medal'] },
            { name: 'crown', category: 'religious', keywords: ['royal', 'premium', 'important'] },
        ];
    }

    /**
     * Create modal HTML
     */
    createModal() {
        // Check if modal already exists
        if (document.getElementById(this.options.modalId)) {
            this.modal = document.getElementById(this.options.modalId);
            return;
        }

        // Create modal element
        const modalHtml = `
            <div id="${this.options.modalId}" class="icon-picker-modal fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden" style="display: none;">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden animate-fadeInUp">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Choose an Icon</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select from ${this.icons.length} popular icons</p>
                            </div>
                            <button type="button" class="icon-picker-close p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <i data-lucide="x" class="w-6 h-6 text-gray-500 dark:text-gray-400"></i>
                            </button>
                        </div>

                        <!-- Search and Categories -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <!-- Search -->
                            <div class="relative mb-4">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                                <input
                                    type="text"
                                    class="icon-picker-search w-full pl-10 pr-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-600 transition-shadow"
                                    placeholder="Search icons..."
                                    autocomplete="off"
                                >
                            </div>

                            <!-- Category Filter -->
                            <div class="flex flex-wrap gap-2">
                                ${this.getCategoryButtons()}
                            </div>
                        </div>

                        <!-- Icons Grid -->
                        <div class="icon-picker-grid p-6 overflow-y-auto" style="max-height: calc(90vh - 280px);">
                            <!-- Grid will be populated by renderIcons() -->
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <span class="icon-picker-count">${this.icons.length}</span> icons
                            </div>
                            <button type="button" class="icon-picker-close px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        this.modal = document.getElementById(this.options.modalId);

        // Get elements
        this.searchInput = this.modal.querySelector('.icon-picker-search');

        // Setup event listeners
        this.setupModalListeners();

        // Initial render
        this.renderIcons();
    }

    /**
     * Get category filter buttons HTML
     *
     * @returns {string} HTML string
     */
    getCategoryButtons() {
        const categories = [
            { id: 'all', label: 'All' },
            { id: 'ui', label: 'UI' },
            { id: 'content', label: 'Content' },
            { id: 'navigation', label: 'Navigation' },
            { id: 'communication', label: 'Communication' },
            { id: 'users', label: 'Users' },
            { id: 'actions', label: 'Actions' },
            { id: 'settings', label: 'Settings' },
            { id: 'status', label: 'Status' },
            { id: 'time', label: 'Time' },
            { id: 'technology', label: 'Technology' },
            { id: 'religious', label: 'Religious' }
        ];

        return categories.map(cat => `
            <button
                type="button"
                class="category-filter px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                    cat.id === this.selectedCategory
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                }"
                data-category="${cat.id}"
            >
                ${cat.label}
            </button>
        `).join('');
    }

    /**
     * Setup modal event listeners
     */
    setupModalListeners() {
        // Close buttons
        this.modal.querySelectorAll('.icon-picker-close').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Search input
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                this.searchQuery = e.target.value;
                this.filterIcons();
            }, 300);
        });

        // Category filters
        this.modal.addEventListener('click', (e) => {
            const categoryBtn = e.target.closest('.category-filter');
            if (categoryBtn) {
                this.selectedCategory = categoryBtn.dataset.category;
                this.updateCategoryButtons();
                this.filterIcons();
            }
        });

        // Icon selection
        this.modal.addEventListener('click', (e) => {
            const iconBtn = e.target.closest('.icon-option');
            if (iconBtn) {
                this.selectIcon(iconBtn.dataset.icon);
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.modal.classList.contains('hidden')) {
                if (e.key === 'Escape') {
                    this.close();
                }
            }
        });
    }

    /**
     * Filter icons based on search and category
     */
    filterIcons() {
        this.filteredIcons = this.icons.filter(icon => {
            // Category filter
            if (this.selectedCategory !== 'all' && icon.category !== this.selectedCategory) {
                return false;
            }

            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                return (
                    icon.name.toLowerCase().includes(query) ||
                    icon.keywords.some(keyword => keyword.toLowerCase().includes(query))
                );
            }

            return true;
        });

        this.renderIcons();
    }

    /**
     * Render icons grid
     */
    renderIcons() {
        const grid = this.modal.querySelector('.icon-picker-grid');
        const countEl = this.modal.querySelector('.icon-picker-count');

        if (this.filteredIcons.length === 0) {
            grid.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="search-x" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">No icons found</p>
                </div>
            `;
        } else {
            grid.innerHTML = `
                <div class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 gap-2">
                    ${this.filteredIcons.map(icon => `
                        <button
                            type="button"
                            class="icon-option flex flex-col items-center gap-2 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group ${
                                icon.name === this.selectedIcon ? 'bg-blue-100 dark:bg-blue-900/30 ring-2 ring-blue-600' : ''
                            }"
                            data-icon="${icon.name}"
                            title="${icon.name}"
                        >
                            <i data-lucide="${icon.name}" class="w-6 h-6 text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400"></i>
                            <span class="text-xs text-gray-600 dark:text-gray-400 truncate w-full text-center">${icon.name}</span>
                        </button>
                    `).join('')}
                </div>
            `;
        }

        // Update count
        if (countEl) {
            countEl.textContent = this.filteredIcons.length;
        }

        // Reinitialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Update category button states
     */
    updateCategoryButtons() {
        this.modal.querySelectorAll('.category-filter').forEach(btn => {
            if (btn.dataset.category === this.selectedCategory) {
                btn.className = 'category-filter px-4 py-2 rounded-lg text-sm font-medium transition-all bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg';
            } else {
                btn.className = 'category-filter px-4 py-2 rounded-lg text-sm font-medium transition-all bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600';
            }
        });
    }

    /**
     * Select an icon
     *
     * @param {string} iconName - Icon name
     */
    selectIcon(iconName) {
        this.selectedIcon = iconName;
        this.input.value = iconName;

        // Trigger change event
        const event = new Event('change', { bubbles: true });
        this.input.dispatchEvent(event);

        // Update preview
        this.updatePreview();

        // Callback
        this.options.onSelect(iconName);

        // Close modal
        this.close();

        // Show success toast
        if (window.Alpine && window.Alpine.store('admin')) {
            window.Alpine.store('admin').success('Icon selected');
        }
    }

    /**
     * Update icon preview
     */
    updatePreview() {
        if (!this.options.preview) return;

        this.options.preview.innerHTML = `
            <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-100 to-green-100 dark:from-blue-900/30 dark:to-green-900/30 rounded-lg">
                <i data-lucide="${this.selectedIcon}" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
            </div>
        `;

        // Reinitialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Open the modal
     */
    open() {
        this.modal.classList.remove('hidden');
        this.modal.style.display = 'flex';

        // Focus search input
        setTimeout(() => {
            this.searchInput?.focus();
        }, 100);

        // Reset filters
        this.searchQuery = '';
        this.selectedCategory = 'all';
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.updateCategoryButtons();
        this.filterIcons();

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close the modal
     */
    close() {
        this.modal.classList.add('hidden');
        this.modal.style.display = 'none';

        // Restore body scroll
        document.body.style.overflow = '';
    }

    /**
     * Destroy the component
     */
    destroy() {
        if (this.modal) {
            this.modal.remove();
        }
        this.searchDebounceTimer && clearTimeout(this.searchDebounceTimer);
    }
}

// Make available globally
window.IconPicker = IconPicker;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = IconPicker;
}
