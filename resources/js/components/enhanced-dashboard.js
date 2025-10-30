export function enhancedDashboard() {
    return {
        darkMode: localStorage.getItem('darkMode') === 'true',
        sidebarOpen: true,
        notificationsOpen: false,
        userMenuOpen: false,
        searchQuery: '',
        recentSearches: JSON.parse(localStorage.getItem('recentSearches') || '[]'),

        init() {
            // Apply dark mode on init
            this.applyDarkMode();

            // Close dropdowns on outside click
            document.addEventListener('click', (e) => {
                if (!e.target.closest('[data-dropdown]')) {
                    this.notificationsOpen = false;
                    this.userMenuOpen = false;
                }
            });
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            this.applyDarkMode();
        },

        applyDarkMode() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        toggleNotifications() {
            this.notificationsOpen = !this.notificationsOpen;
            this.userMenuOpen = false;
        },

        toggleUserMenu() {
            this.userMenuOpen = !this.userMenuOpen;
            this.notificationsOpen = false;
        },

        search() {
            if (this.searchQuery.trim()) {
                // Add to recent searches
                if (!this.recentSearches.includes(this.searchQuery)) {
                    this.recentSearches.unshift(this.searchQuery);
                    if (this.recentSearches.length > 5) {
                        this.recentSearches.pop();
                    }
                    localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
                }

                // Perform search (implement your search logic here)
                console.log('Searching for:', this.searchQuery);
            }
        },

        clearSearch() {
            this.searchQuery = '';
        },

        clearRecentSearches() {
            this.recentSearches = [];
            localStorage.removeItem('recentSearches');
        },

        useRecentSearch(query) {
            this.searchQuery = query;
            this.search();
        },

        showToast(message, type = 'success') {
            // Create toast notification (you can customize this)
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="flex items-center gap-3">
                    <i data-lucide="${type === 'success' ? 'check-circle' : 'alert-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);

            // Reinitialize Lucide icons for the toast
            if (window.refreshIcons) {
                window.refreshIcons();
            }

            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };
}
