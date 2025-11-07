@extends('layouts.admin')

@section('title', 'Media Library')

@section('page-header')
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                Media Library
            </h1>
            <p class="text-sm"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Browse and manage your media files
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Upload Button -->
            <button @click="$dispatch('open-media-upload')"
                    class="px-6 py-3 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold text-sm hover:shadow-lg hover:scale-105 transition-all duration-200">
                <i data-lucide="upload" class="w-4 h-4 inline me-2"></i>
                Upload Media
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="mediaLibrary()" x-init="init()" @media-uploaded.window="handleUploadSuccess($event.detail)" class="space-y-6">

        <!-- Toolbar -->
        <div class="rounded-2xl shadow-lg p-4 animate-fadeInUp"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                <!-- Left Section: Search and View Toggle -->
                <div class="flex items-center gap-3 flex-1">
                    <!-- Search Bar -->
                    <div class="relative flex-1 max-w-md">
                        <input type="text"
                               x-model="searchQuery"
                               @input="debounceSearch()"
                               placeholder="Search media files..."
                               class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                        <i data-lucide="search"
                           class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                    </div>

                    <!-- View Toggle -->
                    <div class="flex items-center gap-1 p-1 rounded-lg"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <button @click="viewMode = 'grid'"
                                class="p-2 rounded transition-all duration-200"
                                :class="viewMode === 'grid' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                            <i data-lucide="grid-3x3" class="w-5 h-5"></i>
                        </button>
                        <button @click="viewMode = 'list'"
                                class="p-2 rounded transition-all duration-200"
                                :class="viewMode === 'list' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                            <i data-lucide="list" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Filter Toggle (Mobile) -->
                    <button @click="showFilters = !showFilters"
                            class="lg:hidden p-2 rounded-lg transition-colors"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        <i data-lucide="filter" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Right Section: Bulk Actions -->
                <div x-show="selectedItems.length > 0"
                     x-transition
                     class="flex items-center gap-2">
                    <span class="text-sm font-medium"
                          :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="selectedItems.length"></span> selected
                    </span>

                    <button @click="bulkDownload"
                            class="p-2 rounded-lg transition-colors"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            title="Download Selected">
                        <i data-lucide="download" class="w-5 h-5"></i>
                    </button>

                    <button @click="bulkTag"
                            class="p-2 rounded-lg transition-colors"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            title="Tag Selected">
                        <i data-lucide="tag" class="w-5 h-5"></i>
                    </button>

                    <button @click="confirmBulkDelete"
                            class="p-2 rounded-lg transition-colors bg-red-500 text-white hover:bg-red-600"
                            title="Delete Selected">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>

                    <button @click="clearSelection"
                            class="p-2 rounded-lg transition-colors"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            title="Clear Selection">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-col lg:flex-row gap-6">

            <!-- Filter Sidebar -->
            <x-admin.media-filter />

            <!-- Media Grid/List -->
            <div class="flex-1">

                <!-- Loading State -->
                <div x-show="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-[#64CCC5] border-t-transparent"></div>
                    <p class="mt-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Loading media...
                    </p>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && filteredMedia.length === 0"
                     class="rounded-2xl shadow-lg p-12 text-center animate-fadeInUp"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="image" class="w-12 h-12 text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        No Media Files Found
                    </h3>
                    <p class="mb-6"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-show="searchQuery || activeFilters.length > 0">
                            Try adjusting your search or filters
                        </span>
                        <span x-show="!searchQuery && activeFilters.length === 0">
                            Start by uploading your first media file
                        </span>
                    </p>
                    <button @click="$dispatch('open-media-upload')"
                            class="px-6 py-3 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:shadow-lg transition-all duration-200">
                        <i data-lucide="upload" class="w-4 h-4 inline me-2"></i>
                        Upload Media
                    </button>
                </div>

                <!-- Grid View -->
                <div x-show="!loading && filteredMedia.length > 0 && viewMode === 'grid'"
                     class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <template x-for="(item, index) in filteredMedia" :key="item.id">
                        <x-admin.media-card :item="item" :index="index" />
                    </template>
                </div>

                <!-- List View -->
                <div x-show="!loading && filteredMedia.length > 0 && viewMode === 'list'"
                     class="rounded-2xl shadow-lg overflow-hidden animate-fadeInUp"
                     :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b"
                                    :class="darkMode ? 'border-gray-800 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
                                    <th class="px-4 py-3 text-left w-12">
                                        <input type="checkbox"
                                               @change="toggleSelectAll"
                                               :checked="selectAll"
                                               class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Preview
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Type
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Size
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Dimensions
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Uploaded
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold"
                                        :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in filteredMedia" :key="item.id">
                                    <tr class="border-b transition-colors duration-200"
                                        :class="darkMode ? 'border-gray-800 hover:bg-gray-800/50' : 'border-gray-200 hover:bg-gray-50'">
                                        <td class="px-4 py-3">
                                            <input type="checkbox"
                                                   :value="item.id"
                                                   x-model="selectedItems"
                                                   class="rounded border-gray-300 text-[#0056b3] focus:ring-[#64CCC5]">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="w-16 h-16 rounded-lg overflow-hidden"
                                                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                                <img :src="item.thumbnail"
                                                     :alt="item.name"
                                                     class="w-full h-full object-cover"
                                                     onerror="this.src='/images/placeholder.png'">
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-sm"
                                                 :class="darkMode ? 'text-white' : 'text-gray-900'"
                                                 x-text="item.name"></div>
                                            <div class="text-xs mt-1"
                                                 :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                                                 x-text="item.filename"></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                  :class="getTypeBadgeClass(item.type)"
                                                  x-text="item.type"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm"
                                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                            x-text="item.size"></td>
                                        <td class="px-4 py-3 text-sm"
                                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                            x-text="item.dimensions || '-'"></td>
                                        <td class="px-4 py-3 text-sm"
                                            :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                                            x-text="item.uploaded"></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="viewMedia(item)"
                                                        class="p-1.5 rounded-lg transition-colors"
                                                        :class="darkMode ? 'text-gray-400 hover:text-white hover:bg-gray-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                                                        title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="editMedia(item)"
                                                        class="p-1.5 rounded-lg transition-colors"
                                                        :class="darkMode ? 'text-gray-400 hover:text-white hover:bg-gray-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                                                        title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="deleteMedia(item)"
                                                        class="p-1.5 rounded-lg transition-colors text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                        title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div x-show="!loading && filteredMedia.length > 0"
                     class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm"
                         :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Showing <span class="font-medium" x-text="((currentPage - 1) * perPage) + 1"></span>
                        to <span class="font-medium" x-text="Math.min(currentPage * perPage, totalItems)"></span>
                        of <span class="font-medium" x-text="totalItems"></span> items
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="prevPage"
                                :disabled="currentPage === 1"
                                class="p-2 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>

                        <template x-for="page in totalPages" :key="page">
                            <button @click="goToPage(page)"
                                    class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
                                    :class="page === currentPage ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200')"
                                    x-text="page"></button>
                        </template>

                        <button @click="nextPage"
                                :disabled="currentPage === totalPages"
                                class="p-2 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Upload Modal Component -->
        <x-admin.media-upload-modal />
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/media-upload.js') }}"></script>
<script>
function mediaLibrary() {
    return {
        // View state
        viewMode: 'grid', // 'grid' or 'list'
        loading: false,
        showFilters: window.innerWidth >= 1024,

        // Search and filters
        searchQuery: '',
        searchTimeout: null,
        activeFilters: [],

        // Selection
        selectedItems: [],
        selectAll: false,

        // Pagination
        currentPage: 1,
        perPage: 24,
        totalItems: 0,

        // Sample data (replace with API call)
        mediaItems: [
            {
                id: 1,
                name: 'Battle of Badr Hero Image',
                filename: 'badr-battle-hero.jpg',
                type: 'image',
                size: '2.4 MB',
                dimensions: '1920x1080',
                thumbnail: 'https://picsum.photos/seed/badr/400/300',
                uploaded: '2 hours ago',
                tags: ['seerah', 'battle', 'hero']
            },
            {
                id: 2,
                name: 'Stories of the Prophets Episode 1',
                filename: 'prophets-ep01.mp4',
                type: 'video',
                size: '124.5 MB',
                dimensions: '1280x720',
                thumbnail: 'https://picsum.photos/seed/prophets/400/300',
                uploaded: '1 day ago',
                tags: ['video', 'series', 'prophets']
            },
            {
                id: 3,
                name: 'Quran Recitation Audio',
                filename: 'surah-albaqarah.mp3',
                type: 'audio',
                size: '15.3 MB',
                dimensions: null,
                thumbnail: '/images/audio-placeholder.png',
                uploaded: '3 days ago',
                tags: ['quran', 'audio', 'recitation']
            },
            {
                id: 4,
                name: 'Hadith Collection PDF',
                filename: 'sahih-bukhari-chapter1.pdf',
                type: 'document',
                size: '4.7 MB',
                dimensions: null,
                thumbnail: '/images/pdf-placeholder.png',
                uploaded: '1 week ago',
                tags: ['hadith', 'document', 'bukhari']
            },
            {
                id: 5,
                name: 'Kaaba Aerial View',
                filename: 'kaaba-aerial.jpg',
                type: 'image',
                size: '3.2 MB',
                dimensions: '2560x1440',
                thumbnail: 'https://picsum.photos/seed/kaaba/400/300',
                uploaded: '2 weeks ago',
                tags: ['mecca', 'kaaba', 'holy']
            },
            {
                id: 6,
                name: 'Islamic Calligraphy',
                filename: 'bismillah-calligraphy.png',
                type: 'image',
                size: '1.8 MB',
                dimensions: '1200x800',
                thumbnail: 'https://picsum.photos/seed/calligraphy/400/300',
                uploaded: '3 weeks ago',
                tags: ['art', 'calligraphy']
            },
            {
                id: 7,
                name: 'Sahaba Documentary',
                filename: 'sahaba-documentary.mp4',
                type: 'video',
                size: '256.4 MB',
                dimensions: '1920x1080',
                thumbnail: 'https://picsum.photos/seed/sahaba/400/300',
                uploaded: '1 month ago',
                tags: ['video', 'documentary', 'sahaba']
            },
            {
                id: 8,
                name: 'Medina Mosque Interior',
                filename: 'medina-mosque.jpg',
                type: 'image',
                size: '4.1 MB',
                dimensions: '3840x2160',
                thumbnail: 'https://picsum.photos/seed/medina/400/300',
                uploaded: '1 month ago',
                tags: ['medina', 'mosque', 'holy']
            }
        ],

        // Initialize
        init() {
            this.totalItems = this.mediaItems.length;
            setTimeout(() => lucide.createIcons(), 100);
        },

        // Computed filtered media
        get filteredMedia() {
            let filtered = this.mediaItems;

            // Apply search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(item =>
                    item.name.toLowerCase().includes(query) ||
                    item.filename.toLowerCase().includes(query) ||
                    item.tags.some(tag => tag.toLowerCase().includes(query))
                );
            }

            // Apply type filters (from sidebar)
            if (this.activeFilters.length > 0) {
                filtered = filtered.filter(item =>
                    this.activeFilters.includes(item.type)
                );
            }

            this.totalItems = filtered.length;
            return filtered.slice(
                (this.currentPage - 1) * this.perPage,
                this.currentPage * this.perPage
            );
        },

        // Pagination
        get totalPages() {
            return Math.ceil(this.totalItems / this.perPage);
        },

        goToPage(page) {
            this.currentPage = page;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        // Search
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.currentPage = 1;
            }, 300);
        },

        // Selection
        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedItems = [];
            } else {
                this.selectedItems = this.filteredMedia.map(item => item.id);
            }
            this.selectAll = !this.selectAll;
        },

        clearSelection() {
            this.selectedItems = [];
            this.selectAll = false;
        },

        // Bulk actions
        bulkDownload() {
            alert(`Downloading ${this.selectedItems.length} files...`);
            // Implement download logic
        },

        bulkTag() {
            const tags = prompt('Enter tags (comma-separated):');
            if (tags) {
                alert(`Adding tags to ${this.selectedItems.length} files: ${tags}`);
                // Implement tagging logic
            }
        },

        confirmBulkDelete() {
            if (confirm(`Are you sure you want to delete ${this.selectedItems.length} files?`)) {
                alert(`Deleting ${this.selectedItems.length} files...`);
                // Implement delete logic
                this.clearSelection();
            }
        },

        // Single item actions
        viewMedia(item) {
            alert(`Viewing: ${item.name}`);
            // Implement view modal
        },

        editMedia(item) {
            alert(`Editing: ${item.name}`);
            // Navigate to edit page or open modal
        },

        deleteMedia(item) {
            if (confirm(`Are you sure you want to delete "${item.name}"?`)) {
                alert(`Deleting: ${item.name}`);
                // Implement delete logic
            }
        },

        // Helpers
        getTypeBadgeClass(type) {
            const classes = {
                'image': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'video': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                'audio': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'document': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
            };
            return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
        },

        // Handle upload success
        handleUploadSuccess(data) {
            console.log('Upload success:', data);

            // Add new media to the list if response contains media data
            if (data.response && data.response.media) {
                const newMedia = {
                    id: data.response.media.id || Date.now(),
                    name: data.response.media.name || data.file.name,
                    filename: data.file.name,
                    type: data.file.type.split('/')[0],
                    size: formatBytes(data.file.size),
                    dimensions: data.response.media.dimensions || null,
                    thumbnail: data.response.media.thumbnail || data.file.thumbnail || '/images/placeholder.png',
                    uploaded: 'Just now',
                    tags: data.response.media.tags || []
                };

                this.mediaItems.unshift(newMedia);
                this.totalItems = this.mediaItems.length;
            }

            // Refresh icons
            this.$nextTick(() => lucide.createIcons());
        }
    };
}
</script>
@endpush
