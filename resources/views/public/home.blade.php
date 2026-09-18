@extends('layouts.public')

@section('title', __('site.site_name'))
@section('meta_description', collect($homeSections ?? [])->firstWhere('key', 'hero')?->subheading ?? '')

{{-- Preload the hero image (LCP) at high priority so it starts fetching before
     CSS/JS parse — paired with fetchpriority="high" on the <img>. --}}
@push('head')
    <link rel="preload" as="image" fetchpriority="high"
          href="{{ asset('images/site/rasool-allah-hero-section-bg.webp') }}"
          imagesrcset="{{ asset('images/site/rasool-allah-hero-section-bg-960.webp') }} 960w, {{ asset('images/site/rasool-allah-hero-section-bg.webp') }} 1600w"
          imagesizes="100vw">
@endpush

@php
    $locale = app()->getLocale();
    $isRtl  = in_array($locale, ['ar', 'ur'], true);
    $arrow  = $isRtl ? 'arrow-left' : 'arrow-right';
    $sahabaUrl = Route::has('public.sahaba') ? route('public.sahaba') : '#';
    $seerahUrl = Route::has('public.seerah') ? route('public.seerah') : '#';
    $genealogyUrl = Route::has('public.genealogy') ? route('public.genealogy') : '#';
    $searchUrl = Route::has('public.search') ? route('public.search') : '#';
    $registerUrl = route('public.register');

    // Module cards arrive DB-managed from HomeContentService (controller prop).
    $heroCards = $heroCards ?? [];
    $moduleCards = $moduleCards ?? [];
    $quickFilters = $quickFilters ?? [];
    $stats = $stats ?? [];
    $homeSections = $homeSections ?? [];
    $homeSectionsByKey = collect($homeSections)->keyBy('key');

    // The Prophet ﷺ card now reads from his record. `pv()` returns the stored
    // extended-profile value for the current locale, falling back to the old
    // translation string so the card still renders on an install that has not
    // run the People seeders.
    $prophet = $prophet ?? null;
    $prophetProfile = $prophet?->profile;
    $pv = function (string $key, string $fallbackTranslation) use ($prophetProfile): string {
        $stored = $prophetProfile?->localized($key, app()->getLocale());

        return $stored !== null && $stored !== '' ? $stored : __($fallbackTranslation);
    };
    $pn = function (string $key, string $fallback) use ($prophetProfile): string {
        $stored = $prophetProfile?->get($key);

        return $stored !== null ? (string) $stored : $fallback;
    };
    $homeSectionMarkup = [];

    // Honest per-card stat lines from real derived counts (no fabricated
    // numbers); cards without a real count simply omit the stat row.
    $cardStats = [
        'seerah' => ['icon' => 'calendar', 'value' => $stats['seerah_events'] ?? 0, 'label' => __('site.events_count')],
        'hadith' => ['icon' => 'book-marked', 'value' => $stats['hadiths'] ?? 0, 'label' => __('site.hadith_count')],
        'quran' => ['icon' => 'file-text', 'value' => $stats['surahs'] ?? 114, 'label' => __('site.surahs')],
    ];

    // Resolve a card/chip target: named route, absolute/relative URL, or '#'.
    $contentUrl = function (?string $target): string {
        if ($target !== null && Route::has($target)) {
            return route($target);
        }

        if ($target !== null && (str_starts_with($target, 'http') || str_starts_with($target, '/'))) {
            return $target;
        }

        return '#';
    };

    $contentImageUrl = function (?string $image): ?string {
        if (blank($image)) {
            return null;
        }

        if (ctype_digit($image)) {
            return route('public.home-card-image', ['media' => $image]);
        }

        if (str_starts_with($image, 'http') || str_starts_with($image, '/')) {
            return $image;
        }

        return asset($image);
    };

@endphp

@section('content')

