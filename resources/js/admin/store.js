/**
 * Admin Dashboard Alpine.js Store
 * Centralized state management for the admin interface
 *
 * Features:
 * - Dark mode with localStorage persistence
 * - Sidebar collapse state
 * - Language preference (RTL/LTR support)
 * - Notifications management
 * - User preferences
 * - Search state
 */

import Alpine from 'alpinejs';

// Admin Dashboard Store
Alpine.store('admin', {
    // ============================================
    // DARK MODE
    // ============================================
    darkMode: localStorage.getItem('adminDarkMode') === 'true', // Default to light

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('adminDarkMode', this.darkMode);
        this.updateDarkModeClass();
    },

    setDarkMode(value) {
        this.darkMode = value;
        localStorage.setItem('adminDarkMode', value);
        this.updateDarkModeClass();
    },

    updateDarkModeClass() {
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },

    // ============================================
    // SIDEBAR
    // ============================================
    sidebarCollapsed: localStorage.getItem('adminSidebarCollapsed') === 'true',
    sidebarOpen: false, // For mobile

    toggleSidebar() {
        if (window.innerWidth < 768) {
            // Mobile: toggle open/close
            this.sidebarOpen = !this.sidebarOpen;
        } else {
            // Desktop: toggle collapsed
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed);
        }
    },

    closeSidebar() {
        this.sidebarOpen = false;
    },

    openSidebar() {
        this.sidebarOpen = true;
    },

    // ============================================
    // LANGUAGE & RTL
    // ============================================
    language: localStorage.getItem('adminLanguage') || 'en',
    isRtl: localStorage.getItem('adminLanguage') === 'ar' || localStorage.getItem('adminLanguage') === 'ur',

    setLanguage(lang) {
        this.language = lang;
        this.isRtl = lang === 'ar' || lang === 'ur';
        localStorage.setItem('adminLanguage', lang);

        // Update document direction
        document.documentElement.setAttribute('dir', this.isRtl ? 'rtl' : 'ltr');
        document.documentElement.setAttribute('lang', lang);

        // Reload page to apply translations
        window.location.reload();
    },

    getDirection() {
        return this.isRtl ? 'rtl' : 'ltr';
    },

    // ============================================
    // NOTIFICATIONS
    // ============================================
    notifications: [],
    unreadNotificationsCount: 0,
    notificationsOpen: false,

    addNotification(notification) {
        const id = Date.now();
        this.notifications.unshift({
            id,
            title: notification.title || 'Notification',
            message: notification.message,
            type: notification.type || 'info', // info, success, warning, danger
            read: false,
            timestamp: new Date().toISOString(),
            icon: notification.icon || 'bell',
            ...notification
        });
        this.unreadNotificationsCount++;

        // Auto-remove after 10 seconds if auto-dismiss is enabled
        if (notification.autoDismiss !== false) {
            setTimeout(() => this.removeNotification(id), 10000);
        }
    },

    removeNotification(id) {
        const index = this.notifications.findIndex(n => n.id === id);
        if (index !== -1) {
            if (!this.notifications[index].read) {
                this.unreadNotificationsCount--;
            }
            this.notifications.splice(index, 1);
        }
    },

    markAsRead(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification && !notification.read) {
            notification.read = true;
            this.unreadNotificationsCount--;
        }
    },

    markAllAsRead() {
        this.notifications.forEach(n => n.read = true);
        this.unreadNotificationsCount = 0;
    },

    clearAllNotifications() {
        this.notifications = [];
        this.unreadNotificationsCount = 0;
    },

    toggleNotifications() {
        this.notificationsOpen = !this.notificationsOpen;
    },

    closeNotifications() {
        this.notificationsOpen = false;
    },

    // ============================================
    // SEARCH
    // ============================================
    searchOpen: false,
    searchQuery: '',
    searchResults: [],
    searchLoading: false,
    recentSearches: JSON.parse(localStorage.getItem('adminRecentSearches') || '[]'),

    openSearch() {
        this.searchOpen = true;
        // Focus search input after a short delay
        setTimeout(() => {
            const input = document.querySelector('#admin-search-input');
            if (input) input.focus();
        }, 100);
    },

    closeSearch() {
        this.searchOpen = false;
        this.searchQuery = '';
        this.searchResults = [];
    },

    async performSearch(query) {
        if (!query || query.length < 2) {
            this.searchResults = [];
            return;
        }

        this.searchLoading = true;
        this.searchQuery = query;

        try {
            // TODO: Replace with actual API call
            // const response = await fetch(`/api/admin/search?q=${encodeURIComponent(query)}`);
            // this.searchResults = await response.json();

            // Mock results for now
            await new Promise(resolve => setTimeout(resolve, 500));
            this.searchResults = [
                { type: 'article', title: `Article matching "${query}"`, url: '#' },
                { type: 'user', title: `User matching "${query}"`, url: '#' },
            ];

            // Add to recent searches
            this.addRecentSearch(query);
        } catch (error) {
            console.error('Search error:', error);
            this.searchResults = [];
        } finally {
            this.searchLoading = false;
        }
    },

    addRecentSearch(query) {
        // Remove if already exists
        this.recentSearches = this.recentSearches.filter(s => s !== query);

        // Add to beginning
        this.recentSearches.unshift(query);

        // Keep only last 10
        this.recentSearches = this.recentSearches.slice(0, 10);

        // Save to localStorage
        localStorage.setItem('adminRecentSearches', JSON.stringify(this.recentSearches));
    },

    clearRecentSearches() {
        this.recentSearches = [];
        localStorage.removeItem('adminRecentSearches');
    },

    // ============================================
    // USER PROFILE
    // ============================================
    userMenuOpen: false,

    toggleUserMenu() {
        this.userMenuOpen = !this.userMenuOpen;
    },

    closeUserMenu() {
        this.userMenuOpen = false;
    },

    // ============================================
    // MODALS
    // ============================================
    modals: {},

    openModal(modalId) {
        this.modals[modalId] = true;
    },

    closeModal(modalId) {
        this.modals[modalId] = false;
    },

    isModalOpen(modalId) {
        return this.modals[modalId] || false;
    },

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    toasts: [],

    showToast(options) {
        const id = Date.now();
        const toast = {
            id,
            message: options.message,
            type: options.type || 'info', // success, error, warning, info
            duration: options.duration || 3000,
            dismissible: options.dismissible !== false,
            ...options
        };

        this.toasts.push(toast);

        // Auto-dismiss
        if (toast.duration > 0) {
            setTimeout(() => this.dismissToast(id), toast.duration);
        }
    },

    dismissToast(id) {
        const index = this.toasts.findIndex(t => t.id === id);
        if (index !== -1) {
            this.toasts.splice(index, 1);
        }
    },

    success(message, options = {}) {
        this.showToast({ message, type: 'success', ...options });
    },

    error(message, options = {}) {
        this.showToast({ message, type: 'error', duration: 5000, ...options });
    },

    warning(message, options = {}) {
        this.showToast({ message, type: 'warning', ...options });
    },

    info(message, options = {}) {
        this.showToast({ message, type: 'info', ...options });
    },

    // ============================================
    // FILTERS & SORTING
    // ============================================
    filters: {},
    sortBy: 'created_at',
    sortOrder: 'desc',

    setFilter(key, value) {
        this.filters[key] = value;
    },

    removeFilter(key) {
        delete this.filters[key];
    },

    clearFilters() {
        this.filters = {};
    },

    setSort(field) {
        if (this.sortBy === field) {
            // Toggle sort order
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortBy = field;
            this.sortOrder = 'asc';
        }
    },

    getSortIcon(field) {
        if (this.sortBy !== field) return 'chevrons-up-down';
        return this.sortOrder === 'asc' ? 'chevron-up' : 'chevron-down';
    },

    // ============================================
    // TABLE SELECTION
    // ============================================
    selectedItems: [],
    selectAll: false,

    toggleSelectAll(items) {
        if (this.selectAll) {
            this.selectedItems = [...items.map(item => item.id)];
        } else {
            this.selectedItems = [];
        }
    },

    toggleItem(id) {
        const index = this.selectedItems.indexOf(id);
        if (index > -1) {
            this.selectedItems.splice(index, 1);
        } else {
            this.selectedItems.push(id);
        }
        this.selectAll = false;
    },

    isSelected(id) {
        return this.selectedItems.includes(id);
    },

    clearSelection() {
        this.selectedItems = [];
        this.selectAll = false;
    },

    // ============================================
    // CONFIRMATION DIALOG
    // ============================================
    confirmDialog: {
        open: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        type: 'danger', // danger, warning, info
        onConfirm: null,
        onCancel: null
    },

    confirm(options) {
        return new Promise((resolve) => {
            this.confirmDialog = {
                open: true,
                title: options.title || 'Confirm Action',
                message: options.message || 'Are you sure?',
                confirmText: options.confirmText || 'Confirm',
                cancelText: options.cancelText || 'Cancel',
                type: options.type || 'danger',
                onConfirm: () => {
                    this.confirmDialog.open = false;
                    resolve(true);
                },
                onCancel: () => {
                    this.confirmDialog.open = false;
                    resolve(false);
                }
            };
        });
    },

    closeConfirmDialog() {
        if (this.confirmDialog.onCancel) {
            this.confirmDialog.onCancel();
        }
        this.confirmDialog.open = false;
    },

    // ============================================
    // LOADING STATE
    // ============================================
    loading: false,

    setLoading(state) {
        this.loading = state;
    },

    // ============================================
    // INITIALIZATION
    // ============================================
    init() {
        // Apply dark mode class on init
        this.updateDarkModeClass();

        // Set RTL direction on init
        document.documentElement.setAttribute('dir', this.getDirection());
        document.documentElement.setAttribute('lang', this.language);

        // Handle window resize for sidebar
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                this.sidebarOpen = false;
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[x-data*="notifications"]')) {
                this.closeNotifications();
            }
            if (!e.target.closest('[x-data*="userMenu"]')) {
                this.closeUserMenu();
            }
        });

        // Close modals and search with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeSearch();
                this.closeNotifications();
                this.closeUserMenu();

                // Close any open modals
                Object.keys(this.modals).forEach(key => {
                    this.modals[key] = false;
                });
            }

            // Open search with Cmd/Ctrl + K
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.openSearch();
            }
        });

        console.log('Admin store initialized');
    }
});

// Initialize the store when Alpine starts
document.addEventListener('alpine:init', () => {
    Alpine.store('admin').init();
});

export default Alpine.store('admin');
