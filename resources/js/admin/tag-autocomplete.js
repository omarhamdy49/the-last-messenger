/**
 * Tag Autocomplete Component
 *
 * An autocomplete component for tag selection with multi-select support, inline creation, and keyboard navigation.
 *
 * Features:
 * - Search tags as user types (debounced AJAX)
 * - Create new tag inline
 * - Multi-select (tag pills)
 * - Keyboard navigation (arrow keys, enter, backspace)
 * - Remove tags with X button or backspace
 * - Visual feedback on hover/focus
 * - RTL support
 * - Duplicate prevention
 *
 * @example
 * // Initialize in your Blade template
 * <div id="tag-autocomplete-container"></div>
 * <input type="hidden" name="tags" id="tags-input">
 *
 * <script>
 *   const tagAutocomplete = new TagAutocomplete(document.getElementById('tag-autocomplete-container'), {
 *     inputName: 'tags',
 *     selectedTags: [
 *       { id: 1, name: 'Islamic History' },
 *       { id: 2, name: 'Hadith' }
 *     ],
 *     apiUrl: '/admin/tags/search',
 *     onTagAdded: (tag) => console.log('Added:', tag),
 *     onTagRemoved: (tag) => console.log('Removed:', tag)
 *   });
 * </script>
 */

class TagAutocomplete {
    /**
     * Create a new TagAutocomplete instance
     *
     * @param {HTMLElement} container - Container element
     * @param {Object} options - Configuration options
     * @param {string} options.inputName - Name for hidden input (default: 'tags')
     * @param {Array<Object>} options.selectedTags - Pre-selected tags
     * @param {string} options.apiUrl - API URL for searching tags
     * @param {Function} options.onTagAdded - Callback when tag is added
     * @param {Function} options.onTagRemoved - Callback when tag is removed
     * @param {number} options.searchDelay - Debounce delay in ms (default: 300)
     * @param {number} options.minSearchLength - Minimum characters to search (default: 2)
     * @param {boolean} options.allowCreate - Allow creating new tags (default: true)
     */
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            inputName: options.inputName || 'tags',
            selectedTags: options.selectedTags || [],
            apiUrl: options.apiUrl || '/admin/tags/search',
            onTagAdded: options.onTagAdded || (() => {}),
            onTagRemoved: options.onTagRemoved || (() => {}),
            searchDelay: options.searchDelay || 300,
            minSearchLength: options.minSearchLength || 2,
            allowCreate: options.allowCreate !== false,
            ...options
        };

        this.selectedTags = [...this.options.selectedTags];
        this.searchResults = [];
        this.searchQuery = '';
        this.isLoading = false;
        this.isOpen = false;
        this.focusedIndex = -1;
        this.searchDebounceTimer = null;

        this.searchInput = null;
        this.dropdown = null;
        this.hiddenInput = null;

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        if (!this.container) {
            console.error('TagAutocomplete: Container element not found');
            return;
        }

        // Render component
        this.render();

        // Setup event listeners
        this.setupEventListeners();

        // Update hidden input
        this.updateHiddenInput();
    }

    /**
     * Render the component HTML
     */
    render() {
        this.container.innerHTML = `
            <div class="tag-autocomplete relative">
                <!-- Hidden input for form submission -->
                <input
                    type="hidden"
                    name="${this.options.inputName}"
                    class="tag-autocomplete-hidden"
                    value="${this.getTagIds().join(',')}"
                >

                <!-- Main input area -->
                <div class="tag-autocomplete-wrapper border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-blue-600 focus-within:border-transparent transition-shadow">
                    <!-- Selected tags pills -->
                    <div class="tag-autocomplete-pills flex flex-wrap gap-2 p-2">
                        ${this.renderSelectedTags()}

                        <!-- Search input -->
                        <input
                            type="text"
                            class="tag-autocomplete-input flex-1 min-w-[120px] px-2 py-1 bg-transparent border-0 focus:outline-none focus:ring-0 text-gray-900 dark:text-white placeholder-gray-500"
                            placeholder="${this.selectedTags.length === 0 ? 'Search or create tags...' : 'Add more tags...'}"
                            autocomplete="off"
                        >
                    </div>
                </div>

                <!-- Dropdown with search results -->
                <div class="tag-autocomplete-dropdown hidden absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                    <!-- Results will be rendered here -->
                </div>
            </div>
        `;

        // Get references
        this.searchInput = this.container.querySelector('.tag-autocomplete-input');
        this.dropdown = this.container.querySelector('.tag-autocomplete-dropdown');
        this.hiddenInput = this.container.querySelector('.tag-autocomplete-hidden');

        // Initialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Render selected tags pills
     *
     * @returns {string} HTML string
     */
    renderSelectedTags() {
        return this.selectedTags.map(tag => `
            <div class="tag-pill inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-blue-100 to-green-100 dark:from-blue-900/30 dark:to-green-900/30 text-gray-800 dark:text-gray-200 rounded-lg text-sm font-medium transition-all hover:shadow-md group">
                <span>${this.escapeHtml(tag.name)}</span>
                <button
                    type="button"
                    class="tag-remove flex-shrink-0 w-4 h-4 flex items-center justify-center hover:bg-red-500 hover:text-white rounded transition-colors"
                    data-tag-id="${tag.id}"
                    data-tag-name="${this.escapeHtml(tag.name)}"
                    aria-label="Remove tag"
                >
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        `).join('');
    }

    /**
     * Render search results dropdown
     */
    renderDropdown() {
        if (!this.isOpen || (!this.isLoading && this.searchResults.length === 0 && this.searchQuery.length < this.options.minSearchLength)) {
            this.dropdown.classList.add('hidden');
            return;
        }

        this.dropdown.classList.remove('hidden');

        if (this.isLoading) {
            this.dropdown.innerHTML = `
                <div class="p-4 text-center">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Searching...</p>
                </div>
            `;
            return;
        }

        if (this.searchResults.length === 0) {
            if (this.searchQuery.length >= this.options.minSearchLength && this.options.allowCreate) {
                this.dropdown.innerHTML = `
                    <div class="p-2">
                        <button
                            type="button"
                            class="tag-result-item w-full flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-left group"
                            data-action="create"
                            data-tag-name="${this.escapeHtml(this.searchQuery)}"
                        >
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg flex items-center justify-center">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    Create "${this.escapeHtml(this.searchQuery)}"
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Add this as a new tag
                                </div>
                            </div>
                        </button>
                    </div>
                `;
            } else {
                this.dropdown.innerHTML = `
                    <div class="p-4 text-center">
                        <i data-lucide="search-x" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ${this.searchQuery.length < this.options.minSearchLength
                                ? `Type at least ${this.options.minSearchLength} characters to search`
                                : 'No tags found'
                            }
                        </p>
                    </div>
                `;
            }
        } else {
            this.dropdown.innerHTML = `
                <div class="p-2 space-y-1">
                    ${this.searchResults.map((tag, index) => `
                        <button
                            type="button"
                            class="tag-result-item w-full flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-left ${
                                index === this.focusedIndex ? 'bg-gray-100 dark:bg-gray-700' : ''
                            }"
                            data-action="select"
                            data-tag-id="${tag.id}"
                            data-tag-name="${this.escapeHtml(tag.name)}"
                        >
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-100 to-green-100 dark:from-blue-900/30 dark:to-green-900/30 rounded-lg flex items-center justify-center">
                                <i data-lucide="tag" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    ${this.highlightMatch(tag.name, this.searchQuery)}
                                </div>
                                ${tag.articles_count !== undefined ? `
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ${tag.articles_count} articles
                                    </div>
                                ` : ''}
                            </div>
                        </button>
                    `).join('')}

                    ${this.options.allowCreate && this.searchQuery ? `
                        <button
                            type="button"
                            class="tag-result-item w-full flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-left border-t border-gray-200 dark:border-gray-700 mt-2 pt-2"
                            data-action="create"
                            data-tag-name="${this.escapeHtml(this.searchQuery)}"
                        >
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg flex items-center justify-center">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    Create "${this.escapeHtml(this.searchQuery)}"
                                </div>
                            </div>
                        </button>
                    ` : ''}
                </div>
            `;
        }

        // Reinitialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Search input
        this.searchInput.addEventListener('input', (e) => {
            this.searchQuery = e.target.value;
            this.focusedIndex = -1;

            // Debounce search
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                if (this.searchQuery.length >= this.options.minSearchLength) {
                    this.search(this.searchQuery);
                } else {
                    this.searchResults = [];
                    this.isOpen = this.searchQuery.length > 0;
                    this.renderDropdown();
                }
            }, this.options.searchDelay);
        });

        // Keyboard navigation
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.focusedIndex = Math.min(this.focusedIndex + 1, this.searchResults.length - 1);
                this.renderDropdown();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.focusedIndex = Math.max(this.focusedIndex - 1, -1);
                this.renderDropdown();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.focusedIndex >= 0 && this.searchResults[this.focusedIndex]) {
                    this.addTag(this.searchResults[this.focusedIndex]);
                } else if (this.searchQuery && this.options.allowCreate) {
                    this.createAndAddTag(this.searchQuery);
                }
            } else if (e.key === 'Escape') {
                this.closeDropdown();
            } else if (e.key === 'Backspace' && this.searchQuery === '' && this.selectedTags.length > 0) {
                // Remove last tag on backspace
                e.preventDefault();
                this.removeTag(this.selectedTags[this.selectedTags.length - 1]);
            }
        });

        // Focus input to open dropdown
        this.searchInput.addEventListener('focus', () => {
            if (this.searchQuery.length >= this.options.minSearchLength) {
                this.isOpen = true;
                this.renderDropdown();
            }
        });

        // Tag result item clicks
        this.dropdown.addEventListener('click', (e) => {
            const item = e.target.closest('.tag-result-item');
            if (item) {
                const action = item.dataset.action;

                if (action === 'select') {
                    const tag = {
                        id: parseInt(item.dataset.tagId),
                        name: item.dataset.tagName
                    };
                    this.addTag(tag);
                } else if (action === 'create') {
                    const name = item.dataset.tagName;
                    this.createAndAddTag(name);
                }
            }
        });

        // Remove tag clicks
        this.container.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.tag-remove');
            if (removeBtn) {
                e.preventDefault();
                const tag = {
                    id: removeBtn.dataset.tagId === 'new' ? 'new' : parseInt(removeBtn.dataset.tagId),
                    name: removeBtn.dataset.tagName
                };
                this.removeTag(tag);
            }
        });

        // Close dropdown on click outside
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.closeDropdown();
            }
        });
    }

    /**
     * Search tags via API
     *
     * @param {string} query - Search query
     */
    async search(query) {
        this.isLoading = true;
        this.isOpen = true;
        this.renderDropdown();

        try {
            const response = await fetch(`${this.options.apiUrl}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.searchResults = (data.tags || data).filter(tag => {
                // Filter out already selected tags
                return !this.selectedTags.some(selected => selected.id === tag.id);
            });

            this.renderDropdown();
        } catch (error) {
            console.error('Error searching tags:', error);
            this.searchResults = [];
            this.renderDropdown();
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Add a tag to selected tags
     *
     * @param {Object} tag - Tag object with id and name
     */
    addTag(tag) {
        // Check if already selected
        if (this.selectedTags.some(t => t.id === tag.id)) {
            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').warning('Tag already added');
            }
            return;
        }

        // Add to selected tags
        this.selectedTags.push(tag);

        // Update UI
        this.updatePills();
        this.updateHiddenInput();

        // Clear search
        this.searchQuery = '';
        this.searchInput.value = '';
        this.searchResults = [];
        this.closeDropdown();

        // Focus back to input
        this.searchInput.focus();

        // Callback
        this.options.onTagAdded(tag);
    }

    /**
     * Create a new tag and add it
     *
     * @param {string} name - Tag name
     */
    async createAndAddTag(name) {
        // Trim and validate
        name = name.trim();
        if (!name) return;

        // Check if already exists
        if (this.selectedTags.some(t => t.name.toLowerCase() === name.toLowerCase())) {
            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').warning('Tag already added');
            }
            return;
        }

        // Add temporary tag with 'new' ID
        const tempTag = {
            id: 'new_' + Date.now(),
            name: name,
            isNew: true
        };

        this.addTag(tempTag);

        // Show success
        if (window.Alpine && window.Alpine.store('admin')) {
            window.Alpine.store('admin').success('Tag will be created on save');
        }
    }

    /**
     * Remove a tag from selected tags
     *
     * @param {Object} tag - Tag object with id and name
     */
    removeTag(tag) {
        this.selectedTags = this.selectedTags.filter(t => t.id !== tag.id);

        // Update UI
        this.updatePills();
        this.updateHiddenInput();

        // Focus back to input
        this.searchInput.focus();

        // Callback
        this.options.onTagRemoved(tag);
    }

    /**
     * Update selected tags pills
     */
    updatePills() {
        const pillsContainer = this.container.querySelector('.tag-autocomplete-pills');
        const inputElement = this.searchInput;

        // Remove input temporarily
        inputElement.remove();

        // Update pills
        pillsContainer.innerHTML = this.renderSelectedTags();

        // Re-add input
        pillsContainer.appendChild(inputElement);

        // Update placeholder
        if (this.selectedTags.length === 0) {
            inputElement.placeholder = 'Search or create tags...';
        } else {
            inputElement.placeholder = 'Add more tags...';
        }

        // Reinitialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Update hidden input with tag IDs
     */
    updateHiddenInput() {
        if (!this.hiddenInput) return;

        // Create JSON array with both existing and new tags
        const tags = this.selectedTags.map(tag => ({
            id: tag.isNew ? null : tag.id,
            name: tag.name,
            isNew: tag.isNew || false
        }));

        this.hiddenInput.value = JSON.stringify(tags);
    }

    /**
     * Get tag IDs
     *
     * @returns {Array<number|string>} Array of tag IDs
     */
    getTagIds() {
        return this.selectedTags.map(tag => tag.id);
    }

    /**
     * Close dropdown
     */
    closeDropdown() {
        this.isOpen = false;
        this.dropdown.classList.add('hidden');
    }

    /**
     * Highlight matching text
     *
     * @param {string} text - Full text
     * @param {string} query - Search query
     * @returns {string} HTML with highlighted match
     */
    highlightMatch(text, query) {
        if (!query) return this.escapeHtml(text);

        const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi');
        return this.escapeHtml(text).replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-900/50">$1</mark>');
    }

    /**
     * Escape HTML to prevent XSS
     *
     * @param {string} str - String to escape
     * @returns {string} Escaped string
     */
    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Escape regex special characters
     *
     * @param {string} str - String to escape
     * @returns {string} Escaped string
     */
    escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Get selected tags
     *
     * @returns {Array<Object>} Array of selected tags
     */
    getSelectedTags() {
        return [...this.selectedTags];
    }

    /**
     * Set selected tags programmatically
     *
     * @param {Array<Object>} tags - Array of tag objects
     */
    setSelectedTags(tags) {
        this.selectedTags = [...tags];
        this.updatePills();
        this.updateHiddenInput();
    }

    /**
     * Clear all selected tags
     */
    clearTags() {
        this.selectedTags = [];
        this.updatePills();
        this.updateHiddenInput();
    }

    /**
     * Destroy the component
     */
    destroy() {
        clearTimeout(this.searchDebounceTimer);
        this.container.innerHTML = '';
    }
}

// Make available globally
window.TagAutocomplete = TagAutocomplete;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TagAutocomplete;
}