@push('head')
<style>
    /* ── Signature hero moment ──────────────────────────────────────────────
       The one authored effect on this page: the hero photograph drifts slower
       than the page and the hero copy lifts away as it scrolls. CSS
       scroll-driven, so no JS; GPU-only (transform/opacity) and fully disabled
       for reduced motion or browsers without scroll-timeline — the hero then
       stays a clean static composition. The entrance itself is the system's
       .sx-rise, not a bespoke keyframe.

       Headroom so the parallax drift never exposes an edge; .sx-hero's own
       overflow-hidden clips the overscan. */
    .hero-parallax { inset: -12% 0; }

    @supports (animation-timeline: scroll()) {
        @media (prefers-reduced-motion: no-preference) {
            .hero-parallax {
                animation: heroParallax linear both;
                animation-timeline: scroll(root);
                animation-range: 0 100vh;
                will-change: transform;
            }
            @keyframes heroParallax { to { transform: translateY(12%); } }

            .hero-fade-out {
                animation: heroFadeOut linear both;
                animation-timeline: scroll(root);
                animation-range: 8vh 88vh;
                will-change: transform, opacity;
            }
            @keyframes heroFadeOut { to { opacity: 0; transform: translateY(-36px); } }
        }
    }

    /* ── Sahaba carousel — clean card alignment on swipe/arrow scroll.
       scroll-snap works in both directions; the JS handles RTL-safe paging. */
    .home-sahaba-row {
        scroll-snap-type: x mandatory;
        scroll-padding-inline-start: 0.5rem;
        scroll-behavior: smooth;
    }
    .home-sahaba-row > * { scroll-snap-align: start; }
    @media (prefers-reduced-motion: reduce) {
        .home-sahaba-row { scroll-behavior: auto; }
    }
</style>
@endpush

{{-- ===================================================================
     SECTION 1 — HERO
     The one Persuade surface: a photographic opener on the system's own
     .sx-hero shape (its scrim, ink and media stacking), with system paper
     controls and cards over it.
     =================================================================== --}}
@php ob_start(); @endphp
    {{-- Background photograph + the hero's scroll parallax. The scrim is
         .sx-hero::after, so no extra overlay is stacked here. --}}
    <div class="hero-parallax sx-hero-media" aria-hidden="true">
        <div class="sx-hero-fallback"></div>
        <img class="relative w-full h-full object-cover"
             alt="{{ __('site.hero_image_alt') }}"
             width="1600" height="685" fetchpriority="high" decoding="async"
             sizes="100vw"
             srcset="{{ asset('images/site/rasool-allah-hero-section-bg-960.webp') }} 960w, {{ asset('images/site/rasool-allah-hero-section-bg.webp') }} 1600w"
             src="{{ asset('images/site/rasool-allah-hero-section-bg.webp') }}" />
    </div>
