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

    /* Premium Surah Card */
    .surah-card {
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.4s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.3s;
      box-shadow: 0 4px 18px -10px rgba(0, 0, 0, 0.28);
    }

    .surah-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 26px 50px -20px rgba(0, 86, 179, 0.42), 0 0 0 1px rgba(100, 204, 197, 0.20);
    }

    /* soft brand glow that blooms from the top on hover */
    .surah-card::after {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      border-radius: inherit;
      background: radial-gradient(130% 75% at 50% -12%, rgba(100, 204, 197, 0.12), transparent 55%);
      opacity: 0;
      transition: opacity 0.4s;
    }

    .surah-card:hover::after { opacity: 1; }

    @media (prefers-reduced-motion: reduce) {
      .surah-card, .surah-card:hover { transition: box-shadow 0.2s; transform: none; }
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

        setFilter(f)  { this.activeFilter = f; },
        setSort(s)    { this.sortBy = s; localStorage.setItem('quranSortBy', s); },
        setViewMode(m){ this.viewMode = m; localStorage.setItem('quranViewMode', m); },

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

        <div class="relative rounded-3xl overflow-hidden mb-12 fade-in-up shadow-xl"
             :class="$store.theme.dark
                 ? 'bg-gradient-to-br from-[#072a4d] via-[#0a2233] to-[#0b3a25] shadow-black/40'
                 : 'bg-gradient-to-br from-blue-600 via-[#1a73c4] to-green-600 shadow-blue-600/20'">
            {{-- Accent glow --}}
            <div class="absolute -top-24 start-1/2 -translate-x-1/2 w-[40rem] h-[40rem] rounded-full pointer-events-none blur-3xl"
                 :class="$store.theme.dark ? 'bg-[#64CCC5]/10' : 'bg-white/15'"></div>
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

            <div class="relative px-6 py-7 md:px-12 md:py-9 text-center text-white">
                {{-- Logo lockup: glyph sized to the title's height for a balanced row --}}
                <div class="flex items-center justify-center gap-3 mb-3">
                    <x-icons.quran-book class="w-12 h-12 md:w-16 md:h-16 text-white/95 shrink-0" />
                    <h1 class="text-4xl md:text-5xl font-bold leading-none">{{ __('quran.public.hero_title') }}</h1>
                </div>
                <p class="text-base md:text-lg opacity-90 max-w-xl mx-auto mb-6">{{ __('quran.public.hero_subtitle') }}</p>

                {{-- Premium CTA — AI "Ask the Quran" (replaces the inline search bar) --}}
                <div class="mb-8">
                    <a href="{{ route('public.quran.ask') }}"
                       class="group inline-flex items-center gap-3 px-7 py-4 rounded-2xl bg-white text-[#0056b3] font-bold text-base shadow-xl shadow-black/10 hover:shadow-2xl hover:-translate-y-0.5 active:scale-[0.98] transition-all">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white">
                            <i data-lucide="sparkles" class="w-5 h-5"></i>
                        </span>
                        <span>{{ __('quran.public.ask_page_title') }}</span>
                    </a>
                    <p class="mt-3 text-sm text-white/80 max-w-xl mx-auto">{{ __('quran.public.ask_hero_subtitle') }}</p>
                </div>

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
        {{-- CONTROLS TOOLBAR (premium unified panel) --}}
        {{-- ============================================================ --}}
        <div class="mb-8 stagger-1 fade-in-up rounded-2xl border p-3 sm:p-4 shadow-sm backdrop-blur-md"
             :class="$store.theme.dark ? 'bg-gray-900/60 border-white/5 shadow-black/20' : 'bg-white/70 border-gray-100 shadow-gray-200/50'">

            {{-- Row 1: search + view toggle + sort --}}
            <div class="flex flex-col lg:flex-row gap-3">

            {{-- Search --}}
            <div class="relative flex-1 min-w-0" @click.outside="suggest.open = false" @keydown="suggestKeydown($event)">
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
                        class="w-full pe-12 ps-4 py-3 text-base sm:text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#64CCC5]/60 focus:border-[#64CCC5] transition-all"
                        :class="$store.theme.dark
                            ? 'bg-gray-950 border-gray-800 text-white placeholder-gray-500'
                            : 'bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-400'"
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

                {{-- View toggle + sort --}}
                <div class="flex gap-2 flex-shrink-0">
                    {{-- View toggle --}}
                    <div class="flex gap-1 rounded-xl p-1 border" :class="$store.theme.dark ? 'bg-gray-950 border-gray-800' : 'bg-gray-50 border-gray-200'">
                        <button
                            @click="setViewMode('grid')"
                            class="p-2 rounded-lg transition-all"
                            :class="viewMode === 'grid'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md shadow-[#0056b3]/30'
                                : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900')"
                            :aria-pressed="viewMode === 'grid'"
                            aria-label="{{ __('quran.public.view_grid') }}"
                        >
                            <i data-lucide="grid-3x3" class="w-5 h-5"></i>
                        </button>
                        <button
                            @click="setViewMode('list')"
                            class="p-2 rounded-lg transition-all"
                            :class="viewMode === 'list'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md shadow-[#0056b3]/30'
                                : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900')"
                            :aria-pressed="viewMode === 'list'"
                            aria-label="{{ __('quran.public.view_list') }}"
                        >
                            <i data-lucide="list" class="w-5 h-5"></i>
                        </button>
                    </div>

                    {{-- Sort (custom chevron) --}}
                    <div class="relative flex-1 lg:flex-none lg:w-52">
                        <i data-lucide="arrow-up-down" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#64CCC5] pointer-events-none"></i>
                        <select
                            x-model="sortBy"
                            @change="setSort(sortBy)"
                            class="w-full appearance-none ps-9 pe-9 py-2.5 text-base sm:text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#64CCC5]/60 focus:border-[#64CCC5] transition-all cursor-pointer"
                            :class="$store.theme.dark
                                ? 'bg-gray-950 border-gray-800 text-white'
                                : 'bg-gray-50 border-gray-200 text-gray-900'"
                            aria-label="{{ __('quran.public.sort_label') }}"
                        >
                            <option value="number">{{ __('quran.public.sort_number') }}</option>
                            <option value="name">{{ __('quran.public.sort_name') }}</option>
                            <option value="verses">{{ __('quran.public.sort_verses') }}</option>
                        </select>
                        <i data-lucide="chevron-down" class="absolute end-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                           :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'"></i>
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div class="my-3 sm:my-4 h-px" :class="$store.theme.dark ? 'bg-white/5' : 'bg-gray-100'"></div>

            {{-- Row 2: type segment + quick jump --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

                {{-- Segmented type filter --}}
                <div class="inline-flex self-start p-1 rounded-xl border gap-1"
                     :class="$store.theme.dark ? 'bg-gray-950 border-gray-800' : 'bg-gray-50 border-gray-200'"
                     role="group" aria-label="{{ __('quran.public.sort_label') }}">
                    <button
                        @click="setFilter('all')"
                        class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                        :class="activeFilter === 'all'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md shadow-[#0056b3]/30'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="activeFilter === 'all'"
                    >
                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                        {{ __('quran.public.filter_all') }}
                    </button>
                    <button
                        @click="setFilter('makki')"
                        class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                        :class="activeFilter === 'makki'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md shadow-[#0056b3]/30'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="activeFilter === 'makki'"
                    >
                        {{ __('quran.public.filter_makki') }}
                        <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 h-5 rounded-full text-[11px] font-bold"
                              :class="activeFilter === 'makki' ? 'bg-white/25 text-white' : ($store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-600')"
                              x-text="makkiCount"></span>
                    </button>
                    <button
                        @click="setFilter('madani')"
                        class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                        :class="activeFilter === 'madani'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md shadow-[#0056b3]/30'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="activeFilter === 'madani'"
                    >
                        {{ __('quran.public.filter_madani') }}
                        <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 h-5 rounded-full text-[11px] font-bold"
                              :class="activeFilter === 'madani' ? 'bg-white/25 text-white' : ($store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-600')"
                              x-text="madaniCount"></span>
                    </button>
                </div>

                {{-- Quick jump: Juz / Hizb / Page --}}
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $quickJumps = [
                            ['url' => url('/quran/juz'),  'icon' => 'layers',       'label' => __('quran.public.go_to_juz'),  'count' => 30],
                            ['url' => url('/quran/hizb'), 'icon' => 'square-stack', 'label' => __('quran.public.go_to_hizb'), 'count' => 60],
                            ['url' => url('/quran/page'), 'icon' => 'file-text',    'label' => __('quran.public.go_to_page'), 'count' => 604],
                        ];
                        $quickJumpUnits = [
                            'layers'       => __('quran.nav.juz'),
                            'square-stack' => __('quran.nav.hizb'),
                            'file-text'    => __('quran.nav.page'),
                        ];
                    @endphp
                    @foreach($quickJumps as $jump)
                        <div class="relative flex-1 sm:flex-none">
                            <i data-lucide="{{ $jump['icon'] }}" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#64CCC5] pointer-events-none"></i>
                            <select
                                @change="if ($event.target.value) { window.location.href = '{{ $jump['url'] }}/' + $event.target.value; }"
                                class="w-full appearance-none ps-9 pe-9 py-2.5 text-base sm:text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#64CCC5]/60 focus:border-[#64CCC5] transition-all cursor-pointer"
                                :class="$store.theme.dark ? 'bg-gray-950 border-gray-800 text-white' : 'bg-gray-50 border-gray-200 text-gray-900'"
                                aria-label="{{ $jump['label'] }}"
                            >
                                <option value="">{{ $jump['label'] }}</option>
                                @for($i = 1; $i <= $jump['count']; $i++)
                                    <option value="{{ $i }}">{{ $quickJumpUnits[$jump['icon']] }} {{ $i }}</option>
                                @endfor
                            </select>
                            <i data-lucide="chevron-down" class="absolute end-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                               :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'"></i>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>{{-- /controls toolbar --}}

        {{-- Results count --}}
        <div class="mb-6 stagger-2 fade-in-up">
            <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                {{ __('site.showing') }}
                <span class="font-bold text-[#64CCC5]" x-text="filteredSurahs.length"></span>
                {{ __('site.of') }}
                <span x-text="surahs.length"></span>
                {{ __('quran.public.surah_unit') }}
            </p>
        </div>

        {{-- ============================================================ --}}
        {{-- GRID VIEW --}}
        {{-- ============================================================ --}}
        <div x-show="viewMode === 'grid'"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <template x-for="(surah, index) in filteredSurahs" :key="surah.number">
                <a
                    :href="`{{ url('/quran') }}/${surah.number}`"
                    class="group relative surah-card overflow-hidden rounded-2xl p-6 border transition-all fade-in-up"
                    :class="[
                        $store.theme.dark ? 'bg-gray-900 border-white/5 hover:border-[#64CCC5]/30' : 'bg-white border-gray-100 hover:border-[#0056b3]/25',
                        'stagger-' + ((index % 3) + 1)
                    ]"
                >
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

                    {{-- Number medallion — Rub el Hizb (8-point star) motif --}}
                    <div class="relative text-center mb-4 mt-1">
                        <div class="relative inline-flex items-center justify-center w-20 h-20 group-hover:scale-105 transition-transform duration-300">
                            {{-- 8-point star: two overlapping squares --}}
                            <span aria-hidden="true" class="absolute w-14 h-14 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] shadow-lg shadow-[#0056b3]/40 group-hover:shadow-[#218838]/50 transition-shadow duration-300"></span>
                            <span aria-hidden="true" class="absolute w-14 h-14 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] rotate-45"></span>
                            {{-- Inner disc with the numeral --}}
                            <span class="relative z-10 flex items-center justify-center w-11 h-11 rounded-full ring-2"
                                  :class="$store.theme.dark ? 'bg-gray-900 ring-white/10' : 'bg-white ring-white/60'">
                                <span class="font-['Amiri'] font-bold text-xl leading-none"
                                      :class="$store.theme.dark ? 'text-white' : 'text-[#0056b3]'" x-text="surah.number"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Surah name --}}
                    <div class="relative text-center mb-4">
                        <h3 class="text-2xl font-bold mb-0.5 leading-snug group-hover:text-[#64CCC5] transition-colors font-['Amiri']"
                            :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                            x-text="surah.nameAr"></h3>
                        <p class="text-[11px] font-semibold tracking-[0.15em] uppercase" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'" x-text="surah.nameEn"></p>
                    </div>

                    {{-- Ornamental divider --}}
                    <div class="relative flex items-center justify-center gap-2 mb-4" aria-hidden="true">
                        <span class="h-px w-12 bg-gradient-to-r from-transparent" :class="$store.theme.dark ? 'to-gray-700' : 'to-gray-300'"></span>
                        <svg class="w-3 h-3 text-[#64CCC5]/80" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0l2.2 7.8L20 10l-7.8 2.2L10 20l-2.2-7.8L0 10l7.8-2.2z"/></svg>
                        <span class="h-px w-12 bg-gradient-to-l from-transparent" :class="$store.theme.dark ? 'to-gray-700' : 'to-gray-300'"></span>
                    </div>

                    {{-- Meta chips --}}
                    <div class="relative flex items-center justify-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                              :class="$store.theme.dark ? 'bg-gray-800/70 text-gray-300' : 'bg-gray-100 text-gray-600'">
                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-[#64CCC5]"></i>
                            <span x-text="surah.verses"></span> {{ __('quran.public.verse_unit') }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold ring-1"
                              :class="surah.type === 'makki'
                                  ? 'bg-amber-500/15 text-amber-600 dark:text-amber-300 ring-amber-500/25'
                                  : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 ring-emerald-500/25'"
                              x-text="surah.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'">
                        </span>
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
                    class="group relative flex items-center gap-4 surah-card overflow-hidden rounded-2xl p-4 border transition-all fade-in-up"
                    :class="[
                        $store.theme.dark ? 'bg-gray-900 border-white/5 hover:border-[#64CCC5]/30' : 'bg-white border-gray-100 hover:border-[#0056b3]/25',
                        'stagger-' + ((index % 3) + 1)
                    ]"
                >
                    {{-- Number medallion — Rub el Hizb (8-point star) motif --}}
                    <div class="relative inline-flex items-center justify-center w-16 h-16 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <span aria-hidden="true" class="absolute w-11 h-11 rounded-md bg-gradient-to-br from-[#0056b3] to-[#218838] shadow-lg shadow-[#0056b3]/40 group-hover:shadow-[#218838]/50 transition-shadow duration-300"></span>
                        <span aria-hidden="true" class="absolute w-11 h-11 rounded-md bg-gradient-to-br from-[#0056b3] to-[#218838] rotate-45"></span>
                        <span class="relative z-10 flex items-center justify-center w-9 h-9 rounded-full ring-2"
                              :class="$store.theme.dark ? 'bg-gray-900 ring-white/10' : 'bg-white ring-white/60'">
                            <span class="font-['Amiri'] font-bold text-base leading-none"
                                  :class="$store.theme.dark ? 'text-white' : 'text-[#0056b3]'" x-text="surah.number"></span>
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-3 mb-1">
                            <h3 class="text-xl font-bold group-hover:text-[#64CCC5] transition-colors font-['Amiri'] truncate"
                                :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                                x-text="surah.nameAr"></h3>
                            <span class="text-[11px] font-semibold tracking-[0.15em] uppercase truncate" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'" x-text="surah.nameEn"></span>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="flex items-center gap-2 sm:gap-3 text-sm flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                              :class="$store.theme.dark ? 'bg-gray-800/70 text-gray-300' : 'bg-gray-100 text-gray-600'">
                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-[#64CCC5]"></i>
                            <span x-text="surah.verses"></span> {{ __('quran.public.verse_unit') }}
                        </span>
                        <span
                            class="px-2.5 py-1 rounded-lg text-xs font-bold ring-1"
                            :class="surah.type === 'makki'
                                ? 'bg-amber-500/15 text-amber-600 dark:text-amber-300 ring-amber-500/25'
                                : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 ring-emerald-500/25'"
                            x-text="surah.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'">
                        </span>
                    </div>

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
