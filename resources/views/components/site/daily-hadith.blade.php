{{--
    Hadith of the day — content for the home page's daily section.

    Props:
        $hadith — HadithDetail::toArray() output, or null when the daily pick
                  failed to resolve (the panel then renders a skeleton)
        $title  — panel heading, DB-managed via home_sections

    Ink contract: #1b6b2e is the AA-safe deep companion to the module's #218838
    accent (5.9:1 on white, 6.5:1 for white text on the panel gradient).
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
    icon="message-square-quote"
    gradient="from-[#1b6b2e] via-[#175c27] to-[#12401c]">

    @if($hadith)
        {{-- Reference strip: source collection, hadith number, authentication.
             The collection is the one interactive chip here; the number and grade are
             plain labels. --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            @if($source)
                {{-- Sized to match the labels beside it. `before:-inset-2` grows the
                     tap area past those visual bounds so the small chip still clears
                     the 44px touch target. --}}
                <a href="{{ $collectionUrl }}"
                   class="relative inline-flex max-w-full items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5
                          text-xs font-bold text-white ring-1 ring-inset ring-white/25 backdrop-blur-sm transition
                          before:absolute before:-inset-2 before:content-[''] hover:bg-white/25 hover:ring-white/40
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-white
                          focus-visible:ring-offset-2 focus-visible:ring-offset-transparent">
                    <i class="h-3.5 w-3.5 shrink-0" data-lucide="book-marked"></i>
                    <span class="truncate">{{ $source }}</span>
                    <i class="h-3 w-3 shrink-0 opacity-70"
                       data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}"></i>
                </a>
            @endif
            @if($hadithNumber)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold
                             text-white/90 ring-1 ring-inset ring-white/15">
                    #{{ $hadithNumber }}
                </span>
            @endif
            @if($gradeLabel)
                {{-- Icon + text, never colour alone, so the grade survives a
                     colour-blind or monochrome read. --}}
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#64CCC5]/20 px-3 py-1.5 text-xs font-bold
                             text-white ring-1 ring-inset ring-[#64CCC5]/40">
                    <i class="h-3.5 w-3.5" data-lucide="badge-check"></i>
                    {{ $gradeLabel }}
                </span>
            @endif
        </div>

        <x-site.scripture-pane tone="text-[#1b6b2e]">
            @if($narratedBy)
                <p class="mb-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
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
                    tone-class="text-[#1b6b2e] dark:text-[#64CCC5]"
                    :text-class="Arr::toCssClasses([
                        'break-words text-center text-gray-900 dark:text-gray-50',
                        'font-arabic text-xl leading-[2] sm:text-2xl' => $mainLang === 'ar',
                        'text-base font-semibold leading-relaxed sm:text-lg' => $mainLang !== 'ar',
                    ])">{{ $mainText }}</x-site.expandable-text>
            @endif

            @if($subText)
                <div class="mt-5 border-t border-black/5 pt-5 dark:border-white/10">
                    <x-site.expandable-text
                        :lang="$subLang"
                        :dir="$dirFor($subLang)"
                        clamp="line-clamp-2"
                        tone-class="text-[#1b6b2e] dark:text-[#64CCC5]"
                        :text-class="Arr::toCssClasses([
                            'break-words text-center text-gray-600 dark:text-gray-300',
                            'font-arabic text-base leading-[2]' => $subLang === 'ar',
                            'text-sm leading-relaxed' => $subLang !== 'ar',
                        ])">{{ $subText }}</x-site.expandable-text>
                </div>
            @endif
        </x-site.scripture-pane>
    @else
        <x-site.scripture-pane tone="text-[#1b6b2e]" role="status" aria-label="{{ __('site.loading') }}">
            <div class="animate-pulse space-y-3">
                <div class="mx-auto h-4 w-3/4 rounded bg-gray-200 dark:bg-white/10"></div>
                <div class="mx-auto h-4 w-5/6 rounded bg-gray-200 dark:bg-white/10"></div>
                <div class="mx-auto h-4 w-2/3 rounded bg-gray-200 dark:bg-white/10"></div>
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
            <span>{{ __('site.read_explanation') }}</span>
            <i class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5"
               data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
        </a>

        <x-site.share-button
            :url="$readUrl"
            :title="$source ?? __('site.hadith_of_day')"
            :text="$mainText" />
    </x-slot:actions>
</x-site.daily-panel>