@php $heroBackgroundMarkup = ob_get_clean(); ob_start(); @endphp

    {{-- Hero copy, search and quick filters. .sx-rise staggers the children. --}}
    <div class="hero-fade-out sx-rise grid justify-items-center gap-6 text-center">
        <h1 class="sx-hero-title max-w-[26ch]">
            {{ $homeSectionsByKey->get('hero')?->heading }}
        </h1>

        <p class="sx-hero-lead">
            {{ $homeSectionsByKey->get('hero')?->subheading }}
        </p>

        <form action="{{ $searchUrl }}" method="GET"
              class="flex w-full max-w-2xl flex-wrap items-center justify-center gap-2 sm:flex-nowrap">
            <div class="relative min-w-0 flex-1">
                <label for="home-search" class="sr-only">{{ __('site.hero_search_placeholder') }}</label>
                <i data-lucide="search" aria-hidden="true"
                   class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]"></i>
                <input id="home-search" type="search" name="q" enterkeyhint="search"
                       placeholder="{{ __('site.hero_search_placeholder') }}"
                       class="sx-input ps-12 rounded-full" />
            </div>
            <button type="submit" class="sx-btn is-primary shrink-0">{{ __('site.search') }}</button>
        </form>

        {{-- Quick Filter chips — DB-managed (HomeContentService). Glass is the
             system's chip treatment on imagery. --}}
        @if (count($quickFilters) > 0)
            <div class="flex flex-wrap justify-center gap-2">
                @foreach ($quickFilters as $filter)
                    @php
                        $filterHref = !empty($filter['target_query'])
                            ? $searchUrl.'?q='.urlencode($filter['target_query'])
                            : $contentUrl($filter['target_url'] ?? null);
                    @endphp
                    <a href="{{ $filterHref }}" title="{{ $filter['label'] }}"
                       class="sx-glass-chip max-w-[14rem] transition-colors hover:border-white/45">
                        <span class="sx-clamp-1">{{ $filter['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@php $heroIntroMarkup = ob_get_clean(); ob_start(); @endphp
    {{-- Hero module cards — the same <x-public.editorial.card> the module pages
         use, so the opener already reads as the rest of the site (and the image
         morphs into the destination hero). --}}
    @if ($homeSectionsByKey->get('hero_cards')?->heading || $homeSectionsByKey->get('hero_cards')?->subheading)
        <div class="mb-8 grid justify-items-center gap-3 text-center">
            @if ($homeSectionsByKey->get('hero_cards')?->heading)
                <h2 class="text-2xl font-extrabold leading-tight md:text-3xl">{{ $homeSectionsByKey->get('hero_cards')?->heading }}</h2>
            @endif
            @if ($homeSectionsByKey->get('hero_cards')?->subheading)
                <p class="sx-hero-lead">{{ $homeSectionsByKey->get('hero_cards')?->subheading }}</p>
            @endif
        </div>
    @endif

    @if (count($heroCards) > 0)
        @php $featuredHeroCard = $heroCards[0]; @endphp
        <div class="grid gap-4 md:grid-cols-3">
            <x-public.editorial.card
                class="md:col-span-2"
                :url="$contentUrl($featuredHeroCard['target_url'])"
                :title="$featuredHeroCard['title']"
                :excerpt="($featuredHeroCard['description'] ?? null) ?: null"
                :image="$contentImageUrl($featuredHeroCard['image'])"
                :icon="($featuredHeroCard['icon'] ?? null) ?: 'book-open'"
                :badge="($featuredHeroCard['badge'] ?? null) ?: null"
                eager>
                <span class="sx-btn is-primary mt-1 justify-self-start">
                    {{ __('site.discover_seerah') }}<i data-lucide="{{ $arrow }}" aria-hidden="true"></i>
                </span>
            </x-public.editorial.card>

            <div class="grid gap-4">
                @foreach (array_slice($heroCards, 1) as $card)
                    <x-public.editorial.card
                        :url="$contentUrl($card['target_url'])"
                        :title="$card['title']"
                        :excerpt="($card['description'] ?? null) ?: null"
                        :image="$contentImageUrl($card['image'] ?? null)"
                        :icon="($card['icon'] ?? null) ?: 'book-open'"
                        :badge="($card['badge'] ?? null) ?: null" />
                @endforeach
            </div>
        </div>
    @endif
@php $heroCardsMarkup = ob_get_clean(); @endphp

{{-- ===================================================================
     SECTION 2 — SAHABA CAROUSEL
     =================================================================== --}}
@php ob_start(); @endphp
<div class="sx-wrap py-14 sm:py-20">
    <x-public.editorial.section data-home-section="sahaba_carousel" icon="users"
        :title="$homeSectionsByKey->get('sahaba_carousel')?->heading">

        @if ($homeSectionsByKey->get('sahaba_carousel')?->subheading)
            <p class="sx-lead -mt-2 mb-6">{{ $homeSectionsByKey->get('sahaba_carousel')?->subheading }}</p>
        @endif

        <div class="relative"
             x-data="{
               c: null,
               canPrev: false,
               canNext: false,
               rtl: document.documentElement.dir === 'rtl',
               reduce: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
               init() {
                 this.c = this.$refs.scrollContainer;
                 this.$nextTick(() => this.update());
                 window.addEventListener('resize', () => this.update());
               },
               update() {
                 if (!this.c) return;
                 // RTL-safe: modern browsers report negative scrollLeft in RTL, so
                 // normalise to a positive distance-from-start before comparing.
                 const max = this.c.scrollWidth - this.c.clientWidth;
                 const pos = Math.abs(this.c.scrollLeft);
                 this.canPrev = pos > 4;
                 this.canNext = pos < max - 4;
               },
               amount() {
                 const card = this.c.querySelector('.sahaba-card');
                 const cardW = card ? card.offsetWidth + 24 : 300;
                 // Advance roughly one viewport but at least one card; snap aligns the rest.
                 return Math.max(cardW, Math.round(this.c.clientWidth * 0.85));
               },
               go(forward) {
                 // In RTL the scroll axis is mirrored, so flip the sign of the step.
                 const sign = this.rtl ? -1 : 1;
                 this.c.scrollBy({ left: (forward ? sign : -sign) * this.amount(), behavior: this.reduce ? 'auto' : 'smooth' });
               }
             }">

            <div class="home-sahaba-row sx-scroller gap-5 pb-4"
                 x-ref="scrollContainer"
                 @scroll.passive="update()"
                 tabindex="0"
                 role="region"
                 aria-label="{{ $homeSectionsByKey->get('sahaba_carousel')?->heading }}">

                @forelse ($featuredSahaba as $index => $sahabi)
                    @php
                        // The corner glyph rotates for rhythm; the colour does not — see
                        // components/site/sahaba-card.blade.php.
                        $badgeIcons = ['star', 'shield', 'heart', 'crown'];
                        $badgeText = $sahabi->getTranslation('laqab', $locale)
                                  ?: $sahabi->getTranslation('laqab', 'ar');
                    @endphp
                    <x-site.sahaba-card
                        :sahabi="$sahabi"
                        :badge="$badgeText"
                        :badge-icon="$badgeIcons[$index % count($badgeIcons)]"
                    />
                @empty
                    {{-- No companions published yet: the shape of the row, not a blank gap. --}}
                    @for ($i = 0; $i < 5; $i++)
                        <div class="sahaba-card w-72 shrink-0" aria-hidden="true">
                            <div class="sx-surface overflow-hidden">
                                <div class="sx-skeleton h-64 rounded-none"></div>
                                <div class="grid gap-3 p-5">
                                    <div class="sx-skeleton h-3"></div>
                                    <div class="sx-skeleton h-3 w-3/4"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                @endforelse
            </div>

            {{-- Previous — scrolls toward the start (start edge: left in LTR, right in RTL) --}}
            <button x-show="canPrev" x-cloak
                    @click="go(false)"
                    x-transition.opacity
                    class="sx-btn absolute start-1 top-1/2 z-10 w-12 -translate-y-1/2 px-0 shadow-lg sm:start-2"
                    aria-label="{{ __('site.scroll_previous') }}"
                    title="{{ __('site.scroll_previous') }}">
                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
            </button>

            {{-- Next — scrolls toward the end (end edge: right in LTR, left in RTL) --}}
            <button x-show="canNext" x-cloak
                    @click="go(true)"
                    x-transition.opacity
                    class="sx-btn absolute end-1 top-1/2 z-10 w-12 -translate-y-1/2 px-0 shadow-lg sm:end-2"
                    aria-label="{{ __('site.scroll_next') }}"
                    title="{{ __('site.scroll_next') }}">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
            </button>
        </div>

        {{-- View all — the count comes from the same aggregate the stats row
             uses, so the button says how much is behind it. --}}
        <div class="mt-8 flex justify-center">
            <a href="{{ $sahabaUrl }}" class="sx-btn is-primary">
                {{ __('site.view_all_companions') }}
                @isset($stats['sahaba'])
                    <span class="rounded-full bg-[rgba(255,255,255,0.22)] px-2 py-0.5 text-xs tabular-nums">
                        {{ \Illuminate\Support\Number::format((int) $stats['sahaba'], locale: $locale) }}
                    </span>
                @endisset
                <i data-lucide="{{ $arrow }}" aria-hidden="true"></i>
            </a>
        </div>
    </x-public.editorial.section>
</div>
@php $homeSectionMarkup['sahaba_carousel'] = ob_get_clean(); ob_start(); @endphp

{{-- ===================================================================
     SECTION 3 — PROPHET MUHAMMAD ﷺ ID CARD
     One surface, the system's key–value facts and ledger figures.
     =================================================================== --}}
<div class="sx-wrap py-14 sm:py-20">
    <x-public.editorial.section data-home-section="seal_band" icon="sparkles"
        :title="$homeSectionsByKey->get('seal_band')?->heading">

        <div class="sx-surface grid gap-6 p-5 sm:p-7">
            <header class="flex flex-wrap items-center gap-4">
                <span class="sx-avatar is-square is-lg" aria-hidden="true">
                    <i data-lucide="sparkles"></i>
                </span>
                <div class="grid min-w-0 gap-2">
                    <span class="sx-chip is-active w-fit">
                        <i data-lucide="crown" aria-hidden="true"></i>{{ __('site.seal_of_prophets') }}
                    </span>
                    @if ($homeSectionsByKey->get('seal_band')?->subheading)
                        <p class="text-[color:var(--sx-ink-2)]">{{ $homeSectionsByKey->get('seal_band')?->subheading }}</p>
                    @endif
                </div>
            </header>

            <dl class="sx-kv">
                <div>
                    <dt class="flex items-center gap-2">
                        <i data-lucide="user" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('site.full_name') }}
                    </dt>
                    <dd>
                        {{ $pv('full_name', 'site.prophet_full_name') }}
                        <span class="mt-1 block text-sm font-normal text-[color:var(--sx-muted)]">{{ $pv('tribe', 'site.prophet_tribe') }}</span>
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-2">
                        <i data-lucide="calendar" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('site.birth_date') }}
                    </dt>
                    <dd>
                        {{ $pv('birth_day', 'site.prophet_birth_hijri') }}
                        <span class="mt-1 block text-sm font-normal text-[color:var(--sx-muted)]">
                            {{ __('site.year_of_elephant') }} · <span class="tabular-nums">{{ $pn('birth_year_ce', '571') }}</span> {{ __('site.ce') }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-2">
                        <i data-lucide="map-pin" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('site.birth_place') }}
                    </dt>
                    <dd>
                        {{ $prophet?->getTranslation('birth_place', app()->getLocale()) ?: __('site.makkah') }}
                        <span class="mt-1 block text-sm font-normal text-[color:var(--sx-muted)]">{{ $pv('birth_region', 'site.hejaz_region') }}</span>
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-2">
                        <i data-lucide="zap" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('site.mission_start') }}
                    </dt>
                    <dd>
                        {{ $pv('mission_place', 'site.hira_cave') }}
                        <span class="mt-1 block text-sm font-normal text-[color:var(--sx-muted)]">
                            {{ __('site.age_at', ['age' => $pn('mission_age', '40')]) }} · <span class="tabular-nums">{{ $pn('mission_year_ce', '610') }}</span> {{ __('site.ce') }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-2">
                        <i data-lucide="star" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('site.death_date') }}
                    </dt>
                    <dd>
                        {{ $pv('death_day', 'site.prophet_death_hijri') }}
                        <span class="mt-1 block text-sm font-normal text-[color:var(--sx-muted)]">
                            <span class="tabular-nums">{{ $pn('death_year_ce', '632') }}</span> {{ __('site.ce') }} · {{ __('site.monday') }}
                        </span>
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-2">
                        <i data-lucide="map" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('site.death_place') }}
                    </dt>
                    <dd>
                        {{ $pv('death_place', 'site.madinah') }}
                        <span class="mt-1 block text-sm font-normal text-[color:var(--sx-muted)]">{{ $pv('death_detail', 'site.aisha_house') }}</span>
                    </dd>
                </div>
            </dl>

            <dl class="sx-panel sx-ledger">
                <div>
                    <dt><i data-lucide="hourglass" aria-hidden="true"></i>{{ __('site.noble_age') }}</dt>
                    <dd>63 <span class="text-sm font-semibold text-[color:var(--sx-muted)]">{{ __('site.years') }}</span></dd>
                </div>
                <div>
                    <dt><i data-lucide="megaphone" aria-hidden="true"></i>{{ __('site.dawah_years') }}</dt>
                    <dd>23 <span class="text-sm font-semibold text-[color:var(--sx-muted)]">{{ __('site.years') }}</span></dd>
                </div>
                <div>
                    <dt><i data-lucide="users" aria-hidden="true"></i>{{ __('site.companions_count') }}</dt>
                    <dd>100,000+ <span class="text-sm font-semibold text-[color:var(--sx-muted)]">{{ __('site.companion') }}</span></dd>
                </div>
            </dl>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $seerahUrl }}" class="sx-btn is-primary">
                    <i data-lucide="book-open" aria-hidden="true"></i>
                    <span>{{ __('site.explore_full_seerah') }}</span>
                    <i data-lucide="{{ $arrow }}" aria-hidden="true"></i>
                </a>
                <a href="{{ $genealogyUrl }}" class="sx-btn">
                    <i data-lucide="git-branch" aria-hidden="true"></i>
                    <span>{{ __('site.family_tree') }}</span>
                </a>
            </div>
        </div>
    </x-public.editorial.section>
