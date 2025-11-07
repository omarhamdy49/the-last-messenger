{{--
    Media Filter Sidebar Component - Advanced filtering for media library

    Usage:
    <x-admin.media-filter />

    Integrated with Alpine.js parent component for reactive filtering
--}}

<aside x-show="showFilters"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 -translate-x-full"
       x-transition:enter-end="opacity-100 translate-x-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100 translate-x-0"
       x-transition:leave-end="opacity-0 -translate-x-full"
       class="w-full lg:w-72 shrink-0">

    <div class="rounded-2xl shadow-lg p-6 sticky top-6 animate-fadeInUp"
         :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
                <div class="p-2 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838]">
                    <i data-lucide="filter" class="w-5 h-5 text-white"></i>
                </div>
                <h3 class="text-lg font-bold"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Filters
                </h3>
            </div>

            <!-- Close Button (Mobile) -->
            <button @click="showFilters = false"
                    class="lg:hidden p-2 rounded-lg transition-colors"
                    :class="darkMode ? 'text-gray-400 hover:text-white hover:bg-gray-800' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- File Type Filter -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold mb-3"
                :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                File Type
            </h4>

            <div class="space-y-2">
                <!-- All Types -->
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="activeFilters.length === 0 ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                    <input type="radio"
                           name="fileType"
                           value="all"
                           @change="activeFilters = []"
                           :checked="activeFilters.length === 0"
                           class="text-[#0056b3] focus:ring-[#64CCC5]">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                    <span class="flex-1 font-medium">All Types</span>
                    <span class="text-sm opacity-75"
                          x-text="mediaItems.length"></span>
                </label>

                <!-- Images -->
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="activeFilters.includes('image') ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                    <input type="checkbox"
                           value="image"
                           @change="toggleFilter('image')"
                           :checked="activeFilters.includes('image')"
                           class="rounded text-blue-500 focus:ring-blue-400">
                    <i data-lucide="image" class="w-5 h-5"></i>
                    <span class="flex-1 font-medium">Images</span>
                    <span class="text-sm opacity-75"
                          x-text="mediaItems.filter(item => item.type === 'image').length"></span>
                </label>

                <!-- Videos -->
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="activeFilters.includes('video') ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                    <input type="checkbox"
                           value="video"
                           @change="toggleFilter('video')"
                           :checked="activeFilters.includes('video')"
                           class="rounded text-purple-500 focus:ring-purple-400">
                    <i data-lucide="video" class="w-5 h-5"></i>
                    <span class="flex-1 font-medium">Videos</span>
                    <span class="text-sm opacity-75"
                          x-text="mediaItems.filter(item => item.type === 'video').length"></span>
                </label>

                <!-- Audio -->
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="activeFilters.includes('audio') ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                    <input type="checkbox"
                           value="audio"
                           @change="toggleFilter('audio')"
                           :checked="activeFilters.includes('audio')"
                           class="rounded text-green-500 focus:ring-green-400">
                    <i data-lucide="music" class="w-5 h-5"></i>
                    <span class="flex-1 font-medium">Audio</span>
                    <span class="text-sm opacity-75"
                          x-text="mediaItems.filter(item => item.type === 'audio').length"></span>
                </label>

                <!-- Documents -->
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="activeFilters.includes('document') ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                    <input type="checkbox"
                           value="document"
                           @change="toggleFilter('document')"
                           :checked="activeFilters.includes('document')"
                           class="rounded text-orange-500 focus:ring-orange-400">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                    <span class="flex-1 font-medium">Documents</span>
                    <span class="text-sm opacity-75"
                          x-text="mediaItems.filter(item => item.type === 'document').length"></span>
                </label>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold mb-3"
                :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                Upload Date
            </h4>

            <div class="space-y-2">
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="radio"
                           name="dateRange"
                           value="any"
                           checked
                           class="text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Any time</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="radio"
                           name="dateRange"
                           value="today"
                           class="text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Today</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="radio"
                           name="dateRange"
                           value="week"
                           class="text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Past week</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="radio"
                           name="dateRange"
                           value="month"
                           class="text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Past month</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="radio"
                           name="dateRange"
                           value="custom"
                           class="text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Custom range</span>
                </label>
            </div>

            <!-- Custom Date Inputs (shown when custom is selected) -->
            <div x-show="false"
                 class="mt-3 space-y-2">
                <input type="date"
                       class="w-full px-3 py-2 rounded-lg border text-sm"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                       placeholder="Start date">
                <input type="date"
                       class="w-full px-3 py-2 rounded-lg border text-sm"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                       placeholder="End date">
            </div>
        </div>

        <!-- Size Filter -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold mb-3"
                :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                File Size
            </h4>

            <div class="space-y-2">
                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="checkbox"
                           value="small"
                           class="rounded text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Small</span>
                    <span class="text-xs opacity-75">(&lt; 1 MB)</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="checkbox"
                           value="medium"
                           class="rounded text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Medium</span>
                    <span class="text-xs opacity-75">(1-10 MB)</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="checkbox"
                           value="large"
                           class="rounded text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">Large</span>
                    <span class="text-xs opacity-75">(10-100 MB)</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                       :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
                    <input type="checkbox"
                           value="xlarge"
                           class="rounded text-[#0056b3] focus:ring-[#64CCC5]">
                    <span class="font-medium">X-Large</span>
                    <span class="text-xs opacity-75">(&gt; 100 MB)</span>
                </label>
            </div>
        </div>

        <!-- Tags Filter -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold mb-3"
                :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                Popular Tags
            </h4>

            <div class="flex flex-wrap gap-2">
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    seerah
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    hadith
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    quran
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    sahaba
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    battle
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    mecca
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    medina
                </button>
                <button class="px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    prophets
                </button>
            </div>
        </div>

        <!-- Clear Filters Button -->
        <button @click="clearAllFilters"
                class="w-full py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-200 hover:shadow-lg"
                :class="activeFilters.length > 0 || searchQuery ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105' : (darkMode ? 'bg-gray-800 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-400 cursor-not-allowed')"
                :disabled="activeFilters.length === 0 && !searchQuery">
            <i data-lucide="x-circle" class="w-4 h-4 inline me-2"></i>
            Clear All Filters
        </button>

        <!-- Storage Info -->
        <div class="mt-6 p-4 rounded-lg glass"
             :class="darkMode ? 'glass-dark' : 'glass-light'">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="hard-drive" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                <span class="text-sm font-medium"
                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    Storage Used
                </span>
            </div>
            <div class="text-2xl font-bold mb-2 gradient-text">
                78.5 GB
            </div>
            <div class="w-full h-2 rounded-full overflow-hidden mb-2"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] rounded-full"
                     style="width: 78.5%"></div>
            </div>
            <p class="text-xs"
               :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                21.5 GB available of 100 GB
            </p>
        </div>
    </div>
</aside>

@once
@push('scripts')
<script>
    // Add toggle filter method to parent component
    document.addEventListener('alpine:init', () => {
        Alpine.data('mediaLibrary', (base) => ({
            ...base,

            toggleFilter(type) {
                const index = this.activeFilters.indexOf(type);
                if (index > -1) {
                    this.activeFilters.splice(index, 1);
                } else {
                    this.activeFilters.push(type);
                }
                this.currentPage = 1;
            },

            clearAllFilters() {
                this.activeFilters = [];
                this.searchQuery = '';
                this.currentPage = 1;
            }
        }));
    });
</script>
@endpush
@endonce
