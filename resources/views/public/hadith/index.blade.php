@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);

    // Every cover carries the brand gradient. The prototype hand-picks a leather
    // colour per book, but a per-position palette reads as if the hue meant
    // something it doesn't — the title and author identify the collection.
    $coverGradient = 'bg-gradient-to-br from-[#0056b3] to-[#218838]';

    $featured = $collections->where('featured', true)->values();
    $featuredCount = $featured->count();
@endphp

@section('title', __('hadith.library_title').' — '.__('site.site_name'))
@section('meta_description', __('hadith.library_tagline'))

@section('content')

    @if ($featuredCount > 0)
        {{-- ── Hero: the featured collections carousel ──────────────────────── --}}
        <section class="relative overflow-hidden py-12 sm:py-16"
                 x-data="{
                     slide: 0,
                     count: {{ $featuredCount }},
                     timer: null,
                     // RTL advances the track the other way; read the document's
                     // own direction rather than duplicating the locale list.
                     get sign() { return document.documentElement.dir === 'rtl' ? 1 : -1 },
                     next() { this.slide = (this.slide + 1) % this.count },
                     prev() { this.slide = (this.slide + this.count - 1) % this.count },
                     start() {
                         if (this.count < 2) return;
                         if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                         this.stop();
                         this.timer = setInterval(() => this.next(), 6000);
                     },
                     stop() { clearInterval(this.timer); this.timer = null },
                 }"
                 x-init="start()"
                 @mouseenter="stop()" @mouseleave="start()"
                 @focusin="stop()" @focusout="start()">

            <div class="absolute inset-0 islamic-pattern" aria-hidden="true"></div>

            <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">

                <div class="text-center mb-10 sm:mb-12">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full
                                 bg-[#64CCC5]/15 text-[#0056b3] dark:text-[#64CCC5]
                                 text-sm font-semibold animate-scale-in">
                        <i data-lucide="sparkles" class="w-4 h-4" aria-hidden="true"></i>
                        {{ __('hadith.featured_collections') }}
                    </span>

                    <h1 class="mt-5 text-3xl sm:text-5xl lg:text-6xl font-black font-['Amiri']
                               text-gray-900 dark:text-white animate-fade-in-up break-words">
                        {{ __('hadith.library_title') }}
                    </h1>

                    <p class="mt-4 text-base sm:text-xl text-gray-600 dark:text-gray-300
                              max-w-3xl mx-auto animate-fade-in-up stagger-1">
                        {{ __('hadith.library_tagline') }}
                    </p>
                </div>

                {{-- Track --}}
                <div class="max-w-6xl mx-auto overflow-hidden"
                     role="region" aria-roledescription="carousel"
                     aria-label="{{ __('hadith.featured_collections') }}">
                    <div class="flex transition-transform duration-700 ease-out motion-reduce:transition-none"
                         :style="`transform: translateX(${slide * sign * 100}%)`">

                        @foreach ($featured as $i => $collection)
                            @php
                                $name = $collection->getTranslation('name', $locale, true);
                                $author = $collection->getTranslation('author', $locale, true);
                                $intro = $collection->getTranslation('short_intro', $locale, true);
                                $url = route('public.hadith.collection', $collection->code);
                            @endphp

                            <div class="w-full shrink-0 px-1"
                                 role="group" aria-roledescription="slide"
                                 aria-label="{{ $i + 1 }} / {{ $featuredCount }}"
                                 :aria-hidden="slide !== {{ $i }}"
                                 :inert="slide !== {{ $i }} ? true : null">

                                <div class="grid md:grid-cols-2 gap-8 lg:gap-12 items-center">

                                    {{-- Cover --}}
                                    <div class="order-2 md:order-1 flex justify-center">
                                        <a href="{{ $url }}" class="book-3d block w-48 sm:w-60 group
                                                                    rounded-lg focus:outline-none
                                                                    focus-visible:ring-2 focus-visible:ring-[#218838]
                                                                    focus-visible:ring-offset-4 focus-visible:ring-offset-transparent"
                                           tabindex="-1" aria-hidden="true">
                                            <div class="book-container">
                                                <div class="book-cover-3d leather-texture aspect-[2.5/3.5]
                                                            {{ $coverGradient }}">
                                                    <span class="book-pages" aria-hidden="true"></span>
                                                    <span class="book-spine" aria-hidden="true"></span>

                                                    <div class="relative z-10 h-full flex flex-col justify-between p-5 text-white">
                                                        <div class="flex justify-center">
                                                            <span class="grid place-items-center w-12 h-12 rounded-full
                                                                         border border-[#64CCC5] bg-white/5 backdrop-blur-sm">
                                                                <i data-lucide="book-open" class="w-6 h-6 text-[#64CCC5]"></i>
                                                            </span>
                                                        </div>

                                                        <p class="text-center text-xl sm:text-2xl font-bold font-['Amiri'] leading-tight break-words">
                                                            {{ $name }}
                                                        </p>

                                                        <p class="text-center text-xs opacity-80 line-clamp-1">{{ $author }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    {{-- Details --}}
                                    <div class="order-1 md:order-2 text-center md:text-start">
                                        <h2 class="text-2xl sm:text-4xl font-bold font-['Amiri'] text-gray-900 dark:text-white break-words">
                                            {{ $name }}
                                        </h2>

                                        <span class="block w-20 h-1 mt-4 mx-auto md:mx-0 rounded-full
                                                     bg-gradient-to-r from-[#64CCC5] to-transparent"
                                              aria-hidden="true"></span>

                                        @if ($author)
                                            <p class="mt-5 text-sm text-gray-600 dark:text-gray-300">{{ __('hadith.author') }}</p>
                                            <p class="text-lg sm:text-xl font-bold font-['Amiri'] text-gray-900 dark:text-white break-words">
                                                {{ $author }}
                                            </p>
                                        @endif

                                        @if ($intro)
                                            <p class="mt-4 text-sm sm:text-base leading-8 text-gray-600 dark:text-gray-300
                                                      max-w-lg mx-auto md:mx-0 line-clamp-3 break-words">
                                                {{ $intro }}
                                            </p>
                                        @endif

                                        <div class="mt-6 grid grid-cols-2 gap-3 max-w-sm mx-auto md:mx-0">
                                            <div class="rounded-xl bg-gray-100 dark:bg-[#0f3460] p-4">
                                                <p class="text-2xl font-black text-gradient tabular-nums">
                                                    {{ number_format($collection->hadiths_count) }}
                                                </p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('hadith.hadiths') }}</p>
                                            </div>
                                            <div class="rounded-xl bg-gray-100 dark:bg-[#0f3460] p-4">
                                                <p class="text-2xl font-black text-gradient tabular-nums">
                                                    {{ number_format($collection->books_count) }}
                                                </p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('hadith.books') }}</p>
                                            </div>
                                        </div>

                                        <a href="{{ $url }}"
                                           class="mt-7 inline-flex min-h-11 items-center gap-3 px-8 py-4 rounded-full
                                                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold
                                                  shadow-lg transition hover:shadow-2xl hover:scale-105
                                                  motion-reduce:transform-none
                                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]
                                                  focus-visible:ring-offset-2">
                                            <span>{{ __('hadith.explore_collection') }}</span>
                                            <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-5 h-5" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($featuredCount > 1)
                    <div class="mt-8 flex items-center justify-center gap-3">
                        <button type="button" @click="prev()" aria-label="{{ __('hadith.previous') }}"
                                class="grid place-items-center w-11 h-11 rounded-full
                                       bg-gradient-to-r from-[#0056b3] to-[#218838] text-white
                                       transition hover:scale-110 motion-reduce:transform-none
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]
                                       focus-visible:ring-offset-2">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
                        </button>

                        <div class="flex items-center gap-2">
                            @foreach ($featured as $i => $collection)
                                <button type="button" @click="slide = {{ $i }}"
                                        aria-label="{{ $collection->getTranslation('name', $locale, true) }}"
                                        :aria-current="slide === {{ $i }} ? 'true' : 'false'"
                                        class="h-11 grid place-items-center px-1 focus:outline-none
                                               focus-visible:ring-2 focus-visible:ring-[#218838] rounded">
                                    <span class="block h-2.5 rounded-full transition-all"
                                          :class="slide === {{ $i }} ? 'w-8 bg-[#64CCC5]' : 'w-2.5 bg-gray-400/50'"></span>
                                </button>
                            @endforeach
                        </div>

                        <button type="button" @click="next()" aria-label="{{ __('hadith.next') }}"
                                class="grid place-items-center w-11 h-11 rounded-full
                                       bg-gradient-to-r from-[#0056b3] to-[#218838] text-white
                                       transition hover:scale-110 motion-reduce:transform-none
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]
                                       focus-visible:ring-offset-2">
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-5 h-5"></i>
                        </button>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ── The full library ─────────────────────────────────────────────────── --}}
    {{-- `items` mirrors the rendered cards so the "no matches" state stays a
         reactive computation: reading the DOM instead would not re-run when the
         query changes. --}}
    <div x-data="{
             q: '',
             only: 'all',
             items: @js($collections->map(fn ($c) => [
                 'name' => \Illuminate\Support\Str::lower(
                     $c->getTranslation('name', $locale, true).' '.$c->getTranslation('author', $locale, true)
                 ),
                 'featured' => (bool) $c->featured,
             ])->values()),
             {{-- Both sides folded at compare time: 17 rows, so there is no
                  reason to precompute a folded column for this list. --}}
             hit(name, featured) {
                 const needle = window.foldForSearch(this.q);

                 return (!needle || window.foldForSearch(name).includes(needle))
                     && (this.only === 'all' || featured);
             },
             matches(el) {
                 return this.hit(el.dataset.name, el.dataset.featured === '1');
             },
             get shown() {
                 return this.items.filter(i => this.hit(i.name, i.featured)).length;
             },
         }">

        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center py-6">
                <span class="inline-flex items-center gap-2 px-6 py-3 rounded-full
                             bg-[#64CCC5]/15 text-[#0056b3] dark:text-[#64CCC5] font-bold">
                    <i data-lucide="library" class="w-5 h-5" aria-hidden="true"></i>
                    <h2 class="text-base sm:text-lg">{{ __('hadith.full_library') }}</h2>
                </span>
            </div>

            {{-- Filter bar --}}
            <div class="max-w-4xl mx-auto">
                <div class="relative">
                    <label for="collection-filter" class="sr-only">{{ __('hadith.search_collections_placeholder') }}</label>
                    <input id="collection-filter" type="search" x-model="q"
                           placeholder="{{ __('hadith.search_collections_placeholder') }}"
                           {{-- text-base keeps iOS from zooming the page on focus --}}
                           class="w-full ps-14 pe-5 py-4 rounded-2xl text-base sm:text-lg
                                  border-2 border-[#64CCC5]/25 focus:border-[#64CCC5] focus:outline-none
                                  bg-white dark:bg-[#16213e] text-gray-900 dark:text-gray-100
                                  placeholder:text-gray-400 transition-colors">
                    <i data-lucide="search" aria-hidden="true"
                       class="pointer-events-none absolute start-5 top-1/2 -translate-y-1/2 w-6 h-6 text-[#64CCC5]"></i>
                </div>

                @if ($featuredCount > 0 && $featuredCount < $collections->count())
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        @foreach ([['all', 'library', __('hadith.filter_all'), $collections->count()], ['featured', 'shield-check', __('hadith.featured'), $featuredCount]] as [$key, $icon, $label, $n])
                            <button type="button" @click="only = '{{ $key }}'"
                                    :aria-pressed="only === '{{ $key }}' ? 'true' : 'false'"
                                    :class="only === '{{ $key }}'
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                        : 'bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-[#0f3460]/70'"
                                    class="inline-flex min-h-11 items-center gap-2 px-5 py-2.5 rounded-full font-semibold
                                           transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                                <i data-lucide="{{ $icon }}" class="w-4 h-4" aria-hidden="true"></i>
                                <span>{{ $label }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold tabular-nums"
                                      :class="only === '{{ $key }}' ? 'bg-white/20' : 'bg-black/10 dark:bg-white/10'">
                                    {{ number_format($n) }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Library grid --}}
        <section class="islamic-pattern py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">

                @if ($collections->isNotEmpty())
                    {{-- Denser than the prototype's 4-up: 17 collections fit on
                         one screen at desktop widths instead of scrolling. --}}
                    <ul x-ref="grid"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6
                               gap-x-4 gap-y-7 sm:gap-x-6 sm:gap-y-9">
                        @foreach ($collections as $i => $collection)
                            @php
                                $name = $collection->getTranslation('name', $locale, true);
                                $author = $collection->getTranslation('author', $locale, true);
                                $intro = $collection->getTranslation('short_intro', $locale, true);
                            @endphp

                            <li data-name="{{ \Illuminate\Support\Str::lower($name.' '.$author) }}"
                                data-featured="{{ $collection->featured ? '1' : '0' }}"
                                x-show="matches($el)" x-transition.opacity>

                                <a href="{{ route('public.hadith.collection', $collection->code) }}"
                                   class="book-3d group block animate-scale-in stagger-{{ ($i % 8) + 1 }}
                                          rounded-lg focus:outline-none focus-visible:ring-2
                                          focus-visible:ring-[#218838] focus-visible:ring-offset-4
                                          focus-visible:ring-offset-transparent">

                                    <div class="book-container">
                                        <div class="book-cover-3d leather-texture aspect-[2.5/3.5]
                                                    {{ $coverGradient }}">
                                            <span class="book-pages" aria-hidden="true"></span>
                                            <span class="book-spine" aria-hidden="true"></span>

                                            <div class="relative z-10 h-full flex flex-col justify-between p-3 sm:p-4 text-white">
                                                <div class="flex justify-center">
                                                    <span class="grid place-items-center w-8 h-8 rounded-full
                                                                 border border-[#64CCC5] bg-white/5 backdrop-blur-sm">
                                                        <i data-lucide="book-open" class="w-4 h-4 text-[#64CCC5]"></i>
                                                    </span>
                                                </div>

                                                <div class="text-center">
                                                    <p class="text-base sm:text-lg font-bold font-['Amiri'] leading-tight break-words line-clamp-3">
                                                        {{ $name }}
                                                    </p>
                                                    @if ($author)
                                                        <p class="mt-1 text-[10px] opacity-80 line-clamp-1">{{ $author }}</p>
                                                    @endif
                                                </div>

                                                <div class="flex items-center justify-between text-[10px] tabular-nums">
                                                    <span class="inline-flex items-center gap-1">
                                                        <i data-lucide="file-text" class="w-3 h-3 text-[#64CCC5]" aria-hidden="true"></i>
                                                        {{ number_format($collection->hadiths_count) }}
                                                    </span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <i data-lucide="book" class="w-3 h-3 text-[#64CCC5]" aria-hidden="true"></i>
                                                        {{ number_format($collection->books_count) }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Hover panel: the intro, which has nowhere else to go on a cover --}}
                                            <div class="book-info-overlay">
                                                @if ($intro)
                                                    <p class="text-white text-[11px] leading-6 font-['Amiri'] line-clamp-5 break-words">
                                                        {{ $intro }}
                                                    </p>
                                                @endif
                                                <span class="mt-3 inline-flex items-center gap-2 text-white text-xs font-bold">
                                                    {{ __('hadith.browse_collection') }}
                                                    <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-center">
                                        <h3 class="font-bold text-sm sm:text-base font-['Amiri'] break-words line-clamp-2
                                                   text-gray-900 dark:text-white
                                                   transition-colors group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5]">
                                            {{ $name }}
                                        </h3>
                                        @if ($author)
                                            <p class="mt-0.5 text-[11px] text-gray-600 dark:text-gray-300 line-clamp-1">{{ $author }}</p>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Nothing left after filtering — distinct from "no collections at all" --}}
                    <div x-show="shown === 0" x-cloak>
                        <x-site.empty-state icon="search-x"
                                            :title="__('hadith.no_collections_match')"
                                            :message="__('site.try_adjusting_filters')" />
                    </div>
                @else
                    <x-site.empty-state icon="library" :title="__('hadith.no_collections')" :message="__('hadith.browse_intro')" />
                @endif
            </div>
        </section>
    </div>
@endsection
