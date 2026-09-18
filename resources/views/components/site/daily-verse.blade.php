{{--
    Quran of the day — content for the home page's daily section.

    Props:
        $verse — VerseDTO::toArray() output, or null when the daily pick failed
                 to resolve (the panel then renders a skeleton, not a dead card)
        $title — panel heading, DB-managed via home_sections

    Ink contract: #0f7c8c is the AA-safe deep companion to the module's #17a2b8
    accent (4.97:1 on white, 4.9:1 for white text on the panel gradient). Keep
    accents on this card at that hue — #17a2b8 itself only reaches ~3:1.
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

<x-site.daily-panel
    :heading="$title ?? __('site.quran_of_day')"
    gradient="from-[#0f7c8c] via-[#0c6675] to-[#0a4f5c]">

    {{-- A mushaf on a rehl, not the generic book-open the Seerah cards already use. --}}
    <x-slot:glyph>
        <x-site.mushaf-icon class="h-7 w-7 text-white sm:h-8 sm:w-8" />
    </x-slot:glyph>

    @if($verse)
        {{-- Reference strip: surah, its number, and the juz it sits in. The surah
             is the one interactive chip here; the number and juz are plain labels. --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            @if($surahName)
                {{-- Sized to match the labels beside it. `before:-inset-2` grows the
                     tap area past those visual bounds so the small chip still clears
                     the 44px touch target. --}}
                <a href="{{ $surahUrl }}"
                   class="relative inline-flex max-w-full items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5
                          text-xs font-bold text-white ring-1 ring-inset ring-white/25 backdrop-blur-sm transition
                          before:absolute before:-inset-2 before:content-[''] hover:bg-white/25 hover:ring-white/40
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-white
                          focus-visible:ring-offset-2 focus-visible:ring-offset-transparent">
                    <i class="h-3.5 w-3.5 shrink-0" data-lucide="book-marked"></i>
                    <span class="truncate">{{ $surahName }}</span>
                    <i class="h-3 w-3 shrink-0 opacity-70"
                       data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}"></i>
                </a>
            @endif
            @if($surahNumber)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold
                             text-white/90 ring-1 ring-inset ring-white/15">
                    {{ __('site.surah_number') }} {{ $surahNumber }}
                </span>
            @endif
            @if($juzNumber)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold
                             text-white/90 ring-1 ring-inset ring-white/15">
                    {{ __('quran.nav.juz') }} {{ $juzNumber }}
                </span>
            @endif
        </div>

        <x-site.scripture-pane tone="text-[#0f7c8c]">
            {{-- Lead line. Amiri and RTL only when the text really is Arabic —
                 Urdu is Arabic-script but needs the UI stack, not a naskh face. --}}
            {{-- Clamped with a show more/less toggle: the long ayat (2:282 and
                 friends) would otherwise push the panel past the fold. --}}
            @if($mainText)
                <x-site.expandable-text
                    :lang="$mainLang"
                    :dir="$dirFor($mainLang)"
                    clamp="line-clamp-2"
                    tone-class="text-[#0f7c8c] dark:text-[#64CCC5]"
                    :text-class="Arr::toCssClasses([
                        'break-words text-center text-gray-900 dark:text-gray-50',
                        'font-arabic text-2xl leading-[2.2] sm:text-3xl' => $mainLang === 'ar',
                        'text-lg font-semibold leading-relaxed sm:text-xl' => $mainLang !== 'ar',
                    ])">{{ $mainText }}@if($mainLang === 'ar' && $verseNumber)<x-site.ayah-marker :number="$verseNumber" />@endif</x-site.expandable-text>
            @endif

            @if($subText)
                <div class="mt-5 border-t border-black/5 pt-5 dark:border-white/10">
                    <x-site.expandable-text
                        :lang="$subLang"
                        :dir="$dirFor($subLang)"
                        clamp="line-clamp-2"
                        tone-class="text-[#0f7c8c] dark:text-[#64CCC5]"
                        :text-class="Arr::toCssClasses([
                            'break-words text-center text-gray-600 dark:text-gray-300',
                            'font-arabic text-lg leading-[2]' => $subLang === 'ar',
                            'text-sm leading-relaxed' => $subLang !== 'ar',
                        ])">{{ $subText }}@if($subLang === 'ar' && $verseNumber)<x-site.ayah-marker :number="$verseNumber" tone="text-[#0f7c8c]/70 dark:text-[#64CCC5]/70" />@endif</x-site.expandable-text>
                </div>
            @endif
        </x-site.scripture-pane>
    @else
        {{-- Resolve failed (cache miss + query error). Show the panel's shape,
             not a blank card, so the section keeps its two-column rhythm. --}}
        <x-site.scripture-pane tone="text-[#0f7c8c]" role="status" aria-label="{{ __('site.loading') }}">
            <div class="animate-pulse space-y-3">
                <div class="mx-auto h-5 w-3/4 rounded bg-gray-200 dark:bg-white/10"></div>
                <div class="mx-auto h-5 w-5/6 rounded bg-gray-200 dark:bg-white/10"></div>
                <div class="mx-auto h-5 w-2/3 rounded bg-gray-200 dark:bg-white/10"></div>
            </div>
            <span class="sr-only">{{ __('site.loading') }}</span>
        </x-site.scripture-pane>
    @endif

    <x-slot:actions>
        {{-- Ghost pill, sized to its label rather than the panel width: white
             text on this gradient clears AA, so the CTA can read as refined
             rather than as a full-bleed white slab. --}}
        <a href="{{ $readUrl }}"
           class="inline-flex h-11 items-center gap-2 rounded-full bg-white/15 px-5 text-sm font-bold text-white
                  ring-1 ring-inset ring-white/30 backdrop-blur-sm transition hover:bg-white/25
                  hover:ring-white/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white
                  focus-visible:ring-offset-2 focus-visible:ring-offset-transparent">
            <i class="h-4 w-4" data-lucide="book-open"></i>
            <span>{{ __('site.read_surah') }}</span>
            <i class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
               data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
        </a>

        <x-site.share-button
            :url="$readUrl"
            :title="$surahName ?? __('site.quran_of_day')"
            :text="$mainText" />
    </x-slot:actions>
</x-site.daily-panel>
