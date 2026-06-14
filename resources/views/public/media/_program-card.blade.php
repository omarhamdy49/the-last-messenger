{{--
    Shahid-style program card, pixel-matched to shahid.mbc.net/ar/series:
    14px radius, flat (no shadow), no hover overlay — just a subtle image
    zoom; portrait 2:3 poster below xl, landscape 16:9 banner on desktop;
    fluid widths (~2.4 visible mobile, ~6 portrait @1280, 5 landscape @xl+);
    strong start-aligned caption below the artwork.
    Expects: $card (array from MediaController::mapProgramCard).

    Tailwind JIT safelist: the gradient utilities below are emitted by
    MediaController::CATEGORY_GRADIENTS (PHP is not in tailwind content paths),
    so they must appear verbatim in a scanned Blade file. Keep in sync:
    from-[#0056b3] via-[#1a6e57] to-[#218838]
    from-emerald-800 via-emerald-600 to-teal-500
    from-indigo-900 via-blue-700 to-sky-600
    from-amber-500 via-orange-500 to-rose-500
    from-slate-800 via-slate-600 to-slate-500
--}}
@php
    $portraitArt = $card['poster'] ?? $card['banner'];
    $landscapeArt = $card['banner'] ?? $card['poster'];
@endphp
<div class="w-[38vw] sm:w-[27vw] md:w-[21vw] lg:w-[15.2vw] xl:w-[calc((100vw-96px)/5)] shrink-0 snap-start">
    <a href="{{ $card['url'] }}"
       class="group relative block aspect-[2/3] xl:aspect-video rounded-[14px] overflow-hidden bg-gradient-to-br {{ $card['gradient'] }} focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
        @if ($portraitArt || $landscapeArt)
            {{-- Portrait poster (< xl) / landscape banner (>= xl) — Shahid swaps art by viewport --}}
            <img src="{{ $portraitArt }}" alt="" loading="lazy"
                 class="xl:hidden absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
            <img src="{{ $landscapeArt }}" alt="" loading="lazy"
                 class="hidden xl:block absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
        @else
            {{-- Category watermark fallback --}}
            <div class="absolute inset-0 flex items-center justify-center opacity-25" aria-hidden="true">
                <i data-lucide="{{ ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'][$card['category']] ?? 'monitor-play' }}"
                   class="w-12 h-12 text-white"></i>
            </div>
        @endif

        {{-- Bottom scrim keeps the title readable on any artwork --}}
        <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/80 via-black/25 to-transparent" aria-hidden="true"></div>

        <h3 class="absolute bottom-2.5 start-3 end-3 text-white font-bold text-sm sm:text-base line-clamp-2 xl:line-clamp-1 break-words">
            {{ $card['title'] }}
        </h3>

        @if ($card['is_trending'])
            <span class="absolute top-0 start-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-[10px] font-bold px-2 py-1 rounded-b-md">
                {{ __('media.public.trending_now') }}
            </span>
        @endif
    </a>

    {{-- Shahid-style availability caption (start-aligned, strong) --}}
    <p class="mt-2.5 text-start text-sm sm:text-base text-gray-900 dark:text-white">
        {{ __('media.public.episodes_count', ['count' => $card['episodes_count']]) }}@if ($card['year']) · {{ $card['year'] }}@endif
    </p>
</div>
