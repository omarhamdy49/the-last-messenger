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
<div class="sx min-h-[100dvh] pb-20"
     x-data="searchPage({
        query: @js($query),
        activeTypes: @js($activeTypes),
        sort: @js($sort),
        results: @js($resultsJson),
        facets: @js($facets),
        meta: @js($moduleMeta),
    })">

    <div class="sx-wrap">
        {{-- ===================== Hero / Search input ===================== --}}
        <x-public.editorial.page-hero
            :eyebrow-label="__('search.page_title')" eyebrow-icon="search"
            :title="__('search.heading')" :lead="__('search.subheading')">

            {{-- Search form (GET) — preserves type filters + sort on submit --}}
            <form method="GET" action="{{ route('public.search') }}" class="sx-surface sx-filters max-w-3xl">
                <div>
                    <label for="site-q" class="sx-label">{{ __('search.search') }}</label>
                    <div class="relative">
                        <i data-lucide="search" aria-hidden="true"
                           class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]"></i>

                        <input type="search" id="site-q" name="q" x-ref="q" x-model="query"
                               value="{{ $query }}"
                               autofocus autocomplete="off"
                               enterkeyhint="search"
                               placeholder="{{ __('search.input_placeholder') }}"
                               class="sx-input ps-12 pe-28 text-base font-semibold sm:text-lg" />

                        {{-- Clear --}}
                        <button type="button" x-show="query.length > 0" x-cloak
                                @click="clearQuery()"
                                aria-label="{{ __('search.clear') }}" title="{{ __('search.clear') }}"
                                class="absolute end-16 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-ink)]">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>

                        {{-- Submit --}}
                        <button type="submit"
                                aria-label="{{ __('search.search') }}" title="{{ __('search.search') }}"
                                class="sx-btn is-primary absolute end-1.5 top-1/2 h-11 w-11 -translate-y-1/2 !p-0 disabled:opacity-60"
                                x-bind:disabled="submitting"
                                @click="submitting = true">
                            <span x-show="!submitting"><i data-lucide="search" class="w-5 h-5"></i></span>
                            <span x-show="submitting" x-cloak><i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i></span>
                        </button>
                    </div>
                </div>

                {{-- Hidden state carried across submit --}}
                <template x-for="t in activeTypes" :key="t">
                    <input type="hidden" name="type[]" :value="t">
                </template>
                <input type="hidden" name="sort" :value="sort">

                {{-- Suggestion chips (popular searches) --}}
                @if (! empty($popularSearches))
                    <div class="border-t border-[color:var(--sx-line)] pt-3">
                        <p class="sx-label">{{ __('search.popular_searches') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($popularSearches as $term)
                                <button type="button" @click="applySuggestion(@js($term))" class="sx-chip" title="{{ $term }}">
                                    <span class="sx-clamp-1 max-w-[12rem]">{{ $term }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>

            {{-- Results summary --}}
            @if ($query !== '')
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="sx-avatar !h-11 !w-11 font-extrabold tabular-nums" aria-hidden="true"
                              x-text="visibleCount">{{ $totalResults }}</span>
                        <div class="min-w-0">
                            <p class="font-bold text-[color:var(--sx-ink)]" aria-live="polite" x-text="resultCountLabel"></p>
                            <p class="sx-clamp-1 max-w-[18rem] text-sm text-[color:var(--sx-muted)]" title="{{ $query }}">
                                {{ __('search.results_for') }}
                                <span class="font-bold text-[color:var(--sx-accent)]">"{{ $query }}"</span>
                            </p>
                        </div>
                    </div>

                    {{-- List / grid toggle --}}
                    <div class="sx-segment" role="group" aria-label="{{ __('search.view_list') }}">
                        <button type="button" @click="viewMode = 'list'" :aria-pressed="(viewMode === 'list').toString()"
                                aria-label="{{ __('search.view_list') }}" title="{{ __('search.view_list') }}">
                            <i data-lucide="list" aria-hidden="true"></i>
                        </button>
                        <button type="button" @click="viewMode = 'grid'" :aria-pressed="(viewMode === 'grid').toString()"
                                aria-label="{{ __('search.view_grid') }}" title="{{ __('search.view_grid') }}">
                            <i data-lucide="layout-grid" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            @endif
        </x-public.editorial.page-hero>

        {{-- ===================== Discovery state (no query) ===================== --}}
        @if ($query === '')
            <x-site.empty-state class="sx-surface" icon="search"
                                :title="__('search.start_prompt_title')"
                                :message="__('search.start_prompt')" />
        @else
            {{-- ===================== Results ===================== --}}
            <main class="grid items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]">

                {{-- Sidebar filters --}}
                <aside class="sx-surface p-5 lg:sticky lg:top-24">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-base font-extrabold text-[color:var(--sx-ink)]">
                            <i data-lucide="sliders-horizontal" class="h-5 w-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                            {{ __('search.filters') }}
                        </h2>
                        <button type="button" @click="resetFilters()"
                                class="text-sm font-bold text-[color:var(--sx-accent)] hover:underline underline-offset-4">
                            {{ __('search.reset') }}
                        </button>
                    </div>

                    {{-- Content type checkboxes --}}
                    <fieldset class="mb-5 border-b border-[color:var(--sx-line)] pb-5">
                        <legend class="sx-label">{{ __('search.content_type') }}</legend>
                        <div class="grid gap-1">
                            @foreach ($moduleMeta as $type => $meta)
                                <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-xl px-2 transition-colors hover:bg-[color:var(--sx-surface-2)]">
                                    <input type="checkbox" x-model="activeTypes" value="{{ $type }}"
                                           @change="onTypeToggle()"
                                           class="h-5 w-5 rounded border-[color:var(--sx-line-strong)] text-[color:var(--sx-accent)] focus:ring-[color:var(--sx-accent)]" />
                                    <span class="sx-clamp-1 flex-1 text-sm font-semibold text-[color:var(--sx-ink-2)]" title="{{ $meta['label'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                    <span class="sx-count tabular-nums">{{ $facets[$type] ?? 0 }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Sort --}}
                    <div>
                        <label for="site-sort" class="sx-label">{{ __('search.sort_by') }}</label>
                        <select id="site-sort" x-model="sort" @change="applySort()" class="sx-input text-base font-semibold sm:text-sm">
                            <option value="relevance">{{ __('search.sort_relevance') }}</option>
                            <option value="date">{{ __('search.sort_date') }}</option>
                            <option value="popular">{{ __('search.sort_popular') }}</option>
                        </select>
                    </div>
                </aside>

                {{-- Results column --}}
                <div class="min-w-0">

                    {{-- List view --}}
                    <div x-show="viewMode === 'list'" class="grid gap-4">
                        <template x-for="(result, index) in filteredResults" :key="index">
                            <article class="sx-card">
                                <div class="sx-card-body">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex min-w-0 flex-1 items-start gap-4">
                                            <span class="sx-avatar is-square !h-12 !w-12" aria-hidden="true">
                                                <i class="h-6 w-6" :data-lucide="meta[result.type]?.icon || 'file-text'"></i>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                                    <span class="sx-chip !min-h-0 !py-1 !text-xs !font-bold !text-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]"
                                                          x-text="meta[result.type]?.label"></span>
                                                    <span class="sx-clamp-1 max-w-[14rem] text-xs text-[color:var(--sx-muted)]"
                                                          x-text="result.source" :title="result.source"></span>
                                                </div>
                                                <h3 class="sx-card-title" :title="result.title" x-html="highlight(result.title)"></h3>
                                                <p class="sx-card-desc sx-clamp-3 mt-1.5" x-html="highlight(result.excerpt)"></p>
                                            </div>
                                        </div>
                                        <button type="button" @click="$store.toast.push(@js(__('search.bookmark')), 'success')"
                                                class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-accent)]"
                                                aria-label="{{ __('search.bookmark') }}" title="{{ __('search.bookmark') }}">
                                            <i data-lucide="bookmark-plus" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 border-t border-[color:var(--sx-line)] pt-4">
                                        <a :href="result.url" class="sx-btn">
                                            <i data-lucide="{{ $arrowIcon }}" aria-hidden="true"></i>
                                            <span>{{ __('search.view_details') }}</span>
                                        </a>
                                        <button type="button" @click="share(result)"
                                                class="grid h-11 w-11 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-accent)]"
                                                aria-label="{{ __('search.share') }}" title="{{ __('search.share') }}">
                                            <i data-lucide="share-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>

                    {{-- Grid view --}}
                    <div x-show="viewMode === 'grid'" x-cloak class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <template x-for="(result, index) in filteredResults" :key="index">
                            <a :href="result.url" :title="result.title" class="sx-card group">
                                <div class="sx-card-media !aspect-[16/7]">
                                    <span class="sx-card-glyph" aria-hidden="true">
                                        <i :data-lucide="meta[result.type]?.icon || 'file-text'"></i>
                                    </span>
                                    <span class="sx-card-type">
                                        <span class="sx-glass-chip sx-clamp-1 max-w-[12rem]" x-text="meta[result.type]?.label"></span>
                                    </span>
                                </div>
                                <div class="sx-card-body">
                                    <h3 class="sx-card-title" x-html="highlight(result.title)"></h3>
                                    <p class="sx-card-desc" x-html="highlight(result.excerpt)"></p>
                                    <div class="flex items-center justify-between gap-3 border-t border-[color:var(--sx-line)] pt-3">
                                        <span class="sx-clamp-1 text-xs text-[color:var(--sx-muted)]" x-text="result.source" :title="result.source"></span>
                                        <i data-lucide="{{ $arrowIcon }}" class="h-5 w-5 shrink-0 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>

                    {{-- No results --}}
                    <div x-show="filteredResults.length === 0" x-cloak>
                        <x-site.empty-state class="sx-surface" icon="search-x"
                                            :title="__('search.no_results_title')"
                                            :message="__('search.no_results_body')"
                                            :show-reset="true"
                                            @click="resetFilters()" />
                    </div>
                </div>
            </main>
        @endif
    </div>
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
                        (m) => `<mark class="rounded px-0.5 text-inherit" style="background: color-mix(in srgb, var(--sx-accent) 22%, transparent)">${m}</mark>`);
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