</div>
@php $homeSectionMarkup['seal_band'] = ob_get_clean(); ob_start(); @endphp

{{-- ===================================================================
     SECTION 4 — DAILY QURAN & HADITH
     Composed from <x-site.daily-verse> / <x-site.daily-hadith>, which share the
     <x-site.daily-panel> shell. The `daily` home_sections row still supplies both
     headings (heading → Quran, subheading → Hadith).
     =================================================================== --}}
<div data-home-section="daily" class="sx-wrap py-14 sm:py-20">
    <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2">

        <x-site.daily-verse
            :verse="$dailyVerse"
            :title="$homeSectionsByKey->get('daily')?->heading" />

        <x-site.daily-hadith
            :hadith="$dailyHadith"
            :title="$homeSectionsByKey->get('daily')?->subheading" />

    </div>
</div>
@php $homeSectionMarkup['daily'] = ob_get_clean(); ob_start(); @endphp

{{-- ===================================================================
     SECTION 5 — MODULE DISCOVERY GRID
     The same cards as the module index pages.
     =================================================================== --}}
<div class="sx-wrap py-14 sm:py-20">
    <x-public.editorial.section id="explore" data-home-section="explore" icon="compass"
        :title="$homeSectionsByKey->get('explore')?->heading">

        @if ($homeSectionsByKey->get('explore')?->subheading)
            <p class="sx-lead -mt-2 mb-6">{{ $homeSectionsByKey->get('explore')?->subheading }}</p>
        @endif

        @if (count($moduleCards) > 0)
            <div class="sx-grid">
                @foreach ($moduleCards as $card)
                    @php $cardStat = $cardStats[$card['key']] ?? null; @endphp
                    <x-public.editorial.card
                        :url="$contentUrl($card['target_url'] ?? null)"
                        :title="$card['title']"
                        :excerpt="($card['description'] ?? null) ?: null"
                        :image="$contentImageUrl($card['image'] ?? null)"
                        :icon="($card['icon'] ?? null) ?: 'book-open'"
                        :featured="(bool) ($card['is_featured'] ?? false)"
                        :meta="$cardStat && $cardStat['value'] > 0 ? [[
                            'icon' => $cardStat['icon'],
                            'text' => number_format($cardStat['value']).' '.$cardStat['label'],
                        ]] : []" />
                @endforeach
            </div>
        @endif
    </x-public.editorial.section>
