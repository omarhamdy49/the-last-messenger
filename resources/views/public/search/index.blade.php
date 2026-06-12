@extends('layouts.public')

@section('title', __('search.page_title') . ' — ' . __('site.site_name'))
@section('meta_description', __('search.subheading'))

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $arrowIcon = $isRtl ? 'arrow-left' : 'arrow-right';

    // JSON payloads consumed by the Alpine component (server-rendered, client-filtered).
    $resultsJson = collect($results)->map(fn ($r) => [
        'type'    => $r['type'],
        'title'   => (string) $r['title'],
        'excerpt' => (string) $r['excerpt'],
        'source'  => (string) $r['source'],
        'url'     => (string) $r['url'],
    ])->values();
@endphp

@section('content')
<div x-data="searchPage({
        query: @js($query),
        activeTypes: @js($activeTypes),
        sort: @js($sort),
        results: @js($resultsJson),
        facets: @js($facets),
        meta: @js($moduleMeta),
    })">

    {{-- ===================== Hero / Search input ===================== --}}
    <section class="border-b border-gray-200 dark:border-gray-800
                    bg-gradient-to-br from-blue-50 via-green-50 to-cyan-50
                    dark:from-[#1a1a2e] dark:via-[#16213e] dark:to-[#0f3460]">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <x-site.breadcrumb :items="[['label' => __('search.page_title')]]" class="mb-6 justify-center" />

                <div class="text-center mb-8">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-3">
                        {{ __('search.heading') }}
                    </h1>
                    <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300">
                        {{ __('search.subheading') }}
                    </p>
                </div>

                {{-- Search form (GET) — preserves type filters + sort on submit --}}
                <form method="GET" action="{{ route('public.search') }}"
                      class="card-premium rounded-2xl p-4 sm:p-6 shadow-lg">
                    <div class="relative">
                        <i data-lucide="search"
                           class="absolute start-4 sm:start-5 top-1/2 -translate-y-1/2 w-5 h-5 sm:w-6 sm:h-6 text-gray-400 pointer-events-none"></i>

                        <input type="search" name="q" x-ref="q" x-model="query"
                               value="{{ $query }}"
                               autofocus autocomplete="off"
                               enterkeyhint="search"
                               placeholder="{{ __('search.input_placeholder') }}"
                               aria-label="{{ __('search.search') }}"
                               class="w-full bg-transparent border-2 rounded-xl py-3.5 sm:py-4 ps-12 sm:ps-14 pe-24
                                      text-base sm:text-lg font-semibold transition-all
                                      border-gray-300 dark:border-gray-700
                                      text-gray-900 dark:text-white
                                      placeholder-gray-400 dark:placeholder-gray-500
                                      focus:border-primary focus:outline-none" />

                        {{-- Clear --}}
                        <button type="button" x-show="query.length > 0" x-cloak
                                @click="clearQuery()"
                                aria-label="{{ __('search.clear') }}"
                                class="absolute end-14 top-1/2 -translate-y-1/2 p-2 rounded-lg
                                       hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            <i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
                        </button>

                        {{-- Submit --}}
                        <button type="submit"
                                aria-label="{{ __('search.search') }}"
                                class="absolute end-2 top-1/2 -translate-y-1/2 px-4 py-2 rounded-lg
                                       bg-gradient-to-l from-primary to-secondary text-white
                                       font-bold text-sm hover:shadow-md transition-all"
                                x-bind:disabled="submitting"
                                @click="submitting = true">
                            <span x-show="!submitting"><i data-lucide="search" class="w-5 h-5"></i></span>
                            <span x-show="submitting" x-cloak><i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i></span>
                        </button>
                    </div>

                    {{-- Hidden state carried across submit --}}
                    <template x-for="t in activeTypes" :key="t">
                        <input type="hidden" name="type[]" :value="t">
                    </template>
                    <input type="hidden" name="sort" :value="sort">

                    {{-- Suggestion chips (popular searches) --}}
                    @if (! empty($popularSearches))
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <p class="text-xs font-semibold mb-3 text-gray-500 dark:text-gray-400">
                                {{ __('search.popular_searches') }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($popularSearches as $term)
                                    <button type="button"
                                            @click="applySuggestion(@js($term))"
                                            class="px-3.5 py-1.5 rounded-full text-sm font-semibold
                                                   bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200
                                                   hover:bg-primary/10 hover:text-primary transition-colors">
                                        {{ $term }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </form>

                {{-- Results summary --}}
                @if ($query !== '')
                    <div class="flex flex-wrap items-center justify-between gap-4 mt-6">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-secondary
                                        flex items-center justify-center shrink-0">
                                <span class="text-white font-black"
                                      x-text="visibleCount">{{ $totalResults }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-gray-900 dark:text-white"
                                   x-text="resultCountLabel"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-[18rem]">
                                    {{ __('search.results_for') }}
                                    <span class="font-bold text-primary">"{{ $query }}"</span>
                                </p>
                            </div>
                        </div>

                        {{-- List / grid toggle --}}
                        <div class="flex items-center gap-1 p-1 rounded-lg bg-white dark:bg-[#16213e]
                                    border border-gray-200 dark:border-gray-700">
                            <button type="button" @click="viewMode = 'list'"
                                    :class="viewMode === 'list'
                                        ? 'bg-gradient-to-l from-primary to-secondary text-white shadow'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                    class="p-2 rounded-md transition-all" aria-label="{{ __('search.view_list') }}">
                                <i data-lucide="list" class="w-5 h-5"></i>
                            </button>
                            <button type="button" @click="viewMode = 'grid'"
                                    :class="viewMode === 'grid'
                                        ? 'bg-gradient-to-l from-primary to-secondary text-white shadow'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                    class="p-2 rounded-md transition-all" aria-label="{{ __('search.view_grid') }}">
                                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== Discovery state (no query) ===================== --}}
    @if ($query === '')
        <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <x-site.empty-state icon="search"
                                :title="__('search.start_prompt_title')"
                                :message="__('search.start_prompt')" />
        </section>
    @else
        {{-- ===================== Results ===================== --}}
        <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">

                {{-- Sidebar filters --}}
                <aside class="lg:col-span-3 mb-8 lg:mb-0">
                    <div class="card-premium rounded-2xl p-6 lg:sticky lg:top-24">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-bold flex items-center gap-2 text-gray-900 dark:text-white">
                                <i data-lucide="sliders-horizontal" class="w-5 h-5 text-primary"></i>
                                {{ __('search.filters') }}
                            </h2>
                            <button type="button" @click="resetFilters()"
                                    class="text-sm font-semibold text-primary hover:text-secondary transition-colors">
                                {{ __('search.reset') }}
                            </button>
                        </div>

                        {{-- Content type checkboxes --}}
                        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-800">
                            <h3 class="font-bold mb-3 text-gray-900 dark:text-white">{{ __('search.content_type') }}</h3>
                            <div class="space-y-3">
                                @foreach ($moduleMeta as $type => $meta)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" x-model="activeTypes" value="{{ $type }}"
                                               @change="onTypeToggle()"
                                               class="w-5 h-5 rounded border-2 border-gray-400 text-primary focus:ring-primary" />
                                        <span class="flex-1 text-sm font-semibold text-gray-700 dark:text-gray-300
                                                     group-hover:text-primary transition-colors">
                                            {{ $meta['label'] }}
                                        </span>
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                                     bg-gray-100 dark:bg-[#0f3460] text-gray-600 dark:text-gray-300">
                                            {{ $facets[$type] ?? 0 }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Sort --}}
                        <div>
                            <h3 class="font-bold mb-3 text-gray-900 dark:text-white">{{ __('search.sort_by') }}</h3>
                            <select x-model="sort" @change="applySort()"
                                    class="w-full rounded-lg px-4 py-3 text-base sm:text-sm font-semibold
                                           bg-white dark:bg-[#16213e] border-2 border-gray-300 dark:border-gray-700
                                           text-gray-900 dark:text-white
                                           focus:outline-none focus:ring-2 focus:ring-primary transition-all">
                                <option value="relevance">{{ __('search.sort_relevance') }}</option>
                                <option value="date">{{ __('search.sort_date') }}</option>
                                <option value="popular">{{ __('search.sort_popular') }}</option>
                            </select>
                        </div>
                    </div>
                </aside>

                {{-- Results column --}}
                <div class="lg:col-span-9">

                    {{-- List view --}}
                    <div x-show="viewMode === 'list'" class="space-y-5">
                        <template x-for="(result, index) in filteredResults" :key="index">
                            <article class="card-premium rounded-2xl p-5 sm:p-6">
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="flex items-start gap-4 flex-1 min-w-0">
                                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center shrink-0
                                                    bg-gradient-to-br"
                                             :class="meta[result.type]?.gradient || 'from-gray-400 to-gray-500'">
                                            <i class="w-6 h-6 sm:w-7 sm:h-7 text-white"
                                               :data-lucide="meta[result.type]?.icon || 'file-text'"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold
                                                             bg-primary/10 text-primary dark:text-accent"
                                                      x-text="meta[result.type]?.label"></span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                                      x-text="result.source"></span>
                                            </div>
                                            <h3 class="text-lg sm:text-xl font-bold mb-2 text-gray-900 dark:text-white line-clamp-2"
                                                x-html="highlight(result.title)"></h3>
                                            <p class="text-sm leading-relaxed line-clamp-3 break-words text-gray-700 dark:text-gray-300"
                                               x-html="highlight(result.excerpt)"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click="$store.toast.push(@js(__('search.bookmark')), 'success')"
                                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors shrink-0"
                                            aria-label="{{ __('search.bookmark') }}">
                                        <i data-lucide="bookmark-plus" class="w-5 h-5 text-gray-400"></i>
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                                    <a :href="result.url"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                                              bg-gray-100 dark:bg-[#0f3460] text-gray-900 dark:text-white
                                              hover:bg-primary/10 hover:text-primary transition-colors">
                                        <i data-lucide="{{ $arrowIcon }}" class="w-4 h-4"></i>
                                        <span>{{ __('search.view_details') }}</span>
                                    </a>
                                    <button type="button" @click="share(result)"
                                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                            aria-label="{{ __('search.share') }}">
                                        <i data-lucide="share-2" class="w-4 h-4 text-gray-400"></i>
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>

                    {{-- Grid view --}}
                    <div x-show="viewMode === 'grid'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <template x-for="(result, index) in filteredResults" :key="index">
                            <article class="card-premium rounded-2xl overflow-hidden flex flex-col">
                                <div class="relative h-36 flex items-center justify-center bg-gradient-to-br"
                                     :class="meta[result.type]?.gradient || 'from-gray-400 to-gray-500'">
                                    <i class="w-16 h-16 text-white/80"
                                       :data-lucide="meta[result.type]?.icon || 'file-text'"></i>
                                    <span class="absolute top-3 start-3 px-3 py-1 rounded-full text-xs font-bold
                                                 bg-black/25 backdrop-blur text-white"
                                          x-text="meta[result.type]?.label"></span>
                                </div>
                                <div class="p-5 flex flex-col flex-1">
                                    <h3 class="text-base font-bold mb-2 line-clamp-2 text-gray-900 dark:text-white"
                                        x-html="highlight(result.title)"></h3>
                                    <p class="text-sm mb-4 line-clamp-3 break-words text-gray-700 dark:text-gray-300"
                                       x-html="highlight(result.excerpt)"></p>
                                    <div class="flex items-center justify-between pt-4 mt-auto
                                                border-t border-gray-200 dark:border-gray-800">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="result.source"></span>
                                        <a :href="result.url" class="text-primary hover:text-secondary transition-colors"
                                           aria-label="{{ __('search.view_details') }}">
                                            <i data-lucide="{{ $arrowIcon }}" class="w-5 h-5"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>

                    {{-- No results --}}
                    <div x-show="filteredResults.length === 0" x-cloak>
                        <x-site.empty-state icon="search-x"
                                            :title="__('search.no_results_title')"
                                            :message="__('search.no_results_body')"
                                            :show-reset="true"
                                            @click="resetFilters()" />
                    </div>
                </div>
            </div>
        </main>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function searchPage(config) {
        return {
            query: config.query || '',
            activeTypes: config.activeTypes || [],
            sort: config.sort || 'relevance',
            allResults: config.results || [],
            facets: config.facets || {},
            meta: config.meta || {},
            viewMode: 'list',
            submitting: false,

            get filteredResults() {
                if (!this.activeTypes.length) {
                    return this.allResults;
                }
                return this.allResults.filter((r) => this.activeTypes.includes(r.type));
            },

            get visibleCount() {
                return this.filteredResults.length;
            },

            get resultCountLabel() {
                const n = this.visibleCount;
                if (n === 0) return @js(__('search.no_results_title'));
                return @js(__('search.results_summary', ['count' => ':n'])).replace(':n', n);
            },

            clearQuery() {
                this.query = '';
                this.$refs.q.focus();
            },

            applySuggestion(term) {
                this.query = term;
                this.$nextTick(() => this.$refs.q.closest('form').submit());
            },

            // Server-truth filtering: re-query so per-type facet counts stay accurate.
            onTypeToggle() {
                this.reload();
            },

            applySort() {
                this.reload();
            },

            resetFilters() {
                this.activeTypes = [];
                this.sort = 'relevance';
                this.reload();
            },

            reload() {
                const url = new URL(@js(route('public.search')));
                if (this.query) url.searchParams.set('q', this.query);
                this.activeTypes.forEach((t) => url.searchParams.append('type[]', t));
                if (this.sort && this.sort !== 'relevance') url.searchParams.set('sort', this.sort);
                window.location.href = url.toString();
            },

            highlight(text) {
                const value = (text ?? '').toString();
                const escaped = value
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const term = (this.query || '').trim();
                if (!term) return escaped;
                const safe = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                try {
                    return escaped.replace(new RegExp(safe, 'gi'),
                        (m) => `<mark class="bg-accent/30 text-inherit rounded px-0.5">${m}</mark>`);
                } catch (e) {
                    return escaped;
                }
            },

            share(result) {
                const shareUrl = new URL(result.url, window.location.origin).toString();
                if (navigator.share) {
                    navigator.share({ title: result.title, url: shareUrl }).catch(() => {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(shareUrl).then(() => {
                        this.$store.toast && this.$store.toast.push(@js(__('search.share')), 'success');
                    });
                }
            },
        };
    }
</script>
@endpush
