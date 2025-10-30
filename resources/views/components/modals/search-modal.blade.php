{{--
    Search Modal Component

    Usage:
    <x-modals.search-modal />
--}}

<div x-show="searchOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     @click.self="searchOpen = false"
     @keydown.escape.window="searchOpen = false"
     class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-start justify-center pt-20 px-4"
     style="display: none;">

    <div class="glass rounded-2xl shadow-2xl w-full max-w-3xl animate-slide-in-right">
        {{-- Search Input --}}
        <div class="p-6">
            <div class="relative">
                <input type="search"
                       x-model="searchQuery"
                       @input="performSearch"
                       :placeholder="'{{ __('site.search_placeholder') }}'"
                       autofocus
                       class="w-full bg-black/30 border-2 border-gray-700 focus:border-[#0056b3] rounded-xl py-4 ps-14 pe-4 text-white text-lg focus:outline-none transition-colors placeholder-gray-500">

                <i class="absolute start-5 top-1/2 -translate-y-1/2 w-6 h-6 text-gray-400"
                   data-lucide="search"></i>

                <button @click="searchOpen = false"
                        class="absolute end-4 top-1/2 -translate-y-1/2 p-2 hover:bg-white/10 rounded-lg transition"
                        aria-label="{{ __('site.close') }}">
                    <i class="w-5 h-5 text-gray-400" data-lucide="x"></i>
                </button>
            </div>

            {{-- Quick Filters --}}
            <div class="flex flex-wrap gap-2 mt-4">
                <button @click="searchFilter = 'all'"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition"
                        :class="searchFilter === 'all' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">
                    {{ __('site.all') }}
                </button>
                <button @click="searchFilter = 'articles'"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition"
                        :class="searchFilter === 'articles' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">
                    {{ __('site.articles') }}
                </button>
                <button @click="searchFilter = 'hadith'"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition"
                        :class="searchFilter === 'hadith' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">
                    {{ __('site.hadith') }}
                </button>
                <button @click="searchFilter = 'media'"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition"
                        :class="searchFilter === 'media' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">
                    {{ __('site.media') }}
                </button>
            </div>
        </div>

        {{-- Results --}}
        <div class="border-t border-gray-800 p-6 max-h-96 overflow-y-auto custom-scrollbar">
            {{-- Recent Searches --}}
            <div x-show="searchQuery === '' && recentSearches.length > 0">
                <p class="text-sm text-gray-400 mb-4 flex items-center gap-2">
                    <i class="w-4 h-4" data-lucide="clock"></i>
                    {{ __('site.recent_searches') }}
                </p>
                <div class="space-y-2">
                    <template x-for="search in recentSearches" :key="search">
                        <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 cursor-pointer transition"
                             @click="searchQuery = search; performSearch()">
                            <i class="w-5 h-5 text-gray-500" data-lucide="search"></i>
                            <span class="text-white flex-1" x-text="search"></span>
                            <button @click.stop="removeRecentSearch(search)"
                                    class="p-1 hover:bg-white/10 rounded transition">
                                <i class="w-4 h-4 text-gray-500 hover:text-white" data-lucide="x"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Search Results --}}
            <div x-show="searchQuery !== '' && searchResults.length > 0">
                <p class="text-sm text-gray-400 mb-4">
                    <span x-text="searchResults.length"></span> {{ __('site.results_found') }}
                </p>
                <div class="space-y-3">
                    <template x-for="result in searchResults" :key="result.id">
                        <a :href="result.url"
                           class="block p-4 rounded-lg hover:bg-white/5 transition group">
                            <div class="flex items-start gap-3">
                                {{-- Icon --}}
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i class="w-5 h-5 text-white" :data-lucide="result.icon"></i>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-white font-semibold mb-1 group-hover:text-[#64CCC5] transition line-clamp-1"
                                        x-text="result.title"></h4>
                                    <p class="text-sm text-gray-400 line-clamp-2 mb-2"
                                       x-text="result.excerpt"></p>
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <span class="px-2 py-0.5 rounded bg-gray-800" x-text="result.type"></span>
                                        <span x-text="result.date"></span>
                                    </div>
                                </div>

                                {{-- Arrow --}}
                                <i class="w-5 h-5 text-gray-600 group-hover:text-[#64CCC5] transition flex-shrink-0"
                                   data-lucide="arrow-right"></i>
                            </div>
                        </a>
                    </template>
                </div>
            </div>

            {{-- No Results --}}
            <div x-show="searchQuery !== '' && searchResults.length === 0">
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="w-8 h-8 text-gray-600" data-lucide="search-x"></i>
                    </div>
                    <p class="text-gray-400 mb-2">{{ __('site.no_results_found') }}</p>
                    <p class="text-sm text-gray-500">{{ __('site.try_different_keywords') }}</p>
                </div>
            </div>

            {{-- Quick Links (when empty) --}}
            <div x-show="searchQuery === '' && recentSearches.length === 0">
                <p class="text-sm text-gray-400 mb-4 flex items-center gap-2">
                    <i class="w-4 h-4" data-lucide="compass"></i>
                    {{ __('site.quick_links') }}
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('seerah.index') }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 transition group">
                        <i class="w-5 h-5 text-[#0056b3]" data-lucide="book-open"></i>
                        <span class="text-white group-hover:text-[#64CCC5]">{{ __('site.seerah') }}</span>
                    </a>
                    <a href="{{ route('hadith.index') }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 transition group">
                        <i class="w-5 h-5 text-[#0056b3]" data-lucide="scroll-text"></i>
                        <span class="text-white group-hover:text-[#64CCC5]">{{ __('site.hadith') }}</span>
                    </a>
                    <a href="{{ route('media.index') }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 transition group">
                        <i class="w-5 h-5 text-[#0056b3]" data-lucide="play-circle"></i>
                        <span class="text-white group-hover:text-[#64CCC5]">{{ __('site.media') }}</span>
                    </a>
                    <a href="{{ route('quran.index') }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 transition group">
                        <i class="w-5 h-5 text-[#0056b3]" data-lucide="book"></i>
                        <span class="text-white group-hover:text-[#64CCC5]">{{ __('site.quran') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Search functionality
    document.addEventListener('alpine:init', () => {
        Alpine.data('search', () => ({
            searchOpen: false,
            searchQuery: '',
            searchFilter: 'all',
            searchResults: [],
            recentSearches: JSON.parse(localStorage.getItem('recentSearches') || '[]'),

            async performSearch() {
                if (this.searchQuery.length < 2) {
                    this.searchResults = [];
                    return;
                }

                // Add to recent searches
                if (!this.recentSearches.includes(this.searchQuery)) {
                    this.recentSearches.unshift(this.searchQuery);
                    this.recentSearches = this.recentSearches.slice(0, 5);
                    localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
                }

                // Perform search via API
                try {
                    const response = await fetch(`/api/search?q=${encodeURIComponent(this.searchQuery)}&filter=${this.searchFilter}`);
                    const data = await response.json();
                    this.searchResults = data.results;
                } catch (error) {
                    console.error('Search error:', error);
                }
            },

            removeRecentSearch(search) {
                this.recentSearches = this.recentSearches.filter(s => s !== search);
                localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
            }
        }));
    });
</script>
@endpush
