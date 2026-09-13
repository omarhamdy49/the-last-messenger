{{--
    Surah Header Component

    Required props:
    - $surah: array with surah data (nameAr, nameEn, transliteration, type, versesCount, juz, revelationOrder, bismillahPre, number)
    - $isMakki: boolean
    - $viewMode: string (from Alpine.js x-data)
--}}

@props(['surah', 'isMakki'])

<div class="mb-8 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp"
     :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
    {{-- Gradient header with pattern overlay --}}
    <div class="relative p-8 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-center">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="relative">
            <h1 class="text-5xl font-bold font-['Amiri'] mb-2">{{ $surah['nameAr'] }}</h1>
            <p class="text-xl text-white/90 mb-4">
                {{ $surah['nameEn'] }}@if($surah['transliteration']) — {{ $surah['transliteration'] }}@endif
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    {{ $isMakki ? __('quran.public.makki') : __('quran.public.madani') }}
                </span>
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    {{ $surah['versesCount'] }} {{ __('quran.public.verse_unit') }}
                </span>
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    {{ __('quran.public.juz') }} {{ $surah['juz'] }}
                </span>
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    {{ __('quran.public.revelation_order') }}: {{ $surah['revelationOrder'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- Bismillah (shown for all surahs except Al-Fatiha #1, where it is verse 1, and At-Tawbah #9, which has no Basmala).
         Hidden in mushaf reading mode, where the mushaf renders its own Bismillah inside the page. --}}
    @if($surah['bismillahPre'] && $surah['number'] != 1 && $surah['number'] != 9)
    <div class="p-8 text-center" x-show="viewMode !== 'reading'" x-cloak :class="$store.theme.dark ? 'bg-gray-800/50' : 'bg-gray-50'">
        <p class="text-4xl font-['Amiri'] leading-loose" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
            بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ
        </p>
    </div>
    @endif
</div>
