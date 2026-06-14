{{--
    Shahid-style Top-10 ranked poster card: portrait at every width, big
    white numeral over the poster corner with a small brand chip above it
    (Shahid uses a red tab), start-aligned caption below.
    Expects: $card (array from MediaController::mapProgramCard), $rank (1-based int).
--}}
<div class="w-[38vw] sm:w-[27vw] md:w-[21vw] lg:w-[15.2vw] xl:w-[13vw] shrink-0 snap-start group/card">
    <a href="{{ $card['url'] }}"
       class="group block rounded-[14px] overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
        <div class="relative aspect-[2/3] bg-gradient-to-br {{ $card['gradient'] }}">
            @if ($card['poster'])
                <img src="{{ $card['poster'] }}" alt="" loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
            @else
                <div class="absolute inset-0 flex items-center justify-center opacity-25" aria-hidden="true">
                    <i data-lucide="{{ ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'][$card['category']] ?? 'monitor-play' }}"
                       class="w-16 h-16 text-white"></i>
                </div>
            @endif

            {{-- Always-visible bottom gradient + title --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent" aria-hidden="true"></div>
            <div class="absolute bottom-0 start-0 end-0 p-3 z-10">
                <h3 class="font-bold text-white text-sm line-clamp-2 break-words">{{ $card['title'] }}</h3>
            </div>

            {{-- Shahid-style rank numeral: brand tab + big white number --}}
            <div class="absolute top-0 start-3 z-20 pointer-events-none" aria-hidden="true">
                <span class="block w-9 h-2 rounded-b-sm bg-gradient-to-r from-[#0056b3] to-[#218838]"></span>
                <span class="block text-5xl sm:text-6xl font-black leading-none text-white mt-1 drop-shadow-[0_2px_6px_rgba(0,0,0,0.7)]">{{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
    </a>

    {{-- Shahid-style availability caption (start-aligned, strong) --}}
    <p class="mt-2.5 text-start text-sm sm:text-base text-gray-900 dark:text-white">
        {{ __('media.public.episodes_count', ['count' => $card['episodes_count']]) }}
    </p>
</div>
