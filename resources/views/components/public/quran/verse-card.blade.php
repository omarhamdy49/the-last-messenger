{{--
    Verse Card Component

    Displays a single verse with header, Arabic text, translation, and action buttons.
    Tafsir is not shown inline: the header's book icon opens the shared verse modal.

    Expected Alpine.js context:
    - verse: current verse object (id, number, arabic, translation, transliteration, has_sajdah)
    - fmtNum(n): function to format numbers
    - readingMode: 'arabic' | 'translation' | 'both'
    - fontSize: number (px)
    - showTransliteration: boolean
    - selectedTafsirId: number
    - $store.theme.dark: boolean

    Functions expected:
    - playVerse(verse), isVerseLoaded(verse), isVersePlaying(verse)
    - copyVerse(verse)
    - openVerseModal(verse, tab)
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

            {{-- Opens the shared verse modal on its tafsir tab — the same modal the
                 reading view opens, so there is one place a verse is explained and the
                 text is fetched only when it is asked for. --}}
            <button @click="openVerseModal(verse, 'tafsir')"
                class="p-2 rounded-lg transition-colors"
                :class="tafsirVerse && tafsirVerse.id === verse.id
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
            {{-- Word by word: each word with its gloss; tap to hear it. --}}
            <p x-show="wordByWord && verse.words && verse.words.length" x-cloak class="font-['Amiri'] leading-loose text-start"
               :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" :style="`font-size: ${fontSize}px`" dir="rtl" lang="ar">
                <template x-for="(word, index) in (verse.words || [])" :key="index">
                    {{-- playWord, not the bottom player: a word is a two-second sound, and
                         routing it through the player replaced the surah recording and lost
                         the listener's place in it. --}}
                    <span class="wbw-word" role="button" tabindex="0"
                          :aria-label="'{{ __('quran.public.play_word') }}: ' + word.text"
                          :title="[word.transliteration, word.translation].filter(Boolean).join(' — ') || '{{ __('quran.public.play_word') }}'"
                          @click="playWord(word)"
                          @keydown.enter.prevent="playWord(word)">
                        <span x-text="word.text"></span>
                        <span x-show="word.type === 'word' && (word.translation || word.transliteration)" class="wbw-gloss"
                              :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                            <span x-show="word.transliteration" class="block italic" x-text="word.transliteration"></span>
                            <span x-text="word.translation"></span>
                        </span>
                    </span>
                </template>
            </p>
            {{-- Tajweed script: server-sanitised <tajweed class> markup only. --}}
            <p x-show="!(wordByWord && verse.words && verse.words.length) && verse.tajweed" x-cloak
               @click="tapWordAt($event, verse)"
               class="tajweed-text quran-text qw-tappable leading-loose text-start"
               :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" :style="`font-size: ${fontSize}px`" dir="rtl" lang="ar"
               x-html="verse.tajweed"></p>
            {{-- Tap any word to hear it — no markup around each word, and no need to
                 turn on word-by-word display; see tapWordAt(). --}}
            <p x-show="!(wordByWord && verse.words && verse.words.length) && !verse.tajweed" class="quran-text qw-tappable leading-loose text-start"
               :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
               :style="`font-size: ${fontSize}px`"
               dir="rtl" lang="ar"
               @click="tapWordAt($event, verse)"
               x-text="verse.arabic"
               title="{{ __('quran.public.play_word') }}"
            ></p>
        </div>

        {{-- Transliteration --}}
        <div x-show="showTransliteration && (readingMode === 'arabic' || readingMode === 'both') && verse.transliteration" class="mb-3">
            <p class="text-sm italic" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="verse.transliteration"></p>
        </div>

        {{-- Translation (hidden when it would duplicate the Arabic verse,
             e.g. an Arabic "translation" of the Arabic text) --}}
        <div x-show="(readingMode === 'translation' || readingMode === 'both') && getTranslation(verse) && getTranslation(verse) !== verse.arabic">
            <p class="text-lg leading-relaxed" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'" x-text="getTranslation(verse)" dir="auto"></p>
            {{-- Footnotes of the edition on screen --}}
            <ol x-show="footNotes(verse).length" x-cloak class="mt-2 space-y-1 text-sm list-decimal ps-5" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" dir="auto">
                <template x-for="[id, note] in footNotes(verse)" :key="id">
                    <li x-text="note"></li>
                </template>
            </ol>
        </div>

        {{-- Verse image (calligraphy) --}}
        <details x-show="verse.image_url" x-cloak class="mt-3">
            <summary class="cursor-pointer text-sm font-semibold text-[#218838]">{{ __('quran.public.verse_image') }}</summary>
            <img :src="verse.image_url" :alt="surah.nameAr + ' ' + verse.verse_key" loading="lazy" class="mt-2 max-w-full rounded-lg bg-white p-2">
        </details>
    </div>
</article>
