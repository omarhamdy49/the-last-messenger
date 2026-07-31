{{--
    One chapter group inside a book page: the chapter's heading and preamble,
    then its hadiths as premium cards.

    Rendered inside the book page's filter scope — `hit($el)` and `view` come
    from that x-data, so this partial is not standalone.
--}}
@php $locale = app()->getLocale(); @endphp

{{-- The whole group hides when the filter empties it: a chapter heading with no
     hadiths under it reads as missing content rather than as a filtered view.
     No x-cloak — the unfiltered state is "shown", so the server-rendered markup
     is already correct and there is nothing to hide before Alpine boots. --}}
<section class="mb-10"
         x-show="[...$el.querySelectorAll('[data-grade]')].some(card => hit(card))">
    @if ($chapter)
        <header class="mb-5 ps-4 border-s-4 border-[#218838] animate-fade-in-up">
            <h2 class="text-lg sm:text-xl font-bold font-['Amiri'] text-gray-900 dark:text-white break-words">
                {{-- Some chapters carry no number in the source. --}}
                @if (filled($chapter->chapter_number))
                    <span class="text-[#0056b3] dark:text-[#64CCC5]">{{ $chapter->chapter_number }}.</span>
                @endif
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
            <li x-show="hit($el.firstElementChild)" x-transition.opacity>
                {{-- showRef off: the book page's header already names the
                     collection and book on every card's behalf. --}}
                <x-site.hadith-card :hadith="$hadith" :index="$loop->index" :show-ref="false" />
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
