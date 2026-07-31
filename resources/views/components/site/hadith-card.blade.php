{{--
    Premium hadith row card — ported from `.hadith-card` in
    hadith-book-chapter-enhanced.html. Shared by the book/chapter listing and by
    search results so a hadith looks identical wherever it is listed.

    The prototype's edit/delete buttons are admin affordances and are omitted:
    this renders on the public site.

    Props:
        $hadith  — App\Domain\Hadith\Models\Hadith (with collection, book,
                   chapter, primaryNarrator and texts already eager loaded)
        $index   — position in the list, for the staggered entrance delay
        $showRef — render the collection/book footer (off on pages that already
                   name the collection in their header)
--}}
@props([
    'hadith',
    'index' => 0,
    'showRef' => true,
])

@php
    $locale = app()->getLocale();
    $text = $hadith->textFor($locale);
    $grade = $hadith->display_grade->value;
    $url = route('public.hadith.show', [$hadith->collection->code, $hadith->hadith_number]);

    // The chain line: the source's own `narrated_by` where it exists, otherwise
    // the linked narrator record. Never both — they say the same thing.
    $chain = $text?->narrated_by
        ?: ($hadith->primaryNarrator?->name
            ? __('hadith.narrated_by').': '.$hadith->primaryNarrator->name
            : null);

    $isArabic = $text?->locale === 'ar';

    // Colour is paired with the label, never carrying the verdict alone.
    $gradeIcon = match ($grade) {
        'sahih', 'hasan' => 'badge-check',
        'daif' => 'alert-triangle',
        'mawdu' => 'shield-x',
        default => 'help-circle',
    };

    // Only 1–8 exist; wrapping keeps long pages from delaying rows off-screen.
    $stagger = 'stagger-'.(($index % 8) + 1);
@endphp

{{-- data-text is the server-folded `search_body` (diacritics stripped, hamza
     forms collapsed) so an in-page filter can match what a reader actually
     types. window.foldForSearch() applies the same fold to the query. --}}
<article data-grade="{{ $grade }}"
         data-text="{{ \Illuminate\Support\Str::lower(
             ($text?->search_body ?: $text?->body ?? '').' '.$chain.' '.$hadith->hadith_number
         ) }}"
         {{ $attributes->class([
             'hadith-card group animate-fade-in-up '.$stagger,
             'rounded-2xl border border-gray-200 dark:border-gray-800',
             'bg-white dark:bg-[#16213e] shadow-sm',
         ]) }}>

    <div class="p-5 sm:p-6">

        <div class="flex items-start gap-4">

            {{-- Number medallion --}}
            <span aria-hidden="true"
                  class="shrink-0 grid place-items-center w-12 h-12 sm:w-14 sm:h-14 rounded-full
                         bg-gradient-to-br from-[#0056b3] to-[#218838] text-white
                         font-black text-sm sm:text-base shadow-lg
                         transition-transform duration-300 group-hover:scale-110
                         motion-reduce:transform-none motion-reduce:transition-none">
                {{ $hadith->hadith_number }}
            </span>

            <div class="min-w-0 flex-1">

                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    {{-- Stretched link: the whole card is the target, while the
                         action buttons below sit above it on the z axis. --}}
                    <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <a href="{{ $url }}"
                           class="after:absolute after:inset-0 rounded-sm transition-colors
                                  group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5]
                                  focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                            {{ __('hadith.hadith_number_label', ['number' => $hadith->hadith_number]) }}
                        </a>
                    </h3>

                    <span class="grade-badge grade-badge-{{ $grade }}
                                 inline-flex items-center gap-1.5 shrink-0
                                 px-3 py-1 rounded-full text-[11px] font-black">
                        <i data-lucide="{{ $gradeIcon }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                        {{ __('hadith.grade_'.$grade) }}
                    </span>
                </div>

                @if ($text)
                    <p dir="{{ $isArabic ? 'rtl' : 'ltr' }}" lang="{{ $text->locale }}"
                       class="hadith-matn mt-2.5 break-words text-start line-clamp-3
                              text-gray-900 dark:text-gray-100
                              transition-colors group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5]
                              {{ $isArabic ? "font-['Amiri'] text-lg sm:text-xl leading-[2.1]" : 'text-sm sm:text-base leading-8' }}">
                        {{ $text->body }}
                    </p>
                @endif

                @if ($chain)
                    <p class="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300 line-clamp-1 break-words">
                        {{ $chain }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Always rendered: it carries the actions even when the reference is
             suppressed, so `justify-between` still needs a start element. --}}
        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80
                    flex flex-wrap items-center justify-between gap-x-3 gap-y-2">

            @if ($showRef)
                <p class="min-w-0 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <i data-lucide="book" class="w-4 h-4 shrink-0 text-[#218838] dark:text-[#64CCC5]" aria-hidden="true"></i>
                    <span class="truncate font-semibold">
                        {{ $hadith->collection->getTranslation('name', $locale, true) }}
                    </span>
                    @if ($bookTitle = $hadith->book?->getTranslation('title', $locale, true))
                        <span class="opacity-50" aria-hidden="true">•</span>
                        <span class="truncate">{{ $bookTitle }}</span>
                    @endif
                </p>
            @else
                <span aria-hidden="true"></span>
            @endif

            {{-- Above the stretched link so these stay independently clickable. --}}
            <div class="relative z-10 flex items-center gap-1">
                <a href="{{ $url }}"
                   class="inline-flex h-11 min-w-11 items-center justify-center gap-1.5 px-3 rounded-lg
                          text-xs font-semibold text-[#0056b3] dark:text-[#64CCC5]
                          transition hover:bg-[#0056b3]/10 dark:hover:bg-[#64CCC5]/10
                          focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                    <i data-lucide="eye" class="w-4 h-4" aria-hidden="true"></i>
                    <span class="hidden sm:inline">{{ __('hadith.read_hadith') }}</span>
                </a>

                <x-site.share-button variant="surface"
                                     :url="$url"
                                     :title="__('hadith.hadith').' '.$hadith->hadith_number"
                                     :text="\Illuminate\Support\Str::limit($text?->body ?? '', 160)" />
            </div>
        </div>
    </div>
</article>
