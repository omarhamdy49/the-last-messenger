{{--
    Playlist card — gallery, profile tab and Quran home.

    Props:
        $card   — PlaylistCard::from() array
        $index  — position in its grid (drives the staggered entrance)
        $size   — 'default' | 'wide' (the featured band variant)
--}}
@props(['card', 'index' => 0, 'size' => 'default'])

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $reciters = $card['reciters'];
    $shown = array_slice($reciters, 0, $size === 'wide' ? 6 : 4);
    $more = count($reciters) - count($shown);
@endphp

<a href="{{ $card['url'] }}"
   style="--i: {{ (int) $index }}"
   {{ $attributes->merge(['class' => 'pl-card group relative flex flex-col rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 transition-[transform,box-shadow] duration-200 ease-out hover:-translate-y-0.5 hover:shadow-[0_18px_40px_-20px_rgba(0,86,179,0.25)] active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60 '.($size === 'wide' ? 'p-6 sm:p-8' : 'p-5')]) }}>

    {{-- Reciter portraits + badges --}}
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="flex items-center">
            <div class="flex {{ $isRtl ? 'space-x-reverse' : '' }} -space-x-2.5">
                @forelse ($shown as $r)
                    @if ($r['image'])
                        <img src="{{ $r['image'] }}" alt="{{ $r['name'] }}" loading="lazy" width="40" height="40"
                             class="{{ $size === 'wide' ? 'w-12 h-12' : 'w-10 h-10' }} rounded-full object-cover ring-2 ring-white dark:ring-zinc-900 bg-zinc-100">
                    @else
                        <span class="{{ $size === 'wide' ? 'w-12 h-12 text-base' : 'w-10 h-10 text-sm' }} rounded-full ring-2 ring-white dark:ring-zinc-900 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white font-bold flex items-center justify-center"
                              title="{{ $r['name'] }}">{{ mb_substr($r['name'], 0, 1) }}</span>
                    @endif
                @empty
                    <span class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400">
                        <i data-lucide="mic-off" class="w-4 h-4"></i>
                    </span>
                @endforelse
            </div>
            @if ($more > 0)
                <span class="ms-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 tabular-nums">+{{ $num($more) }}</span>
            @endif
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @if ($card['isFeatured'])
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                    <i data-lucide="star" class="w-3 h-3 fill-current"></i>{{ __('quran.playlists.featured') }}
                </span>
            @endif
            @if (! $card['isPublic'])
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <i data-lucide="lock" class="w-3 h-3"></i>{{ __('quran.playlists.private') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Name + meta --}}
    <h3 class="{{ $size === 'wide' ? 'text-2xl sm:text-3xl' : 'text-lg' }} font-bold text-zinc-900 dark:text-white leading-snug line-clamp-2 mb-1.5 group-hover:text-primary transition-colors">
        {{ $card['name'] }}
    </h3>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 tabular-nums">
        {{ __('quran.playlists.surahs_count', ['count' => $num($card['items'])]) }}
        <span aria-hidden="true" class="mx-1">·</span>
        {{ __('quran.playlists.reciters_count', ['count' => $num(count($reciters))]) }}
    </p>
    @if ($size === 'wide' && $card['description'])
        <p class="mt-3 text-base text-zinc-600 dark:text-zinc-300 leading-relaxed max-w-[60ch] line-clamp-3">{{ $card['description'] }}</p>
    @endif

    {{-- Footer --}}
    <div class="mt-auto pt-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            @if ($card['owner']['avatar'])
                <img src="{{ $card['owner']['avatar'] }}" alt="" width="24" height="24" loading="lazy" class="w-6 h-6 rounded-full object-cover">
            @else
                <span class="w-6 h-6 rounded-full bg-zinc-200 dark:bg-zinc-700 text-[11px] font-bold text-zinc-600 dark:text-zinc-200 flex items-center justify-center">{{ mb_substr($card['owner']['name'] ?: '?', 0, 1) }}</span>
            @endif
            <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $card['owner']['name'] }}</span>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400 tabular-nums">
                <i data-lucide="headphones" class="w-3.5 h-3.5"></i>{{ $num($card['plays']) }}
            </span>
            <span class="w-11 h-11 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] text-white flex items-center justify-center shadow-sm transition-transform duration-200 group-hover:scale-105"
                  aria-hidden="true">
                <i data-lucide="play" class="w-4 h-4 fill-current {{ $isRtl ? '-scale-x-100' : '' }}"></i>
            </span>
        </div>
    </div>
</a>
