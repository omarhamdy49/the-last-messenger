{{--
    Shahid-style full-bleed slider row, pixel-matched: 26px bold title,
    12px gaps (8px on the portrait layouts), track bleeds to the viewport
    edges, solid circular arrows that appear on row hover when overflowing.
    Expects: $rowTitle (string), $cards (array of card arrays).
    Optional: $rowSubtitle (string),
              $rowViewAllUrl (string — "view all" renders as a link), or
              $rowViewAllClick (string — Alpine expression, renders as a button).
    Parent scope must provide scrollableRow() (index scripts).
--}}
<div class="relative group/row" x-data="scrollableRow()">
    <h2 class="text-xl sm:text-[26px] font-bold leading-snug text-gray-900 dark:text-white mb-4 flex items-baseline gap-2">
        <span class="break-words">{{ $rowTitle }}</span>
        @isset ($rowSubtitle)
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 hidden sm:inline border-s border-gray-300 dark:border-white/20 ps-2">{{ $rowSubtitle }}</span>
        @endisset
        @if (! empty($rowViewAllUrl ?? null) || ! empty($rowViewAllClick ?? null))
            @php $viewAllClasses = 'ms-auto shrink-0 inline-flex items-center gap-1 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline cursor-pointer'; @endphp
            @if (! empty($rowViewAllUrl ?? null))
                <a href="{{ $rowViewAllUrl }}" class="{{ $viewAllClasses }}">
                    <span>{{ __('media.public.view_all') }}</span>
                    <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                </a>
            @else
                <button type="button" @click="{{ $rowViewAllClick }}" class="{{ $viewAllClasses }}">
                    <span>{{ __('media.public.view_all') }}</span>
                    <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                </button>
            @endif
        @endif
    </h2>

    <div x-ref="track" @scroll.passive.debounce.150ms="checkScroll()"
         class="flex gap-2 xl:gap-3 overflow-x-auto scrollbar-hide snap-x pb-2 -mx-4 sm:-mx-6 px-4 sm:px-6">
        @foreach ($cards as $card)
            @include('public.media._program-card', ['card' => $card])
        @endforeach
    </div>

    {{-- Solid circular navigation, revealed on row hover (smart: only when the row overflows) --}}
    <button type="button" x-show="canScrollBack" x-cloak @click="scrollRow(-1)"
            aria-label="{{ __('media.public.scroll_back') }}"
            class="absolute start-0 top-1/3 -ms-3 sm:-ms-4 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/95 text-gray-900 dark:bg-gray-900/85 dark:text-white backdrop-blur-sm hidden sm:flex items-center justify-center sm:opacity-0 sm:group-hover/row:opacity-100 focus-visible:opacity-100 hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'chevron-right' : 'chevron-left' }}" class="w-6 h-6"></i>
    </button>
    <button type="button" x-show="canScrollForward" x-cloak @click="scrollRow(1)"
            aria-label="{{ __('media.public.scroll_forward') }}"
            class="absolute end-0 top-1/3 -me-3 sm:-me-4 w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/95 text-gray-900 dark:bg-gray-900/85 dark:text-white backdrop-blur-sm hidden sm:flex items-center justify-center sm:opacity-0 sm:group-hover/row:opacity-100 focus-visible:opacity-100 hover:scale-110 motion-reduce:transform-none transition-all duration-300 shadow-xl z-30">
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'chevron-left' : 'chevron-right' }}" class="w-6 h-6"></i>
    </button>
</div>
