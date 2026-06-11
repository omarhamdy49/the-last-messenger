{{--
    Premium landscape program card (prototype: programs-premium.html rows).
    Expects: $card (array from MediaController::mapProgramCard).
    Banner/poster images come from MediaLibrary; the category gradient is the fallback.

    Tailwind JIT safelist: the gradient utilities below are emitted by
    MediaController::CATEGORY_GRADIENTS (PHP is not in tailwind content paths),
    so they must appear verbatim in a scanned Blade file. Keep in sync:
    from-[#0056b3] via-[#1a6e57] to-[#218838]
    from-emerald-800 via-emerald-600 to-teal-500
    from-indigo-900 via-blue-700 to-sky-600
    from-amber-500 via-orange-500 to-rose-500
    from-slate-800 via-slate-600 to-slate-500
--}}
<div class="relative w-64 sm:w-72 lg:w-80 shrink-0 snap-start group/card">
    <a href="{{ $card['url'] }}"
       class="premium-card group block rounded-2xl overflow-hidden shadow-xl bg-white dark:bg-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]">
        <div class="relative aspect-video bg-gradient-to-br {{ $card['gradient'] }}">
            @if ($card['banner'])
                <img src="{{ $card['banner'] }}" alt="" loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 motion-reduce:transform-none transition-transform duration-500">
            @else
                {{-- Category watermark fallback --}}
                <div class="absolute inset-0 flex items-center justify-center opacity-25" aria-hidden="true">
                    <i data-lucide="{{ ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'][$card['category']] ?? 'monitor-play' }}"
                       class="w-14 h-14 text-white"></i>
                </div>
            @endif

            {{-- Hover overlay with play icon --}}
            <div class="card-overlay absolute inset-0 bg-black/50 opacity-0 group-focus-within/card:opacity-100 transition-opacity duration-300 flex items-center justify-center motion-reduce:transition-none">
                <span class="w-14 h-14 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow-xl">
                    <i data-lucide="play" class="w-7 h-7 text-gray-900 ms-0.5"></i>
                </span>
            </div>

            {{-- Episodes badge --}}
            <span class="absolute bottom-2 start-2 glass px-2.5 py-1 rounded-full text-gray-900 dark:text-white text-[11px] font-semibold">
                {{ __('media.public.episodes_count', ['count' => $card['episodes_count']]) }}
            </span>

            @if ($card['rating'] > 0)
                <span class="absolute top-2 start-2 inline-flex items-center gap-1 bg-black/60 backdrop-blur-sm text-yellow-400 text-[11px] font-bold px-2 py-0.5 rounded-full">
                    <i data-lucide="star" class="w-3 h-3"></i>{{ number_format($card['rating'], 1) }}
                </span>
            @endif
        </div>

        {{-- Card body --}}
        <div class="p-4">
            <h3 class="font-bold text-base text-gray-900 dark:text-white mb-1 line-clamp-1 break-words group-hover:text-[#64CCC5] transition-colors">
                {{ $card['title'] }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2">
                <span>{{ $card['category_label'] }}</span>
                @if ($card['year'])<span aria-hidden="true">·</span><span>{{ $card['year'] }}</span>@endif
            </p>
        </div>
    </a>

    {{-- My List toggle (server favorites for auth users; localStorage for guests) --}}
    <button type="button"
            @click.stop="toggleMyList({{ $card['id'] }})"
            :aria-pressed="inMyList({{ $card['id'] }}) ? 'true' : 'false'"
            :disabled="isPending({{ $card['id'] }})"
            :title="inMyList({{ $card['id'] }}) ? @js(__('media.public.in_my_list')) : @js(__('media.public.my_list'))"
            class="absolute top-2 end-2 z-30 w-8 h-8 rounded-full bg-black/60 backdrop-blur-sm flex items-center justify-center text-white hover:bg-black/80 transition-colors disabled:opacity-60">
        <span class="sr-only">{{ __('media.public.my_list') }}</span>
        <i data-lucide="plus" class="w-4 h-4" x-show="!inMyList({{ $card['id'] }})"></i>
        <i data-lucide="check" class="w-4 h-4 text-[#64CCC5]" x-show="inMyList({{ $card['id'] }})" x-cloak></i>
    </button>
</div>
