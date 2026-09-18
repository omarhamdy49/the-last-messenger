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

@props(['surah', 'tafsirResources', 'translationResources' => [], 'defaultTranslationLang'])

{{-- ============================================================ --}}
{{-- VERSE TAFSIR MODAL (reading view — tapping an ayah opens this)  --}}
{{-- ============================================================ --}}
<div x-show="tafsirVerse" x-cloak x-transition.opacity
     @keydown.escape.window="tafsirVerse = null"
     class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 70;">
    <div class="absolute inset-0 bg-black/50" @click="tafsirVerse = null"></div>
    <div class="sx-surface relative flex w-full max-w-lg flex-col overflow-hidden"
         style="max-height: 80vh;"
         role="dialog" aria-modal="true">
        <div class="relative flex-shrink-0 border-b border-[color:var(--sx-line)] bg-[color:var(--sx-surface-2)] p-5">
            <button type="button" @click="tafsirVerse = null" class="sx-btn absolute top-3 end-3 w-11 px-0 !rounded-full"
                    aria-label="{{ __('quran.public.close') }}" title="{{ __('quran.public.close') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="text-xs tabular-nums text-[color:var(--sx-muted)]">{{ $surah['nameAr'] }} · <span x-text="tafsirVerse ? fmtNum(tafsirVerse.verse_key) : ''"></span></p>
            <p class="sx-scripture mt-2 pe-10 !text-2xl" dir="rtl" x-text="tafsirVerse ? tafsirVerse.arabic : ''"></p>
        </div>
        <div class="overflow-y-auto p-5">
            {{-- Edition selector — same source list as the rail; text lazy-loads per verse/edition on change --}}
            <template x-if="tafsirResources.length > 1">
                <select x-model.number="selectedTafsirId" @change="setTafsirSource(selectedTafsirId)"
                        class="sx-input mb-3" aria-label="{{ __('quran.public.tafsir_source') }}">
                    @foreach($tafsirResources as $tr)
                    <option value="{{ $tr['id'] }}">{{ $tr['name'] }}</option>
                    @endforeach
                </select>
            </template>
            <p x-show="tafsirResources.length <= 1" class="sx-label" x-text="currentTafsirName()"></p>
            {{-- Loading --}}
            <p x-show="tafsirVerse && tafsirLoadingVerse === tafsirVerse.id" class="animate-pulse text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.tafsir_loading') }}</p>
            {{-- Text --}}
            <p x-show="tafsirVerse && tafsirLoadingVerse !== tafsirVerse.id && currentTafsir(tafsirVerse)"
               class="font-arabic text-lg leading-relaxed text-[color:var(--sx-ink-2)]" dir="auto"
               x-text="tafsirVerse ? currentTafsir(tafsirVerse) : ''"></p>
            {{-- Loaded but this edition has no tafsir for the verse --}}
            <p x-show="tafsirVerse && tafsirLoadingVerse !== tafsirVerse.id && tafsirLoaded(tafsirVerse) && !currentTafsir(tafsirVerse)"
               class="text-sm italic text-[color:var(--sx-muted)]">{{ __('quran.public.no_tafsir') }}</p>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SURAH INFO MODAL --}}
{{-- ============================================================ --}}
<div x-show="infoOpen" x-cloak x-transition.opacity
     @keydown.escape.window="infoOpen = false"
     class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 70;">
    <div class="absolute inset-0 bg-black/50" @click="infoOpen = false"></div>
    <div class="sx-surface relative flex w-full max-w-lg flex-col overflow-hidden"
         style="max-height: 80vh;"
         role="dialog" aria-modal="true">
        <div class="relative flex-shrink-0 border-b border-[color:var(--sx-line)] bg-[color:var(--sx-surface-2)] p-5">
            <button type="button" @click="infoOpen = false" class="sx-btn absolute top-3 end-3 w-11 px-0 !rounded-full"
                    aria-label="{{ __('quran.public.close') }}" title="{{ __('quran.public.close') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="font-arabic pe-10 text-3xl font-bold leading-tight text-[color:var(--sx-ink)]">{{ $surah['nameAr'] }}</p>
            <p class="mt-1 text-sm text-[color:var(--sx-muted)]">{{ $surah['nameEn'] }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="sx-chip">{{ $surah['type'] === 'makki' ? __('quran.public.makki') : __('quran.public.madani') }}</span>
                <span class="sx-chip tabular-nums">{{ $surah['versesCount'] }} {{ __('quran.public.verse_count') }}</span>
                <span class="sx-chip tabular-nums">{{ __('quran.public.juz') }} {{ $surah['juz'] }}</span>
                <span class="sx-chip tabular-nums">{{ __('quran.public.revelation_order') }} {{ $surah['revelationOrder'] }}</span>
            </div>
        </div>
        <div class="overflow-y-auto p-5">
            @if(! empty($surah['info']))
                <p class="sx-prose !text-base text-start" style="word-break: break-word;">{{ $surah['info'] }}</p>
            @else
                <p class="py-6 text-center text-sm tabular-nums text-[color:var(--sx-muted)]">{{ $surah['nameEn'] }} · {{ $surah['versesCount'] }} {{ __('quran.public.verse_count') }}</p>
            @endif
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
    <div class="sx-surface relative flex w-full max-w-md flex-col overflow-hidden"
         style="max-height: 80vh;"
         role="dialog" aria-modal="true">
        {{-- Header + search --}}
        <div class="flex-shrink-0 border-b border-[color:var(--sx-line)] p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-extrabold text-[color:var(--sx-ink)]">{{ __('quran.public.switch_surah') }}</h2>
                <button type="button" @click="switcherOpen = false" class="sx-btn w-11 px-0 !rounded-full"
                        aria-label="{{ __('quran.public.close') }}" title="{{ __('quran.public.close') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="relative">
                <svg class="pointer-events-none absolute top-1/2 start-3 w-4 h-4 -translate-y-1/2 text-[color:var(--sx-muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4-4"/></svg>
                <input type="text" x-model="switcherSearch" placeholder="{{ __('quran.public.search_surahs') }}"
                       class="sx-input ps-10 text-base sm:text-sm">
            </div>
        </div>
        {{-- List --}}
        <div class="flex-1 overflow-y-auto p-2">
            {{-- Loading --}}
            <div x-show="switcherLoading" class="py-10 text-center">
                <svg class="mx-auto w-6 h-6 animate-spin text-[color:var(--sx-accent)]" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <p class="mt-2 text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.surahs_loading') }}</p>
            </div>
            {{-- Error --}}
            <div x-show="switcherError" x-cloak class="py-10 text-center">
                <p class="text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.surahs_load_error') }}</p>
                <button type="button" @click="switcherSurahs = []; openSwitcher()" class="sx-btn is-primary mt-3"
                        aria-label="{{ __('quran.public.surahs_loading') }}" title="{{ __('quran.public.surahs_loading') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3l3 3M19 15a7 7 0 01-11 3l-3-3"/></svg>
                </button>
            </div>
            {{-- Rows --}}
            <div class="sx-tile-list">
                <template x-for="s in filteredSurahs" :key="s.number">
                    <button type="button" @click="goToSurah(s.number)" class="sx-tile text-start"
                        :class="s.number === {{ $surah['number'] }} ? '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]' : ''"
                        :aria-current="s.number === {{ $surah['number'] }} ? 'page' : 'false'">
                        <span class="sx-tile-icon text-sm font-extrabold tabular-nums" x-text="fmtNum(s.number)"></span>
                        <span class="min-w-0">
                            <span class="sx-tile-title font-arabic text-lg" :title="s.nameAr" x-text="s.nameAr"></span>
                            <span class="sx-tile-sub block tabular-nums">
                                <span x-text="s.nameEn"></span> · <span x-text="s.verses"></span> {{ __('quran.public.verse_count') }}
                            </span>
                        </span>
                        <span class="sx-chip !min-h-0 !text-[10px]"
                              x-text="s.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'"></span>
                    </button>
                </template>
            </div>
            {{-- Empty --}}
            <p x-show="!switcherLoading && !switcherError && filteredSurahs.length === 0" x-cloak
               class="py-10 text-center text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.no_surahs_found') }}</p>
        </div>
    </div>
</div>
