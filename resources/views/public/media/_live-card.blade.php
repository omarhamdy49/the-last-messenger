{{--
    Live channel card — landscape 16:9, flat, with a LIVE/status badge.
    Expects: $card (array from MediaController::mapLiveCard).
    Optional: $cardClass — wrapper sizing override (defaults to slider-row widths;
              pass '' when the parent grid controls the size).
--}}
<div class="{{ $cardClass ?? 'w-[60vw] sm:w-[40vw] md:w-[30vw] lg:w-[24vw] xl:w-[calc((100vw-96px)/5)] shrink-0 snap-start' }}">
    <a href="{{ $card['url'] }}"
       class="group relative block aspect-video rounded-[14px] overflow-hidden bg-gradient-to-br from-[#0056b3] via-[#1a6e57] to-[#218838] ring-1 ring-black/5 dark:ring-white/10 hover:ring-2 hover:ring-[#64CCC5]/70 hover:shadow-xl transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
        @if ($card['thumbnail'])
            <img src="{{ $card['thumbnail'] }}" alt="" loading="lazy"
                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
        @else
            <div class="absolute inset-0 flex items-center justify-center opacity-30" aria-hidden="true">
                <i data-lucide="radio" class="w-12 h-12 text-white"></i>
            </div>
        @endif

        {{-- Bottom scrim keeps the title readable --}}
        <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/85 via-black/25 to-transparent" aria-hidden="true"></div>

        {{-- Status badge --}}
        @if ($card['is_live'])
            <span class="absolute top-2.5 start-3 inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-600 text-white text-[11px] font-bold rounded-full">
                <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>{{ __('media.public.live_now') }}
            </span>
        @else
            <span class="absolute top-2.5 start-3 inline-flex items-center gap-1.5 px-2.5 py-1 glass !bg-black/55 text-white text-[11px] font-semibold rounded-full">
                {{ __('media.public.live_status_'.$card['status']) }}
            </span>
        @endif

        <div class="absolute bottom-2.5 start-3 end-3 flex items-end justify-between gap-2">
            <h3 class="text-white font-bold text-sm sm:text-base line-clamp-1 break-words">
                {{ $card['title'] }}
            </h3>
            @if ($card['views_count'] > 0)
                <span class="shrink-0 inline-flex items-center gap-1 text-white/80 text-[11px] font-semibold">
                    <i data-lucide="eye" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ number_format($card['views_count']) }}
                </span>
            @endif
        </div>

        {{-- Hover play overlay --}}
        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 transition-opacity duration-300 flex items-center justify-center motion-reduce:transition-none">
            <span class="w-12 h-12 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-xl">
                <i data-lucide="play" class="w-6 h-6 text-gray-900 ms-0.5"></i>
            </span>
        </div>
    </a>
</div>
