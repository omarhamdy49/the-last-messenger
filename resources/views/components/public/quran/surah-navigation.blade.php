{{--
    Surah Navigation Component (Previous/Next)

    Maps the surah payload onto the shared editorial pager.

    Props:
    - $prevSurah: int|null - Previous surah number
    - $prevSurahName: string - Previous surah name in Arabic
    - $nextSurah: int|null - Next surah number
    - $nextSurahName: string - Next surah name in Arabic
--}}

@props(['prevSurah', 'prevSurahName', 'nextSurah', 'nextSurahName'])

<div class="py-8">
    <x-public.editorial.pager
        :label="__('quran.public.surah_navigation')"
        :prev="$prevSurah ? ['url' => route('public.quran.surah', $prevSurah), 'title' => $prevSurahName] : null"
        :next="$nextSurah ? ['url' => route('public.quran.surah', $nextSurah), 'title' => $nextSurahName] : null"
        :prevLabel="__('quran.public.previous_surah')"
        :nextLabel="__('quran.public.next_surah')"
    />
</div>
