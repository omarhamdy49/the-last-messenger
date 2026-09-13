{{--
    Verse Card Component

    Displays a single verse with header, Arabic text, translation, and action buttons.
    Includes optional tafseer accordion panel.

    Expected Alpine.js context:
    - verse: current verse object (id, number, arabic, translation, transliteration, has_sajdah)
    - fmtNum(n): function to format numbers
    - readingMode: 'arabic' | 'translation' | 'both'
    - fontSize: number (px)
    - showTransliteration: boolean
    - openTafsir: object mapping verse.id to boolean
    - selectedTafsirId: number
    - $store.theme.dark: boolean

    Functions expected:
    - playVerse(verse), isVerseLoaded(verse), isVersePlaying(verse)
    - copyVerse(verse)
    - toggleTafsir(verse)
    - getTranslation(verse)
--}}

<article
    :id="'v' + verse.number"
    style="scroll-margin-top: 6rem"
    class="verse-card rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl"
    :class="[$store.theme.dark ? 'bg-gray-900' : 'bg-white', isVerseLoaded(verse) ? 'is-playing' : '']"
>
    {{-- Verse header --}}
    <div class="flex items-center justify-between p-4"
         :class="$store.theme.dark ? 'bg-gray-800/50 border-b border-gray-700' : 'bg-gray-50 border-b border-gray-200'">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full flex items-center justify-center shadow-lg">
                <span class="text-white font-bold text-sm" x-text="fmtNum(verse.number)"></span>
            </div>
            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                {{ __('quran.public.verse_label') }} <span x-text="fmtNum(verse.number)"></span>
            </span>
            <span x-show="verse.has_sajdah"
                class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">
                {{ __('quran.public.sajdah') }}
            </span>
        </div>

        {{-- Verse actions (always visible) --}}
        <div class="flex items-center gap-2">
            {{-- Play/Pause button --}}
            <button @click="playVerse(verse)"
                class="p-2 rounded-lg transition-colors"
                :class="isVerseLoaded(verse)
                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                    : ($store.theme.dark ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900')"
                :title="'{{ __('quran.public.listen') }}'"
            >
                {{-- play, or pause while this verse is playing --}}
                <svg x-show="!isVersePlaying(verse)" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                </svg>
                <svg x-show="isVersePlaying(verse)" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 7a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1zm6 0a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
            </button>

            {{-- Copy button --}}
            <button @click="copyVerse(verse)"
                class="p-2 rounded-lg transition-colors"
                :class="$store.theme.dark ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900'"
                :title="'{{ __('site.share') }}'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>

            {{-- Tafseer toggle button --}}
            <button x-show="selectedTafsirId" @click="toggleTafsir(verse)"
                class="p-2 rounded-lg transition-colors"
                :class="openTafsir[verse.id]
                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                    : ($store.theme.dark ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900')"
                :title="'{{ __('quran.public.show_tafsir') }}'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Verse content --}}
    <div class="p-6">
        {{-- Arabic text --}}
        <div x-show="readingMode === 'arabic' || readingMode === 'both'" class="mb-4">
            <p class="font-['Amiri'] leading-loose text-start"
               :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
               :style="`font-size: ${fontSize}px`"
               x-text="verse.arabic"
               dir="rtl"
            ></p>
        </div>

        {{-- Transliteration --}}
        <div x-show="showTransliteration && (readingMode === 'arabic' || readingMode === 'both') && verse.transliteration" class="mb-3">
            <p class="text-sm italic" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="verse.transliteration"></p>
        </div>

        {{-- Translation (hidden when it would duplicate the Arabic verse,
             e.g. an Arabic "translation" of the Arabic text) --}}
        <div x-show="(readingMode === 'translation' || readingMode === 'both') && getTranslation(verse) && getTranslation(verse) !== verse.arabic">
            <p class="text-lg leading-relaxed" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'" x-text="getTranslation(verse)"></p>
        </div>

        {{-- Tafsir accordion (loaded per verse, only when opened) --}}
        <x-public.quran.tafseer-panel />
    </div>
</article>
