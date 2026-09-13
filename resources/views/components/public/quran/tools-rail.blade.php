{{--
    Floating Tools Rail Component

    Desktop: vertical pill docked to inline-end edge
    Mobile: horizontal bar pinned bottom-centre

    Expected Alpine.js context:
    - viewMode: 'reading' | 'verses'
    - panel: null | 'display' | 'font' | 'settings'
    - readingMode: 'arabic' | 'translation' | 'both'
    - fontSize: number
    - editionsForLang: array
    - tafsirResources: array
    - reciters: array
    - selectedTranslationId: number
    - selectedTafsirId: number
    - translationResources: array
    - surah: object
    - $store.audioPlayer: audio player store
    - $store.theme.dark: boolean

    Functions expected:
    - setViewMode(mode)
    - setReadingMode(mode)
    - adjustFontSize(delta)
    - setTranslationEdition(id)
    - setTafsirSource(id)
    - openSwitcher()
--}}

<div x-data="{ panel: null }" @keydown.escape.window="panel = null" @click.outside="panel = null"
     class="mushaf-rail" role="toolbar" aria-label="{{ __('quran.public.surah_tools') }}">
    <div class="mushaf-rail-inner" :class="$store.theme.dark ? 'mushaf-rail-dark' : ''">

        {{-- Layout toggle: Mushaf <-> Verses --}}
        <button type="button" @click="setViewMode(viewMode === 'reading' ? 'verses' : 'reading'); panel = null"
            class="mushaf-rail-btn" :class="viewMode === 'reading' ? 'is-active' : ''"
            :title="viewMode === 'reading' ? @js(__('quran.public.view_by_verse')) : @js(__('quran.public.view_reading'))"
            :aria-label="viewMode === 'reading' ? @js(__('quran.public.view_by_verse')) : @js(__('quran.public.view_reading'))">
            {{-- In mushaf mode → icon shows the target (verse list); in verse mode → shows the mushaf (open book). --}}
            <svg x-show="viewMode === 'reading'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/></svg>
            <svg x-show="viewMode !== 'reading'" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2 1.5-1.5 4-2 8-2v-13c-4 0-6.5.5-8 2zM12 6.5v13"/></svg>
            {{-- Label tracks the icon: it names the mode you switch *to*. --}}
            <span class="mushaf-rail-label" x-text="viewMode === 'reading' ? @js(__('quran.public.view_by_verse')) : @js(__('quran.public.view_reading'))"></span>
        </button>

        {{-- Display mode: Arabic / Translation / Both --}}
        <div class="mushaf-rail-item">
            <button type="button" @click="panel = panel === 'display' ? null : 'display'"
                class="mushaf-rail-btn" :class="panel === 'display' ? 'is-active' : ''"
                title="{{ __('quran.public.filter_display') }}" aria-label="{{ __('quran.public.filter_display') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h7M4 5c0 4 3 9 7 11M9 3v2m-2 9c2-1 4-3 5-6m6 13-3-7-3 7m1.2-3h3.6"/></svg>
                <span class="mushaf-rail-label">{{ __('quran.public.filter_display') }}</span>
            </button>
            <div x-show="panel === 'display'" x-cloak x-transition.opacity class="mushaf-rail-pop" :class="$store.theme.dark ? 'mushaf-pop-dark' : ''">
                <p class="mushaf-pop-title">{{ __('quran.public.filter_display') }}</p>
                @foreach(['arabic' => __('quran.public.mode_arabic'), 'translation' => __('quran.public.mode_translation'), 'both' => __('quran.public.mode_both')] as $mode => $label)
                <button type="button" @click="setReadingMode('{{ $mode }}')" class="mushaf-pop-opt" :class="readingMode === '{{ $mode }}' ? 'is-on' : ''">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Font size --}}
        <div class="mushaf-rail-item">
            <button type="button" @click="panel = panel === 'font' ? null : 'font'"
                class="mushaf-rail-btn" :class="panel === 'font' ? 'is-active' : ''"
                title="{{ __('quran.public.filter_font') }}" aria-label="{{ __('quran.public.filter_font') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 18 8.5 7l4.5 11M5.4 14h6.2M15 18l3-8 3 8m-5.3-2.8h4.6"/></svg>
                <span class="mushaf-rail-label">{{ __('quran.public.filter_font') }}</span>
            </button>
            <div x-show="panel === 'font'" x-cloak x-transition.opacity class="mushaf-rail-pop" :class="$store.theme.dark ? 'mushaf-pop-dark' : ''">
                <p class="mushaf-pop-title">{{ __('quran.public.filter_font') }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="adjustFontSize(-2)" class="mushaf-step-btn" aria-label="{{ __('quran.public.decrease_font') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M20 12H4"/></svg>
                    </button>
                    <span class="min-w-[3.25rem] text-center text-sm font-semibold tabular-nums" x-text="fontSize + 'px'"></span>
                    <button type="button" @click="adjustFontSize(2)" class="mushaf-step-btn" aria-label="{{ __('quran.public.increase_font') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Play: open/control the shared bottom player for this surah --}}
        <button type="button"
            @click="$store.audioPlayer.track ? $store.audioPlayer.toggle() : $store.audioPlayer.playSurah()"
            class="mushaf-rail-btn" :class="$store.audioPlayer.playing ? 'is-active' : ''"
            title="{{ __('quran.public.listen') }}" aria-label="{{ __('quran.public.listen') }}">
            <svg x-show="!$store.audioPlayer.playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
            <svg x-show="$store.audioPlayer.playing" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
            <span class="mushaf-rail-label">{{ __('quran.public.listen') }}</span>
        </button>

        {{-- Settings: translation edition + tafsir source (filters, tucked away) --}}
        <div class="mushaf-rail-item" x-show="editionsForLang.length > 1 || tafsirResources.length > 1 || reciters.length > 1" x-cloak>
            <button type="button" @click="panel = panel === 'settings' ? null : 'settings'"
                class="mushaf-rail-btn" :class="panel === 'settings' ? 'is-active' : ''"
                title="{{ __('quran.public.display_settings') }}" aria-label="{{ __('quran.public.display_settings') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h10m4 0h2M4 12h2m4 0h10M4 18h10m4 0h2"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="18" r="2"/></svg>
                <span class="mushaf-rail-label">{{ __('quran.public.rail_settings') }}</span>
            </button>
            <div x-show="panel === 'settings'" x-cloak x-transition.opacity class="mushaf-rail-pop mushaf-rail-pop-wide" :class="$store.theme.dark ? 'mushaf-pop-dark' : ''">
                <div x-show="editionsForLang.length > 1">
                    <p class="mushaf-pop-title">{{ __('quran.public.translation_edition') }}</p>
                    <select x-model.number="selectedTranslationId" @change="setTranslationEdition(selectedTranslationId)" class="mushaf-pop-select">
                        @foreach($translationResources as $tr)
                            @if($tr['language'] === $defaultTranslationLang)
                            <option value="{{ $tr['id'] }}">{{ $tr['name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div x-show="tafsirResources.length > 1" class="mt-3">
                    <p class="mushaf-pop-title">{{ __('quran.public.tafsir_source') }}</p>
                    <select x-model.number="selectedTafsirId" @change="setTafsirSource(selectedTafsirId)" class="mushaf-pop-select">
                        @foreach($tafsirResources as $tr)
                        <option value="{{ $tr['id'] }}">{{ $tr['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="reciters.length > 1" class="mt-3">
                    <p class="mushaf-pop-title">{{ __('quran.public.reciter') }}</p>
                    <select :value="$store.audioPlayer.currentReciter()?.profileId || $store.audioPlayer.reciterId" @change="$store.audioPlayer.selectPerson(Number($event.target.value))" class="w-full rounded-lg border-gray-300 dark:bg-gray-800">
                        <template x-for="person in $store.audioPlayer.personOptions()" :key="person.id"><option :value="person.id" x-text="person.name"></option></template>
                    </select>
                    <label class="block mt-3" x-show="$store.audioPlayer.recordingOptions().length > 1">{{ __('quran.recordings.choose') }}
                        <select :value="$store.audioPlayer.reciterId" @change="$store.audioPlayer.selectReciter(Number($event.target.value))" class="w-full rounded-lg border-gray-300 dark:bg-gray-800">
                            <template x-for="r in $store.audioPlayer.recordingOptions()" :key="r.id"><option :value="r.id" x-text="r.recordingLabel || r.nameAr || r.nameEn"></option></template>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <span class="mushaf-rail-divider" aria-hidden="true"></span>

        {{-- Switch surah — opens the lazy-loaded surah list modal --}}
        <button type="button" @click="openSwitcher(); panel = null"
            class="mushaf-rail-btn" title="{{ __('quran.public.switch_surah') }}" aria-label="{{ __('quran.public.switch_surah') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            <span class="mushaf-rail-label">{{ __('quran.public.rail_surahs') }}</span>
        </button>

        {{-- Surah info — opens the description modal --}}
        <button type="button" @click="infoOpen = true; panel = null"
            class="mushaf-rail-btn" title="{{ __('quran.public.surah_info') }}" aria-label="{{ __('quran.public.surah_info') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5M12 8h.01"/></svg>
            <span class="mushaf-rail-label">{{ __('quran.public.rail_info') }}</span>
        </button>

        {{-- Ask about this surah — prefilled AI question --}}
        <a href="{{ route('public.quran.ask', ['q' => $surah['nameAr'].': ']) }}"
            class="mushaf-rail-btn" title="{{ __('quran.public.ask_about_surah') }}" aria-label="{{ __('quran.public.ask_about_surah') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8zM18 14l.9 2.1L21 17l-2.1.9L18 20l-.9-2.1L15 17l2.1-.9z"/></svg>
            <span class="mushaf-rail-label">{{ __('quran.public.rail_ask') }}</span>
        </a>
    </div>
</div>
