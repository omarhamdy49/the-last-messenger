{{--
    Surah Navigation Component (Previous/Next)

    Props:
    - $prevSurah: int|null - Previous surah number
    - $prevSurahName: string - Previous surah name in Arabic
    - $nextSurah: int|null - Next surah number
    - $nextSurahName: string - Next surah name in Arabic
--}}

@props(['prevSurah', 'prevSurahName', 'nextSurah', 'nextSurahName'])

<div class="flex items-center justify-between gap-4 py-8">
    @if($prevSurah)
    <a href="{{ route('public.quran.surah', $prevSurah) }}"
       class="flex items-center gap-3 px-5 py-3 rounded-xl max-w-[48%] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-[1.02] transition-all"
       aria-label="{{ __('quran.public.previous_surah') }}: {{ $prevSurahName }}"
       title="{{ __('quran.public.previous_surah') }}: {{ $prevSurahName }}">
        <svg class="w-5 h-5 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span class="flex flex-col text-start min-w-0">
            <span class="text-xs opacity-80">{{ __('quran.public.previous_surah') }}</span>
            <span class="font-['Amiri'] text-lg truncate">{{ $prevSurahName }}</span>
        </span>
    </a>
    @else <span></span> @endif

    @if($nextSurah)
    <a href="{{ route('public.quran.surah', $nextSurah) }}"
       class="flex items-center gap-3 px-5 py-3 rounded-xl max-w-[48%] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-[1.02] transition-all"
       aria-label="{{ __('quran.public.next_surah') }}: {{ $nextSurahName }}"
       title="{{ __('quran.public.next_surah') }}: {{ $nextSurahName }}">
        <span class="flex flex-col text-end min-w-0">
            <span class="text-xs opacity-80">{{ __('quran.public.next_surah') }}</span>
            <span class="font-['Amiri'] text-lg truncate">{{ $nextSurahName }}</span>
        </span>
        <svg class="w-5 h-5 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    @endif
</div>
