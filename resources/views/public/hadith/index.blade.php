@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);

    // Every cover carries the brand gradient. The prototype hand-picks a leather
    // colour per book, but a per-position palette reads as if the hue meant
    // something it doesn't — the title and author identify the collection.
    $coverGradient = 'bg-gradient-to-br from-[#0056b3] to-[#218838]';

    // The hero fans at most three covers. Beyond that the fan stops reading as a
    // stack of books and starts reading as a row of thumbnails, and the rest of
    // the featured set is one scroll away in the library grid regardless.
    $featured = $collections->where('featured', true)->values();
    $featuredCount = $featured->count();
    $heroFan = $featured->take(3);

    // ── Collection titles ───────────────────────────────────────────────────
    // The Arabic name is the collection's identity, so it leads in every locale
    // and a second line carries the reader's own language. An Arabic reader gets
    // the English transliteration there rather than a line that repeats the one
    // directly above it.
    $secondaryLocale = $locale === 'ar' ? 'en' : $locale;
    $secondaryDir = $secondaryLocale === 'ur' ? 'rtl' : 'ltr';

    $titlesFor = function ($model) use ($secondaryLocale): array {
        $primary = $model->getTranslation('name', 'ar', true);

        // No fallback: a locale that has no name of its own must not echo the
        // Arabic back as its "translation". Falling through to the English
        // transliteration keeps the second line useful instead of dropping it.
        $secondary = $model->getTranslation('name', $secondaryLocale, false);

        if ($secondary === '') {
            $secondary = $model->getTranslation('name', 'en', false);
        }

        return [$primary, $secondary === $primary ? '' : $secondary];
    };

    // Both titles feed the client-side filter. Without the second one, a reader
    // on the Arabic UI typing "bukhari" matched nothing, even though the card
    // in front of them reads "Sahih al-Bukhari".
    $searchKeyFor = function ($model) use ($titlesFor, $locale): string {
        [$primary, $secondary] = $titlesFor($model);

        return \Illuminate\Support\Str::lower(implode(' ', array_filter([
            $primary,
            $secondary,
            $model->getTranslation('author', $locale, true),
        ])));
    };
@endphp

@section('title', __('hadith.library_title').' — '.__('site.site_name'))
@section('meta_description', __('hadith.library_tagline'))

