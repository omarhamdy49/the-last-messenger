@extends('layouts.public')

@section('title', __('media.public.page_title'))
@section('meta_description', __('media.public.page_description'))

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div x-data="mediaHub()"
     class="min-h-screen font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300 overflow-x-clip">

    @php $hasAnyContent = ! empty($heroSlides) || ! empty($featured) || ! empty($trending) || ! empty($popular) || ! empty($categoryRows); @endphp

    {{-- ============================================================ --}}
    {{-- HERO — full-height featured carousel with progress dots      --}}
    {{-- ============================================================ --}}
    @if (! empty($heroSlides))
        <section x-data="mediaHeroCarousel({{ count($heroSlides) }})" x-init="start()"
                 @mouseenter="stop()" @mouseleave="start()"
                 class="relative min-h-[70vh] md:min-h-[calc(100vh-4rem)] flex items-center overflow-hidden bg-gray-950"
                 aria-roledescription="carousel" aria-label="{{ __('media.public.featured') }}">
            {{-- Slide backgrounds --}}
            @foreach ($heroSlides as $index => $slide)
                <div x-show="current === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 motion-reduce:duration-0"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-out duration-300 motion-reduce:duration-0"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     @if ($index > 0) style="display: none;" @endif
                     class="absolute inset-0">
                    @if ($slide['banner'])
                        <img src="{{ $slide['banner'] }}" alt="" @if ($index > 0) loading="lazy" @endif
                             class="absolute inset-0 w-full h-full object-cover scale-105">
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br {{ $slide['gradient'] }}">
                            <div class="absolute inset-0 opacity-10" aria-hidden="true">
                                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                                    <defs><pattern id="hero-grid-{{ $index }}" width="40" height="40" patternUnits="userSpaceOnUse">
                                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1"/>
                                    </pattern></defs>
                                    <rect width="100%" height="100%" fill="url(#hero-grid-{{ $index }})"/>
                                </svg>
                            </div>
                        </div>
                    @endif
                    {{-- Scrim: lighter in light mode so the scheme toggle is visible in the hero --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/40 to-black/10 dark:from-black/95 dark:via-black/60 dark:to-black/20" aria-hidden="true"></div>
                </div>
            @endforeach

            {{-- Blend the hero bottom into the page background (light/dark aware) --}}
            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-gray-50 to-transparent dark:from-gray-950 pointer-events-none" aria-hidden="true"></div>

            {{-- Slide content --}}
            <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 w-full">
                <div class="max-w-3xl">
                    @foreach ($heroSlides as $index => $slide)
                        <div x-show="current === {{ $index }}"
                             x-transition:enter="transition ease-out duration-500 delay-200 motion-reduce:duration-0"
                             x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
                             @if ($index > 0) style="display: none;" @endif>

                            <div class="flex flex-wrap items-center gap-3 mb-6">
                                <span class="inline-flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg uppercase tracking-wide">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>{{ __('media.public.featured') }}
                                </span>
                                @if ($slide['is_trending'])
                                    <span class="inline-flex items-center gap-1.5 glass !bg-white/10 text-white text-sm px-3 py-1.5 rounded-full">
                                        <i data-lucide="trending-up" class="w-4 h-4"></i>{{ __('media.public.trending_now') }}
                                    </span>
                                @endif
                                <span class="glass !bg-white/10 text-white text-xs px-3 py-1.5 rounded-full">{{ $slide['category_label'] }}</span>
                            </div>

                            <h1 class="text-4xl sm:text-5xl md:text-7xl font-black text-white mb-6 leading-tight break-words">{{ $slide['title'] }}</h1>

                            <div class="flex flex-wrap items-center gap-4 mb-6 text-sm text-gray-200">
                                @if ($slide['rating'] > 0)
                                    <span class="flex items-center gap-1 text-yellow-400 font-semibold">
                                        <i data-lucide="star" class="w-5 h-5"></i>{{ number_format($slide['rating'], 1) }}
                                    </span>
                                @endif
                                @if ($slide['year'])<span>{{ $slide['year'] }}</span>@endif
                                <span class="px-3 py-1 border border-gray-400/60 rounded text-xs">{{ $slide['maturity_label'] }}</span>
                                <span>{{ __('media.public.episodes_count', ['count' => $slide['episodes_count']]) }}</span>
                            </div>

                            <p class="text-base md:text-lg text-gray-200 mb-8 leading-relaxed max-w-2xl line-clamp-3 break-words">{{ strip_tags((string) $slide['description']) }}</p>

                            <div class="flex flex-wrap gap-4">
                                <a href="{{ $slide['url'] }}"
                                   {{-- bg-[#ffffff] (not bg-white): app.css force-darkens .bg-white in dark mode --}}
                                   class="group inline-flex items-center gap-3 bg-[#ffffff] text-gray-900 font-bold text-base md:text-lg px-7 md:px-8 py-3.5 md:py-4 rounded-full shadow-2xl hover:bg-gray-100 hover:scale-105 motion-reduce:transform-none transition-all duration-300">
                                    <i data-lucide="play" class="w-6 h-6 group-hover:scale-110 motion-reduce:transform-none transition-transform"></i>{{ __('media.public.play_now') }}
                                </a>
                                <button type="button" @click="toggleMyList({{ $slide['id'] }})"
                                        :aria-pressed="inMyList({{ $slide['id'] }}) ? 'true' : 'false'"
                                        :disabled="isPending({{ $slide['id'] }})"
                                        class="group inline-flex items-center gap-3 glass !bg-white/10 text-white font-bold text-base md:text-lg px-7 md:px-8 py-3.5 md:py-4 rounded-full hover:bg-white/20 hover:scale-105 motion-reduce:transform-none transition-all duration-300 disabled:opacity-60">
                                    <i data-lucide="plus" class="w-5 h-5" x-show="!inMyList({{ $slide['id'] }})"></i>
                                    <i data-lucide="check" class="w-5 h-5 text-[#64CCC5]" x-show="inMyList({{ $slide['id'] }})" x-cloak></i>
                                    <span x-text="inMyList({{ $slide['id'] }}) ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"></span>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    {{-- Progress navigation dots --}}
                    @if (count($heroSlides) > 1)
                        <div class="flex gap-2 mt-12">
                            @foreach ($heroSlides as $index => $slide)
                                <button type="button" @click="go({{ $index }})"
                                        aria-label="{{ __('media.public.go_to_slide', ['number' => $index + 1]) }}"
                                        :aria-current="current === {{ $index }} ? 'true' : 'false'"
                                        class="relative h-1.5 rounded-full overflow-hidden transition-all duration-300 motion-reduce:transition-none"
                                        :class="current === {{ $index }} ? 'w-12 bg-white/30' : 'w-8 bg-white/20 hover:bg-white/40'">
                                    <span x-show="current === {{ $index }}"
                                          class="absolute inset-y-0 start-0 bg-gradient-to-r from-[#0056b3] to-[#218838] rounded-full"
                                          :style="'width: ' + progress + '%'"></span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Scroll indicator --}}
            <div class="absolute bottom-6 inset-x-0 z-10 hidden md:flex justify-center pointer-events-none" aria-hidden="true">
                <div class="flex flex-col items-center gap-2 text-gray-500 dark:text-white/60 animate-bounce motion-reduce:animate-none">
                    <span class="text-xs uppercase tracking-wider">{{ __('media.public.scroll_down') }}</span>
                    <i data-lucide="chevron-down" class="w-5 h-5"></i>
                </div>
            </div>
        </section>
    @endif

    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ============================================================ --}}
        {{-- STICKY CATEGORY PILLS                                        --}}
        {{-- ============================================================ --}}
        <div class="sticky top-16 z-40 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3 mb-6 glass backdrop-blur-md">
            <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto scrollbar-hide" role="tablist"
                 aria-label="{{ __('media.public.page_title') }}">
                @foreach ($categories as $category)
                    <button type="button" role="tab"
                            @click="activeCategory = '{{ $category['value'] }}'"
                            :aria-selected="activeCategory === '{{ $category['value'] }}' ? 'true' : 'false'"
                            :class="activeCategory === '{{ $category['value'] }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:text-white'"
                            class="whitespace-nowrap shrink-0 px-5 sm:px-6 py-2 rounded-full text-sm font-semibold transition-all hover:-translate-y-0.5 motion-reduce:transform-none">
                        {{ $category['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        @if (! $hasAnyContent)
            {{-- Global empty state --}}
            <div class="rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/40 mb-12">
                <x-site.empty-state icon="clapperboard"
                    :title="__('media.public.no_programs_title')"
                    :message="__('media.public.no_programs_message')" class="py-20">
                    <a href="{{ route('public.home') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-md hover:shadow-lg transition-all">
                        <i data-lucide="home" class="w-4 h-4"></i>{{ __('media.public.cta_home') }}
                    </a>
                </x-site.empty-state>
            </div>
        @else

            {{-- Featured row — programs hand-picked via the admin is_featured flag --}}
            @if (! empty($featured))
                <section class="py-8" x-show="activeCategory === 'all'">
                    @include('public.media._row', ['rowTitle' => __('media.public.featured_programs'), 'rowIcon' => 'sparkles', 'cards' => $featured])
                </section>
            @endif

            {{-- Continue Watching (auth users with in-progress episodes) --}}
            @if (! empty($continueWatching))
                <section class="py-8" x-show="activeCategory === 'all'">
                    <div class="relative" x-data="scrollableRow()">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                <span class="w-1 h-8 bg-gradient-to-b from-[#0056b3] to-[#218838] rounded-full" aria-hidden="true"></span>
                                <i data-lucide="history" class="w-6 h-6 text-[#0056b3] dark:text-[#64CCC5]" aria-hidden="true"></i>
                                {{ __('media.public.continue_watching') }}
                            </h2>
                        </div>

                        <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="flex gap-4 overflow-x-auto scrollbar-hide snap-x pb-4 -mx-1 px-1">
                            @foreach ($continueWatching as $item)
                                <a href="{{ $item['url'] }}"
                                   class="premium-card group block w-72 sm:w-80 lg:w-96 shrink-0 snap-start rounded-2xl overflow-hidden shadow-xl bg-white dark:bg-gray-900">
                                    <div class="relative aspect-video bg-gradient-to-br from-gray-800 to-gray-700">
                                        @if ($item['thumbnail'])
                                            <img src="{{ $item['thumbnail'] }}" alt="" loading="lazy"
                                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 motion-reduce:transform-none transition-transform duration-500">
                                        @endif
                                        <div class="card-overlay absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-0 transition-opacity duration-300 flex items-center justify-center motion-reduce:transition-none">
                                            <span class="w-14 h-14 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center">
                                                <i data-lucide="play" class="w-7 h-7 text-gray-900 ms-0.5"></i>
                                            </span>
                                        </div>
                                        @if ($item['duration'])
                                            <span class="absolute top-3 end-3 glass !bg-black/50 px-3 py-1 rounded-full text-white text-xs font-semibold inline-flex items-center gap-1">
                                                <i data-lucide="clock" class="w-3 h-3"></i>{{ $item['duration'] }}
                                            </span>
                                        @endif
                                        {{-- Progress bar --}}
                                        <div class="absolute bottom-0 inset-x-0 h-1.5 bg-white/20">
                                            <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838]" style="width: {{ $item['progress_percent'] }}%"></div>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <h3 class="font-bold text-base text-gray-900 dark:text-white mb-1 line-clamp-1 break-words group-hover:text-[#64CCC5] transition-colors">{{ $item['title'] }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-1">{{ $item['program_title'] }} · {{ __('media.public.episode_label', ['number' => $item['number']]) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item['progress_percent'] }}% {{ __('media.public.complete') }} · {{ __('media.public.resume') }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
                                aria-label="{{ __('media.public.scroll_back') }}"
                                class="absolute start-0 top-1/3 -ms-3 sm:-ms-5 w-11 h-11 sm:w-14 sm:h-14 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
                        </button>
                        <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
                                aria-label="{{ __('media.public.scroll_forward') }}"
                                class="absolute end-0 top-1/3 -me-3 sm:-me-5 w-11 h-11 sm:w-14 sm:h-14 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </section>
            @endif

            {{-- Popular Shows — ranked posters (real views_count ordering) --}}
            @if (! empty($popular))
                <section class="py-8" x-show="activeCategory === 'all'">
                    <div class="relative" x-data="scrollableRow()">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                <span class="w-1 h-8 bg-gradient-to-b from-[#0056b3] to-[#218838] rounded-full" aria-hidden="true"></span>
                                <i data-lucide="flame" class="w-6 h-6 text-[#0056b3] dark:text-[#64CCC5]" aria-hidden="true"></i>
                                {{ __('media.public.popular_shows') }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 hidden sm:inline">{{ __('media.public.this_week') }}</span>
                            </h2>
                        </div>

                        <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="flex gap-4 overflow-x-auto scrollbar-hide snap-x pb-4 -mx-1 px-1">
                            @foreach ($popular as $index => $card)
                                @include('public.media._rank-card', ['card' => $card, 'rank' => $index + 1])
                            @endforeach
                        </div>

                        <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
                                aria-label="{{ __('media.public.scroll_back') }}"
                                class="absolute start-0 top-1/3 -ms-3 sm:-ms-5 w-11 h-11 sm:w-14 sm:h-14 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
                        </button>
                        <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
                                aria-label="{{ __('media.public.scroll_forward') }}"
                                class="absolute end-0 top-1/3 -me-3 sm:-me-5 w-11 h-11 sm:w-14 sm:h-14 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </section>
            @endif

            {{-- Trending row --}}
            @if (! empty($trending))
                <section class="py-8" x-show="activeCategory === 'all'">
                    @include('public.media._row', ['rowTitle' => __('media.public.trending_now'), 'rowIcon' => 'trending-up', 'cards' => $trending])
                </section>
            @endif

            {{-- Per-category rows --}}
            @foreach ($categoryRows as $categoryKey => $row)
                <section class="py-8" x-show="activeCategory === 'all' || activeCategory === '{{ $categoryKey }}'">
                    @include('public.media._row', ['rowTitle' => $row['label'], 'rowIcon' => ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'][$categoryKey] ?? 'monitor-play', 'cards' => $row['programs']])
                </section>
            @endforeach

            {{-- Empty state for a selected category with no programs --}}
            <div x-show="activeCategory !== 'all' && !availableCategories.includes(activeCategory)" x-cloak class="py-6">
                <div class="rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/40">
                    <x-site.empty-state icon="clapperboard"
                        :title="__('media.public.no_programs_title')"
                        :message="__('media.public.no_programs_message')" class="py-14">
                        <button type="button" @click="activeCategory = 'all'"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-primary border border-primary/30 hover:bg-primary/5 transition-colors">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>{{ __('media.public.filter_all') }}
                        </button>
                    </x-site.empty-state>
                </div>
            </div>

            <div class="pb-12"></div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function mediaHub() {
        return {
            activeCategory: 'all',
            availableCategories: @json(array_keys($categoryRows)),
            isAuthenticated: @json(auth()->check()),
            storeUrl: @js(route('public.favorites.store')),
            destroyUrl: @js(route('public.favorites.destroy')),
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

    // Hero carousel with per-slide progress fill (prototype: 7s per slide).
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

    // Smart horizontal row: floating arrows only when the track overflows.
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
