{{--
    Verse Card Component

    Displays a single verse with header, Arabic text, translation, and action buttons.
    Includes optional tafseer accordion panel.

    Rendered inside an Alpine `x-for` template, so its icons are inline SVG:
    `data-lucide` placeholders inside a template are not upgraded on re-render.

    Expected Alpine.js context:
    - verse: current verse object (id, number, arabic, translation, transliteration, has_sajdah)
    - fmtNum(n): function to format numbers
    - readingMode: 'arabic' | 'translation' | 'both'
    - fontSize: number (px)
    - showTransliteration: boolean
    - openTafsir: object mapping verse.id to boolean
    - selectedTafsirId: number

    Functions expected:
    - playVerse(verse), isVerseLoaded(verse), isVersePlaying(verse)
    - copyVerse(verse)
    - toggleTafsir(verse)
    - getTranslation(verse)
--}}

<article
    :id="'v' + verse.number"
    style="scroll-margin-top: 6rem"
    class="verse-card sx-surface overflow-hidden"
    :class="isVerseLoaded(verse) ? 'is-playing' : ''"
>
    {{-- Verse header --}}
    <div class="flex items-center justify-between gap-3 border-b border-[color:var(--sx-line)] bg-[color:var(--sx-surface-2)] p-4">
        <div class="flex min-w-0 items-center gap-3">
            <span class="sx-avatar h-10 w-10 text-sm font-bold tabular-nums" x-text="fmtNum(verse.number)"></span>
            <span class="font-bold tabular-nums text-[color:var(--sx-ink)]">
                {{ __('quran.public.verse_label') }} <span x-text="fmtNum(verse.number)"></span>
            </span>
            <span x-show="verse.has_sajdah" class="sx-chip">{{ __('quran.public.sajdah') }}</span>
        </div>

        {{-- Verse actions (always visible) --}}
        <div class="flex flex-shrink-0 items-center gap-2">
            {{-- Play/Pause button --}}
            <button type="button" @click="playVerse(verse)"
                class="sx-btn w-11 px-0 !rounded-full"
                :class="isVerseLoaded(verse) ? '!text-white !border-transparent' : ''"
                :style="isVerseLoaded(verse) ? 'background: var(--sx-grad)' : ''"
                title="{{ __('quran.public.listen') }}" aria-label="{{ __('quran.public.listen') }}"
            >
                {{-- play, or pause while this verse is playing --}}
                <svg x-show="!isVersePlaying(verse)" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                </svg>
                <svg x-show="isVersePlaying(verse)" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 7a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1zm6 0a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
            </button>

            {{-- Copy button --}}
            <button type="button" @click="copyVerse(verse)"
                class="sx-btn w-11 px-0 !rounded-full"
                title="{{ __('site.share') }}" aria-label="{{ __('site.share') }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>

            {{-- Tafseer toggle button --}}
            <button type="button" x-show="selectedTafsirId" @click="toggleTafsir(verse)"
                class="sx-btn w-11 px-0 !rounded-full"
                :class="openTafsir[verse.id] ? '!text-white !border-transparent' : ''"
                :style="openTafsir[verse.id] ? 'background: var(--sx-grad)' : ''"
                :aria-expanded="openTafsir[verse.id] ? 'true' : 'false'"
                title="{{ __('quran.public.show_tafsir') }}" aria-label="{{ __('quran.public.show_tafsir') }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Verse content --}}
    <div class="p-6">
        {{-- Arabic text --}}
        <div x-show="readingMode === 'arabic' || readingMode === 'both'" class="mb-4">
            <p class="sx-scripture text-start"
               :style="`font-size: ${fontSize}px`"
               x-text="verse.arabic"
               dir="rtl"
            ></p>
        </div>

        {{-- Transliteration --}}
        <div x-show="showTransliteration && (readingMode === 'arabic' || readingMode === 'both') && verse.transliteration" class="mb-3">
            <p class="text-sm italic text-[color:var(--sx-muted)]" x-text="verse.transliteration"></p>
        </div>

        {{-- Translation (hidden when it would duplicate the Arabic verse,
             e.g. an Arabic "translation" of the Arabic text) --}}
        <div x-show="(readingMode === 'translation' || readingMode === 'both') && getTranslation(verse) && getTranslation(verse) !== verse.arabic">
            <p class="sx-prose !text-lg" x-text="getTranslation(verse)"></p>
        </div>

        {{-- Tafsir accordion (loaded per verse, only when opened) --}}
        <x-public.quran.tafseer-panel />
    </div>
</article>