@section('content')

    {{-- ── Hero: the vault ───────────────────────────────────────────────────
         A reader arriving here wants one of two things: a specific hadith, or a
         specific collection. Both are now reachable without scrolling — the
         previous full-viewport carousel spent the entire first screen showing
         one collection at a time and offered no way to search at all.

         This is the page's one dark surface, matching the language the homepage
         hero already establishes (dark ground, light type, brand gradient
         underneath), so it reads as the same site rather than a theme flip. --}}
    {{-- Deliberately NOT overflow-hidden: the suggestion panel hangs below the
         search field and past the band's lower edge, and clipping it here meant
         the dropdown opened into nothing. Both decorative layers below are
         inset within the section, so there is nothing to clip. --}}
    <section class="hadith-vault relative">
        <div class="hadith-vault-tile absolute inset-0" aria-hidden="true"></div>

        {{-- Dissolves the vault into the page instead of ending it on a hard
             line. Matched to the body background in both themes. --}}
        <div class="absolute inset-x-0 bottom-0 h-24 pointer-events-none
                    bg-gradient-to-t from-[#F8F9FA] dark:from-[#0f1729] to-transparent"
             aria-hidden="true"></div>

        <div class="container relative mx-auto px-4 sm:px-6 lg:px-8 pt-14 sm:pt-20 pb-16 sm:pb-20">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-10 items-center">

                {{-- ── Copy and the primary action ───────────────────────────
                     Splits with the fan when there is one, and takes the whole
                     band centred when there isn't. Holding the 7-column well
                     open with nothing beside it left half the hero empty on a
                     library that has no featured collections yet. --}}
                <div class="{{ $heroFan->isNotEmpty()
                        ? 'lg:col-span-7 text-center lg:text-start'
                        : 'lg:col-span-12 max-w-3xl mx-auto text-center' }}">

                    <h1 class="font-['Amiri'] font-black text-white break-words
                               text-4xl sm:text-5xl lg:text-6xl leading-[1.15]
                               animate-fade-in-up">
                        {{ __('hadith.library_title') }}
                    </h1>

                    <p class="mt-5 text-base sm:text-lg leading-8 text-slate-300
                              max-w-xl mx-auto {{ $heroFan->isNotEmpty() ? 'lg:mx-0' : '' }}
                              animate-fade-in-up stagger-1">
                        {{ __('hadith.library_tagline') }}
                    </p>

                    {{-- Search. The form posts to the server-rendered results page
                         exactly as before; the suggestion panel is an accelerator
                         layered on top, so the field keeps working with JS off. --}}
                    <div class="relative mt-8 max-w-xl mx-auto {{ $heroFan->isNotEmpty() ? 'lg:mx-0' : '' }} animate-fade-in-up stagger-2"
                         x-data="hadithSuggest({
                             endpoint: @js(route('v1.hadith.search')),
                             basePath: @js(url('/hadith')),
                             locale: @js($locale),
                         })"
                         @click.outside="close()"
                         @keydown.escape="close()">

                        <form action="{{ route('public.hadith.search') }}" method="GET" role="search">
                            <label for="hadith-hero-q" class="sr-only">{{ __('hadith.search_hadiths') }}</label>

                            {{-- text-base keeps iOS from zooming the page on focus --}}
                            <input id="hadith-hero-q" name="q" type="search"
                                   x-model="q" @input="onInput()" @focus="open = true"
                                   @keydown.arrow-down.prevent="move(1)"
                                   @keydown.arrow-up.prevent="move(-1)"
                                   @keydown.enter="choose($event)"
                                   autocomplete="off" role="combobox" aria-autocomplete="list"
                                   aria-controls="hadith-hero-suggest"
                                   :aria-expanded="showPanel ? 'true' : 'false'"
                                   :aria-activedescendant="active >= 0 ? `hadith-hero-opt-${active}` : null"
                                   placeholder="{{ __('hadith.search_placeholder') }}"
                                   class="w-full ps-14 pe-28 sm:pe-32 py-4 sm:py-5 rounded-2xl
                                          text-base sm:text-lg shadow-2xl
                                          bg-white dark:bg-[#16213e]
                                          text-gray-900 dark:text-gray-100 placeholder:text-gray-500
                                          border-2 border-transparent focus:border-[#64CCC5]
                                          focus:outline-none transition-colors">

                            <i data-lucide="search" aria-hidden="true"
                               class="pointer-events-none absolute start-5 top-1/2 -translate-y-1/2
                                      w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>

                            <button type="submit"
                                    class="absolute end-2 top-1/2 -translate-y-1/2 min-h-11
                                           px-5 sm:px-7 rounded-xl font-bold text-white whitespace-nowrap
                                           bg-gradient-to-r from-[#0056b3] to-[#218838]
                                           transition hover:shadow-lg hover:brightness-110
                                           active:scale-[0.98] motion-reduce:transform-none
                                           focus:outline-none focus-visible:ring-2
                                           focus-visible:ring-white focus-visible:ring-offset-2
                                           focus-visible:ring-offset-[#0a2137]">
                                {{ __('hadith.search_submit') }}
                            </button>
                        </form>

                        {{-- Suggestion panel. Four explicit states, because "no
                             results yet" and "no results at all" need different
                             copy and an empty array cannot tell them apart. --}}
                        {{-- Visibility is driven by classes, not x-show. Pairing
                             x-show with x-transition races: closing and reopening
                             inside the transition window let the finishing leave
                             animation stamp `display: none` over an already-open
                             panel, which left the field silently dead. Animating
                             opacity/visibility keeps the box in layout and has no
                             such window. --}}
                        <div id="hadith-hero-suggest" x-cloak
                             :class="showPanel
                                 ? 'opacity-100 translate-y-0 visible'
                                 : 'opacity-0 -translate-y-1 invisible pointer-events-none'"
                             role="listbox" aria-label="{{ __('hadith.search_suggestions') }}"
                             class="absolute inset-x-0 z-30 mt-3 text-start overflow-hidden
                                    rounded-2xl shadow-2xl bg-white dark:bg-[#16213e]
                                    ring-1 ring-black/5 dark:ring-white/10
                                    transition duration-150 ease-out motion-reduce:transition-none">

                            <template x-if="state === 'loading'">
                                <x-site.skeleton type="suggest" :count="4" />
                            </template>

                            <template x-if="state === 'error'">
                                <div class="px-4 py-6 text-center">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ __('hadith.error_title') }}
                                    </p>
                                    <button type="button" @click="fetch()"
                                            class="mt-3 inline-flex min-h-11 items-center gap-2 px-5 rounded-full
                                                   text-sm font-bold text-[#0056b3] dark:text-[#64CCC5]
                                                   bg-[#64CCC5]/15 transition hover:bg-[#64CCC5]/25
                                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                                        <i data-lucide="rotate-cw" class="w-4 h-4" aria-hidden="true"></i>
                                        {{ __('hadith.retry') }}
                                    </button>
                                </div>
                            </template>

                            <template x-if="state === 'ready' && results.length === 0">
                                <p class="px-4 py-6 text-center text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('hadith.search_no_results') }}
                                </p>
                            </template>

                            <template x-if="state === 'ready' && results.length > 0">
                                <div>
                                    <ul class="max-h-80 overflow-y-auto custom-scrollbar
                                               divide-y divide-gray-100 dark:divide-white/10">
                                        <template x-for="(r, i) in results" :key="r.key">
                                            <li>
                                                <a :href="r.url" :id="`hadith-hero-opt-${i}`"
                                                   role="option" :aria-selected="active === i ? 'true' : 'false'"
                                                   @mouseenter="active = i"
                                                   :class="active === i ? 'bg-gray-50 dark:bg-white/5' : ''"
                                                   class="flex items-start gap-3 px-4 py-3.5 transition-colors
                                                          focus:outline-none focus-visible:bg-gray-50
                                                          dark:focus-visible:bg-white/5">
                                                    <span class="grid place-items-center w-9 h-9 shrink-0 rounded-lg
                                                                 bg-[#64CCC5]/15 text-[#0056b3] dark:text-[#64CCC5]">
                                                        <i data-lucide="book-marked" class="w-4 h-4" aria-hidden="true"></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-sm leading-7 font-['Amiri'] line-clamp-2
                                                                     text-gray-900 dark:text-gray-100"
                                                              x-text="r.body"></span>
                                                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400 tabular-nums"
                                                              x-text="r.reference"></span>
                                                    </span>
                                                </a>
                                            </li>
                                        </template>
                                    </ul>

                                    <a :href="`{{ route('public.hadith.search') }}?q=${encodeURIComponent(q.trim())}`"
                                       class="flex min-h-11 items-center justify-center gap-2 px-4 py-3
                                              text-sm font-bold border-t border-gray-100 dark:border-white/10
                                              text-[#0056b3] dark:text-[#64CCC5]
                                              transition hover:bg-[#64CCC5]/10
                                              focus:outline-none focus-visible:bg-[#64CCC5]/10">
                                        <span>{{ __('hadith.search_view_all_results') }}</span>
                                        <span class="tabular-nums" x-text="`(${total})`"></span>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ── The featured fan ────────────────────────────────────────
                     Every featured collection is directly reachable here. The
                     carousel this replaces could only ever show one, and hid the
                     rest behind a six-second timer. --}}
                @if ($heroFan->isNotEmpty())
                    @php
                        // Depth tiers: the first cover stands closest to the
                        // reader, the rest recede. Written out rather than
                        // computed because Tailwind cannot compile a class from
                        // a runtime value.
                        $fanTiers = [
                            'w-32 sm:w-40 lg:w-44 lg:-translate-y-4 z-30',
                            'w-28 sm:w-36 lg:w-40 opacity-95 z-20',
                            'w-24 sm:w-32 lg:w-36 opacity-90 z-10 hidden sm:block',
                        ];
                    @endphp

                    <div class="lg:col-span-5 animate-fade-in-up stagger-2">
                        <h2 id="hero-featured-label"
                            class="mb-6 text-center lg:text-start text-sm font-bold text-[#64CCC5]">
                            {{ __('hadith.featured_collections') }}
                        </h2>

                        <ul class="flex items-end justify-center lg:justify-start gap-4 sm:gap-5"
                            aria-labelledby="hero-featured-label">
                            @foreach ($heroFan as $i => $collection)
                                @php
                                    [$name, $subName] = $titlesFor($collection);
                                    $author = $collection->getTranslation('author', $locale, true);
                                @endphp

                                <li class="{{ $fanTiers[$i] }}">
                                    <a href="{{ route('public.hadith.collection', $collection->code) }}"
                                       class="book-3d group block rounded-lg
                                              focus:outline-none focus-visible:ring-2
                                              focus-visible:ring-[#64CCC5] focus-visible:ring-offset-4
                                              focus-visible:ring-offset-transparent">
                                        <div class="book-container">
                                            <div class="book-cover-3d leather-texture aspect-[2.5/3.5] {{ $coverGradient }}">
                                                <span class="book-pages" aria-hidden="true"></span>
                                                <span class="book-spine" aria-hidden="true"></span>

                                                <div class="relative z-10 h-full flex flex-col justify-between p-3 sm:p-4 text-white">
                                                    <div class="flex justify-center">
                                                        <span class="grid place-items-center w-8 h-8 rounded-full
                                                                     border border-[#64CCC5] bg-white/5 backdrop-blur-sm">
                                                            <i data-lucide="book-open" class="w-4 h-4 text-[#64CCC5]" aria-hidden="true"></i>
                                                        </span>
                                                    </div>

                                                    <p class="text-center text-sm sm:text-base font-bold font-['Amiri']
                                                              leading-tight break-words line-clamp-3">
                                                        {{ $name }}
                                                    </p>

                                                    <p class="text-center text-[10px] opacity-80 tabular-nums">
                                                        {{ trans_choice('hadith.hadiths_count', $collection->hadiths_count, ['count' => number_format($collection->hadiths_count)]) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="mt-3 text-center text-xs sm:text-sm font-bold font-['Amiri']
                                                  text-white line-clamp-2 transition-colors group-hover:text-[#64CCC5]">
                                            {{ $name }}
                                        </p>
                                        @if ($subName !== '')
                                            {{-- lang/dir declared: a Latin title inside an RTL document
                                                 needs its own direction or its punctuation reorders. --}}
                                            <p lang="{{ $secondaryLocale }}" dir="{{ $secondaryDir }}"
                                               class="mt-1 text-center text-[11px] leading-4 text-slate-400 line-clamp-2">
                                                {{ $subName }}
                                            </p>
                                        @endif
                                        @if ($author)
                                            <p class="mt-0.5 text-center text-[11px] text-slate-500 line-clamp-1">
                                                {{ $author }}
                                            </p>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ── What the library actually holds ───────────────────────────────────
         Aggregated at the source (HadithBrowseService::libraryTotals) rather
         than summed from `$collections`: these figures describe the whole
         published library, so they must stay correct if that listing is ever
         paginated or filtered. Sits under the hero rather than inside it — the
         hero carries the value and the action, and a figure strip wedged beside
         them dilutes both. --}}
    <section class="bg-white dark:bg-[#0f1729] border-b border-gray-200 dark:border-white/10">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <dl class="grid grid-cols-3">
                @foreach ([
                    ['library', $totals['collections'], __('hadith.collections')],
                    ['file-text', $totals['hadiths'], __('hadith.hadiths')],
                    ['book', $totals['books'], __('hadith.books')],
                ] as [$icon, $value, $label])
                    {{-- flex-col-reverse: <dt> must precede <dd> in the markup,
                         but the figure reads first. --}}
                    <div class="flex flex-col-reverse items-center gap-1 py-6 sm:py-7 px-2
                                border-s border-gray-200 dark:border-white/10 first:border-s-0">
                        <dt class="flex items-center gap-1.5 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                            <i data-lucide="{{ $icon }}" class="w-4 h-4 text-[#64CCC5]" aria-hidden="true"></i>
                            {{ $label }}
                        </dt>
                        <dd class="text-2xl sm:text-3xl font-black tabular-nums
                                   text-[#0056b3] dark:text-[#64CCC5]">
                            {{ number_format($value) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ── The full library ─────────────────────────────────────────────────── --}}
    {{-- `items` mirrors the rendered cards so the "no matches" state stays a
         reactive computation: reading the DOM instead would not re-run when the
         query changes. --}}
    <div x-data="{
             q: '',
             only: 'all',
             items: @js($collections->map(fn ($c) => [
                 'name' => $searchKeyFor($c),
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
                                [$name, $subName] = $titlesFor($collection);
                                $author = $collection->getTranslation('author', $locale, true);
                                $intro = $collection->getTranslation('short_intro', $locale, true);
                            @endphp

                            <li data-name="{{ $searchKeyFor($collection) }}"
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
                                        @if ($subName !== '')
                                            {{-- lang/dir declared: a Latin title inside an RTL document
                                                 needs its own direction or its punctuation reorders. --}}
                                            <p lang="{{ $secondaryLocale }}" dir="{{ $secondaryDir }}"
                                               class="mt-1 text-[11px] leading-4 line-clamp-2
                                                      text-gray-500 dark:text-gray-400">
                                                {{ $subName }}
                                            </p>
                                        @endif
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
