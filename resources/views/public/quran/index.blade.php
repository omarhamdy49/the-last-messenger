@extends('layouts.public')

@section('title', __('quran.public.page_title'))
@section('meta_description', __('quran.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('quran.public.page_title') }}",
    "description": "{{ __('quran.public.page_description') }}",
    "url": "{{ route('public.quran') }}",
    "inLanguage": "ar"
}
</script>
@endpush

@push('head')
<style>
    [x-cloak] { display: none !important; }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #0056b3, #218838);
      border-radius: 10px;
    }

    /* Glassmorphism */
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    .fade-in-up {
      animation: fadeInUp 0.6s ease-out;
    }

    .stagger-1 { animation-delay: 0.1s; opacity: 0; animation-fill-mode: forwards; }
    .stagger-2 { animation-delay: 0.2s; opacity: 0; animation-fill-mode: forwards; }
    .stagger-3 { animation-delay: 0.3s; opacity: 0; animation-fill-mode: forwards; }

    /* Surah Card Hover */
    .surah-card {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .surah-card:hover {
      transform: translateY(-8px);
    }

    .surah-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(to bottom, #0056b3, #218838, #64CCC5);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .surah-card:hover::before {
      opacity: 1;
    }

    /* Progress Ring */
    .progress-ring {
      transform: rotate(-90deg);
    }

    /* Arabic Font for Numbers */
    .arabic-number {
      font-family: 'Amiri', serif;
      font-size: 1.25rem;
    }
</style>
@endpush

@section('content')

@php
    $surahsJson = json_encode($surahs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $statsJson  = json_encode($stats,  JSON_UNESCAPED_UNICODE);
@endphp

<div
    x-data="{
        searchQuery: '',
        suggest: { items: [], open: false, loading: false, error: false, active: -1, timer: null },
        activeFilter: 'all',
        viewMode: localStorage.getItem('quranViewMode') || 'grid',
        sortBy: localStorage.getItem('quranSortBy') || 'number',
        favorites: JSON.parse(localStorage.getItem('favoriteSurahs') || '[]'),
        progress: JSON.parse(localStorage.getItem('surahProgress') || '{}'),
        surahs: {{ $surahsJson }},
        stats: {{ $statsJson }},

        get filteredSurahs() {
            let filtered = this.surahs;
            if (this.activeFilter !== 'all') {
                filtered = filtered.filter(s => s.type === this.activeFilter);
            }
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                filtered = filtered.filter(s =>
                    s.nameAr.includes(this.searchQuery) ||
                    s.nameEn.toLowerCase().includes(q) ||
                    String(s.number).includes(q)
                );
            }
            if (this.sortBy === 'number') {
                filtered = [...filtered].sort((a, b) => a.number - b.number);
            } else if (this.sortBy === 'verses') {
                filtered = [...filtered].sort((a, b) => b.verses - a.verses);
            } else if (this.sortBy === 'name') {
                filtered = [...filtered].sort((a, b) => a.nameAr.localeCompare(b.nameAr, 'ar'));
            }
            return filtered;
        },

        get makkiCount()  { return this.surahs.filter(s => s.type === 'makki').length; },
        get madaniCount() { return this.surahs.filter(s => s.type === 'madani').length; },

        get progressPercent() {
            const done = Object.keys(this.progress).length;
            return Math.round((done / this.surahs.length) * 100);
        },

        setFilter(f)  { this.activeFilter = f; },
        setSort(s)    { this.sortBy = s; localStorage.setItem('quranSortBy', s); },
        setViewMode(m){ this.viewMode = m; localStorage.setItem('quranViewMode', m); },

        toggleFavorite(n) {
            const i = this.favorites.indexOf(n);
            if (i > -1) { this.favorites.splice(i, 1); }
            else { this.favorites.push(n); }
            localStorage.setItem('favoriteSurahs', JSON.stringify(this.favorites));
        },
        isFavorite(n) { return this.favorites.includes(n); },
        getProgress(n){ return this.progress[n] || 0; },

        onSearchInput() {
            clearTimeout(this.suggest.timer);
            const q = this.searchQuery.trim();
            if (q.length < 1) { this.suggest.items = []; this.suggest.open = false; this.suggest.error = false; return; }
            this.suggest.timer = setTimeout(() => this.fetchSuggest(q), 250);
        },
        async fetchSuggest(q) {
            this.suggest.loading = true; this.suggest.error = false; this.suggest.open = true; this.suggest.active = -1;
            try {
                const loc = document.documentElement.lang || 'ar';
                const res = await fetch('/api/v1/quran/search/suggest?q=' + encodeURIComponent(q) + '&limit=8&locale=' + encodeURIComponent(loc), { headers: { Accept: 'application/json' } });
                if (!res.ok) { throw new Error('status ' + res.status); }
                const json = await res.json();
                this.suggest.items = (json.data || []).filter(s => ['surah', 'verse', 'juz', 'hizb', 'page'].includes(s.type));
            } catch (e) {
                this.suggest.error = true; this.suggest.items = [];
            } finally {
                this.suggest.loading = false;
                this.$nextTick(() => { if (window.lucide) { window.lucide.createIcons(); } });
            }
        },
        gotoSuggestion(s) {
            if (!s) { return; }
            let url = '/quran/surah/' + s.surah_number;
            if (s.type === 'juz') { url = '/quran/juz/' + s.number; }
            else if (s.type === 'hizb') { url = '/quran/hizb/' + s.number; }
            else if (s.type === 'page') { url = '/quran/page/' + s.number; }
            window.location.href = url;
        },
        suggestKeydown(e) {
            if (!this.suggest.open) { return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); this.suggest.active = Math.min(this.suggest.active + 1, this.suggest.items.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.suggest.active = Math.max(this.suggest.active - 1, 0); }
            else if (e.key === 'Enter' && this.suggest.active >= 0) { e.preventDefault(); this.gotoSuggestion(this.suggest.items[this.suggest.active]); }
            else if (e.key === 'Escape') { this.suggest.open = false; }
        },
    }"
    :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'"
    class="min-h-screen font-['Cairo'] transition-colors duration-300"
