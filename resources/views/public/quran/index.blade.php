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

@section('content')
@php
    $quickJumps = [
        ['url' => url('/quran/juz'),  'label' => __('quran.public.go_to_juz'),  'unit' => __('quran.nav.juz'),  'count' => 30],
        ['url' => url('/quran/hizb'), 'label' => __('quran.public.go_to_hizb'), 'unit' => __('quran.nav.hizb'), 'count' => 60],
        ['url' => url('/quran/page'), 'label' => __('quran.public.go_to_page'), 'unit' => __('quran.nav.page'), 'count' => 604],
    ];
@endphp

<div class="sx min-h-[100dvh] pb-20" x-data="quranIndex(@js(['surahs' => $surahs]))">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-label="__('quran.public.page_title')" eyebrow-icon="book-open"
            :title="__('quran.public.hero_title')" :lead="__('quran.public.hero_subtitle')"
            :stats="[
                ['icon' => 'book-open', 'label' => __('quran.public.stat_surahs'), 'value' => $stats['total']],
                ['icon' => 'list-ordered', 'label' => __('quran.public.stat_verses'), 'value' => number_format($stats['verses'])],
                ['icon' => 'sunrise', 'label' => __('quran.public.stat_makki'), 'value' => $stats['makki']],
                ['icon' => 'moon-star', 'label' => __('quran.public.stat_madani'), 'value' => $stats['madani']],
            ]">
            <div class="grid gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('public.quran.ask') }}" class="sx-btn is-primary">
                        <i data-lucide="sparkles" aria-hidden="true"></i>{{ __('quran.public.ask_page_title') }}
                    </a>
                    <a href="{{ route('public.quran.reciters') }}" class="sx-btn">
                        <i data-lucide="mic" aria-hidden="true"></i>{{ __('quran.public.reciters_hero_title') }}
                    </a>
                    <a href="{{ route('public.quran.playlists.index') }}" class="sx-btn">
                        <i data-lucide="list-music" aria-hidden="true"></i>{{ __('quran.playlists.title') }}
                    </a>
                </div>
                <p class="max-w-xl text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.ask_hero_subtitle') }}</p>
            </div>
        </x-public.editorial.page-hero>

        {{-- Search, sort, view, type filter and the juz / hizb / page jumps. --}}
        <div class="sx-surface sx-filters">
            <div class="sx-filter-row">
                {{-- Navigational autocomplete uses the 114 surahs already embedded in this page. --}}
                <div class="relative" @click.outside="suggest.open = false" @keydown="suggestKeydown($event)">
                    <label for="quran-search" class="sr-only">{{ __('quran.public.search_placeholder') }}</label>
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="quran-search" type="search" x-model="searchQuery" @input="onSearchInput()"
                           @focus="if (suggest.items.length || suggest.error) { suggest.open = true; }"
                           autocomplete="off" enterkeyhint="search" role="combobox"
                           :aria-expanded="suggest.open" aria-controls="quran-suggest-list"
                           placeholder="{{ __('quran.public.search_placeholder') }}"
                           aria-label="{{ __('quran.public.search_placeholder') }}"
                           class="sx-input ps-12">

                    <div x-show="suggest.open" x-transition.opacity x-cloak id="quran-suggest-list" role="listbox"
                         class="sx-surface absolute z-40 mt-2 w-full max-h-80 overflow-y-auto p-1.5">
                        <template x-if="suggest.loading">
                            <p class="flex items-center gap-3 px-3 py-2.5 text-sm text-[color:var(--sx-ink-2)]">
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                                {{ __('quran.public.suggest_loading') }}
                            </p>
                        </template>

                        <template x-if="!suggest.loading && suggest.error">
                            <button type="button" @click="fetchSuggest(searchQuery.trim())"
                                    class="sx-tile w-full text-start hover:border-[color:var(--sx-line-strong)]">
                                <span class="sx-tile-icon bg-[color:var(--sx-danger-soft)] text-[color:var(--sx-danger)]"><i data-lucide="rotate-cw" aria-hidden="true"></i></span>
                                <span class="sx-tile-title text-[color:var(--sx-danger)]">{{ __('quran.public.suggest_error') }}</span>
                                <span></span>
                            </button>
                        </template>

                        <template x-if="!suggest.loading && !suggest.error && suggest.items.length === 0">
                            <p class="px-3 py-2.5 text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.suggest_empty') }}</p>
                        </template>

                        <div class="sx-tile-list">
                            <template x-for="(s, i) in suggest.items" :key="s.type + '-' + (s.verse_key || s.surah_number || s.number)">
                                <button type="button" role="option" :aria-selected="suggest.active === i"
                                        @click="gotoSuggestion(s)" @mouseenter="suggest.active = i"
                                        class="sx-tile w-full text-start"
                                        :class="suggest.active === i && '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]'">
                                    <span class="sx-tile-icon">
                                        <i :data-lucide="s.type === 'verse' ? 'bookmark' : (s.type === 'juz' ? 'layers' : (s.type === 'hizb' ? 'square-stack' : (s.type === 'page' ? 'file-text' : 'book-open')))" aria-hidden="true"></i>
                                    </span>
                                    <span class="sx-tile-title sx-clamp-1" x-text="s.label" :title="s.label"></span>
                                    <i data-lucide="chevron-left" class="h-4 w-4 text-[color:var(--sx-muted)] ltr:rotate-180" aria-hidden="true"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="quran-sort" class="sr-only">{{ __('quran.public.sort_label') }}</label>
                    <select id="quran-sort" x-model="sortBy" @change="setSort(sortBy)" class="sx-input">
                        <option value="number">{{ __('quran.public.sort_number') }}</option>
                        <option value="name">{{ __('quran.public.sort_name') }}</option>
                        <option value="verses">{{ __('quran.public.sort_verses') }}</option>
                    </select>
                </div>

                <div class="sx-segment" role="group" aria-label="{{ __('quran.public.view_grid') }}">
                    <button type="button" @click="setViewMode('grid')" :aria-pressed="(viewMode === 'grid').toString()"
                            aria-label="{{ __('quran.public.view_grid') }}" title="{{ __('quran.public.view_grid') }}">
                        <i data-lucide="layout-grid" aria-hidden="true"></i>{{ __('quran.public.view_grid') }}
                    </button>
                    <button type="button" @click="setViewMode('list')" :aria-pressed="(viewMode === 'list').toString()"
                            aria-label="{{ __('quran.public.view_list') }}" title="{{ __('quran.public.view_list') }}">
                        <i data-lucide="list" aria-hidden="true"></i>{{ __('quran.public.view_list') }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[color:var(--sx-line)] pt-3">
                <div class="sx-scroller" role="group" aria-label="{{ __('quran.public.revelation_place') }}">
                    <button type="button" @click="setFilter('all')" class="sx-chip shrink-0"
                            :class="activeFilter === 'all' && 'is-active'" :aria-pressed="(activeFilter === 'all').toString()">
                        <i data-lucide="layout-grid" aria-hidden="true"></i>{{ __('quran.public.filter_all') }}<span class="sx-count" x-text="surahs.length"></span>
                    </button>
                    <button type="button" @click="setFilter('makki')" class="sx-chip shrink-0"
                            :class="activeFilter === 'makki' && 'is-active'" :aria-pressed="(activeFilter === 'makki').toString()">
                        <i data-lucide="sunrise" aria-hidden="true"></i>{{ __('quran.public.filter_makki') }}<span class="sx-count" x-text="makkiCount"></span>
                    </button>
                    <button type="button" @click="setFilter('madani')" class="sx-chip shrink-0"
                            :class="activeFilter === 'madani' && 'is-active'" :aria-pressed="(activeFilter === 'madani').toString()">
                        <i data-lucide="moon-star" aria-hidden="true"></i>{{ __('quran.public.filter_madani') }}<span class="sx-count" x-text="madaniCount"></span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($quickJumps as $jump)
                        <select @change="if ($event.target.value) { window.location.href = '{{ $jump['url'] }}/' + $event.target.value; }"
                                class="sx-input w-auto text-sm" aria-label="{{ $jump['label'] }}">
                            <option value="">{{ $jump['label'] }}</option>
                            @for ($i = 1; $i <= $jump['count']; $i++)
                                <option value="{{ $i }}">{{ $jump['unit'] }} {{ $i }}</option>
                            @endfor
                        </select>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-8 mb-5">
            <p class="text-sm text-[color:var(--sx-muted)]" aria-live="polite">
                {{ __('site.showing') }}
                <strong class="tabular-nums text-[color:var(--sx-ink)]" x-text="filteredSurahs.length"></strong>
                {{ __('site.of') }}
                <span class="tabular-nums" x-text="surahs.length"></span>
                {{ __('quran.public.surah_unit') }}
            </p>
        </div>

        {{-- Cards are server-rendered; Alpine only shows the matching surahs and orders them. --}}
        <div class="sx-grid" :class="viewMode === 'list' && '!grid-cols-1'">
            @foreach ($surahs as $surah)
                <x-public.quran.surah-directory-card :surah="$surah"
                    x-show="isVisible({{ (int) $surah['number'] }})"
                    ::style="'order:' + orderRank({{ (int) $surah['number'] }})"
                    ::class="viewMode === 'list' && 'is-row'" />
            @endforeach
        </div>

        <div x-show="filteredSurahs.length === 0" x-cloak class="mt-8 grid justify-items-center gap-4">
            <x-public.editorial.empty-state class="w-full" icon="search-x"
                :title="__('site.no_results')" :hint="__('site.try_adjusting_filters')" />
            <button type="button" @click="searchQuery = ''; activeFilter = 'all'" class="sx-btn is-primary">
                <i data-lucide="rotate-ccw" aria-hidden="true"></i>{{ __('site.reset_filters') }}
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function quranIndex(config) {
        return {
            surahs: config.surahs || [],
            searchQuery: '',
            suggest: { items: [], open: false, loading: false, error: false, active: -1, timer: null },
            activeFilter: 'all',
            viewMode: (() => { try { return localStorage.getItem('quranViewMode') || 'grid'; } catch (e) { return 'grid'; } })(),
            sortBy: (() => { try { return localStorage.getItem('quranSortBy') || 'number'; } catch (e) { return 'number'; } })(),
            progress: (() => { try { return JSON.parse(localStorage.getItem('surahProgress') || '{}'); } catch (e) { return {}; } })(),

            init() {
                this.$watch('filteredSurahs', () => this.refreshIcons());
                this.$watch('viewMode', () => this.refreshIcons());
                this.$watch('activeFilter', () => this.refreshIcons());
            },

            refreshIcons() { this.$nextTick(() => window.lucide?.createIcons()); },

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

            // The cards are in mushaf order in the DOM: filtering hides, sorting reorders.
            get visibleOrder() {
                const order = {};
                this.filteredSurahs.forEach((s, i) => { order[s.number] = i; });
                return order;
            },
            isVisible(number) { return this.visibleOrder[number] !== undefined; },
            orderRank(number) { return this.visibleOrder[number] ?? 0; },

            get makkiCount()  { return this.surahs.filter(s => s.type === 'makki').length; },
            get madaniCount() { return this.surahs.filter(s => s.type === 'madani').length; },

            setFilter(f)  { this.activeFilter = f; },
            setSort(s)    { this.sortBy = s; try { localStorage.setItem('quranSortBy', s); } catch (e) {} },
            setViewMode(m){ this.viewMode = m; try { localStorage.setItem('quranViewMode', m); } catch (e) {} },

            getProgress(n){ return this.progress[n] || 0; },

            onSearchInput() {
                clearTimeout(this.suggest.timer);
                const q = this.searchQuery.trim();
                if (q.length < 1) { this.suggest.items = []; this.suggest.open = false; this.suggest.error = false; return; }
                this.suggest.timer = setTimeout(() => this.fetchSuggest(q), 250);
            },
            fetchSuggest(q) {
                this.suggest.loading = true; this.suggest.error = false; this.suggest.open = true; this.suggest.active = -1;
                const normalizeDigits = value => value
                    .replace(/[٠-٩]/g, digit => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)))
                    .replace(/[۰-۹]/g, digit => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)));
                const value = normalizeDigits(q.trim());
                const lower = value.toLocaleLowerCase();
                const items = [];
                const add = item => {
                    const key = `${item.type}:${item.verse_key || item.surah_number || item.number}`;
                    if (!items.some(existing => `${existing.type}:${existing.verse_key || existing.surah_number || existing.number}` === key)) {
                        items.push(item);
                    }
                };

                const verseMatch = value.match(/^(\d{1,3})\s*:\s*(\d{1,3})$/);
                if (verseMatch) {
                    const surahNumber = Number(verseMatch[1]);
                    const verseNumber = Number(verseMatch[2]);
                    const surah = this.surahs.find(item => item.number === surahNumber);
                    if (surah && verseNumber >= 1 && verseNumber <= surah.verses) {
                        add({ type: 'verse', surah_number: surahNumber, verse_key: `${surahNumber}:${verseNumber}`, label: `${surah.nameAr} (${surahNumber}:${verseNumber})` });
                    }
                }

                const navigationPatterns = [
                    { type: 'juz', regex: /^(?:juz|جزء)\s*(\d{1,2})$/i, max: 30, label: @js(__('quran.nav.juz')) },
                    { type: 'hizb', regex: /^(?:hizb|حزب)\s*(\d{1,2})$/i, max: 60, label: @js(__('quran.nav.hizb')) },
                    { type: 'page', regex: /^(?:page|صفحة)\s*(\d{1,3})$/i, max: 604, label: @js(__('quran.nav.page')) },
                ];
                navigationPatterns.forEach(pattern => {
                    const match = value.match(pattern.regex);
                    const number = match ? Number(match[1]) : 0;
                    if (number >= 1 && number <= pattern.max) {
                        add({ type: pattern.type, number, label: `${pattern.label} ${number}` });
                    }
                });

                this.surahs
                    .filter(surah => String(surah.number) === value || surah.nameAr.includes(value) || surah.nameEn.toLocaleLowerCase().includes(lower))
                    .slice(0, 8)
                    .forEach(surah => add({ type: 'surah', surah_number: surah.number, label: `${surah.nameAr} — ${surah.nameEn}` }));

                this.suggest.items = items.slice(0, 8);
                this.suggest.loading = false;
                this.refreshIcons();
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
        };
    }
</script>
@endpush
