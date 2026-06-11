{{--
    Horizontal scrollable program row with floating glass arrows (premium prototype).
    Expects: $rowTitle (string), $rowIcon (lucide name), $cards (array of card arrays).
    Optional: $rowSubtitle (string).
    Parent scope must provide mediaHub() (My List helpers); scrollableRow() comes from index scripts.
--}}
<div class="relative" x-data="scrollableRow()">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="w-1 h-8 bg-gradient-to-b from-[#0056b3] to-[#218838] rounded-full" aria-hidden="true"></span>
            <i data-lucide="{{ $rowIcon }}" class="w-6 h-6 text-[#0056b3] dark:text-[#64CCC5]" aria-hidden="true"></i>
            <span class="break-words">{{ $rowTitle }}</span>
            @isset ($rowSubtitle)
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 hidden sm:inline">{{ $rowSubtitle }}</span>
            @endisset
        </h2>
    </div>

    <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
         class="flex gap-4 overflow-x-auto scrollbar-hide snap-x pb-4 -mx-1 px-1">
        @foreach ($cards as $card)
            @include('public.media._program-card', ['card' => $card])
        @endforeach
    </div>

    {{-- Floating glass navigation (smart: only when the row overflows) --}}
    <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
            aria-label="{{ __('media.public.scroll_back') }}"
            class="absolute start-0 top-1/3 -ms-3 sm:-ms-5 w-11 h-11 sm:w-14 sm:h-14 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
    </button>
    <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
            aria-label="{{ __('media.public.scroll_forward') }}"
            class="absolute end-0 top-1/3 -me-3 sm:-me-5 w-11 h-11 sm:w-14 sm:h-14 glass rounded-full hidden sm:flex items-center justify-center text-gray-900 dark:text-white hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
    </button>
</div>
