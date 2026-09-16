/**
 * Category Tree Component
 *
 * A sortable, drag-and-drop hierarchical tree component for managing categories.
 * Uses SortableJS for drag-and-drop functionality with nested list support.
 *
 * Features:
 * - Drag and drop to reorder categories
 * - Drag to change parent (nest/unnest)
 * - Visual feedback during drag (ghost element, drop zones)
 * - Expand/collapse nodes with state persistence
 * - AJAX calls to update server after reorder
 * - Smooth animations
 * - RTL support
 * - Touch support for mobile
 * - Maximum depth validation (prevents nesting beyond 5 levels)
 *
 * Dependencies:
 * - SortableJS: https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js
 * - Lucide Icons (already included in project)
 *
 * @example
 * // Initialize in your Blade template
 * <div id="category-tree"></div>
 *
 * <script>
 *   const tree = new CategoryTree(document.getElementById('category-tree'), {
 *     maxDepth: 5,
 *     apiUrl: '/admin/categories',
 *     onReorder: (data) => console.log('Reordered:', data),
 *     onError: (error) => console.error('Error:', error)
 *   });
 * </script>
 */

class CategoryTree {
    /**
     * Create a new CategoryTree instance
     *
     * @param {HTMLElement} container - The container element
     * @param {Object} options - Configuration options
     * @param {number} options.maxDepth - Maximum nesting depth (default: 5)
     * @param {string} options.apiUrl - Base API URL (default: '/admin/categories')
     * @param {Function} options.onReorder - Callback after successful reorder
     * @param {Function} options.onError - Callback on error
     * @param {boolean} options.animation - Enable animations (default: true)
     * @param {number} options.animationDuration - Animation duration in ms (default: 300)
     */
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            maxDepth: options.maxDepth || 5,
            apiUrl: options.apiUrl || window.adminRoutes?.categories,
            onReorder: options.onReorder || (() => {}),
            onError: options.onError || console.error,
            animation: options.animation !== false,
            animationDuration: options.animationDuration || 300,
            ...options
        };

        this.categories = [];
        this.sortables = [];
        this.expandedNodes = this.loadExpandedState();
        this.isLoading = false;

        this.init();
    }

    /**
     * Initialize the component
     */
    async init() {
        if (!this.container) {
            console.error('CategoryTree: Container element not found');
            return;
        }

        // Check if SortableJS is loaded
        if (typeof Sortable === 'undefined') {
            console.error('CategoryTree: SortableJS library not found. Please include: https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js');
            return;
        }

        // Add loading state
        this.setLoading(true);

        try {
            // Load tree data from server
            await this.loadTree();

            // Initialize sortable on all lists
            this.initializeSortable();

            // Setup event listeners
            this.setupEventListeners();
        } catch (error) {
            this.options.onError(error);
            this.showError('Failed to load categories');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Load tree data from API
     */
    async loadTree() {
        try {
            const response = await fetch(`${this.options.apiUrl}/tree`, {
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
            this.categories = data.categories || data;

            // Render the tree
            this.render();
        } catch (error) {
            console.error('Error loading tree:', error);
            throw error;
        }
    }

    /**
     * Render the tree HTML
     */
    render() {
        if (!this.categories || this.categories.length === 0) {
            this.container.innerHTML = this.renderEmptyState();
            return;
        }

        this.container.innerHTML = `
            <div class="category-tree">
                <ul class="category-list" data-depth="0">
                    ${this.categories.map(category => this.renderCategory(category, 0)).join('')}
                </ul>
            </div>
        `;

        // Reinitialize Lucide icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Render a single category item
     *
     * @param {Object} category - Category data
     * @param {number} depth - Current nesting depth
     * @returns {string} HTML string
     */
    renderCategory(category, depth) {
        const hasChildren = category.children && category.children.length > 0;
        const isExpanded = this.expandedNodes.includes(category.id);
        const expandIcon = isExpanded ? 'chevron-down' : 'chevron-right';
        const childrenClass = isExpanded ? '' : 'hidden';

        return `
            <li class="category-item" data-id="${category.id}" data-depth="${depth}">
                <div class="category-item-content group flex items-center gap-2 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-move">
                    ${hasChildren ? `
                        <button
                            type="button"
                            class="expand-toggle flex-shrink-0 w-6 h-6 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                            data-category-id="${category.id}"
                            aria-label="Toggle children"
                        >
                            <i data-lucide="${expandIcon}" class="w-4 h-4 text-gray-500 dark:text-gray-400"></i>
                        </button>
                    ` : `
                        <span class="flex-shrink-0 w-6 h-6"></span>
                    `}

                    <div class="drag-handle flex-shrink-0 cursor-grab active:cursor-grabbing">
                        <i data-lucide="grip-vertical" class="w-5 h-5 text-gray-400 dark:text-gray-500"></i>
                    </div>

                    ${category.icon ? `
                        <div class="category-icon flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: ${category.color || '#0056b3'}20;">
                            <i data-lucide="${category.icon}" class="w-5 h-5" style="color: ${category.color || '#0056b3'};"></i>
                        </div>
                    ` : ''}

                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white truncate">${this.escapeHtml(category.name)}</h4>
                        ${category.description ? `
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">${this.escapeHtml(category.description)}</p>
                        ` : ''}
                    </div>

                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                            ${category.articles_count || 0} articles
                        </span>

                        <a
                            href="${this.options.apiUrl}/${category.id}/edit"
                            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                            title="Edit category"
                        >
                            <i data-lucide="edit" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                        </a>

                        <button
                            type="button"
                            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors delete-category"
                            data-category-id="${category.id}"
                            title="Delete category"
                        >
                            <i data-lucide="trash-2" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                        </button>
                    </div>
                </div>

                ${hasChildren ? `
                    <ul class="category-list ${childrenClass} pl-8 mt-2 space-y-2" data-depth="${depth + 1}">
                        ${category.children.map(child => this.renderCategory(child, depth + 1)).join('')}
                    </ul>
                ` : ''}
            </li>
        `;
    }

    /**
     * Render empty state
     *
     * @returns {string} HTML string
     */
    renderEmptyState() {
        return `
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                    <i data-lucide="folder-x" class="w-8 h-8 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No categories yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Create your first category to get started</p>
                <a
                    href="${this.options.apiUrl}/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-shadow"
                >
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    <span>Create Category</span>
                </a>
            </div>
        `;
    }

    /**
     * Initialize SortableJS on all category lists
     */
    initializeSortable() {
        // Destroy existing sortables
        this.sortables.forEach(sortable => sortable.destroy());
        this.sortables = [];

        // Find all category lists
        const lists = this.container.querySelectorAll('.category-list');

        lists.forEach(list => {
            const depth = parseInt(list.dataset.depth || 0);

            const sortable = Sortable.create(list, {
                group: 'nested',
                animation: this.options.animation ? this.options.animationDuration : 0,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',

                // Check if drop is allowed
                onMove: (evt) => {
                    const itemDepth = parseInt(evt.dragged.dataset.depth || 0);
                    const targetDepth = parseInt(evt.to.dataset.depth || 0);
                    const newDepth = targetDepth;

                    // Calculate max depth of dragged item and its children
                    const maxChildDepth = this.getMaxChildDepth(evt.dragged);
                    const totalDepth = newDepth + maxChildDepth;

                    // Prevent drop if it would exceed max depth
                    if (totalDepth >= this.options.maxDepth) {
                        return false;
                    }

                    return true;
                },

                // Handle drop
                onEnd: (evt) => {
                    this.handleDrop(evt);
                }
            });

            this.sortables.push(sortable);
        });
    }

    /**
     * Get maximum depth of children for an element
     *
     * @param {HTMLElement} element - The element to check
     * @returns {number} Maximum child depth
     */
    getMaxChildDepth(element) {
        const childList = element.querySelector('.category-list');
        if (!childList) return 0;

        const children = childList.querySelectorAll('.category-item');
        if (children.length === 0) return 1;

        let maxDepth = 1;
        children.forEach(child => {
            const childDepth = this.getMaxChildDepth(child);
            maxDepth = Math.max(maxDepth, childDepth + 1);
        });

        return maxDepth;
    }

    /**
     * Handle drop event after drag
     *
     * @param {Object} evt - SortableJS event object
     */
    async handleDrop(evt) {
        // Get category ID
        const categoryId = evt.item.dataset.id;

        // Get new parent ID
        const parentList = evt.to;
        const parentItem = parentList.closest('.category-item');
        const parentId = parentItem ? parentItem.dataset.id : null;

        // Get new position (index in list)
        const position = evt.newIndex;

        // Get new depth
        const newDepth = parseInt(evt.to.dataset.depth || 0);

        // Update depth attribute on moved item
        evt.item.dataset.depth = newDepth;

        // Update depth on all children recursively
        this.updateChildrenDepth(evt.item, newDepth);

        // Send update to server
        try {
            await this.updateCategoryPosition({
                id: parseInt(categoryId),
                parent_id: parentId ? parseInt(parentId) : null,
                position: position
            });

            // Show success toast
            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').success('Category moved successfully');
            }

            // Reinitialize sortable to update depth constraints
            this.initializeSortable();

            // Callback
            this.options.onReorder({
                categoryId,
                parentId,
                position,
                depth: newDepth
            });

        } catch (error) {
            console.error('Error updating category position:', error);

            // Show error toast
            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').error('Failed to move category');
            }

            // Revert the change by reloading
            await this.loadTree();
            this.initializeSortable();
        }
    }

    /**
     * Update depth attribute on all children recursively
     *
     * @param {HTMLElement} element - Parent element
     * @param {number} parentDepth - Parent depth
     */
    updateChildrenDepth(element, parentDepth) {
        const childList = element.querySelector('.category-list');
        if (!childList) return;

        const newDepth = parentDepth + 1;
        childList.dataset.depth = newDepth;

        const children = childList.querySelectorAll(':scope > .category-item');
        children.forEach(child => {
            child.dataset.depth = newDepth;
            this.updateChildrenDepth(child, newDepth);
        });
    }

    /**
     * Send category position update to server
     *
     * @param {Object} data - Update data
     * @returns {Promise}
     */
    async updateCategoryPosition(data) {
        const response = await fetch(`${this.options.apiUrl}/reorder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Expand/collapse toggle
        this.container.addEventListener('click', (e) => {
            const button = e.target.closest('.expand-toggle');
            if (button) {
                e.preventDefault();
                e.stopPropagation();
                const categoryId = parseInt(button.dataset.categoryId);
                this.toggleNode(categoryId);
            }
        });

        // Delete category
        this.container.addEventListener('click', async (e) => {
            const button = e.target.closest('.delete-category');
            if (button) {
                e.preventDefault();
                e.stopPropagation();
                const categoryId = parseInt(button.dataset.categoryId);
                await this.deleteCategory(categoryId);
            }
        });
    }

    /**
     * Toggle expand/collapse of a node
     *
     * @param {number} categoryId - Category ID
     */
    toggleNode(categoryId) {
        const item = this.container.querySelector(`.category-item[data-id="${categoryId}"]`);
        if (!item) return;

        const childList = item.querySelector(':scope > .category-list');
        if (!childList) return;

        const button = item.querySelector('.expand-toggle');
        const icon = button?.querySelector('i');

        if (childList.classList.contains('hidden')) {
            // Expand
            childList.classList.remove('hidden');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-down');
            }
            this.expandedNodes.push(categoryId);
        } else {
            // Collapse
            childList.classList.add('hidden');
            if (icon) {
                icon.setAttribute('data-lucide', 'chevron-right');
            }
            this.expandedNodes = this.expandedNodes.filter(id => id !== categoryId);
        }

        // Save expanded state
        this.saveExpandedState();

        // Reinitialize icons
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    /**
     * Delete a category
     *
     * @param {number} categoryId - Category ID
     */
    async deleteCategory(categoryId) {
        // Confirm deletion
        if (window.Alpine && window.Alpine.store('admin')) {
            const confirmed = await window.Alpine.store('admin').confirm({
                title: 'Delete Category',
                message: 'Are you sure you want to delete this category? This action cannot be undone.',
                confirmText: 'Delete',
                type: 'danger'
            });

            if (!confirmed) return;
        } else {
            if (!confirm('Are you sure you want to delete this category?')) return;
        }

        try {
            const response = await fetch(`${this.options.apiUrl}/${categoryId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Reload tree
            await this.loadTree();
            this.initializeSortable();

            // Show success
            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').success('Category deleted successfully');
            }
        } catch (error) {
            console.error('Error deleting category:', error);

            if (window.Alpine && window.Alpine.store('admin')) {
                window.Alpine.store('admin').error('Failed to delete category');
            } else {
                alert('Failed to delete category');
            }
        }
    }

    /**
     * Load expanded nodes from localStorage
     *
     * @returns {Array<number>} Array of expanded category IDs
     */
    loadExpandedState() {
        try {
            const stored = localStorage.getItem('categoryTreeExpanded');
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading expanded state:', error);
            return [];
        }
    }

    /**
     * Save expanded nodes to localStorage
     */
    saveExpandedState() {
        try {
            localStorage.setItem('categoryTreeExpanded', JSON.stringify(this.expandedNodes));
        } catch (error) {
            console.error('Error saving expanded state:', error);
        }
    }

    /**
     * Set loading state
     *
     * @param {boolean} loading - Loading state
     */
    setLoading(loading) {
        this.isLoading = loading;

        if (loading) {
            this.container.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            `;
        }
    }

    /**
     * Show error message
     *
     * @param {string} message - Error message
     */
    showError(message) {
        this.container.innerHTML = `
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full mb-4">
                    <i data-lucide="alert-circle" class="w-8 h-8 text-red-600 dark:text-red-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Error</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">${this.escapeHtml(message)}</p>
                <button
                    onclick="location.reload()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Reload Page
                </button>
            </div>
        `;
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
     * Refresh the tree
     */
    async refresh() {
        await this.loadTree();
        this.initializeSortable();
    }

    /**
     * Destroy the component
     */
    destroy() {
        // Destroy all sortables
        this.sortables.forEach(sortable => sortable.destroy());
        this.sortables = [];

        // Clear container
        this.container.innerHTML = '';
    }
}

// Make available globally
window.CategoryTree = CategoryTree;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CategoryTree;
}
