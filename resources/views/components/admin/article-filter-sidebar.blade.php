{{--
/**
 * Article Filter Sidebar Component
 *
 * Advanced filtering sidebar for articles with AJAX category/author search (max 20),
 * localized status filters, date range picker, and feature toggles.
 *
 * @props
 * - statuses: array (optional) - Available status keys
 */
--}}

@props([
    'statuses' => ['published', 'pending', 'draft', 'scheduled', 'archived']
])

@php
    $categorySearchUrl = route('admin.categories.search');
    $authorSearchUrl   = route('admin.users.search');
@endphp

<div x-data="articleFilters()"
     x-show="filtersOpen"
     @toggle-filters.window="filtersOpen = !filtersOpen"
     @click.away="filtersOpen = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-full"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-full"
     class="fixed right-0 top-0 h-full w-full sm:w-96 z-50 shadow-2xl overflow-y-auto custom-scrollbar"
     :class="darkMode ? 'bg-gray-900/95 border-l border-gray-800' : 'bg-white/95 border-l border-gray-200'"
     style="display:none;"
     x-cloak>

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
                        {{ __('admin.advanced_filters') }}
                    </h3>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <span x-text="activeFiltersCount"></span> {{ __('admin.filters_active') }}
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
                {{ __('admin.clear_all') }}
            </button>
        </div>
    </div>

    <!-- Filter Sections -->
    <div class="p-6 space-y-6">

        <!-- ── Category Filter (AJAX Search, max 20) ── -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="folder" class="w-4 h-4 inline-block me-1"></i>
                    {{ __('articles.fields.category') }}
                </label>
                <button x-show="filters.category_id"
                        @click="filters.category_id = null; selectedCategoryName = ''; applyFilters()"
                        class="text-xs text-accent hover:underline">
                    {{ __('admin.clear') }}
                </button>
            </div>

            <!-- Selected category display -->
            <div x-show="selectedCategoryName"
                 class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium
                        bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                <i data-lucide="check" class="w-4 h-4 flex-shrink-0"></i>
                <span x-text="selectedCategoryName" class="truncate"></span>
            </div>

            <!-- Search Input -->
            <div class="relative">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                <input type="text"
                       x-model="categorySearch"
                       @input.debounce.400ms="fetchCategories(categorySearch)"
                       @focus="if(!categoryResults.length) fetchCategories('')"
                       placeholder="{{ __('articles.placeholders.search_category') }}"
                       class="w-full ps-9 pe-3 py-2 rounded-lg border text-sm transition-all focus:ring-2 focus:ring-accent/50"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                <div x-show="categoryLoading" class="absolute end-3 top-1/2 -translate-y-1/2">
                    <div class="w-4 h-4 border-2 border-accent border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

            <!-- Results -->
            <div class="space-y-1 max-h-52 overflow-y-auto custom-scrollbar rounded-lg p-2"
                 :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'"
                 x-show="categoryResults.length > 0">
                <template x-for="cat in categoryResults" :key="cat.id">
                    <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                           :class="filters.category_id == cat.id
                               ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                               : (darkMode ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-100 text-gray-700')">
                        <input type="radio"
                               name="category_filter"
                               :value="cat.id"
                               x-model="filters.category_id"
                               @change="selectedCategoryName = cat.name; applyFilters()"
                               class="form-radio text-accent focus:ring-accent flex-shrink-0">
                        <span class="text-sm font-medium truncate" x-text="cat.name"></span>
                    </label>
                </template>
            </div>

            <p x-show="!categoryLoading && categoryResults.length === 0 && categorySearch.length > 0"
               class="text-xs text-center py-2"
               :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                {{ __('admin.no_results') }}
            </p>
        </div>

        <!-- ── Status Filter (Localized) ── -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="activity" class="w-4 h-4 inline-block me-1"></i>
                    {{ __('articles.fields.status') }}
                </label>
                <button x-show="filters.status.length > 0"
                        @click="filters.status = []; applyFilters()"
                        class="text-xs text-accent hover:underline">
                    {{ __('admin.clear') }}
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
                        <span class="text-sm font-medium">{{ __('articles.status.' . $status) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- ── Author Filter (AJAX Search, max 20) ── -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="user" class="w-4 h-4 inline-block me-1"></i>
                    {{ __('articles.fields.author') }}
                </label>
                <button x-show="filters.author_id"
                        @click="filters.author_id = null; selectedAuthorName = ''; applyFilters()"
                        class="text-xs text-accent hover:underline">
                    {{ __('admin.clear') }}
                </button>
            </div>

            <!-- Selected author display -->
            <div x-show="selectedAuthorName"
                 class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium
                        bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                <i data-lucide="check" class="w-4 h-4 flex-shrink-0"></i>
                <span x-text="selectedAuthorName" class="truncate"></span>
            </div>

            <!-- Search Input -->
            <div class="relative">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                <input type="text"
                       x-model="authorSearch"
                       @input.debounce.400ms="fetchAuthors(authorSearch)"
                       @focus="if(!authorResults.length) fetchAuthors('')"
                       placeholder="{{ __('articles.placeholders.search_writer') }}"
                       class="w-full ps-9 pe-3 py-2 rounded-lg border text-sm transition-all focus:ring-2 focus:ring-accent/50"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                <div x-show="authorLoading" class="absolute end-3 top-1/2 -translate-y-1/2">
                    <div class="w-4 h-4 border-2 border-accent border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

            <!-- Results -->
            <div class="space-y-1 max-h-48 overflow-y-auto custom-scrollbar rounded-lg p-2"
                 :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'"
                 x-show="authorResults.length > 0">
                <template x-for="author in authorResults" :key="author.id">
                    <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                           :class="filters.author_id == author.id
                               ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                               : (darkMode ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-100 text-gray-700')">
                        <input type="radio"
                               name="author_filter"
                               :value="author.id"
                               x-model="filters.author_id"
                               @change="selectedAuthorName = author.name; applyFilters()"
                               class="form-radio text-accent focus:ring-accent flex-shrink-0">
                        <span class="text-sm font-medium truncate" x-text="author.name"></span>
                    </label>
                </template>
            </div>

            <p x-show="!authorLoading && authorResults.length === 0 && authorSearch.length > 0"
               class="text-xs text-center py-2"
               :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                {{ __('admin.no_results') }}
            </p>
        </div>

        <!-- ── Date Range Filter ── -->
        <div class="space-y-3">
            <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="calendar" class="w-4 h-4 inline-block me-1"></i>
                {{ __('admin.date_range') }}
            </label>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs mb-1 block" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('admin.from') }}
                    </label>
                    <input type="date"
                           x-model="filters.date_from"
                           @change="applyFilters"
                           class="w-full px-3 py-2 rounded-lg border text-sm transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
                <div>
                    <label class="text-xs mb-1 block" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('admin.to') }}
                    </label>
                    <input type="date"
                           x-model="filters.date_to"
                           @change="applyFilters"
                           class="w-full px-3 py-2 rounded-lg border text-sm transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
            </div>
        </div>

        <!-- ── Feature Toggles ── -->
        <div class="space-y-3">
            <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="star" class="w-4 h-4 inline-block me-1"></i>
                {{ __('admin.special_filters') }}
            </label>

            <div class="space-y-2">
                <!-- Is Featured -->
                <label class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
                       :class="filters.is_featured ?
                           'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' :
                           (darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-100 hover:bg-gray-200')">
                    <div class="flex items-center gap-3">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        <span class="text-sm font-medium">{{ __('admin.featured_only') }}</span>
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
                        <span class="text-sm font-medium">{{ __('admin.pinned_only') }}</span>
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
                {{ __('admin.reset_all') }}
            </button>
            <button @click="applyFilters(); filtersOpen = false;"
                    class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105
                           bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                {{ __('admin.apply_filters') }}
            </button>
        </div>
    </div>

</div>

<script>
function articleFilters() {
    const CATEGORY_SEARCH_URL = '{{ $categorySearchUrl }}';
    const AUTHOR_SEARCH_URL   = '{{ $authorSearchUrl }}';

    return {
        filtersOpen: false,

        // Filter values
        filters: {
            category_id: null,
            status: [],
            author_id: null,
            date_from: '',
            date_to: '',
            is_featured: false,
            is_pinned: false,
        },

        // Category search
        categorySearch: '',
        categoryResults: [],
        categoryLoading: false,
        selectedCategoryName: '',

        // Author search
        authorSearch: '',
        authorResults: [],
        authorLoading: false,
        selectedAuthorName: '',

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

        async fetchCategories(query) {
            this.categoryLoading = true;
            try {
                const url = CATEGORY_SEARCH_URL + '?q=' + encodeURIComponent(query) + '&limit=20';
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const json = await res.json();
                const items = Array.isArray(json) ? json : (json.data || json.items || []);
                this.categoryResults = items.slice(0, 20).map(c => ({
                    id: c.id,
                    name: c.name || c.title || c.slug || String(c.id),
                }));
            } catch (e) {
                this.categoryResults = [];
            }
            this.categoryLoading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async fetchAuthors(query) {
            this.authorLoading = true;
            try {
                const url = AUTHOR_SEARCH_URL + '?q=' + encodeURIComponent(query) + '&limit=20';
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const json = await res.json();
                const items = Array.isArray(json) ? json : (json.data || json.items || []);
                this.authorResults = items.slice(0, 20).map(u => ({
                    id: u.id,
                    name: u.name || u.email || String(u.id),
                }));
            } catch (e) {
                this.authorResults = [];
            }
            this.authorLoading = false;
            this.$nextTick(() => lucide.createIcons());
        },

        applyFilters() {
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
                is_pinned: false,
            };
            this.selectedCategoryName = '';
            this.selectedAuthorName   = '';
            this.categorySearch = '';
            this.authorSearch   = '';
            this.categoryResults = [];
            this.authorResults   = [];
            this.applyFilters();
        },
    };
}
</script>
