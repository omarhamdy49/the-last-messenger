{{--
    Tafseer Panel Component

    Displays an accordion-style tafseer panel within a verse card.
    Tafseer is loaded on-demand when the panel is opened.

    Rendered inside an Alpine `x-for` template, so its icons are inline SVG.

    Expected Alpine.js context:
    - verse: current verse object (id)
    - selectedTafsirId: number | null
    - openTafsir: object mapping verse.id to boolean
    - tafsirLoadingVerse: number | null

    Functions expected:
    - toggleTafsir(verse)
    - currentTafsir(verse): returns tafsir text
    - currentTafsirName(): returns tafsir source name
    - tafsirLoaded(verse): boolean
--}}

<div class="mt-4 border-t border-[color:var(--sx-line)] pt-4" x-show="selectedTafsirId">
    {{-- Explicit per-verse action: loads this verse's tafsir on demand --}}
    <button type="button" @click="toggleTafsir(verse)"
        class="sx-chip"
        :class="openTafsir[verse.id] ? 'is-active' : ''"
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
         class="sx-panel mt-4 p-4">

        {{-- Loading state --}}
        <p x-show="tafsirLoadingVerse === verse.id" class="animate-pulse text-sm text-[color:var(--sx-muted)]">
            {{ __('quran.public.tafsir_loading') }}
        </p>

        {{-- Tafseer text --}}
        <p x-show="tafsirLoadingVerse !== verse.id && currentTafsir(verse)"
           class="font-arabic text-lg leading-relaxed text-[color:var(--sx-ink-2)]" dir="auto"
           x-text="currentTafsir(verse)"></p>

        {{-- Loaded but this edition has no tafsir for the verse --}}
        <p x-show="tafsirLoadingVerse !== verse.id && tafsirLoaded(verse) && !currentTafsir(verse)"
           class="text-sm italic text-[color:var(--sx-muted)]">
            {{ __('quran.public.no_tafsir') }}
        </p>
    </div>
</div>
