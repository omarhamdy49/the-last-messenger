{{--
    Tafseer Panel Component

    Displays an accordion-style tafseer panel within a verse card.
    Tafseer is loaded on-demand when the panel is opened.

    Expected Alpine.js context:
    - verse: current verse object (id)
    - selectedTafsirId: number | null
    - openTafsir: object mapping verse.id to boolean
    - tafsirLoadingVerse: number | null
    - $store.theme.dark: boolean

    Functions expected:
    - toggleTafsir(verse)
    - currentTafsir(verse): returns tafsir text
    - currentTafsirName(): returns tafsir source name
    - tafsirLoaded(verse): boolean
--}}

<div class="mt-4 pt-4" :class="$store.theme.dark ? 'border-t border-gray-700' : 'border-t border-gray-200'"
     x-show="selectedTafsirId">
    {{-- Explicit per-verse action: loads this verse's tafsir on demand --}}
    <button @click="toggleTafsir(verse)"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
        :class="openTafsir[verse.id]
            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
            : ($store.theme.dark ? 'bg-gray-800 text-gray-200 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')"
        :aria-expanded="openTafsir[verse.id] ? 'true' : 'false'"
    >
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        <span x-text="(openTafsir[verse.id] ? '{{ __('quran.public.hide_tafsir') }}' : '{{ __('quran.public.show_tafsir') }}') + ' · ' + currentTafsirName()"></span>
        <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-300"
             :class="openTafsir[verse.id] ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Tafseer content (with smooth accordion animation) --}}
    <div x-show="openTafsir[verse.id]"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="mt-4 p-4 rounded-xl"
         :class="$store.theme.dark ? 'bg-gray-800/50' : 'bg-gray-50'">

        {{-- Loading state --}}
        <p x-show="tafsirLoadingVerse === verse.id" class="text-sm animate-pulse"
           :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
            {{ __('quran.public.tafsir_loading') }}
        </p>

        {{-- Tafseer text --}}
        <p x-show="tafsirLoadingVerse !== verse.id && currentTafsir(verse)"
           class="leading-relaxed font-['Amiri'] text-lg" dir="auto"
           :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'"
           x-text="currentTafsir(verse)"></p>

        {{-- Loaded but this edition has no tafsir for the verse --}}
        <p x-show="tafsirLoadingVerse !== verse.id && tafsirLoaded(verse) && !currentTafsir(verse)"
           class="text-sm italic"
           :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
            {{ __('quran.public.no_tafsir') }}
        </p>
    </div>
</div>
