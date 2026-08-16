@extends('layouts.public')

@section('title', __('media.public.page_title'))
@section('meta_description', __('media.public.page_description'))

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div x-data="mediaHub()"
     class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300 overflow-x-clip">

    @php $hasAnyContent = ! empty($heroSlides) || ! empty($featured) || ! empty($trending) || ! empty($popular) || ! empty($liveChannels) || ! empty($categoryRows); @endphp

    {{-- ============================================================ --}}
    {{-- HERO — Shahid-style full-bleed featured carousel             --}}
    {{-- ============================================================ --}}
    @if (! empty($heroSlides))
        <section x-data="mediaHeroCarousel({{ count($heroSlides) }})" x-init="start()"
                 @mouseenter="stop()" @mouseleave="start()"
                 {{-- svh: stable small-viewport unit — vh jumps as mobile browser chrome collapses --}}
                 class="relative min-h-[78svh] md:min-h-[calc(100vh-4rem)] flex items-end overflow-hidden bg-gray-950"
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
                        {{-- Full-bleed hero: render maxresdefault (1280px) when the
                             banner is a CDN thumbnail, or the 480px hqdefault
                             visibly pixelates at this size. onerror covers videos
                             with no HD upload. --}}
                        <img src="{{ $slide['banner_hq'] ?? $slide['banner'] }}" alt="" @if ($index > 0) loading="lazy" @endif
                             @if (! empty($slide['banner_hq'])) onerror="this.onerror=null;this.src='{{ $slide['banner'] }}'" @endif
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
                    {{-- Scrim: side gradient toward the content (Shahid) + bottom fade --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-black/10 dark:from-black/95 dark:via-black/55 dark:to-black/20" aria-hidden="true"></div>
                    {{-- Side scrim from the content (start) side — Tailwind has no logical gradient direction --}}
                    <div class="absolute inset-0 {{ $isRtl ? 'bg-gradient-to-l' : 'bg-gradient-to-r' }} from-black/60 via-black/20 to-transparent" aria-hidden="true"></div>
                </div>
            @endforeach

            {{-- Blend the hero bottom into the page background (light/dark aware).
                 Taller, two-stop fade so the dark slider melts into the light
                 page surface without a visible seam against the sections below. --}}
            <div class="absolute inset-x-0 bottom-0 h-48 md:h-56 bg-gradient-to-t from-gray-50 via-gray-50/70 to-transparent dark:from-gray-950 dark:via-gray-950/70 pointer-events-none" aria-hidden="true"></div>

            {{-- Slide content — Shahid: anchored to the bottom, title first,
                 kicker line under the title, CTA row last --}}
            <div class="relative z-10 w-full px-4 sm:px-6 lg:px-14 pt-28 pb-10 md:pb-12">
                <div class="flex items-end justify-between gap-6">
                    <div class="max-w-2xl w-full sm:w-auto min-w-0 mx-auto sm:mx-0 text-center sm:text-start">
                        @foreach ($heroSlides as $index => $slide)
                            <div x-show="current === {{ $index }}"
                                 x-transition:enter="transition ease-out duration-500 delay-200 motion-reduce:duration-0"
                                 x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0"
                                 @if ($index > 0) style="display: none;" @endif>

                                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-white mb-4 leading-tight break-words">{{ $slide['title'] }}</h1>

                                {{-- Kicker under the title (Shahid "TOP … في المسلسلات اليوم" line) --}}
                                <p class="inline-flex items-center gap-2 text-[#64CCC5] text-sm font-bold mb-3">
                                    <i data-lucide="sparkles" class="w-4 h-4" aria-hidden="true"></i>
                                    <span>{{ __('media.public.featured') }}</span>
                                    @if ($slide['is_trending'])
                                        <span class="text-white/80 font-normal border-s border-white/30 ps-2">{{ __('media.public.trending_now') }}</span>
                                    @endif
                                </p>

                                {{-- Shahid meta line: count • genre • year • age chip --}}
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-3 gap-y-2 mb-3 text-sm text-gray-200">
                                    <span>{{ __('media.public.episodes_count', ['count' => $slide['episodes_count']]) }}</span>
                                    <span class="text-white/40" aria-hidden="true">•</span>
                                    <span>{{ $slide['category_label'] }}</span>
                                    @if ($slide['year'])
                                        <span class="text-white/40" aria-hidden="true">•</span>
                                        <span>{{ $slide['year'] }}</span>
                                    @endif
                                    <span class="px-2 py-0.5 border border-gray-400/60 rounded text-xs">{{ $slide['maturity_label'] }}</span>
                                </div>

                                <p class="text-sm md:text-base text-gray-200 mb-6 leading-relaxed max-w-xl line-clamp-2 break-words mx-auto sm:mx-0">{{ strip_tags((string) $slide['description']) }}</p>

                                {{-- Shahid action row: white pill CTA + circular my-list button --}}
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                                    <a href="{{ $slide['url'] }}"
                                       {{-- bg-[#ffffff] (not bg-white): app.css force-darkens .bg-white in dark mode --}}
                                       class="group inline-flex items-center justify-center gap-3 bg-[#ffffff] text-gray-900 font-bold text-base px-8 md:px-14 py-3 rounded-full shadow-2xl hover:bg-gray-100 hover:scale-[1.03] motion-reduce:transform-none transition-all duration-300 w-full sm:w-auto">
                                        <i data-lucide="play" class="w-5 h-5 group-hover:scale-110 motion-reduce:transform-none transition-transform"></i>{{ __('media.public.play_now') }}
                                    </a>
                                    <button type="button" @click="toggleMyList({{ $slide['id'] }})"
                                            :aria-pressed="inMyList({{ $slide['id'] }}) ? 'true' : 'false'"
                                            :disabled="isPending({{ $slide['id'] }})"
                                            :title="inMyList({{ $slide['id'] }}) ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"
                                            class="w-12 h-12 rounded-full glass !bg-white/10 text-white flex items-center justify-center hover:bg-white/20 hover:scale-105 motion-reduce:transform-none transition-all duration-300 disabled:opacity-60">
                                        <span class="sr-only" x-text="inMyList({{ $slide['id'] }}) ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"></span>
                                        <i data-lucide="plus" class="w-5 h-5" x-show="!inMyList({{ $slide['id'] }})"></i>
                                        <i data-lucide="check" class="w-5 h-5 text-[#64CCC5]" x-show="inMyList({{ $slide['id'] }})" x-cloak></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        {{-- Mobile slide dots (thumbnail nav is hidden below md) --}}
                        @if (count($heroSlides) > 1)
                            <div class="flex md:hidden justify-center sm:justify-start gap-2 mt-8">
                                @foreach ($heroSlides as $index => $slide)
                                    <button type="button" @click="go({{ $index }})"
                                            aria-label="{{ __('media.public.go_to_slide', ['number' => $index + 1]) }}"
                                            :aria-current="current === {{ $index }} ? 'true' : 'false'"
                                            class="relative h-1.5 rounded-full overflow-hidden transition-all duration-300 motion-reduce:transition-none"
                                            :class="current === {{ $index }} ? 'w-10 bg-white/30' : 'w-6 bg-white/20 hover:bg-white/40'">
                                        <span x-show="current === {{ $index }}"
                                              class="absolute inset-y-0 start-0 bg-gradient-to-r from-[#0056b3] to-[#218838] rounded-full"
                                              :style="'width: ' + progress + '%'"></span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Shahid circular thumbnail navigation (md+), opposite corner --}}
                    @if (count($heroSlides) > 1)
                        <div class="hidden md:flex items-center gap-3 shrink-0 mb-1" role="tablist"
                             aria-label="{{ __('media.public.featured') }}">
                            @foreach ($heroSlides as $index => $slide)
                                <button type="button" role="tab" @click="go({{ $index }})"
                                        aria-label="{{ __('media.public.go_to_slide', ['number' => $index + 1]) }}"
                                        :aria-selected="current === {{ $index }} ? 'true' : 'false'"
                                        class="relative rounded-full overflow-hidden shrink-0 transition-all duration-300 motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]"
                                        :class="current === {{ $index }}
                                            ? 'w-20 h-20 outline outline-2 outline-white/90 outline-offset-4'
                                            : 'w-14 h-14 opacity-75 hover:opacity-100 hover:scale-105'">
                                    @if ($slide['poster'] ?? $slide['banner'])
                                        <img src="{{ $slide['poster'] ?? $slide['banner'] }}" alt=""
                                             class="absolute inset-0 w-full h-full object-cover">
                                    @else
                                        <span class="absolute inset-0 bg-gradient-to-br {{ $slide['gradient'] }} flex items-center justify-center text-white font-bold text-lg">
                                            {{ \Illuminate\Support\Str::substr($slide['title'], 0, 1) }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Sticky category pills — filter the per-category rows client-side.
         "الكل" restores the full stacked (Shahid-style) row list. --}}
    @if (count($categoryRows) > 1)
        <div class="sticky top-20 z-40 py-3 mb-2 backdrop-blur-xl bg-[#F8F9FA]/85 dark:bg-gray-950/80 border-b border-gray-200/60 dark:border-white/10">
            <div class="w-full px-4 sm:px-6">
                <div class="flex gap-2.5 overflow-x-auto scrollbar-hide">
                    <button type="button" @click="activeCategory = 'all'"
                            class="px-5 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-200 cursor-pointer"
                            :class="activeCategory === 'all'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-[#0056b3]/40'">
                        {{ __('media.public.filter_all') }}
                    </button>
                    @foreach ($categoryRows as $categoryKey => $row)
                        <button type="button" @click="activeCategory = '{{ $categoryKey }}'"
                                class="px-5 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-200 cursor-pointer"
                                :class="activeCategory === '{{ $categoryKey }}'
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                    : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-[#0056b3]/40'">
                            {{ $row['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Shahid layout is full-width fluid with ~24px gutters — no max-width container. --}}
    <div class="w-full px-4 sm:px-6 pt-2">

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
                <section class="py-6">
                    @include('public.media._row', ['rowTitle' => __('media.public.featured_programs'), 'cards' => $featured])
                </section>
            @endif

            {{-- Live channels row — only with 2+ channels; a single channel is
                 promoted by the CTA banner at the bottom instead. --}}
            @if (count($liveChannels) > 1)
                <section class="py-6">
                    <div class="relative group/row" x-data="scrollableRow()">
                        <h2 class="text-xl sm:text-[26px] font-bold leading-snug text-gray-900 dark:text-white mb-4 flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-600/15">
                                <i data-lucide="radio" class="w-5 h-5 text-red-600" aria-hidden="true"></i>
                            </span>
                            <span class="break-words">{{ __('media.public.live_channels') }}</span>
                            <a href="{{ route('public.media.live.index') }}"
                               class="ms-auto shrink-0 inline-flex items-center gap-1 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline">
                                <span>{{ __('media.public.view_all') }}</span>
                                <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                            </a>
                        </h2>

                        <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="flex gap-2 xl:gap-3 overflow-x-auto scrollbar-hide snap-x pb-2 -mx-4 sm:-mx-6 px-4 sm:px-6">
                            @foreach ($liveChannels as $card)
                                @include('public.media._live-card', ['card' => $card])
                            @endforeach
                        </div>

                        <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
                                aria-label="{{ __('media.public.scroll_back') }}"
                                class="absolute start-0 top-1/3 -ms-3 sm:-ms-4 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/95 text-gray-900 dark:bg-gray-900/85 dark:text-white backdrop-blur-sm hidden sm:flex items-center justify-center sm:opacity-0 sm:group-hover/row:opacity-100 focus-visible:opacity-100 hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
                        </button>
                        <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
                                aria-label="{{ __('media.public.scroll_forward') }}"
                                class="absolute end-0 top-1/3 -me-3 sm:-me-4 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/95 text-gray-900 dark:bg-gray-900/85 dark:text-white backdrop-blur-sm hidden sm:flex items-center justify-center sm:opacity-0 sm:group-hover/row:opacity-100 focus-visible:opacity-100 hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </section>
            @endif

            {{-- Continue Watching — lazy-loaded per user so the page markup
                 stays user-free (safe for page/edge caching). Shimmer while
                 fetching; the whole section disappears when there is nothing
                 to resume. Guests never render it at all. --}}
            @auth
                <section class="py-6" x-data="continueWatchingRow()" x-show="loading || items.length > 0" x-cloak>
                    <div class="relative" x-data="scrollableRow()">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('media.public.continue_watching') }}</h2>

                        {{-- Shimmer skeleton (structure mirrors the real cards) --}}
                        <div x-show="loading" aria-hidden="true"
                             class="flex gap-3 overflow-hidden pb-2 -mx-4 sm:-mx-6 px-4 sm:px-6">
                            <template x-for="i in 5" :key="i">
                                <div class="w-[60vw] sm:w-[40vw] md:w-[30vw] lg:w-[24vw] xl:w-[calc((100vw-96px)/5)] shrink-0 animate-pulse">
                                    <div class="aspect-video rounded-xl bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="mt-2 h-3.5 w-3/4 rounded bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="mt-1.5 h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-800"></div>
                                </div>
                            </template>
                        </div>

                        <div x-show="!loading" x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="flex gap-3 overflow-x-auto scrollbar-hide snap-x pb-2 -mx-4 sm:-mx-6 px-4 sm:px-6">
                            <template x-for="item in items" :key="item.id">
                                <div class="w-[60vw] sm:w-[40vw] md:w-[30vw] lg:w-[24vw] xl:w-[calc((100vw-96px)/5)] shrink-0 snap-start">
                                    <a :href="item.url"
                                       class="group relative block aspect-video rounded-xl overflow-hidden bg-gradient-to-br from-gray-800 to-gray-700 shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
                                        <template x-if="item.thumbnail">
                                            <img :src="item.thumbnail" alt="" loading="lazy"
                                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
                                        </template>
                                        <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/85 via-black/30 to-transparent" aria-hidden="true"></div>

                                        {{-- dir=ltr must be on an inner element: inset-inline-end resolves
                                             against the element's own direction, which would flip end-3 in RTL --}}
                                        <span x-show="item.duration" class="absolute bottom-3 end-3 text-white/90 text-xs font-medium"><span dir="ltr" x-text="item.duration"></span></span>
                                        <span class="absolute bottom-2.5 start-3 text-white font-bold text-sm sm:text-base"
                                              x-text="episodeLabel.replace(':number', item.number)"></span>

                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center motion-reduce:transition-none">
                                            <span class="w-12 h-12 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-xl">
                                                <i data-lucide="play" class="w-6 h-6 text-gray-900 ms-0.5"></i>
                                            </span>
                                        </div>

                                        {{-- Watch progress --}}
                                        <div class="absolute bottom-0 inset-x-0 h-1 bg-white/20">
                                            <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838]" :style="`width: ${item.progress_percent}%`"></div>
                                        </div>
                                    </a>
                                    <p class="mt-2 text-start text-xs sm:text-sm font-semibold text-gray-900 dark:text-white line-clamp-1 break-words" x-text="item.title"></p>
                                    <p class="text-start text-xs text-gray-700 dark:text-gray-200 line-clamp-1 break-words"
                                       x-text="`${item.program_title} · ${item.progress_percent}% ${completeLabel}`"></p>
                                </div>
                            </template>
                        </div>

                        <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
                                aria-label="{{ __('media.public.scroll_back') }}"
                                class="absolute start-0 top-1/3 -ms-3 sm:-ms-5 w-11 h-11 sm:w-12 sm:h-12 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
                        </button>
                        <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
                                aria-label="{{ __('media.public.scroll_forward') }}"
                                class="absolute end-0 top-1/3 -me-3 sm:-me-5 w-11 h-11 sm:w-12 sm:h-12 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </section>
            @endauth

            {{-- Top 10 — Shahid-style ranked posters (real views_count ordering) --}}
            @if (! empty($popular))
                <section class="py-6">
                    <div class="relative" x-data="scrollableRow()">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-baseline gap-2">
                            <span>{{ __('media.public.popular_shows') }}</span>
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 hidden sm:inline border-s border-gray-300 dark:border-white/20 ps-2">{{ __('media.public.this_week') }}</span>
                        </h2>

                        <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="flex gap-3 overflow-x-auto scrollbar-hide snap-x pb-2 -mx-4 sm:-mx-6 px-4 sm:px-6">
                            @foreach ($popular as $index => $card)
                                @include('public.media._rank-card', ['card' => $card, 'rank' => $index + 1])
                            @endforeach
                        </div>

                        <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
                                aria-label="{{ __('media.public.scroll_back') }}"
                                class="absolute start-0 top-1/3 -ms-3 sm:-ms-5 w-11 h-11 sm:w-12 sm:h-12 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
                        </button>
                        <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
                                aria-label="{{ __('media.public.scroll_forward') }}"
                                class="absolute end-0 top-1/3 -me-3 sm:-me-5 w-11 h-11 sm:w-12 sm:h-12 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </section>
            @endif

            {{-- Trending row --}}
            @if (! empty($trending))
                <section class="py-6">
                    @include('public.media._row', ['rowTitle' => __('media.public.trending_now'), 'cards' => $trending])
                </section>
            @endif

            {{-- Per-category rows — stacked Shahid-style; the pills bar narrows to one.
                 "View all" mirrors the pill for that category (only useful with 2+ rows). --}}
            @foreach ($categoryRows as $categoryKey => $row)
                <section class="py-6" x-show="activeCategory === 'all' || activeCategory === '{{ $categoryKey }}'">
                    @include('public.media._row', array_filter([
                        'rowTitle' => $row['label'],
                        'cards' => $row['programs'],
                        'rowViewAllClick' => count($categoryRows) > 1 ? "activeCategory = '".$categoryKey."'" : null,
                    ]))
                </section>
            @endforeach

            {{-- Quran reciters teaser — latest 10, round avatars, links into
                 the Quran section (full list + per-reciter profile). --}}
            @if (! empty($reciters))
                <section class="py-6">
                    <div class="relative group/row" x-data="scrollableRow()">
                        <h2 class="text-xl sm:text-[26px] font-bold leading-snug text-gray-900 dark:text-white mb-4 flex items-baseline gap-2">
                            <span class="break-words">{{ __('media.public.quran_reciters') }}</span>
                            <a href="{{ route('public.quran.reciters') }}"
                               class="ms-auto shrink-0 inline-flex items-center gap-1 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline">
                                <span>{{ __('media.public.view_all') }}</span>
                                <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                            </a>
                        </h2>

                        <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
                             class="flex gap-5 sm:gap-7 overflow-x-auto scrollbar-hide snap-x pb-2 -mx-4 sm:-mx-6 px-4 sm:px-6">
                            @foreach ($reciters as $reciter)
                                <a href="{{ $reciter['url'] }}"
                                   class="group/reciter flex-none snap-start w-36 sm:w-40 text-center rounded-3xl p-4 sm:p-5 border shadow-sm hover:shadow-xl hover:-translate-y-1 motion-reduce:transform-none transition-all duration-300 bg-white dark:bg-gray-900 border-gray-100 dark:border-white/10 hover:border-[#0056b3]/30">
                                    <div class="relative w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-full p-[3px] bg-gradient-to-br from-[#0056b3] to-[#218838] shadow-lg group-hover/reciter:shadow-[0_8px_30px_rgba(0,86,179,0.35)] group-hover/reciter:scale-105 motion-reduce:transform-none transition-all duration-300">
                                        @if ($reciter['image'])
                                            <img src="{{ $reciter['image'] }}" alt="{{ $reciter['name'] }}"
                                                 class="w-full h-full rounded-full object-cover bg-white dark:bg-gray-900" loading="lazy">
                                        @else
                                            <div class="w-full h-full rounded-full bg-white dark:bg-gray-900 flex items-center justify-center">
                                                <span class="text-2xl font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">{{ mb_substr($reciter['name'], 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <span class="absolute -bottom-0.5 -end-0.5 w-7 h-7 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center justify-center shadow ring-2 ring-white dark:ring-gray-900 opacity-0 group-hover/reciter:opacity-100 transition-opacity duration-300">
                                            <i data-lucide="play" class="w-3.5 h-3.5 {{ $isRtl ? '-scale-x-100' : '' }}"></i>
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm font-bold text-gray-900 dark:text-white line-clamp-2 group-hover/reciter:text-[#0056b3] dark:group-hover/reciter:text-[#64CCC5] transition-colors">{{ $reciter['name'] }}</p>
                                    @if ($reciter['country_name'])
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">
                                            <span aria-hidden="true">{{ $reciter['country_flag'] }}</span> {{ $reciter['country_name'] }}
                                        </p>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
                                aria-label="{{ __('media.public.scroll_back') }}"
                                class="absolute start-0 top-1/3 -ms-3 sm:-ms-4 w-11 h-11 rounded-full bg-white/95 text-gray-900 dark:bg-gray-900/85 dark:text-white backdrop-blur-sm hidden sm:flex items-center justify-center sm:opacity-0 sm:group-hover/row:opacity-100 focus-visible:opacity-100 hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
                        </button>
                        <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
                                aria-label="{{ __('media.public.scroll_forward') }}"
                                class="absolute end-0 top-1/3 -me-3 sm:-me-4 w-11 h-11 rounded-full bg-white/95 text-gray-900 dark:bg-gray-900/85 dark:text-white backdrop-blur-sm hidden sm:flex items-center justify-center sm:opacity-0 sm:group-hover/row:opacity-100 focus-visible:opacity-100 hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
                        </button>
                    </div>
                </section>
            @endif

            {{-- Live stream CTA banner — the primary channel, prototype-style.
                 The channels *row* above only renders with 2+ channels so a lone
                 channel is not presented twice on the same page. --}}
            @if (! empty($liveChannels))
                <section class="py-8">
                    <a href="{{ $liveChannels[0]['url'] }}"
                       class="group relative block overflow-hidden rounded-3xl shadow-2xl">
                        <div class="absolute inset-0 bg-gradient-to-l from-[#0056b3] via-[#116a53] to-[#218838]"></div>
                        <div class="absolute inset-0 opacity-[0.12]"
                             style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
                        <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-end justify-between gap-6 p-8 sm:p-12 text-center sm:text-start">
                            <div>
                                <div class="flex items-center justify-center sm:justify-start gap-3 mb-3">
                                    <span class="relative flex h-3 w-3" aria-hidden="true">
                                        <span class="animate-ping motion-reduce:animate-none absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                    </span>
                                    <span class="text-white/90 text-sm font-bold tracking-wider">{{ __('media.public.live_now') }}</span>
                                </div>
                                <h2 class="text-3xl sm:text-4xl font-black text-white">{{ $liveChannels[0]['title'] }}</h2>
                            </div>
                            <span class="inline-flex items-center gap-3 bg-white text-gray-900 font-bold text-base sm:text-lg px-6 py-3 sm:px-8 sm:py-4 rounded-full shadow-2xl group-hover:scale-105 motion-reduce:transform-none transition-transform duration-300">
                                <i data-lucide="radio" class="w-6 h-6"></i>
                                <span>{{ __('media.public.watch_live') }}</span>
                            </span>
                        </div>
                    </a>
                </section>
            @endif

            <div class="pb-12"></div>
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
