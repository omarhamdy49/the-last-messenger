{{--
/**
 * Article Filter Sidebar Component
 *
 * Advanced filtering sidebar for articles with category tree, status filters,
 * author selection, date range picker, and feature toggles.
 *
 * @props
 * - categories: Collection (required) - Categories for tree view
 * - authors: Collection (required) - List of authors
 * - statuses: array (optional) - Available statuses
 *
 * @example
 * <x-admin.article-filter-sidebar
 *     :categories="$categories"
 *     :authors="$authors"
 * />
 */
--}}

@props([
    'categories' => [],
    'authors' => [],
    'statuses' => ['published', 'draft', 'scheduled', 'archived']
])

<div x-data="articleFilters()"
     x-show="filtersOpen"
     @click.away="filtersOpen = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="transform translate-x-full"
     x-transition:enter-end="transform translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="transform translate-x-0"
     x-transition:leave-end="transform translate-x-full"
     class="fixed right-0 top-0 h-full w-full sm:w-96 z-50 glass shadow-2xl overflow-y-auto custom-scrollbar"
     :class="darkMode ? 'bg-gray-900/95 border-l border-gray-800' : 'bg-white/95 border-l border-gray-200'">

    <!-- Header -->
    <div class="sticky top-0 z-10 glass px-6 py-4 border-b"
         :class="darkMode ? 'bg-gray-900/95 border-gray-800' : 'bg-white/95 border-gray-200'">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838]">
                    <i data-lucide="filter" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        Advanced Filters
                    </h3>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="activeFiltersCount"></span> filters active
                    </p>
                </div>
            </div>
            <button @click="filtersOpen = false"
                    class="p-2 rounded-lg transition-colors hover:scale-110"
                    :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Active Filters Pills -->
        <div x-show="activeFiltersCount > 0"
             class="flex flex-wrap gap-2 mt-4">
            <button @click="clearAllFilters"
                    class="px-3 py-1 rounded-full text-xs font-semibold transition-all hover:scale-105
                           bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                <i data-lucide="x-circle" class="w-3 h-3 inline-block me-1"></i>
                Clear All
            </button>
        </div>
    </div>

    <!-- Filter Sections -->
    <div class="p-6 space-y-6">

        <!-- Category Filter (Tree View) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="folder" class="w-4 h-4 inline-block me-1"></i>
                    Category
                </label>
                <button x-show="filters.category_id"
                        @click="filters.category_id = null; applyFilters()"
                        class="text-xs text-accent hover:underline">
                    Clear
                </button>
            </div>

            <div class="space-y-1 max-h-64 overflow-y-auto custom-scrollbar rounded-lg p-3"
                 :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                @foreach($categories as $category)
                    <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors hover:bg-white/10"
                           :class="filters.category_id == {{ $category->id }} ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : ''">
                        <input type="radio"
                               name="category"
                               value="{{ $category->id }}"
                               x-model="filters.category_id"
                               @change="applyFilters"
                               class="form-radio text-accent focus:ring-accent">
                        <span class="text-sm font-medium">{{ $category->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Status Filter -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="activity" class="w-4 h-4 inline-block me-1"></i>
                    Status
                </label>
                <button x-show="filters.status.length > 0"
                        @click="filters.status = []; applyFilters()"
                        class="text-xs text-accent hover:underline">
                    Clear
                </button>
            </div>

            <div class="space-y-2">
                @foreach($statuses as $status)
                    <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-all"
                           :class="filters.status.includes('{{ $status }}') ?
                               'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                               (darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-100 hover:bg-gray-200')">
                        <input type="checkbox"
                               value="{{ $status }}"
                               x-model="filters.status"
                               @change="applyFilters"
                               class="form-checkbox rounded text-accent focus:ring-accent">
                        <span class="text-sm font-medium capitalize">{{ $status }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Author Filter -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="user" class="w-4 h-4 inline-block me-1"></i>
                    Author
                </label>
                <button x-show="filters.author_id"
                        @click="filters.author_id = null; applyFilters()"
                        class="text-xs text-accent hover:underline">
                    Clear
                </button>
            </div>

            <select x-model="filters.author_id"
                    @change="applyFilters"
                    class="w-full px-4 py-2.5 rounded-xl border-2 transition-all focus:ring-2 focus:ring-accent/50"
                    :class="darkMode ?
                        'bg-gray-800 border-gray-700 text-white' :
                        'bg-white border-gray-300 text-gray-900'">
                <option value="">All Authors</option>
                @foreach($authors as $author)
                    <option value="{{ $author->id }}">{{ $author->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Date Range Filter -->
        <div class="space-y-3">
            <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="calendar" class="w-4 h-4 inline-block me-1"></i>
                Date Range
            </label>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs mb-1 block" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        From
                    </label>
                    <input type="date"
                           x-model="filters.date_from"
                           @change="applyFilters"
                           class="w-full px-3 py-2 rounded-lg border text-sm transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ?
                               'bg-gray-800 border-gray-700 text-white' :
                               'bg-white border-gray-300 text-gray-900'">
                </div>
                <div>
                    <label class="text-xs mb-1 block" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        To
                    </label>
                    <input type="date"
                           x-model="filters.date_to"
                           @change="applyFilters"
                           class="w-full px-3 py-2 rounded-lg border text-sm transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ?
                               'bg-gray-800 border-gray-700 text-white' :
                               'bg-white border-gray-300 text-gray-900'">
                </div>
            </div>
        </div>

        <!-- Feature Toggles -->
        <div class="space-y-3">
            <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="star" class="w-4 h-4 inline-block me-1"></i>
                Special Filters
            </label>

            <div class="space-y-2">
                <!-- Is Featured -->
                <label class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
                       :class="filters.is_featured ?
                           'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                           (darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-100 hover:bg-gray-200')">
                    <div class="flex items-center gap-3">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        <span class="text-sm font-medium">Featured Only</span>
                    </div>
                    <input type="checkbox"
                           x-model="filters.is_featured"
                           @change="applyFilters"
                           class="form-checkbox rounded text-accent focus:ring-accent">
                </label>

                <!-- Is Pinned -->
                <label class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
                       :class="filters.is_pinned ?
                           'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                           (darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-100 hover:bg-gray-200')">
                    <div class="flex items-center gap-3">
                        <i data-lucide="pin" class="w-4 h-4"></i>
                        <span class="text-sm font-medium">Pinned Only</span>
                    </div>
                    <input type="checkbox"
                           x-model="filters.is_pinned"
                           @change="applyFilters"
                           class="form-checkbox rounded text-accent focus:ring-accent">
                </label>
            </div>
        </div>

    </div>

    <!-- Footer Actions -->
    <div class="sticky bottom-0 glass px-6 py-4 border-t"
         :class="darkMode ? 'bg-gray-900/95 border-gray-800' : 'bg-white/95 border-gray-200'">
        <div class="flex gap-3">
            <button @click="clearAllFilters"
                    class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105"
                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                Reset All
            </button>
            <button @click="applyFilters(); filtersOpen = false;"
                    class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105
                           bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                Apply Filters
            </button>
        </div>
    </div>

</div>

<script>
function articleFilters() {
    return {
        filtersOpen: false,
        filters: {
            category_id: null,
            status: [],
            author_id: null,
            date_from: '',
            date_to: '',
            is_featured: false,
            is_pinned: false
        },

        get activeFiltersCount() {
            let count = 0;
            if (this.filters.category_id) count++;
            if (this.filters.status.length > 0) count++;
            if (this.filters.author_id) count++;
            if (this.filters.date_from || this.filters.date_to) count++;
            if (this.filters.is_featured) count++;
            if (this.filters.is_pinned) count++;
            return count;
        },

        applyFilters() {
            // Emit event to parent component (DataTable)
            this.$dispatch('filters-changed', this.filters);
            this.$nextTick(() => lucide.createIcons());
        },

        clearAllFilters() {
            this.filters = {
                category_id: null,
                status: [],
                author_id: null,
                date_from: '',
                date_to: '',
                is_featured: false,
                is_pinned: false
            };
            this.applyFilters();
        }
    };
}
</script>
