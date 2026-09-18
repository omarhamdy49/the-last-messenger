{{--
    Quran Surah Modals Component

    Contains all modals for the surah page:
    - Tafsir modal (for reading view)
    - Surah info modal
    - Surah switcher modal

    Props:
    - $surah: array with surah data
    - $tafsirResources: array
    - $translationResources: array (optional)
    - $defaultTranslationLang: string
--}}

@props(['surah', 'chapterInfo' => null, 'tafsirResources', 'translationResources' => [], 'defaultTranslationLang'])

{{-- ============================================================ --}}
{{-- VERSE MODAL — translation, words and tafsir for one ayah       --}}
{{--                                                                --}}
{{-- Opened by tapping an ayah in reading view. Translation and the  --}}
{{-- word list ship with the page, so only tafsir fetches, and only  --}}
{{-- for the source the reader actually opens.                       --}}
{{-- ============================================================ --}}
<div x-show="tafsirVerse" x-cloak x-transition.opacity
     @keydown.escape.window="tafsirVerse = null"
     class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 70;">
    <div class="absolute inset-0 bg-black/50" @click="tafsirVerse = null"></div>
    <div class="relative w-full max-w-4xl overflow-hidden rounded-2xl shadow-2xl flex flex-col"
         style="max-height: 86vh;" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
         role="dialog" aria-modal="true">
        <div class="p-5 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white relative flex-shrink-0">
            <button @click="tafsirVerse = null" type="button" class="absolute top-3 end-3 p-1.5 rounded-full hover:bg-white/20 transition-colors" aria-label="{{ __('quran.public.close') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="text-xs text-white/80">{{ $surah['nameAr'] }} · <span x-text="tafsirVerse ? fmtNum(tafsirVerse.verse_key) : ''"></span></p>
            {{-- Tajweed colouring when the reader has that script on (server-sanitised
                 markup), plain text otherwise. --}}
            <p x-show="tafsirVerse && tafsirVerse.tajweed" class="tajweed-text quran-text text-2xl leading-relaxed mt-2 pe-8" dir="rtl"
               x-html="tafsirVerse ? tafsirVerse.tajweed : ''"></p>
            <p x-show="!(tafsirVerse && tafsirVerse.tajweed)" class="quran-text text-2xl leading-relaxed mt-2 pe-8" dir="rtl"
               x-text="tafsirVerse ? tafsirVerse.arabic : ''"></p>
            <button type="button" @click="tafsirVerse && playVerse(tafsirVerse)"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold hover:bg-white/25 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                {{ __('quran.public.listen') }}
            </button>
        </div>

        {{-- Section tabs --}}
        <div class="flex gap-1 border-b px-3 pt-3 flex-shrink-0" role="tablist"
             :class="$store.theme.dark ? 'border-gray-800' : 'border-gray-200'">
            @foreach(['translation' => __('quran.public.translation'), 'words' => __('quran.public.word_by_word'), 'tafsir' => __('quran.public.tafsir')] as $tab => $label)
                <button type="button" role="tab" @click="setVerseTab('{{ $tab }}')"
                        :aria-selected="verseTab === '{{ $tab }}' ? 'true' : 'false'"
                        class="rounded-t-lg px-4 py-2 text-sm font-semibold transition-colors"
                        :class="verseTab === '{{ $tab }}'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900')">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="p-5 overflow-y-auto">
            {{-- Translation: already on the page, no fetch --}}
            <div x-show="verseTab === 'translation'">
                <p x-show="tafsirVerse && tafsirVerse.translation" class="leading-relaxed text-lg" dir="auto"
                   :class="$store.theme.dark ? 'text-gray-200' : 'text-gray-800'"
                   x-text="tafsirVerse ? tafsirVerse.translation : ''"></p>
                <p x-show="tafsirVerse && tafsirVerse.transliteration" class="mt-3 text-sm italic" dir="ltr"
                   :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
                   x-text="tafsirVerse ? tafsirVerse.transliteration : ''"></p>
                <p x-show="tafsirVerse && !tafsirVerse.translation" class="text-sm italic"
                   :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_translation') }}</p>
            </div>

            {{-- Words: meaning, transliteration and the recorded word itself --}}
            <div x-show="verseTab === 'words'">
                <div x-show="tafsirVerse && wordsLoadingVerse === tafsirVerse.id" class="flex flex-wrap gap-2" aria-live="polite">
                    <template x-for="i in 8" :key="i">
                        <div class="h-14 w-20 animate-pulse rounded-xl" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    </template>
                </div>
                <div class="flex flex-wrap gap-2" x-show="!tafsirVerse || wordsLoadingVerse !== tafsirVerse.id">
                <template x-for="(word, i) in wordsFor(tafsirVerse)" :key="i">
                    <button type="button" @click="playWord(word)"
                            class="rounded-xl border px-3 py-2 text-center transition-colors"
                            :class="$store.theme.dark ? 'border-gray-700 hover:border-[#64CCC5] bg-gray-800/60' : 'border-gray-200 hover:border-[#218838] bg-gray-50'"
                            :title="'{{ __('quran.public.play_word') }}'">
                        <span class="block font-['Amiri'] text-xl" dir="rtl" x-text="word.text"></span>
                        <span class="block text-[11px] mt-1" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="word.translation || ''"></span>
                        <span class="block text-[10px] italic" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'" x-text="word.transliteration || ''"></span>
                    </button>
                </template>
                <p x-show="tafsirVerse && wordsLoadingVerse !== tafsirVerse.id && !wordsFor(tafsirVerse).length" class="text-sm italic"
                   :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_words') }}</p>
                </div>
            </div>

            {{-- Tafsir: the source strip is tabs, and each source loads on demand --}}
            <div x-show="verseTab === 'tafsir'">
                <div class="-mx-1 mb-3 flex gap-1 overflow-x-auto scrollbar-hide pb-1" role="tablist"
                     aria-label="{{ __('quran.public.tafsir_source') }}">
                    <template x-for="src in tafsirResources" :key="src.id">
                        <button type="button" role="tab" @click="setTafsirSource(src.id)"
                                :aria-selected="src.id === selectedTafsirId ? 'true' : 'false'"
                                class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                                :class="src.id === selectedTafsirId
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')"
                                x-text="src.name"></button>
                    </template>
                </div>

                {{-- Loading --}}
                <div x-show="tafsirVerse && tafsirLoadingVerse === tafsirVerse.id" class="space-y-2" aria-live="polite">
                    <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.tafsir_loading') }}</p>
                    <div class="h-3 w-full animate-pulse rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    <div class="h-3 w-5/6 animate-pulse rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    <div class="h-3 w-4/6 animate-pulse rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                </div>
                {{-- Text --}}
                <p x-show="tafsirVerse && tafsirLoadingVerse !== tafsirVerse.id && currentTafsir(tafsirVerse)"
                   class="leading-relaxed font-['Amiri'] text-lg" dir="auto"
                   :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'"
                   x-text="tafsirVerse ? currentTafsir(tafsirVerse) : ''"></p>
                {{-- Loaded, but this source has nothing for the verse --}}
                <p x-show="tafsirVerse && tafsirLoadingVerse !== tafsirVerse.id && tafsirLoaded(tafsirVerse) && !currentTafsir(tafsirVerse)"
                   class="text-sm italic" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_tafsir') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SURAH INFO MODAL --}}
{{-- ============================================================ --}}
<div x-show="infoOpen" x-cloak x-transition.opacity
     x-effect="infoOpen && loadChapterInfos()"
     @keydown.escape.window="infoOpen = false"
     class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 70;">
    <div class="absolute inset-0 bg-black/50" @click="infoOpen = false"></div>
    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl flex flex-col"
         style="max-height: 80vh;" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
         role="dialog" aria-modal="true">
        <div class="p-5 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white relative flex-shrink-0">
            <button @click="infoOpen = false" type="button" class="absolute top-3 end-3 p-1.5 rounded-full hover:bg-white/20 transition-colors" aria-label="{{ __('quran.public.pause') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="font-['Amiri'] text-3xl leading-tight">{{ $surah['nameAr'] }}</p>
            <p class="text-sm text-white/80 mt-1">{{ $surah['nameEn'] }}</p>
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ $surah['type'] === 'makki' ? __('quran.public.makki') : __('quran.public.madani') }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ $surah['versesCount'] }} {{ __('quran.public.verse_count') }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ __('quran.public.juz') }} {{ $surah['juz'] }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ __('quran.public.revelation_order') }} {{ $surah['revelationOrder'] }}</span>
                @if(! empty($surah['revelationPlaceName']))
                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ $surah['revelationPlaceName'] }}</span>
                @endif
            </div>
        </div>
        {{-- Language tabs: the site's own language first when it has an introduction.
             The text itself is fetched when the modal opens, not with the page. --}}
        <div x-show="(chapterInfos || []).length > 1" class="flex gap-1 border-b px-3 pt-3 flex-shrink-0" role="tablist"
             :class="$store.theme.dark ? 'border-gray-800' : 'border-gray-200'">
            <template x-for="info in (chapterInfos || [])" :key="info.language">
                <button type="button" role="tab" @click="chapterInfoLang = info.language"
                        :aria-selected="chapterInfoLang === info.language ? 'true' : 'false'"
                        class="rounded-t-lg px-3 py-2 text-xs font-semibold uppercase transition-colors"
                        :class="chapterInfoLang === info.language
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900')"
                        x-text="info.language"></button>
            </template>
        </div>

        <div class="p-5 overflow-y-auto" :class="$store.theme.dark ? 'text-gray-200' : 'text-gray-700'">
            {{-- Loading --}}
            <div x-show="chapterInfoLoading" class="space-y-2" aria-live="polite">
                <div class="h-3 w-full animate-pulse rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                <div class="h-3 w-11/12 animate-pulse rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                <div class="h-3 w-4/6 animate-pulse rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
            </div>

            {{-- The introduction in the chosen language --}}
            <template x-if="!chapterInfoLoading && currentChapterInfo()">
                <div>
                    <p class="leading-relaxed text-start whitespace-pre-line" style="word-break: break-word;" dir="auto"
                       x-text="currentChapterInfo().text"></p>
                    <p x-show="currentChapterInfo().source || currentChapterInfo().resource" class="mt-4 text-xs"
                       :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                        {{ __('quran.public.source') }}: <span x-text="currentChapterInfo().source || currentChapterInfo().resource"></span>
                    </p>
                </div>
            </template>

            {{-- Nothing for this chapter in any language --}}
            <p x-show="!chapterInfoLoading && chapterInfos !== null && !currentChapterInfo()"
               class="text-sm text-center py-6" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                {{ $surah['nameEn'] }} · {{ $surah['versesCount'] }} {{ __('quran.public.verse_count') }}
            </p>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SURAH SWITCHER MODAL (lazy-loaded list of all 114 surahs)   --}}
{{-- ============================================================ --}}
<div x-show="switcherOpen" x-cloak x-transition.opacity
     @keydown.escape.window="switcherOpen = false"
     class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 70;">
    <div class="absolute inset-0 bg-black/50" @click="switcherOpen = false"></div>
    <div class="relative w-full max-w-md rounded-2xl shadow-2xl flex flex-col overflow-hidden"
         style="max-height: 80vh;" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
         role="dialog" aria-modal="true">
        {{-- Header + search --}}
        <div class="p-4 border-b flex-shrink-0" :class="$store.theme.dark ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('quran.public.switch_surah') }}</h2>
                <button @click="switcherOpen = false" type="button" class="p-1.5 rounded-full transition-colors"
                    :class="$store.theme.dark ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-500'" aria-label="{{ __('quran.public.pause') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="relative">
                <svg class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3 pointer-events-none" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4-4"/></svg>
                <input type="text" x-model="switcherSearch" placeholder="{{ __('quran.public.search_surahs') }}"
                    class="w-full h-10 ps-9 pe-3 text-base sm:text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3]/30"
                    :class="$store.theme.dark ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900'">
            </div>
        </div>
        {{-- List --}}
        <div class="flex-1 overflow-y-auto p-2">
            {{-- Loading --}}
            <div x-show="switcherLoading" class="py-10 text-center">
                <svg class="w-6 h-6 mx-auto animate-spin text-[#0056b3]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <p class="text-sm mt-2" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.surahs_loading') }}</p>
            </div>
            {{-- Error --}}
            <div x-show="switcherError" x-cloak class="py-10 text-center">
                <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.surahs_load_error') }}</p>
                <button @click="switcherSurahs = []; openSwitcher()" type="button" class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white" aria-label="{{ __('quran.public.surahs_loading') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3l3 3M19 15a7 7 0 01-11 3l-3-3"/></svg>
                </button>
            </div>
            {{-- Rows --}}
            <template x-for="s in filteredSurahs" :key="s.number">
                <button type="button" @click="goToSurah(s.number)"
                    class="w-full flex items-center gap-3 p-2.5 rounded-xl transition-colors text-start"
                    :class="s.number === {{ $surah['number'] }}
                        ? ($store.theme.dark ? 'bg-gray-800 ring-1 ring-[#64CCC5]/40' : 'bg-[#0056b3]/5 ring-1 ring-[#0056b3]/20')
                        : ($store.theme.dark ? 'hover:bg-gray-800' : 'hover:bg-gray-100')">
                    <span class="w-9 h-9 flex-shrink-0 rounded-lg flex items-center justify-center text-sm font-bold tabular-nums"
                          :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'" x-text="fmtNum(s.number)"></span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-['Amiri'] text-lg truncate" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="s.nameAr"></span>
                        <span class="block text-xs truncate" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                            <span x-text="s.nameEn"></span> · <span x-text="s.verses"></span> {{ __('quran.public.verse_count') }}
                        </span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0"
                          :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'"
                          x-text="s.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'"></span>
                </button>
            </template>
            {{-- Empty --}}
            <p x-show="!switcherLoading && !switcherError && filteredSurahs.length === 0" x-cloak
               class="py-10 text-center text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_surahs_found') }}</p>
        </div>
    </div>
</div>
