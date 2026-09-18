@extends('layouts.public')

@section('title', __('quran.public.asbab_page_title'))
@section('meta_description', __('quran.public.asbab_page_description'))

@php
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    // The server already sorted the page (mushaf or chronological); grouping keeps
    // that order and turns the rail into one station per surah.
    $bySurah = collect($contexts->items())->groupBy(fn ($context) => $context->surahNumber);
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20" x-data="{ loading: false }">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.quran')" :eyebrow-label="__('site.quran')" eyebrow-icon="book-open"
            :title="__('quran.public.asbab_hero_title')" :lead="__('quran.public.asbab_hero_subtitle')"
            :stats="[
                ['icon' => 'calendar-days', 'label' => __('quran.public.asbab_title'), 'value' => $num($contexts->total())],
                ['icon' => 'library', 'label' => __('quran.public.all_surahs'), 'value' => $num(count($surahOptions))],
            ]">
            <div class="flex flex-wrap items-center gap-2">
                <span class="sx-chip"><i data-lucide="calendar-days" aria-hidden="true"></i>{{ __('quran.public.asbab_badge_events') }}</span>
                <span class="sx-chip"><i data-lucide="info" aria-hidden="true"></i>{{ __('quran.public.asbab_badge_context') }}</span>
                <span class="sx-chip"><i data-lucide="book-marked" aria-hidden="true"></i>{{ __('quran.public.asbab_badge_references') }}</span>
            </div>
        </x-public.editorial.page-hero>

        {{-- Filters (server-side via GET). Period stays a link set so a share carries it. --}}
        <form method="GET" action="{{ route('public.quran.asbab') }}" id="asbab-filters" role="search" class="sx-surface sx-filters" @submit="loading = true">
            <input type="hidden" name="period" value="{{ $filters['period'] ?? '' }}">

            <div class="sx-filter-row">
                <div class="relative min-w-0">
                    <label for="asbab-q" class="sr-only">{{ __('quran.public.asbab_search_placeholder') }}</label>
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="asbab-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                           enterkeyhint="search" placeholder="{{ __('quran.public.asbab_search_placeholder') }}" class="sx-input ps-12"
                           aria-label="{{ __('quran.public.asbab_search_placeholder') }}">
                </div>

                <div>
                    <label for="asbab-surah" class="sr-only">{{ __('quran.public.all_surahs') }}</label>
                    <select id="asbab-surah" name="surah" onchange="this.form.submit()" class="sx-input text-base sm:text-sm"
                            aria-label="{{ __('quran.public.all_surahs') }}">
                        <option value="">{{ __('quran.public.all_surahs') }}</option>
                        @foreach ($surahOptions as $surahOption)
                            <option value="{{ $surahOption['number'] }}" @selected(($filters['surah'] ?? null) === $surahOption['number'])>
                                {{ $surahOption['number'] }}. {{ $surahOption['nameAr'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="asbab-sort" class="sr-only">{{ __('quran.public.sort_label') }}</label>
                    <select id="asbab-sort" name="sort" onchange="this.form.submit()" class="sx-input text-base sm:text-sm"
                            aria-label="{{ __('quran.public.sort_label') }}">
                        <option value="mushaf" @selected(($filters['sort'] ?? 'mushaf') === 'mushaf')>{{ __('quran.public.sort_number') }}</option>
                        <option value="chronological" @selected(($filters['sort'] ?? '') === 'chronological')>{{ __('quran.public.sort_chronological') }}</option>
                    </select>
                </div>

                <div>
                    <label for="asbab-per-page" class="sr-only">{{ __('quran.public.sort_label') }}</label>
                    <select id="asbab-per-page" name="per_page" onchange="this.form.submit()" class="sx-input text-base sm:text-sm"
                            aria-label="{{ __('quran.public.sort_label') }}">
                        @foreach ([5, 10, 20, 50] as $size)
                            <option value="{{ $size }}" @selected(($filters['per_page'] ?? 10) === $size)>{{ $size }} {{ __('quran.public.asbab_event_unit') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('quran.public.filter_all') }}">
                @foreach (['' => ['filter_all', 'layers'], 'makki' => ['filter_makki', 'sunrise'], 'madani' => ['filter_madani', 'landmark']] as $value => [$label, $icon])
                    @php $isOn = ($filters['period'] ?? '') === $value || (($filters['period'] ?? null) === null && $value === ''); @endphp
                    <a href="{{ route('public.quran.asbab', array_filter(array_merge($filters, ['period' => $value, 'page' => null]), fn ($v) => $v !== null && $v !== '')) }}"
                       @click="loading = true"
                       class="sx-chip @if ($isOn) is-active @endif"
                       @if ($isOn) aria-current="true" @endif>
                        <i data-lucide="{{ $icon }}" aria-hidden="true"></i>{{ __('quran.public.'.$label) }}
                    </a>
                @endforeach

                <button type="submit" class="sx-btn is-primary ms-auto">
                    <i data-lucide="search" aria-hidden="true"></i>{{ __('site.search') }}
                </button>
            </div>
        </form>

        {{-- Results count --}}
        <div class="mt-8 mb-5 flex flex-wrap items-center justify-between gap-2 text-sm text-[color:var(--sx-muted)]">
            <p aria-live="polite">
                {{ __('site.showing') }}
                <strong class="tabular-nums text-[color:var(--sx-ink)]">{{ $num($contexts->count()) }}</strong>
                {{ __('site.of') }}
                <strong class="tabular-nums text-[color:var(--sx-ink)]">{{ $num($contexts->total()) }}</strong>
                {{ __('quran.public.asbab_event_unit') }}
            </p>
            @if ($contexts->total() > 0)
                <p>
                    {{ __('quran.public.page') }} <strong class="tabular-nums text-[color:var(--sx-ink)]">{{ $num($contexts->currentPage()) }}</strong>
                    {{ __('site.of') }} <strong class="tabular-nums text-[color:var(--sx-ink)]">{{ $num($contexts->lastPage()) }}</strong>
                </p>
            @endif
        </div>

        {{-- Skeleton while a filter submit navigates. --}}
        <div x-show="loading" x-cloak class="grid gap-4" role="status" aria-label="{{ __('quran.public.asbab_loading') }}">
            <p class="text-center text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.asbab_loading') }}</p>
            @for ($i = 0; $i < 3; $i++)
                <div class="sx-surface grid gap-4 p-5" aria-hidden="true">
                    <div class="sx-skeleton h-8 w-40 !rounded-full"></div>
                    <div class="sx-skeleton h-7 w-2/3"></div>
                    <div class="sx-skeleton h-24"></div>
                    <div class="sx-skeleton h-4"></div>
                    <div class="sx-skeleton h-4 w-5/6"></div>
                </div>
            @endfor
        </div>

        <div x-show="!loading">
            @if ($bySurah->isEmpty())
                <x-public.editorial.empty-state icon="search-x"
                    :title="__('site.no_results')" :hint="__('quran.public.asbab_empty_message')"
                    :action-url="route('public.quran.asbab')" :action-label="__('site.reset_filters')" />
            @else
                <div class="sx-timeline">
                    @foreach ($bySurah as $surahNumber => $group)
                        @php $first = $group->first(); @endphp
                        <section class="sx-year" aria-label="{{ __('quran.public.surah_unit') }} {{ $first->surahNameAr }}">
                            <div class="sx-year-head">
                                <strong>{{ $num($surahNumber) }}</strong>
                                <span>
                                    <span class="block sx-clamp-1" title="{{ $first->surahNameAr }}">{{ __('quran.public.surah_unit') }} {{ $first->surahNameAr }}</span>
                                    <span class="block tabular-nums">{{ $num($group->count()) }} {{ __('quran.public.asbab_event_unit') }}</span>
                                </span>
                            </div>

                            <div class="sx-year-list">
                                @foreach ($group as $context)
                                    <article class="sx-surface grid gap-4 p-5 sm:p-6">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($context->period === 'makki')
                                                <span class="sx-chip"><i data-lucide="sunrise" aria-hidden="true"></i>{{ __('quran.public.makki') }}</span>
                                            @else
                                                <span class="sx-chip"><i data-lucide="landmark" aria-hidden="true"></i>{{ __('quran.public.madani') }}</span>
                                            @endif
                                            <span class="sx-chip tabular-nums"><i data-lucide="hash" aria-hidden="true"></i>{{ __('quran.public.asbab_verses_label') }} {{ $context->verseRange }}</span>
                                        </div>

                                        <h3 class="sx-clamp-2 text-xl font-extrabold leading-snug text-[color:var(--sx-ink)]" title="{{ $context->title }}">
                                            {{ $context->title }}
                                        </h3>

                                        @if ($context->verseExcerpt)
                                            <div class="sx-panel p-4">
                                                <p class="sx-scripture text-center" dir="rtl">{{ $context->verseExcerpt }}</p>
                                            </div>
                                        @endif

                                        <div>
                                            <h4 class="mb-2 flex items-center gap-2 font-bold text-[color:var(--sx-ink)]">
                                                <i data-lucide="info" class="h-5 w-5 shrink-0 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                                                {{ __('quran.public.historical_context') }}
                                            </h4>
                                            <p class="sx-prose !max-w-none !text-base">{{ $context->narrative }}</p>
                                        </div>

                                        @if ($context->sourceReference)
                                            <p class="sx-note text-sm">
                                                <i data-lucide="book-marked" class="h-5 w-5 shrink-0" aria-hidden="true"></i>
                                                <span>
                                                    <strong class="text-[color:var(--sx-ink)]">{{ __('quran.public.reference_label') }}:</strong>
                                                    {{ $context->sourceReference }}
                                                </span>
                                            </p>
                                        @endif

                                        <a href="{{ route('public.quran.surah', $context->surahNumber) }}" class="sx-btn is-primary justify-self-start">
                                            <i data-lucide="book-open" aria-hidden="true"></i>{{ __('quran.public.read_surah') }}
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif

            <div class="mt-12">
                <x-public.editorial.pagination :paginator="$contexts->withQueryString()" />
            </div>
        </div>
    </div>
</div>
@endsection