>

    {{-- ============================================================ --}}
    {{-- HERO --}}
    {{-- ============================================================ --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="relative rounded-3xl overflow-hidden mb-12 fade-in-up"
             :class="$store.theme.dark
                 ? 'bg-gradient-to-r from-gray-900 to-gray-800'
                 : 'bg-gradient-to-r from-blue-600 to-green-600'">
            {{-- Grid pattern --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="quran-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#quran-grid)"/>
                </svg>
            </div>

            <div class="relative px-6 py-12 md:px-12 md:py-16 text-center text-white">
                {{-- Icon --}}
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i data-lucide="book-open" class="w-10 h-10 text-white"></i>
                    </div>
                </div>

                <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ __('quran.public.hero_title') }}</h1>
                <p class="text-xl mb-8 opacity-90">{{ __('quran.public.hero_subtitle') }}</p>

                {{-- Statistics --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ $stats['total'] }}</div>
                        <div class="text-sm opacity-80">{{ __('quran.public.stat_surahs') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ number_format($stats['verses']) }}</div>
                        <div class="text-sm opacity-80">{{ __('quran.public.stat_verses') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ $stats['makki'] }}</div>
                        <div class="text-sm opacity-80">{{ __('quran.public.stat_makki') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ $stats['madani'] }}</div>
                        <div class="text-sm opacity-80">{{ __('quran.public.stat_madani') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- CONTROLS BAR --}}
        {{-- ============================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8 stagger-1 fade-in-up">

            {{-- Search --}}
            <div class="lg:col-span-5">
                <div class="relative" @click.outside="suggest.open = false" @keydown="suggestKeydown($event)">
                    <i data-lucide="search" class="absolute end-4 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none"
                       :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'"></i>
                    <input
                        type="search"
                        x-model="searchQuery"
                        @input="onSearchInput()"
                        @focus="if (suggest.items.length || suggest.error) { suggest.open = true; }"
                        autocomplete="off"
                        role="combobox"
                        :aria-expanded="suggest.open"
                        aria-controls="quran-suggest-list"
                        placeholder="{{ __('quran.public.search_placeholder') }}"
                        class="w-full pe-12 ps-4 py-3 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
                        :class="$store.theme.dark
                            ? 'bg-gray-900 border-gray-800 text-white'
                            : 'bg-white border-gray-200 text-gray-900'"
                        aria-label="{{ __('quran.public.search_placeholder') }}"
                    >

                    {{-- Navigational autocomplete — powered by /api/v1/quran/search/suggest --}}
                    <div
                        x-show="suggest.open"
                        x-transition.opacity
                        x-cloak
                        id="quran-suggest-list"
                        role="listbox"
                        class="absolute z-50 mt-2 w-full rounded-xl border-2 shadow-xl overflow-hidden max-h-80 overflow-y-auto"
                        :class="$store.theme.dark ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
                    >
                        <template x-if="suggest.loading">
                            <div class="px-4 py-3 flex items-center gap-3 text-sm"
                                 :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-600'">
                                <span class="w-4 h-4 border-2 border-[#64CCC5] border-t-transparent rounded-full animate-spin"></span>
                                <span>{{ __('quran.public.suggest_loading') }}</span>
                            </div>
                        </template>

                        <template x-if="!suggest.loading && suggest.error">
                            <button type="button" @click="fetchSuggest(searchQuery.trim())"
                                    class="w-full text-start px-4 py-3 text-sm flex items-center gap-2"
                                    :class="$store.theme.dark ? 'text-red-300 hover:bg-gray-800' : 'text-red-600 hover:bg-gray-50'">
                                <i data-lucide="rotate-cw" class="w-4 h-4 flex-shrink-0"></i>
                                <span>{{ __('quran.public.suggest_error') }}</span>
                            </button>
                        </template>

                        <template x-if="!suggest.loading && !suggest.error && suggest.items.length === 0">
                            <div class="px-4 py-3 text-sm"
                                 :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                                {{ __('quran.public.suggest_empty') }}
                            </div>
                        </template>

                        <template x-for="(s, i) in suggest.items" :key="s.type + '-' + (s.verse_key || s.surah_number)">
                            <button
                                type="button"
                                role="option"
                                :aria-selected="suggest.active === i"
                                @click="gotoSuggestion(s)"
                                @mouseenter="suggest.active = i"
                                class="w-full text-start px-4 py-3 flex items-center gap-3 transition-colors"
                                :class="suggest.active === i ? ($store.theme.dark ? 'bg-gray-800' : 'bg-gray-100') : ''"
                            >
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg flex-shrink-0 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                                    <i :data-lucide="s.type === 'verse' ? 'bookmark' : (s.type === 'juz' ? 'layers' : (s.type === 'hizb' ? 'square-stack' : (s.type === 'page' ? 'file-text' : 'book-open')))" class="w-4 h-4"></i>
                                </span>
                                <span class="truncate" x-text="s.label"
                                      :class="$store.theme.dark ? 'text-gray-100' : 'text-gray-900'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Filter pills --}}
            <div class="lg:col-span-4 flex gap-2 flex-wrap">
                <button
                    @click="setFilter('all')"
                    class="flex-1 px-4 py-3 rounded-xl font-semibold transition-all"
                    :class="activeFilter === 'all'
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : ($store.theme.dark ? 'bg-gray-900 text-gray-400 hover:text-white' : 'bg-white text-gray-600 hover:text-gray-900')"
                    aria-pressed="true"
                >
                    {{ __('quran.public.filter_all') }}
                </button>
                <button
                    @click="setFilter('makki')"
                    class="flex-1 px-4 py-3 rounded-xl font-semibold transition-all"
                    :class="activeFilter === 'makki'
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : ($store.theme.dark ? 'bg-gray-900 text-gray-400 hover:text-white' : 'bg-white text-gray-600 hover:text-gray-900')"
                >
                    {{ __('quran.public.filter_makki') }} (<span x-text="makkiCount"></span>)
                </button>
                <button
                    @click="setFilter('madani')"
                    class="flex-1 px-4 py-3 rounded-xl font-semibold transition-all"
                    :class="activeFilter === 'madani'
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : ($store.theme.dark ? 'bg-gray-900 text-gray-400 hover:text-white' : 'bg-white text-gray-600 hover:text-gray-900')"
                >
                    {{ __('quran.public.filter_madani') }} (<span x-text="madaniCount"></span>)
                </button>
            </div>

            {{-- View mode + sort --}}
            <div class="lg:col-span-3 flex gap-2 flex-wrap">
                {{-- View toggle --}}
                <div class="flex gap-1 rounded-xl p-1" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <button
                        @click="setViewMode('grid')"
                        class="p-2 rounded-lg transition-all"
                        :class="viewMode === 'grid'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="viewMode === 'grid'"
                        aria-label="{{ __('quran.public.view_grid') }}"
                    >
                        <i data-lucide="grid-3x3" class="w-5 h-5"></i>
                    </button>
                    <button
                        @click="setViewMode('list')"
                        class="p-2 rounded-lg transition-all"
                        :class="viewMode === 'list'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="viewMode === 'list'"
                        aria-label="{{ __('quran.public.view_list') }}"
                    >
                        <i data-lucide="list" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Sort --}}
                <select
                    x-model="sortBy"
                    @change="setSort(sortBy)"
                    class="flex-1 px-4 py-2 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
                    :class="$store.theme.dark
                        ? 'bg-gray-900 border-gray-800 text-white'
                        : 'bg-white border-gray-200 text-gray-900'"
                    aria-label="{{ __('quran.public.sort_label') }}"
                >
                    <option value="number">{{ __('quran.public.sort_number') }}</option>
                    <option value="name">{{ __('quran.public.sort_name') }}</option>
                    <option value="verses">{{ __('quran.public.sort_verses') }}</option>
                </select>
            </div>
        </div>

        {{-- Quick jump: Juz / Page --}}
        <div class="mb-6 flex flex-wrap items-center gap-3 stagger-2 fade-in-up">
            <select
                @change="if ($event.target.value) { window.location.href = '{{ url('/quran/juz') }}/' + $event.target.value; }"
                class="px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
                :class="$store.theme.dark ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'"
                aria-label="{{ __('quran.public.go_to_juz') }}"
            >
                <option value="">{{ __('quran.public.go_to_juz') }}</option>
                @for($j = 1; $j <= 30; $j++)
                    <option value="{{ $j }}">{{ __('quran.nav.juz') }} {{ $j }}</option>
                @endfor
            </select>

            <select
                @change="if ($event.target.value) { window.location.href = '{{ url('/quran/hizb') }}/' + $event.target.value; }"
                class="px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
                :class="$store.theme.dark ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'"
                aria-label="{{ __('quran.public.go_to_hizb') }}"
            >
                <option value="">{{ __('quran.public.go_to_hizb') }}</option>
                @for($h = 1; $h <= 60; $h++)
                    <option value="{{ $h }}">{{ __('quran.nav.hizb') }} {{ $h }}</option>
                @endfor
            </select>

            <select
                @change="if ($event.target.value) { window.location.href = '{{ url('/quran/page') }}/' + $event.target.value; }"
                class="px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
                :class="$store.theme.dark ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'"
                aria-label="{{ __('quran.public.go_to_page') }}"
            >
                <option value="">{{ __('quran.public.go_to_page') }}</option>
                @for($p = 1; $p <= 604; $p++)
                    <option value="{{ $p }}">{{ __('quran.nav.page') }} {{ $p }}</option>
                @endfor
            </select>
        </div>

        {{-- Results count + progress --}}
        <div class="mb-6 flex items-center justify-between flex-wrap gap-3 stagger-2 fade-in-up">
            <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                {{ __('site.showing') }}
                <span class="font-bold text-[#64CCC5]" x-text="filteredSurahs.length"></span>
                {{ __('site.of') }}
                <span x-text="surahs.length"></span>
                {{ __('quran.public.surah_unit') }}
            </p>

            <div class="flex items-center gap-3">
                <span class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('quran.public.overall_progress') }}:
                </span>
                <div class="flex items-center gap-2">
                    <div class="w-32 h-2 rounded-full" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-500"
                             :style="`width: ${progressPercent}%`"></div>
                    </div>
                    <span class="text-sm font-bold text-[#64CCC5]" x-text="progressPercent + '%'"></span>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- GRID VIEW --}}
        {{-- ============================================================ --}}
        <div x-show="viewMode === 'grid'"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <template x-for="(surah, index) in filteredSurahs" :key="surah.number">
                <a
                    :href="`{{ url('/quran') }}/${surah.number}`"
                    class="group relative surah-card rounded-2xl p-6 shadow-lg transition-all fade-in-up"
                    :class="[
                        $store.theme.dark ? 'bg-gray-900 hover:bg-gray-800' : 'bg-white hover:bg-gray-50',
                        'stagger-' + ((index % 3) + 1)
                    ]"
                >
                    {{-- Favorite button --}}
                    <button
                        @click.prevent="toggleFavorite(surah.number)"
                        class="absolute top-4 start-4 w-8 h-8 rounded-full flex items-center justify-center transition-all z-10"
                        :class="isFavorite(surah.number)
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')"
                        :aria-label="isFavorite(surah.number) ? '{{ __('quran.public.remove_favorite') }}' : '{{ __('quran.public.add_favorite') }}'"
                    >
                        <i data-lucide="heart" class="w-4 h-4" :class="isFavorite(surah.number) ? 'fill-current' : ''"></i>
                    </button>

                    {{-- Progress ring --}}
                    <template x-if="getProgress(surah.number) > 0">
                        <div class="absolute top-4 end-4">
                            <svg class="w-12 h-12 progress-ring" aria-hidden="true">
                                <circle cx="24" cy="24" r="20" stroke="currentColor"
                                    :class="$store.theme.dark ? 'text-gray-800' : 'text-gray-200'"
                                    stroke-width="4" fill="none"/>
                                <circle cx="24" cy="24" r="20" stroke="url(#prog-gradient)"
                                    stroke-width="4" fill="none"
                                    :stroke-dasharray="`${2 * Math.PI * 20}`"
                                    :stroke-dashoffset="`${2 * Math.PI * 20 * (1 - getProgress(surah.number) / 100)}`"/>
                                <defs>
                                    <linearGradient id="prog-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#0056b3"/>
                                        <stop offset="100%" style="stop-color:#218838"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                    </template>

                    {{-- Surah number --}}
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-2xl font-['Amiri'] group-hover:scale-110 transition-transform">
                            <span x-text="surah.number"></span>
                        </div>
                    </div>

                    {{-- Surah name --}}
                    <div class="text-center mb-3">
                        <h3 class="text-2xl font-bold mb-1 group-hover:text-[#64CCC5] transition-colors font-['Amiri']"
                            :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                            x-text="surah.nameAr"></h3>
                        <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="surah.nameEn"></p>
                    </div>

                    {{-- Meta --}}
                    <div class="flex items-center justify-between pt-4 border-t"
                         :class="$store.theme.dark ? 'border-gray-800' : 'border-gray-200'">
                        <div class="flex items-center gap-2">
                            <i data-lucide="book-open" class="w-4 h-4 text-[#64CCC5]"></i>
                            <span class="text-sm font-medium" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                                <span x-text="surah.verses"></span> {{ __('quran.public.verse_unit') }}
                            </span>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold"
                            :class="surah.type === 'makki'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                            x-text="surah.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'">
                        </span>
                    </div>

                    {{-- Juz --}}
                    <div class="mt-3 flex items-center justify-center gap-2 text-xs"
                         :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-500'">
                        <i data-lucide="layers" class="w-3 h-3"></i>
                        <span>{{ __('quran.public.juz') }} <span x-text="surah.juz"></span></span>
                    </div>
                </a>
            </template>
        </div>

        {{-- ============================================================ --}}
        {{-- LIST VIEW --}}
        {{-- ============================================================ --}}
        <div x-show="viewMode === 'list'" class="space-y-3">
            <template x-for="(surah, index) in filteredSurahs" :key="surah.number">
                <a
                    :href="`{{ url('/quran') }}/${surah.number}`"
                    class="group relative flex items-center gap-4 surah-card rounded-2xl p-4 shadow-lg transition-all fade-in-up"
                    :class="[
                        $store.theme.dark ? 'bg-gray-900 hover:bg-gray-800' : 'bg-white hover:bg-gray-50',
                        'stagger-' + ((index % 3) + 1)
                    ]"
                >
                    {{-- Number circle --}}
                    <div class="w-14 h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform font-['Amiri']">
                        <span x-text="surah.number"></span>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-3 mb-1">
                            <h3 class="text-xl font-bold group-hover:text-[#64CCC5] transition-colors font-['Amiri']"
                                :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                                x-text="surah.nameAr"></h3>
                            <span class="text-sm" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-500'" x-text="surah.nameEn"></span>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="flex items-center gap-4 sm:gap-6 text-sm flex-wrap">
                        <div class="flex items-center gap-2">
                            <i data-lucide="book-open" class="w-4 h-4 text-[#64CCC5]"></i>
                            <span :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                                <span x-text="surah.verses"></span> {{ __('quran.public.verse_unit') }}
                            </span>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold"
                            :class="surah.type === 'makki'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                            x-text="surah.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'">
                        </span>
                        <div class="flex items-center gap-1 text-xs" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-500'">
                            <i data-lucide="layers" class="w-3 h-3"></i>
                            <span>{{ __('quran.public.juz') }} <span x-text="surah.juz"></span></span>
                        </div>
                    </div>

                    {{-- Favorite --}}
                    <button
                        @click.prevent="toggleFavorite(surah.number)"
                        class="w-10 h-10 rounded-full flex items-center justify-center transition-all flex-shrink-0"
                        :class="isFavorite(surah.number)
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')"
                    >
                        <i data-lucide="heart" class="w-5 h-5" :class="isFavorite(surah.number) ? 'fill-current' : ''"></i>
                    </button>

                    {{-- Progress bar at bottom --}}
                    <template x-if="getProgress(surah.number) > 0">
                        <div class="absolute bottom-0 start-0 end-0 h-1 bg-gradient-to-r from-[#0056b3] to-[#218838] rounded-b-2xl"
                             :style="`width: ${getProgress(surah.number)}%`"></div>
                    </template>
                </a>
            </template>
        </div>

        {{-- ============================================================ --}}
        {{-- EMPTY STATE --}}
        {{-- ============================================================ --}}
        <div x-show="filteredSurahs.length === 0" x-cloak class="text-center py-20">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-6"
                 :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'">
                <i data-lucide="search-x" class="w-10 h-10" :class="$store.theme.dark ? 'text-gray-600' : 'text-gray-400'"></i>
            </div>
            <h3 class="text-2xl font-bold mb-2" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                {{ __('site.no_results') }}
            </h3>
            <p class="mb-6" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                {{ __('site.try_adjusting_filters') }}
            </p>
            <button
                @click="searchQuery = ''; activeFilter = 'all'"
                class="px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:shadow-lg transition-all"
            >
                {{ __('site.reset_filters') }}
            </button>
        </div>

    </div>{{-- /container --}}
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
