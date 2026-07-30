@php $locale = app()->getLocale(); @endphp

<section class="mb-10">
    @if ($chapter)
        <header class="mb-4 ps-4 border-s-4 border-[#218838]">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white break-words">
                <span class="text-[#0056b3] dark:text-[#64CCC5]">{{ $chapter->chapter_number }}.</span>
                {{ $chapter->getTranslation('title', $locale, true) }}
            </h2>

            {{-- The preamble the source prints before a chapter's hadiths. --}}
            @if ($intro = $chapter->getTranslation('intro', $locale, true))
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-7 break-words">{{ $intro }}</p>
            @endif
        </header>
    @endif

    <ol class="space-y-4">
        @foreach ($hadiths as $hadith)
            @php $text = $hadith->textFor($locale); @endphp
            <li class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#16213e] p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <a href="{{ route('public.hadith.show', [$collection->code, $hadith->hadith_number]) }}"
                       class="text-sm font-bold text-[#0056b3] dark:text-[#64CCC5] hover:underline">
                        {{ $collection->code }}:{{ $hadith->hadith_number }}
                    </a>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('hadith.grade') }}: {{ __('hadith.grade_'.$hadith->display_grade->value) }}
                    </span>
                </div>

                @if ($text)
                    <p dir="{{ $text->locale === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ $text->locale }}"
                       class="{{ $text->locale === 'ar' ? "font-['Amiri'] text-xl leading-[2.2]" : 'text-sm leading-7' }}
                              text-gray-800 dark:text-gray-200 line-clamp-4 break-words text-start">
                        {{ $text->body }}
                    </p>
                @endif
            </li>
        @endforeach
    </ol>

    {{-- The chapter's closing passage, where the source has one. --}}
    @if ($chapter && ($ending = $chapter->getTranslation('ending', $locale, true)))
        <p class="mt-4 ps-4 border-s-2 border-gray-200 dark:border-gray-700
                  text-sm italic text-gray-600 dark:text-gray-300 leading-7 break-words">
            {{ $ending }}
        </p>
    @endif
</section>