</div>
@php $homeSectionMarkup['explore'] = ob_get_clean(); ob_start(); @endphp

{{-- ===================================================================
     SECTION 6 — FEATURED CONTENT
     =================================================================== --}}
@if($featuredArticles->isNotEmpty())
<div class="sx-wrap py-14 sm:py-20">
    <x-public.editorial.section data-home-section="featured_content" icon="newspaper"
        :title="$homeSectionsByKey->get('featured_content')?->heading">

        <div class="-mt-2 mb-6 flex flex-wrap items-end justify-between gap-3">
            @if ($homeSectionsByKey->get('featured_content')?->subheading)
                <p class="sx-lead">{{ $homeSectionsByKey->get('featured_content')?->subheading }}</p>
            @endif
            <a href="{{ Route::has('public.articles') ? route('public.articles') : '#' }}" class="sx-btn">
                {{ __('site.view_all') }}<i data-lucide="{{ $arrow }}" aria-hidden="true"></i>
            </a>
        </div>

        <div class="sx-grid is-wide">
            @foreach($featuredArticles as $article)
                @php
                    $articleLocale = $article->locales->first();
                    $image = $article->featured_image;
                    $imageUrl = $image
                        ? (filter_var($image, FILTER_VALIDATE_URL) ? $image : asset('storage/'.ltrim($image, '/')))
                        : ($article->thumbUrl('card') ?? asset('images/placeholder.png'));
                @endphp
                <x-public.editorial.card
                    :url="route('public.articles.show', $articleLocale->slug)"
                    :title="$articleLocale->title"
                    :excerpt="$articleLocale->excerpt ?: $articleLocale->description"
                    :image="$imageUrl"
                    icon="newspaper" />
            @endforeach
        </div>
    </x-public.editorial.section>
