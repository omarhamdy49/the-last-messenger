{{--
    Mushaf Reading View Component

    Displays verses in mushaf (line-for-line) reading mode, similar to a printed Quran.
    Falls back to page-grouped flow if mushaf layout is not available.

    Props:
    - $mushaf: array of mushaf data (optional)
    - $partial: path to mushaf partial (default: 'public.quran._mushaf')

    Expected Alpine.js context:
    - viewMode: string ('reading' or 'verses')
    - versesByPage: array of grouped verses by page
    - verses: array of all verses
    - fmtNum(n): number formatting function
    - openTafsirFromMushaf($event): event handler for tafsir modal
    - tafsirVerse: currently selected verse for tafsir
    - loadVerseTafsir(verse): function to load tafsir
    - fontSize: current font size setting
    - $store.theme.dark: boolean for dark mode
--}}

@props(['mushaf' => null, 'partial' => 'public.quran._mushaf'])

<div x-show="viewMode === 'reading'" x-cloak class="mb-12">
    @if(! empty($mushaf))
        {{-- Mushaf layout (line-for-line, page-by-page) --}}
        {{-- Tapping any ayah (word or end medallion) opens its tafsir. --}}
        <div class="verse-card mushaf-card rounded-2xl shadow-lg px-5 sm:px-8 py-8 mx-auto"
             @click="openTafsirFromMushaf($event)"
             :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
            @include($partial)
        </div>
    @else
        {{-- Fallback: page-grouped flow when mushaf layout is not available --}}
        <div x-show="verses.length > 0" class="space-y-6">
            <template x-for="grp in versesByPage" :key="'rp' + grp.page">
            <div>
                {{-- Page divider --}}
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-600'">
                        {{ __('quran.nav.page') }} <span x-text="fmtNum(grp.page)"></span>
                    </span>
                    <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                </div>
                {{-- Verses grouped as continuous text --}}
                <div class="verse-card rounded-2xl shadow-lg p-6 sm:p-8" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <p class="font-['Amiri'] leading-loose text-start" dir="rtl"
                       :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                       :style="`font-size: ${fontSize}px`">
                        <template x-for="verse in grp.items" :key="verse.id">
                            <span class="cursor-pointer" @click="tafsirVerse = verse; loadVerseTafsir(verse)">
                                <span x-text="verse.arabic"></span>
                                <span class="inline-flex items-center justify-center align-middle mx-1 w-7 h-7 text-xs font-sans font-bold rounded-full"
                                      :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'"
                                      x-text="fmtNum(verse.number)"></span>
                            </span>
                        </template>
                    </p>
                </div>
            </div>
            </template>
        </div>
    @endif
</div>
