{{--
    Playlist card — gallery, profile tab and Quran home.

    Cover-first: every playlist gets its own gradient (hue derived from the slug,
    kept inside the brand's blue→green band) so a wall of cards reads as a
    collection, not a spreadsheet. Portraits cluster on the cover, the surah
    count sits as a quiet watermark, and the play control is always visible
    (no hover-only affordances).

    Props:
        $card   — PlaylistCard::from() array
        $index  — position in its grid (drives the staggered entrance)
        $size   — 'default' | 'wide' (horizontal, larger portraits, description)
--}}
@props(['card', 'index' => 0, 'size' => 'default'])

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $reciters = $card['reciters'];
    $shown = array_slice($reciters, 0, $size === 'wide' ? 5 : 3);
    $more = count($reciters) - count($shown);
    // Stable per-playlist hue in the brand band (teal → blue), two stops 28° apart.
    $hue = 165 + (crc32($card['slug']) % 60);
    $cover = sprintf('linear-gradient(135deg, hsl(%d 62%% 34%%) 0%%, hsl(%d 58%% 26%%) 100%%)', $hue, $hue + 28);
    $wide = $size === 'wide';
@endphp

<a href="{{ $card['url'] }}"
   style="--i: {{ (int) $index }}"
   {{ $attributes->merge(['class' => 'pl-card group relative flex '.($wide ? 'flex-col md:grid md:grid-cols-[2fr_3fr]' : 'flex-col').' overflow-hidden rounded-3xl border border-zinc-200/70 dark:border-white/10 bg-white dark:bg-zinc-900 shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition-[transform,box-shadow] duration-300 ease-out hover:-translate-y-1 hover:shadow-[0_24px_48px_-24px_rgba(0,86,179,0.35)] active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60 focus-visible:ring-offset-2']) }}>

    {{-- Cover --}}
    <div class="relative overflow-hidden {{ $wide ? 'aspect-[16/10] md:aspect-auto md:min-h-[260px]' : 'aspect-[16/10]' }}" style="background: {{ $cover }}">
        {{-- soft light + fine grid, both static and pointer-inert --}}
        <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(120% 80% at 85% 0%, rgba(255,255,255,0.22) 0%, rgba(255,255,255,0) 55%);"></div>
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 18px 18px;"></div>

        {{-- surah count watermark --}}
        <span aria-hidden="true" class="absolute top-2 end-4 text-[64px] {{ $wide ? 'md:text-[96px]' : '' }} leading-none font-black tabular-nums text-white/15 select-none">{{ $num($card['items']) }}</span>

        {{-- badges --}}
        <div class="absolute top-3 start-3 flex items-center gap-1.5">
            @if ($card['isFeatured'])
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-bold bg-white/90 text-amber-700 backdrop-blur-sm">
                    <i data-lucide="star" class="w-3 h-3 fill-current"></i>{{ __('quran.playlists.featured') }}
                </span>
            @endif
            @if (! $card['isPublic'])
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-bold bg-white/90 text-zinc-700 backdrop-blur-sm">
                    <i data-lucide="lock" class="w-3 h-3"></i>{{ __('quran.playlists.private') }}
                </span>
            @endif
        </div>

        {{-- portraits --}}
        <div class="absolute bottom-4 start-4 flex items-center">
            <div class="flex {{ $isRtl ? 'space-x-reverse' : '' }} -space-x-3">
                @forelse ($shown as $r)
                    @if ($r['image'])
                        <img src="{{ $r['image'] }}" alt="{{ $r['name'] }}" loading="lazy" width="48" height="48"
                             class="{{ $wide ? 'w-14 h-14' : 'w-12 h-12' }} rounded-full object-cover ring-[3px] ring-white/90 bg-zinc-100 shadow-md">
                    @else
                        <span class="{{ $wide ? 'w-14 h-14 text-lg' : 'w-12 h-12 text-base' }} rounded-full ring-[3px] ring-white/90 bg-white/20 backdrop-blur-sm text-white font-bold flex items-center justify-center shadow-md"
                              title="{{ $r['name'] }}">{{ mb_substr($r['name'], 0, 1) }}</span>
                    @endif
                @empty
                    <span class="w-12 h-12 rounded-full ring-[3px] ring-white/90 bg-white/20 flex items-center justify-center text-white/80">
                        <i data-lucide="mic-off" class="w-5 h-5"></i>
                    </span>
                @endforelse
            </div>
            @if ($more > 0)
                <span class="ms-2 px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-sm text-white text-xs font-bold tabular-nums">+{{ $num($more) }}</span>
            @endif
        </div>

        {{-- play (always visible; grows on hover) --}}
        <span class="absolute bottom-4 end-4 w-12 h-12 rounded-full bg-white text-primary flex items-center justify-center shadow-lg transition-transform duration-300 group-hover:scale-110" aria-hidden="true">
            <i data-lucide="play" class="w-5 h-5 fill-current {{ $isRtl ? '-scale-x-100' : '' }}"></i>
        </span>
    </div>

    {{-- Body --}}
    <div class="flex flex-col flex-1 {{ $wide ? 'p-6 md:p-8' : 'p-5' }}">
        <h3 class="{{ $wide ? 'text-2xl md:text-3xl' : 'text-lg' }} font-bold text-zinc-900 dark:text-white leading-snug line-clamp-2 group-hover:text-primary transition-colors">
            {{ $card['name'] }}
        </h3>
        <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400 tabular-nums">
            {{ __('quran.playlists.surahs_count', ['count' => $num($card['items'])]) }}
            <span aria-hidden="true" class="mx-1.5 text-zinc-300 dark:text-zinc-600">|</span>
            {{ __('quran.playlists.reciters_count', ['count' => $num(count($reciters))]) }}
        </p>
        @if ($wide && $card['description'])
            <p class="mt-3 text-base text-zinc-600 dark:text-zinc-300 leading-relaxed max-w-[60ch] line-clamp-3">{{ $card['description'] }}</p>
        @endif
        @if ($wide && $reciters)
            <ul class="mt-4 flex flex-wrap gap-1.5" aria-label="{{ __('quran.public.reciters') }}">
                @foreach (array_slice($reciters, 0, 4) as $r)
                    <li class="px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-white/10 text-xs font-semibold text-zinc-700 dark:text-zinc-200">{{ $r['name'] }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-auto pt-4 flex items-center justify-between gap-3 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-2 min-w-0">
                @if ($card['owner']['avatar'])
                    <img src="{{ $card['owner']['avatar'] }}" alt="" width="24" height="24" loading="lazy" class="w-6 h-6 rounded-full object-cover">
                @else
                    <span class="w-6 h-6 rounded-full bg-zinc-200 dark:bg-zinc-700 text-[11px] font-bold text-zinc-600 dark:text-zinc-200 flex items-center justify-center">{{ mb_substr($card['owner']['name'] ?: '?', 0, 1) }}</span>
                @endif
                <span class="truncate">{{ $card['owner']['name'] }}</span>
            </span>
            <span class="inline-flex items-center gap-1 tabular-nums shrink-0">
                <i data-lucide="headphones" class="w-3.5 h-3.5"></i>{{ $num($card['plays']) }}
            </span>
        </div>
    </div>
</a>