</div>
@endif
@php $homeSectionMarkup['featured_content'] = ob_get_clean(); ob_start(); @endphp

{{-- ===================================================================
     SECTION 7 — CALL TO ACTION (guests only)
     Hidden for authenticated users: it duplicates the header's persistent
     "Create Account" signup, so it only earns its place as a conversion
     prompt for logged-out visitors.
     =================================================================== --}}
@guest
<section data-home-section="cta" class="bg-[image:var(--sx-grad)] py-20 text-white">
    <div class="sx-wrap grid justify-items-center gap-6 text-center">
        <h2 class="text-3xl font-extrabold leading-tight md:text-4xl">{{ $homeSectionsByKey->get('cta')?->heading }}</h2>
        <p class="max-w-3xl text-lg leading-loose text-white/90">{{ $homeSectionsByKey->get('cta')?->subheading }}</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ $registerUrl }}" class="sx-btn is-light">
                <i data-lucide="user-plus" aria-hidden="true"></i>
                <span>{{ __('site.register') }}</span>
            </a>
            <a href="#explore" class="sx-btn is-glass">
                <i data-lucide="info" aria-hidden="true"></i>
                <span>{{ __('site.learn_more') }}</span>
            </a>
        </div>
    </div>
</section>
@endguest
@php $homeSectionMarkup['cta'] = ob_get_clean(); @endphp

