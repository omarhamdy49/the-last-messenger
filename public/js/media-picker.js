/**
 * Media Picker Alpine Component
 * Reusable component for selecting media from library
 */

function mediaPicker(mode = 'single', initialSelected = null, initialSelectedItems = []) {
    return {
        // Picker State
        pickerMode: mode,
        selectedMedia: initialSelected,
        selectedMediaItems: initialSelectedItems || [],

        // Modal State
        isOpen: false,
        loading: false,
        search: '',
        typeFilter: 'all',
        dateFilter: 'all',

        // Media Data
        allItems: [],
        filteredItems: [],
        tempSelected: [],

        // Pagination
        pagination: {
            currentPage: 1,
            perPage: 12,
            total: 0,
            from: 0,
            to: 0,
            lastPage: 1
        },

        init() {
            // Component is ready
        },

        async openModal() {
            this.isOpen = true;
            this.tempSelected = [];
            this.search = '';
            this.typeFilter = 'all';
            this.dateFilter = 'all';
            this.pagination.currentPage = 1;

            await this.loadMediaLibrary();
        },

        async loadMediaLibrary() {
            this.loading = true;
            try {
                const response = await fetch('/su/portal/v1/admin/simple-media', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load media library');
                }

                const data = await response.json();

                this.allItems = data.data.map(item => ({
                    id: item.id,
                    name: item.original_filename || item.filename,
                    url: item.url,
                    type: item.type || 'image',
                    size: item.size,
                    created_at: item.created_at
                }));

                this.filterMedia();
            } catch (error) {
                console.error('Error loading media library:', error);
                alert('فشل تحميل مكتبة الوسائط. يرجى المحاولة مرة أخرى.');
            } finally {
                this.loading = false;
            }
        },

        filterMedia() {
            let filtered = [...this.allItems];

            // Search filter
            if (this.search) {
                const searchLower = this.search.toLowerCase();
                filtered = filtered.filter(item =>
                    item.name.toLowerCase().includes(searchLower)
                );
            }

            // Type filter
            if (this.typeFilter !== 'all') {
                filtered = filtered.filter(item => item.type === this.typeFilter);
            }

            // Date filter
            if (this.dateFilter !== 'all') {
                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());

                filtered = filtered.filter(item => {
                    const itemDate = new Date(item.created_at);
                    if (this.dateFilter === 'today') return itemDate >= today;
                    if (this.dateFilter === 'week') return itemDate >= weekAgo;
                    if (this.dateFilter === 'month') return itemDate >= monthAgo;
                    return true;
                });
            }

            // Update pagination
            this.pagination.total = filtered.length;
            this.pagination.lastPage = Math.ceil(filtered.length / this.pagination.perPage);

            // Paginate
            const start = (this.pagination.currentPage - 1) * this.pagination.perPage;
            const end = start + this.pagination.perPage;

            this.pagination.from = filtered.length > 0 ? start + 1 : 0;
            this.pagination.to = Math.min(end, filtered.length);

            this.filteredItems = filtered.slice(start, end);
        },

        selectMedia(media) {
            if (this.pickerMode === 'single') {
                this.tempSelected = [media];
            } else {
                const index = this.tempSelected.findIndex(item => item.id === media.id);
                if (index > -1) {
                    this.tempSelected.splice(index, 1);
                } else {
                    this.tempSelected.push(media);
                }
            }
        },

        isMediaSelected(media) {
            return this.tempSelected.some(item => item.id === media.id);
        },

        confirmSelection() {
            if (this.pickerMode === 'single') {
                if (this.tempSelected.length > 0) {
                    this.selectedMedia = this.tempSelected[0];
                    this.$dispatch('media-selected', this.selectedMedia);
                }
            } else {
                // Add to existing items (avoid duplicates)
                this.tempSelected.forEach(media => {
                    const exists = this.selectedMediaItems.some(item => item.id === media.id);
                    if (!exists) {
                        this.selectedMediaItems.push(media);
                    }
                });
                this.$dispatch('media-items-selected', this.selectedMediaItems);
            }
            this.isOpen = false;
        },

        removeMedia() {
            this.selectedMedia = null;
            this.$dispatch('media-removed');
        },

        removeMediaItem(index) {
            this.selectedMediaItems.splice(index, 1);
            this.$dispatch('media-item-removed', { index });
        },

        previousPage() {
            if (this.pagination.currentPage > 1) {
                this.pagination.currentPage--;
                this.filterMedia();
            }
        },

        nextPage() {
            if (this.pagination.currentPage < this.pagination.lastPage) {
                this.pagination.currentPage++;
                this.filterMedia();
            }
        },

        goToPage(page) {
            if (page !== '...') {
                this.pagination.currentPage = page;
                this.filterMedia();
            }
        },

        pageNumbers() {
            const pages = [];
            const current = this.pagination.currentPage;
            const last = this.pagination.lastPage;

            if (last <= 7) {
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }
            } else {
                if (current <= 4) {
                    for (let i = 1; i <= 5; i++) pages.push(i);
                    pages.push('...');
                    pages.push(last);
                } else if (current >= last - 3) {
                    pages.push(1);
                    pages.push('...');
                    for (let i = last - 4; i <= last; i++) pages.push(i);
                } else {
                    pages.push(1);
                    pages.push('...');
                    for (let i = current - 1; i <= current + 1; i++) pages.push(i);
                    pages.push('...');
                    pages.push(last);
                }
            }

            return pages.filter((p, i, arr) => p !== '...' || arr.indexOf(p) === i);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    };
}

// Make it globally available
window.mediaPicker = mediaPicker;
