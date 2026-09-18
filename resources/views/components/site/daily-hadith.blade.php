{{--
    Hadith of the day — content for the home page's daily section.

    Props:
        $hadith — HadithDetail::toArray() output, or null when the daily pick
                  failed to resolve (the panel then renders a skeleton)
        $title  — panel heading, DB-managed via home_sections

    Colour: everything comes from the public design tokens, the same as the
    verse card beside it. Never reintroduce a per-card hue.
--}}
@props([
    'hadith' => null,
    'title' => null,
])

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);

    $texts = collect($hadith['texts'] ?? []);
    $arabic = $texts->firstWhere('locale', 'ar')['body'] ?? null;

    // Same rule as the verse card: the lead line follows the UI language and the
    // other language drops beneath it. No translation for this hadith → Arabic
    // leads alone rather than the card rendering empty.
    $localeText = $locale === 'ar'
        ? null
        : ($texts->firstWhere('locale', $locale)['body']
            ?? $texts->firstWhere('locale', 'en')['body']
            ?? null);

    [$mainText, $mainLang, $subText, $subLang] = $locale === 'ar'
        ? [$arabic, 'ar', $texts->firstWhere('locale', 'en')['body'] ?? null, 'en']
        : ($localeText
            ? [$localeText, $locale, $arabic, 'ar']
            : [$arabic, 'ar', null, null]);

    $narratedBy = $texts->firstWhere('locale', 'ar')['narrated_by'] ?? null;
    $grade = $hadith['display_grade'] ?? null;
    $source = $hadith['collection_name'] ?? $hadith['book_title'] ?? null;
    $hadithNumber = $hadith['hadith_number'] ?? null;

    // Two targets, mirroring the verse card: the source chip opens the whole
    // collection, the CTA lands on this hadith (`{collection}:{number}`).
    $collectionCode = $hadith['collection'] ?? null;
    $hubUrl = Route::has('public.hadith') ? route('public.hadith') : '#';

    $collectionUrl = ($collectionCode && Route::has('public.hadith.collection'))
        ? route('public.hadith.collection', $collectionCode)
        : $hubUrl;

    // One param, not two: the route is `/hadith/{collection}:{referenceKey}` and
    // the key is reference_key ("1-1"), not the display number ("1").
    $referenceKey = $hadith['reference_key'] ?? null;

    $readUrl = ($collectionCode && $referenceKey && Route::has('public.hadith.show'))
        ? route('public.hadith.show', ['reference' => $collectionCode.':'.$referenceKey])
        : $hubUrl;

    // Only 'sahih' has a guaranteed key; anything else falls back to the raw value
    // rather than rendering `site.grading_hasan` at the user.
    $gradeKey = $grade ? 'site.grading_'.$grade : null;
    $gradeLabel = $gradeKey ? (Lang::has($gradeKey) ? __($gradeKey) : $grade) : null;
    // Explicit dir per paragraph: an English line inside the RTL page inherits
    // rtl and its trailing punctuation jumps to the front. Never rely on
    // inheritance for a paragraph whose language differs from the page.
    $dirFor = static fn (?string $lang): string => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr';
@endphp

<x-site.daily-panel
    :heading="$title ?? __('site.hadith_of_day')"
    icon="message-square-quote">

    @if($hadith)
        {{-- Reference strip: source collection, hadith number, authentication.
             The collection is the one interactive chip here; the number and grade are
             plain labels. --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            @if($source)
                <a href="{{ $collectionUrl }}" class="sx-chip max-w-full" title="{{ $source }}">
                    <i data-lucide="book-marked" aria-hidden="true"></i>
                    <span class="sx-clamp-1 max-w-[12rem]">{{ $source }}</span>
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                </a>
            @endif
            @if($hadithNumber)
                <span class="sx-chip tabular-nums">#{{ $hadithNumber }}</span>
            @endif
            @if($gradeLabel)
                {{-- Icon + text, never colour alone, so the grade survives a
                     colour-blind or monochrome read. --}}
                <span class="sx-chip is-active">
                    <i data-lucide="badge-check" aria-hidden="true"></i>{{ $gradeLabel }}
                </span>
            @endif
        </div>

        <x-site.scripture-pane>
            @if($narratedBy)
                <p class="mb-3 text-center text-xs font-semibold text-[color:var(--sx-muted)]">
                    {{ $narratedBy }}
                </p>
            @endif

            {{-- Hadith bodies carry the full isnad and run far longer than a
                 verse, so both lines clamp with a show more/less toggle rather
                 than stretching the panel past the fold. --}}
            @if($mainText)
                <x-site.expandable-text
                    :lang="$mainLang"
                    :dir="$dirFor($mainLang)"
                    clamp="line-clamp-2"
                    :text-class="Arr::toCssClasses([
                        'break-words text-center text-[color:var(--sx-ink)]',
                        'sx-scripture !text-[length:clamp(1.25rem,1.1rem+0.8vw,1.6rem)]' => $mainLang === 'ar',
                        'text-base font-semibold leading-relaxed sm:text-lg' => $mainLang !== 'ar',
                    ])">{{ $mainText }}</x-site.expandable-text>
            @endif

            @if($subText)
                <div class="mt-5 border-t border-[color:var(--sx-line)] pt-5">
                    <x-site.expandable-text
                        :lang="$subLang"
                        :dir="$dirFor($subLang)"
                        clamp="line-clamp-2"
                        :text-class="Arr::toCssClasses([
                            'break-words text-center text-[color:var(--sx-ink-2)]',
                            'font-arabic text-base leading-[2]' => $subLang === 'ar',
                            'text-sm leading-relaxed' => $subLang !== 'ar',
                        ])">{{ $subText }}</x-site.expandable-text>
                </div>
            @endif
        </x-site.scripture-pane>
    @else
        <x-site.scripture-pane role="status" aria-label="{{ __('site.loading') }}">
            <div class="grid gap-3">
                <div class="sx-skeleton mx-auto h-4 w-3/4"></div>
                <div class="sx-skeleton mx-auto h-4 w-5/6"></div>
                <div class="sx-skeleton mx-auto h-4 w-2/3"></div>
            </div>
            <span class="sr-only">{{ __('site.loading') }}</span>
        </x-site.scripture-pane>
    @endif

    <x-slot:actions>
        <a href="{{ $readUrl }}" class="sx-btn is-primary">
            <i data-lucide="book-open" aria-hidden="true"></i>
            <span>{{ __('site.read_explanation') }}</span>
            <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
        </a>

        <x-site.share-button
            variant="surface"
            :url="$readUrl"
            :title="$source ?? __('site.hadith_of_day')"
            :text="$mainText" />
    </x-slot:actions>
</x-site.daily-panel>