@foreach ($homeSections as $position => $homeSection)
    @php
        $previousKey = $homeSections[$position - 1]->key ?? null;
        $nextKey = $homeSections[$position + 1]->key ?? null;
    @endphp

    @if ($homeSection->key === 'hero')
        <section data-home-section="hero" class="hero-section sx-hero">
            {!! $heroBackgroundMarkup !!}
            <div class="sx-wrap py-20 md:py-28">
                {!! $heroIntroMarkup !!}
                @if ($nextKey === 'hero_cards')
                    <div data-home-section="hero_cards" class="mt-16">
                        {!! $heroCardsMarkup !!}
                    </div>
                @endif
            </div>
        </section>
    @elseif ($homeSection->key === 'hero_cards' && $previousKey !== 'hero')
        <section data-home-section="hero_cards" class="hero-section sx-hero">
            {!! $heroBackgroundMarkup !!}
            <div class="sx-wrap py-16">
                {!! $heroCardsMarkup !!}
            </div>
        </section>
    @elseif ($homeSection->key !== 'hero_cards')
        {!! $homeSectionMarkup[$homeSection->key] ?? '' !!}
    @endif
@endforeach

@endsection

@push('scripts')
<script>
    // Re-render icons after Blade renders dynamic elements
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.renderIcons === 'function') {
            window.renderIcons();
        }
    });
</script>
@endpush
