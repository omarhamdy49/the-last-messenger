@extends('layouts.public')

@section('title', __('media.public.page_title'))
@section('meta_description', __('media.public.page_description'))

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $hasAnyContent = ! empty($heroSlides) || ! empty($featured) || ! empty($trending) || ! empty($popular) || ! empty($liveChannels) || ! empty($categoryRows);
    // One width for every card in a rail: ~1.3 visible on a phone, 5 at xl.
    $railCard = 'w-[78vw] sm:w-[46vw] md:w-[34vw] lg:w-[26vw] xl:w-[20rem] shrink-0 snap-start';
@endphp

<div class="sx min-h-[100dvh] pb-20 overflow-x-clip" x-data="mediaHub()">

    {{-- ============================================================ --}}
    {{-- HERO — full-bleed featured carousel on the shared hero shell --}}
    {{-- ============================================================ --}}
    @if (! empty($heroSlides))
        <section class="sx-hero is-story" x-data="mediaHeroCarousel({{ count($heroSlides) }})" x-init="start()"
                 @mouseenter="stop()" @mouseleave="start()"
                 aria-roledescription="carousel" aria-label="{{ __('media.public.featured') }}">
            {{-- Slide backdrops --}}
            @foreach ($heroSlides as $index => $slide)
                <div class="sx-hero-media" aria-hidden="true"
                     x-show="current === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 motion-reduce:duration-0"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-out duration-300 motion-reduce:duration-0"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     @if ($index > 0) style="display: none;" @endif>
                    <div class="sx-hero-fallback"></div>
                    @if ($slide['banner'])
                        {{-- banner_hq is maxresdefault (1280px) when the banner is a CDN
                             thumbnail; the 480px hqdefault visibly pixelates at hero size.
                             onerror covers videos with no HD upload. --}}
                        <img src="{{ $slide['banner_hq'] ?? $slide['banner'] }}" alt=""
                             @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async"
                             @if (! empty($slide['banner_hq'])) onerror="this.onerror=null;this.src='{{ $slide['banner'] }}'" @endif
                             class="relative">
                    @endif
                </div>
            @endforeach

            <div class="sx-wrap sx-hero-inner">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div class="min-w-0 max-w-2xl">
                        @foreach ($heroSlides as $index => $slide)
                            <div class="grid gap-5" x-show="current === {{ $index }}"
                                 x-transition:enter="transition ease-out duration-500 delay-200 motion-reduce:duration-0"
                                 x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
                                 @if ($index > 0) style="display: none;" @endif>

                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="sx-glass-chip"><i data-lucide="sparkles" aria-hidden="true"></i>{{ __('media.public.featured') }}</span>
                                    @if ($slide['is_trending'])
                                        <span class="sx-glass-chip"><i data-lucide="trending-up" aria-hidden="true"></i>{{ __('media.public.trending_now') }}</span>
                                    @endif
                                </div>

                                <h1 class="sx-hero-title sx-clamp-3" title="{{ $slide['title'] }}">{{ $slide['title'] }}</h1>

                                <div class="sx-hero-meta">
                                    <span class="sx-glass-chip"><i data-lucide="list-video" aria-hidden="true"></i><span class="tabular-nums">{{ __('media.public.episodes_count', ['count' => $slide['episodes_count']]) }}</span></span>
                                    <span class="sx-glass-chip sx-clamp-1 max-w-[14rem]" title="{{ $slide['category_label'] }}">{{ $slide['category_label'] }}</span>
                                    @if ($slide['year'])
                                        <span class="sx-glass-chip tabular-nums">{{ $slide['year'] }}</span>
                                    @endif
                                    <span class="sx-glass-chip">{{ $slide['maturity_label'] }}</span>
                                </div>

                                @if ($slide['description'])
                                    <p class="sx-hero-lead sx-clamp-2">{{ strip_tags((string) $slide['description']) }}</p>
                                @endif

                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <a href="{{ $slide['url'] }}" class="sx-btn is-light"><i data-lucide="play" aria-hidden="true"></i>{{ __('media.public.play_now') }}</a>
                                    <button type="button" @click="toggleMyList({{ $slide['id'] }})"
                                            :aria-pressed="inMyList({{ $slide['id'] }}) ? 'true' : 'false'"
                                            :disabled="isPending({{ $slide['id'] }})"
                                            :title="inMyList({{ $slide['id'] }}) ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"
                                            :aria-label="inMyList({{ $slide['id'] }}) ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"
                                            class="sx-btn is-glass !w-11 !px-0 disabled:opacity-60">
                                        <i data-lucide="plus" x-show="!inMyList({{ $slide['id'] }})" aria-hidden="true"></i>
                                        <i data-lucide="check" x-show="inMyList({{ $slide['id'] }})" x-cloak aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        {{-- Slide dots (the portrait navigator is hidden below md) --}}
                        @if (count($heroSlides) > 1)
                            <div class="mt-8 flex md:hidden gap-2">
                                @foreach ($heroSlides as $index => $slide)
                                    <button type="button" @click="go({{ $index }})"
                                            aria-label="{{ __('media.public.go_to_slide', ['number' => $index + 1]) }}"
                                            title="{{ $slide['title'] }}"
                                            :aria-current="current === {{ $index }} ? 'true' : 'false'"
                                            {{-- Dots ride on the hero artwork, so the track is a white
                                                 wash rather than an sx surface token. --}}
                                            class="relative h-1.5 overflow-hidden rounded-full bg-[rgb(255_255_255/0.25)] transition-all duration-300 motion-reduce:transition-none"
                                            :class="current === {{ $index }} ? 'w-10' : 'w-6 hover:bg-[rgb(255_255_255/0.45)]'">
                                        <span x-show="current === {{ $index }}" class="absolute inset-y-0 start-0 rounded-full"
                                              style="background: var(--sx-grad)" :style="'width: ' + progress + '%'"></span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Portrait navigator (md+) --}}
                    @if (count($heroSlides) > 1)
                        <div class="hidden md:flex items-center gap-3 shrink-0" role="tablist" aria-label="{{ __('media.public.featured') }}">
                            @foreach ($heroSlides as $index => $slide)
                                <button type="button" role="tab" @click="go({{ $index }})"
                                        aria-label="{{ __('media.public.go_to_slide', ['number' => $index + 1]) }}"
                                        title="{{ $slide['title'] }}"
                                        :aria-selected="current === {{ $index }} ? 'true' : 'false'"
                                        class="sx-avatar transition-all duration-300 motion-reduce:transition-none"
                                        :class="current === {{ $index }}
                                            ? '!w-20 !h-20 outline outline-2 outline-white/90 outline-offset-4'
                                            : '!w-14 !h-14 opacity-75 hover:opacity-100'">
                                    @if ($slide['poster'] ?? $slide['banner'])
                                        <img src="{{ $slide['poster'] ?? $slide['banner'] }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                                    @endif
                                    <span class="text-lg font-extrabold">{{ \Illuminate\Support\Str::substr($slide['title'], 0, 1) }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Category pills — filter the per-category rows client-side; "all" restores every row. --}}
    @if (count($categoryRows) > 1)
        <div class="sticky top-20 z-40 mb-2 border-b border-[color:var(--sx-line)] bg-[color:var(--sx-bg)]/85 py-3 backdrop-blur-xl">
            <div class="sx-wrap">
                <div class="sx-scroller" role="group" aria-label="{{ __('media.public.page_title') }}">
                    <button type="button" @click="activeCategory = 'all'" class="sx-chip shrink-0"
                            :class="activeCategory === 'all' && 'is-active'" :aria-pressed="(activeCategory === 'all').toString()">
                        {{ __('media.public.filter_all') }}
                    </button>
                    @foreach ($categoryRows as $categoryKey => $row)
                        <button type="button" @click="activeCategory = '{{ $categoryKey }}'" class="sx-chip shrink-0"
                                :class="activeCategory === '{{ $categoryKey }}' && 'is-active'"
                                :aria-pressed="(activeCategory === '{{ $categoryKey }}').toString()">
                            <span class="sx-clamp-1 max-w-[12rem]" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="sx-wrap grid gap-10 pt-10">
        @if (! $hasAnyContent)
            <x-public.editorial.empty-state icon="clapperboard"
                :title="__('media.public.no_programs_title')" :hint="__('media.public.no_programs_message')"
                :action-url="route('public.home')" :action-label="__('media.public.cta_home')" />
        @else
            {{-- Featured — programs hand-picked via the admin is_featured flag --}}
            @if (! empty($featured))
                <x-public.media.rail :title="__('media.public.featured_programs')" icon="sparkles">
                    @foreach ($featured as $card)
                        <x-public.media.program-card :card="$card" class="{{ $railCard }}" :eager="$loop->first" />
                    @endforeach
                </x-public.media.rail>
            @endif

            {{-- Live channels — only with 2+ channels; a single channel is promoted
                 by the spotlight at the bottom instead. --}}
            @if (count($liveChannels) > 1)
                <x-public.media.rail :title="__('media.public.live_channels')" icon="radio" :view-all-url="route('public.media.live.index')">
                    @foreach ($liveChannels as $card)
                        <x-public.media.live-card :card="$card" class="{{ $railCard }}" />
                    @endforeach
                </x-public.media.rail>
            @endif

            {{-- Continue Watching — lazy-loaded per user so the page markup stays
                 user-free (safe for page/edge caching). Skeleton while fetching;
                 the section disappears when there is nothing to resume.

                 The only row that does NOT use <x-public.media.rail>: it needs two
                 alternate tracks (skeleton, then the fetched cards) and the rail owns
                 a single x-ref="track". The head below mirrors the rail's exactly —
                 keep them in step. --}}
            @auth
                <section class="sx-section" x-data="continueWatchingRow()" x-show="loading || items.length > 0" x-cloak
                         aria-label="{{ __('media.public.continue_watching') }}">
                    <div x-data="scrollableRow()">
                        <header class="sx-section-head">
                            <span class="sx-section-icon"><i data-lucide="history" aria-hidden="true"></i></span>
                            <div class="min-w-0 flex-1">
                                <h2 class="sx-section-title">{{ __('media.public.continue_watching') }}</h2>
                            </div>
                            <div class="hidden sm:flex items-center gap-2 shrink-0" x-show="canScrollBack || canScrollForward" x-cloak>
                                <button type="button" @click="scrollRow(-1)" :disabled="!canScrollBack" class="sx-btn !min-h-11 !w-11 !px-0 disabled:opacity-40"
                                        aria-label="{{ __('media.public.scroll_back') }}" title="{{ __('media.public.scroll_back') }}">
                                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                                </button>
                                <button type="button" @click="scrollRow(1)" :disabled="!canScrollForward" class="sx-btn !min-h-11 !w-11 !px-0 disabled:opacity-40"
                                        aria-label="{{ __('media.public.scroll_forward') }}" title="{{ __('media.public.scroll_forward') }}">
                                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                                </button>
                            </div>
                        </header>

                        {{-- Skeleton shaped like the cards it stands in for --}}
                        <div x-show="loading" aria-hidden="true" class="sx-scroller !gap-4 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                            <template x-for="i in 5" :key="i">
                                <div class="sx-card {{ $railCard }}">
                                    <div class="sx-card-media sx-skeleton !rounded-none"></div>
                                    <div class="sx-card-body">
                                        <div class="sx-skeleton h-5 w-3/4"></div>
                                        <div class="sx-skeleton h-4 w-1/2"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="!loading" x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="sx-scroller snap-x !gap-4 pb-2 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                            <template x-for="item in items" :key="item.id">
                                <a :href="item.url" :title="item.title" class="sx-card group {{ $railCard }}">
                                    <div class="sx-card-media">
                                        <template x-if="item.thumbnail">
                                            <img :src="item.thumbnail" alt="" loading="lazy" decoding="async">
                                        </template>
                                        <span class="sx-card-glyph" aria-hidden="true"><i data-lucide="monitor-play"></i></span>
                                        <span class="sx-card-type">
                                            <span class="sx-glass-chip" x-text="episodeLabel.replace(':number', item.number)"></span>
                                        </span>
                                        {{-- dir=ltr must be on an inner element: inset-inline-end resolves
                                             against the element's own direction, which would flip end-3 in RTL --}}
                                        <span class="absolute bottom-2.5 end-3 z-[1] text-xs font-bold text-white" x-show="item.duration">
                                            <span dir="ltr" class="tabular-nums" x-text="item.duration"></span>
                                        </span>
                                    </div>
                                    <div class="sx-card-body">
                                        <h3 class="sx-card-title" x-text="item.title"></h3>
                                        <p class="sx-card-desc !line-clamp-1" x-text="`${item.program_title} · ${item.progress_percent}% ${completeLabel}`"></p>
                                        <div class="h-1 overflow-hidden rounded-full bg-[color:var(--sx-surface-2)]"
                                             role="img" :aria-label="`${item.progress_percent}% ${completeLabel}`">
                                            <span class="block h-full rounded-full" style="background: var(--sx-grad)" :style="`width: ${item.progress_percent}%`"></span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </section>
            @endauth

            {{-- Top 10 — ranked by real views_count --}}
            @if (! empty($popular))
                <x-public.media.rail :title="__('media.public.popular_shows')" :subtitle="__('media.public.this_week')" icon="trophy">
                    @foreach ($popular as $index => $card)
                        <x-public.media.program-card :card="$card" :rank="$index + 1" class="{{ $railCard }}" />
                    @endforeach
                </x-public.media.rail>
            @endif

            {{-- Trending --}}
            @if (! empty($trending))
                <x-public.media.rail :title="__('media.public.trending_now')" icon="flame">
                    @foreach ($trending as $card)
                        <x-public.media.program-card :card="$card" class="{{ $railCard }}" />
                    @endforeach
                </x-public.media.rail>
            @endif

            {{-- Per-category rows — the pills above narrow the stack to one.
                 "View all" mirrors the pill for that category (only useful with 2+ rows). --}}
            @foreach ($categoryRows as $categoryKey => $row)
                <x-public.media.rail :title="$row['label']" icon="layers"
                    :view-all-click="count($categoryRows) > 1 ? 'activeCategory = \''.$categoryKey.'\'' : null"
                    x-show="activeCategory === 'all' || activeCategory === '{{ $categoryKey }}'">
                    @foreach ($row['programs'] as $card)
                        <x-public.media.program-card :card="$card" class="{{ $railCard }}" />
                    @endforeach
                </x-public.media.rail>
            @endforeach

            {{-- Quran reciters teaser — round portraits linking into the Quran section. --}}
            @if (! empty($reciters))
                <x-public.media.rail :title="__('media.public.quran_reciters')" icon="mic" :view-all-url="route('public.quran.reciters')">
                    @foreach ($reciters as $reciter)
                        <a href="{{ $reciter['url'] }}" title="{{ $reciter['name'] }}"
                           class="sx-surface group w-36 shrink-0 snap-start p-4 text-center transition-colors hover:border-[color:var(--sx-accent)] sm:w-40">
                            <span class="sx-avatar is-lg mx-auto">
                                @if ($reciter['image'])
                                    <img src="{{ $reciter['image'] }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                                @endif
                                <span class="text-2xl font-extrabold">{{ mb_substr($reciter['name'], 0, 1) }}</span>
                            </span>
                            <p class="mt-3 font-bold text-[color:var(--sx-ink)] sx-clamp-2 transition-colors group-hover:text-[color:var(--sx-accent)]">{{ $reciter['name'] }}</p>
                            @if ($reciter['country_name'])
                                <p class="mt-1 text-xs text-[color:var(--sx-muted)] sx-clamp-1">
                                    <span aria-hidden="true">{{ $reciter['country_flag'] }}</span> {{ $reciter['country_name'] }}
                                </p>
                            @endif
                        </a>
                    @endforeach
                </x-public.media.rail>
            @endif

            {{-- Live spotlight — the primary channel. The channels row above only
                 renders with 2+ channels so a lone channel is not shown twice. --}}
            @if (! empty($liveChannels))
                @php $lead = $liveChannels[0]; @endphp
                {{-- Labelled by the channel, NOT by "live channels": with a single
                     channel the row above does not render, and LiveChannelTest
                     asserts that string is absent from the CTA-banner variant. --}}
                <section aria-label="{{ $lead['title'] }}">
                    <a href="{{ $lead['url'] }}" title="{{ $lead['title'] }}" class="sx-surface sx-spotlight group">
                        <div class="sx-spotlight-media">
                            @if ($lead['thumbnail'])
                                <img src="{{ $lead['thumbnail'] }}" alt="" loading="lazy" decoding="async"
                                     class="transition-transform duration-700 group-hover:scale-[1.03]" onerror="this.remove()">
                            @endif
                        </div>
                        <div class="sx-spotlight-body">
                            <div class="flex flex-wrap items-center gap-2">
                                <span @class(['sx-chip', 'is-danger' => $lead['is_live'], 'is-active' => ! $lead['is_live']])>
                                    <i data-lucide="radio" aria-hidden="true"></i>
                                    {{ $lead['is_live'] ? __('media.public.live_now') : __('media.public.live_status_'.$lead['status']) }}
                                </span>
                                @if ($lead['views_count'] > 0)
                                    <span class="sx-chip"><i data-lucide="eye" aria-hidden="true"></i><span class="tabular-nums">{{ number_format($lead['views_count']) }}</span> {{ __('media.public.views') }}</span>
                                @endif
                            </div>
                            <h2 class="sx-section-title sx-clamp-2 transition-colors group-hover:text-[color:var(--sx-accent)]">{{ $lead['title'] }}</h2>
                            <span class="sx-btn is-primary justify-self-start"><i data-lucide="radio" aria-hidden="true"></i>{{ __('media.public.watch_live') }}</span>
                        </div>
                    </a>
                </section>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
    function mediaHub() {
        return {
            isAuthenticated: @json(auth()->check()),
            storeUrl: @js(route('public.favorites.store')),
            destroyUrl: @js(route('public.favorites.destroy')),
            activeCategory: 'all',
            myList: [],
            pending: [],
            init() {
                if (this.isAuthenticated) {
                    // Server truth for signed-in visitors (favorites table).
                    this.myList = @json($myListIds ?? []);
                    return;
                }
                // Guest/offline fallback: device-local list of program ids.
                try { this.myList = JSON.parse(localStorage.getItem('media_my_list') || '[]'); } catch (e) { this.myList = []; }
            },
            inMyList(id) { return this.myList.includes(id); },
            isPending(id) { return this.pending.includes(id); },
            async toggleMyList(id) {
                if (this.isPending(id)) return;

                const adding = !this.inMyList(id);

                // Optimistic toggle.
                this.myList = adding ? [...this.myList, id] : this.myList.filter(item => item !== id);
                this.refreshIcons();

                if (!this.isAuthenticated) {
                    try { localStorage.setItem('media_my_list', JSON.stringify(this.myList)); } catch (e) {}
                    return;
                }

                this.pending.push(id);
                try {
                    const response = await fetch(adding ? this.storeUrl : this.destroyUrl, {
                        method: adding ? 'POST' : 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({ type: 'media', id: id }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || payload.success === false) throw new Error('favorite_failed');
                    if (this.$store.toast && payload.message) this.$store.toast.push(payload.message, 'success');
                } catch (e) {
                    // Roll back the optimistic toggle.
                    this.myList = adding ? this.myList.filter(item => item !== id) : [...this.myList, id];
                    if (this.$store.toast) this.$store.toast.push(@js(__('media.public.my_list_error')), 'error');
                } finally {
                    this.pending = this.pending.filter(item => item !== id);
                    this.refreshIcons();
                }
            },
            refreshIcons() {
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            },
        };
    }

    // Hero carousel with per-slide progress fill (7s per slide).
    // Named mediaHeroCarousel — app.js registers a different Alpine.data('heroCarousel').
    function mediaHeroCarousel(total) {
        return {
            current: 0,
            total: total,
            progress: 0,
            timer: null,
            progressTimer: null,
            startedAt: 0,
            reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            start() {
                if (this.reduced || this.total < 2 || this.timer) return;
                this.startedAt = Date.now();
                this.timer = setInterval(() => { this.next(); }, 7000);
                this.progressTimer = setInterval(() => {
                    this.progress = Math.min(100, ((Date.now() - this.startedAt) / 7000) * 100);
                }, 100);
            },
            stop() {
                clearInterval(this.timer); this.timer = null;
                clearInterval(this.progressTimer); this.progressTimer = null;
            },
            next() {
                this.current = (this.current + 1) % this.total;
                this.startedAt = Date.now();
                this.progress = 0;
                this.refreshIcons();
            },
            go(index) {
                this.stop();
                this.current = index;
                this.progress = 0;
                this.refreshIcons();
            },
            refreshIcons() {
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            },
        };
    }

    // Lazy-loads the signed-in visitor's Continue Watching cards after the
    // (cacheable, user-free) page renders. Shimmer while fetching; on empty
    // or error the section collapses entirely.
    function continueWatchingRow() {
        return {
            loading: true,
            items: [],
            episodeLabel: @js(__('media.public.episode_label')),
            completeLabel: @js(__('media.public.complete')),
            async init() {
                try {
                    const response = await fetch(@js(route('public.media.continue-watching')), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (response.ok && payload.success) {
                        this.items = Array.isArray(payload.items) ? payload.items : [];
                    }
                } catch (e) { /* section collapses on failure */ } finally {
                    this.loading = false;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                        // scrollableRow recomputes its arrows on resize.
                        window.dispatchEvent(new Event('resize'));
                    });
                }
            },
        };
    }

    // Smart horizontal row: arrows are live only while the track overflows.
    function scrollableRow() {
        return {
            canScrollBack: false,
            canScrollForward: false,
            isRtl: @json($isRtl),
            init() {
                this.$nextTick(() => {
                    this.checkScroll();
                    window.addEventListener('resize', () => this.checkScroll(), { passive: true });
                });
            },
            checkScroll() {
                const track = this.$refs.track;
                if (!track) return;
                const overflow = track.scrollWidth - track.clientWidth;
                if (overflow <= 10) {
                    this.canScrollBack = false;
                    this.canScrollForward = false;
                    return;
                }
                // RTL scrollLeft is 0 at the start and negative as you scroll.
                const position = Math.abs(track.scrollLeft);
                this.canScrollBack = position > 10;
                this.canScrollForward = position < overflow - 10;
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            },
            scrollRow(direction) {
                const track = this.$refs.track;
                if (!track) return;
                const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                track.scrollBy({
                    left: direction * track.clientWidth * 0.8 * (this.isRtl ? -1 : 1),
                    behavior: reduced ? 'auto' : 'smooth',
                });
                setTimeout(() => this.checkScroll(), 450);
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush
@endsection
