{{--
    Surah Header Component

    Required props:
    - $surah: array with surah data (nameAr, nameEn, transliteration, type, versesCount, juz, revelationOrder, bismillahPre, number)
    - $isMakki: boolean
    - $viewMode: string (from Alpine.js x-data)
--}}

@props(['surah', 'isMakki'])

@php
    $subtitle = $surah['nameEn'].($surah['transliteration'] ? ' — '.$surah['transliteration'] : '');
@endphp

<header class="sx-surface mb-8 overflow-hidden">
    <div class="sx-rise grid justify-items-center gap-3 p-6 text-center sm:p-8">
        <h1 class="font-arabic text-4xl font-bold leading-tight text-[color:var(--sx-ink)] sm:text-5xl">{{ $surah['nameAr'] }}</h1>
        <p class="sx-clamp-1 max-w-full text-[color:var(--sx-muted)]" title="{{ $subtitle }}">{{ $subtitle }}</p>
        <span class="sx-signature" aria-hidden="true"></span>
        <div class="flex flex-wrap items-center justify-center gap-2">
            <span class="sx-chip">
                <i data-lucide="map-pin" aria-hidden="true"></i>{{ $isMakki ? __('quran.public.makki') : __('quran.public.madani') }}
            </span>
            <span class="sx-chip tabular-nums">
                <i data-lucide="list" aria-hidden="true"></i>{{ $surah['versesCount'] }} {{ __('quran.public.verse_unit') }}
            </span>
            <span class="sx-chip tabular-nums">
                <i data-lucide="layers" aria-hidden="true"></i>{{ __('quran.public.juz') }} {{ $surah['juz'] }}
            </span>
            <span class="sx-chip tabular-nums">
                <i data-lucide="hash" aria-hidden="true"></i>{{ __('quran.public.revelation_order') }}: {{ $surah['revelationOrder'] }}
            </span>
        </div>
    </div>

    {{-- Bismillah (shown for all surahs except Al-Fatiha #1, where it is verse 1, and At-Tawbah #9, which has no Basmala).
         Hidden in mushaf reading mode, where the mushaf renders its own Bismillah inside the page. --}}
    @if($surah['bismillahPre'] && $surah['number'] != 1 && $surah['number'] != 9)
    <div class="border-t border-[color:var(--sx-line)] bg-[color:var(--sx-surface-2)] p-8 text-center"
         x-show="viewMode !== 'reading'" x-cloak>
        <p class="sx-scripture">بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ</p>
    </div>
    @endif
</header>
