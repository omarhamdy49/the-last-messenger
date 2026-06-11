{{--
    Netflix-style ranked poster card (premium prototype "Popular Shows").
    Expects: $card (array from MediaController::mapProgramCard), $rank (1-based int).
--}}
<div class="relative shrink-0 snap-start pt-6 group/card">
    <a href="{{ $card['url'] }}"
       class="premium-card group block w-44 sm:w-56 lg:w-64 rounded-xl overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
        <div class="relative aspect-[2/3] bg-gradient-to-br {{ $card['gradient'] }}">
            @if ($card['poster'])
                <img src="{{ $card['poster'] }}" alt="" loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover">
            @else
                <div class="absolute inset-0 flex items-center justify-center opacity-25" aria-hidden="true">
                    <i data-lucide="{{ ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'][$card['category']] ?? 'monitor-play' }}"
                       class="w-16 h-16 text-white"></i>
                </div>
            @endif

            {{-- Always-visible bottom gradient + title --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent" aria-hidden="true"></div>
            <div class="absolute bottom-0 start-0 end-0 p-4 z-10">
                <h3 class="font-bold text-white text-sm mb-2 line-clamp-2 break-words">{{ $card['title'] }}</h3>
                <div class="flex items-center gap-2 text-xs text-gray-300">
                    @if ($card['rating'] > 0)
                        <span class="flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3 text-yellow-400"></i>{{ number_format($card['rating'], 1) }}
                        </span>
                    @endif
                    @if ($card['year'])<span>{{ $card['year'] }}</span>@endif
                </div>
            </div>

            {{-- Hover dim for contrast with the rank number --}}
            <div class="card-overlay absolute inset-0 bg-black/40 opacity-0 group-focus-within/card:opacity-100 transition-opacity duration-300 z-20 motion-reduce:transition-none"></div>
        </div>
    </a>

    {{-- Rank badge — slightly outside the poster, Netflix style --}}
    <div class="absolute top-0 -start-2 leading-none pointer-events-none z-30" aria-hidden="true">
        <span class="text-[4.5rem] sm:text-[6rem] font-black tracking-tighter"
              style="color: rgba(0,0,0,0.8); -webkit-text-stroke: 3px rgba(0,86,179,0.9);">{{ $rank }}</span>
    </div>
</div>
