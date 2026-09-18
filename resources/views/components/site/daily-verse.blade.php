{{--
    Quran of the day — content for the home page's daily section.

    Props:
        $verse — VerseDTO::toArray() output, or null when the daily pick failed
                 to resolve (the panel then renders a skeleton, not a dead card)
        $title — panel heading, DB-managed via home_sections

    Colour: everything comes from the public design tokens — one accent, one
    gradient, one ink scale — so this card reads as the same product as the
    module pages. Never reintroduce a per-card hue.
--}}
@props([
    'verse' => null,
    'title' => null,
])

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);

    // quran_chapters.id IS the mushaf number 1–114, so it doubles as the
    // {surahNumber} route parameter. Do not swap in revelation_order here — the
    // two have been confused before.
    $surahNumber = $verse['surah_id'] ?? null;
    $verseNumber = $verse['verse_number'] ?? null;
    $juzNumber = $verse['juz_number'] ?? null;
    $surahName = $verse['surah_name'] ?? null;
    $arabic = $verse['text_arabic'] ?? null;

    // The lead line follows the UI language and the other language drops to the
    // sub line: Arabic readers lead with the ayah and get the English beneath;
    // everyone else leads with their own translation and keeps the Arabic
    // beneath it. A missing translation falls back to Arabic as the lead, with
    // no sub line — never an empty card.
    $localeText = $locale === 'ar'
        ? null
        : ($verse['translations'][$locale] ?? $verse['translations']['en'] ?? null);

    [$mainText, $mainLang, $subText, $subLang] = $locale === 'ar'
        ? [$arabic, 'ar', $verse['translations']['en'] ?? null, 'en']
        : ($localeText
            ? [$localeText, $locale, $arabic, 'ar']
            : [$arabic, 'ar', null, null]);

    // Two targets: the surah chip opens the whole surah, the CTA lands on this
    // ayah (the surah reader scrolls to and highlights `#v{n}`). Both fall back
    // to the hub when the route is missing.
    $surahUrl = ($surahNumber && Route::has('public.quran.surah'))
        ? route('public.quran.surah', $surahNumber)
        : (Route::has('public.quran') ? route('public.quran') : '#');

    $readUrl = ($surahNumber && Route::has('public.quran.surah') && $verseNumber)
        ? $surahUrl.'#v'.$verseNumber
        : $surahUrl;
    // Explicit dir per paragraph: an English line inside the RTL page inherits
    // rtl and its trailing punctuation jumps to the front. Never rely on
    // inheritance for a paragraph whose language differs from the page.
    $dirFor = static fn (?string $lang): string => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr';
@endphp

<x-site.daily-panel :heading="$title ?? __('site.quran_of_day')">

    {{-- A mushaf on a rehl, not the generic book-open the Seerah cards already use. --}}
    <x-slot:glyph>
        <x-site.mushaf-icon class="h-6 w-6 text-white" />
    </x-slot:glyph>

    @if($verse)
        {{-- Reference strip: surah, its number, and the juz it sits in. The surah
             is the one interactive chip here; the number and juz are plain labels. --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            @if($surahName)
                <a href="{{ $surahUrl }}" class="sx-chip max-w-full" title="{{ $surahName }}">
                    <i data-lucide="book-marked" aria-hidden="true"></i>
                    <span class="sx-clamp-1 max-w-[12rem]">{{ $surahName }}</span>
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                </a>
            @endif
            @if($surahNumber)
                <span class="sx-chip tabular-nums">{{ __('site.surah_number') }} {{ $surahNumber }}</span>
            @endif
            @if($juzNumber)
                <span class="sx-chip tabular-nums">{{ __('quran.nav.juz') }} {{ $juzNumber }}</span>
            @endif
        </div>

        <x-site.scripture-pane>
            {{-- Lead line. The revealed text uses .sx-scripture (Amiri) and rtl
                 only when it really is Arabic — Urdu is Arabic-script but needs
                 the interface stack, not a naskh face.
                 Clamped with a show more/less toggle: the long ayat (2:282 and
                 friends) would otherwise push the panel past the fold. --}}
            @if($mainText)
                <x-site.expandable-text
                    :lang="$mainLang"
                    :dir="$dirFor($mainLang)"
                    clamp="line-clamp-2"
                    :text-class="Arr::toCssClasses([
                        'break-words text-center text-[color:var(--sx-ink)]',
                        'sx-scripture' => $mainLang === 'ar',
                        'text-lg font-semibold leading-relaxed sm:text-xl' => $mainLang !== 'ar',
                    ])">{{ $mainText }}@if($mainLang === 'ar' && $verseNumber)<x-site.ayah-marker :number="$verseNumber" />@endif</x-site.expandable-text>
            @endif

            @if($subText)
                <div class="mt-5 border-t border-[color:var(--sx-line)] pt-5">
                    <x-site.expandable-text
                        :lang="$subLang"
                        :dir="$dirFor($subLang)"
                        clamp="line-clamp-2"
                        :text-class="Arr::toCssClasses([
                            'break-words text-center text-[color:var(--sx-ink-2)]',
                            'font-arabic text-lg leading-[2]' => $subLang === 'ar',
                            'text-sm leading-relaxed' => $subLang !== 'ar',
                        ])">{{ $subText }}@if($subLang === 'ar' && $verseNumber)<x-site.ayah-marker :number="$verseNumber" />@endif</x-site.expandable-text>
                </div>
            @endif
        </x-site.scripture-pane>
    @else
        {{-- Resolve failed (cache miss + query error). Show the panel's shape,
             not a blank card, so the section keeps its two-column rhythm. --}}
        <x-site.scripture-pane role="status" aria-label="{{ __('site.loading') }}">
            <div class="grid gap-3">
                <div class="sx-skeleton mx-auto h-5 w-3/4"></div>
                <div class="sx-skeleton mx-auto h-5 w-5/6"></div>
                <div class="sx-skeleton mx-auto h-5 w-2/3"></div>
            </div>
            <span class="sr-only">{{ __('site.loading') }}</span>
        </x-site.scripture-pane>
    @endif

    <x-slot:actions>
        <a href="{{ $readUrl }}" class="sx-btn is-primary">
            <i data-lucide="book-open" aria-hidden="true"></i>
            <span>{{ __('site.read_surah') }}</span>
            <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
        </a>

        <x-site.share-button
            variant="surface"
            :url="$readUrl"
            :title="$surahName ?? __('site.quran_of_day')"
            :text="$mainText" />
    </x-slot:actions>
</x-site.daily-panel>
