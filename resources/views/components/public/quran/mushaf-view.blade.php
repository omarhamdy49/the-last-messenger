{{--
    Mushaf Reading View Component

    Displays verses in mushaf (line-for-line) reading mode, similar to a printed Quran.
    Falls back to page-grouped flow if mushaf layout is not available.

    Props:
    - $mushaf: array of mushaf data (optional)
    - $surah: array with surah data (the _mushaf partial reads it)

    Expected Alpine.js context:
    - viewMode: string ('reading' or 'verses')
    - versesByPage: array of grouped verses by page
    - verses: array of all verses
    - fmtNum(n): number formatting function
    - openVerseMenu($event, verse?): opens the listen / tafsir menu for a tapped ayah
    - fontSize: current font size setting
--}}

@props(['mushaf' => null, 'surah'])

@if(! empty($mushaf))
<div x-show="viewMode === 'reading'" x-cloak class="mb-12">
    {{-- Tapping any ayah (word or end medallion) opens its tafsir. --}}
    <div class="verse-card mushaf-card sx-surface mx-auto px-5 py-8 sm:px-8"
         @click="openVerseMenu($event)">
        @include('public.quran._mushaf')
    </div>
</div>
@else
{{-- Mushaf layout not imported for this surah — fall back to the verse flow --}}
<div x-show="viewMode === 'reading' && verses.length > 0" x-cloak class="space-y-6 mb-12">
    <template x-for="grp in versesByPage" :key="'rp' + grp.page">
    <div>
        <div class="mb-3 flex items-center gap-3">
            <div class="h-px flex-1 bg-[color:var(--sx-line)]"></div>
            <span class="sx-chip tabular-nums">
                {{ __('quran.nav.page') }} <span x-text="fmtNum(grp.page)"></span>
            </span>
            <div class="h-px flex-1 bg-[color:var(--sx-line)]"></div>
        </div>
        <div class="verse-card sx-surface p-6 sm:p-8">
            <p class="sx-scripture text-start" dir="rtl"
               :style="`font-size: ${fontSize}px`"><template x-for="verse in grp.items" :key="verse.id"><span class="cursor-pointer" @click="openVerseMenu($event, verse)"><span x-text="verse.arabic"></span><span class="mx-1 inline-flex h-7 w-7 items-center justify-center rounded-full align-middle text-xs font-bold tabular-nums bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)]" x-text="fmtNum(verse.number)"></span> </span></template></p>
        </div>
    </div>
    </template>
</div>
@endif
