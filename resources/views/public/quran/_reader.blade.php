{{--
    Shared continuous reader for Juz / Hizb / Page views.
    Expects: $verses = array of ['surahNumber','surahName','number','verseKey','arabic','translation','transliteration','has_sajdah']
    Two view modes (Alpine-driven, persisted): 'verses' = card-per-ayah, 'reading' = flowing whole-surah text.
--}}
@php
    $grouped = collect($verses)->groupBy('surahNumber');
    $hasAnyTranslation = collect($verses)->contains(fn ($v) => ! empty($v['translation']));
@endphp

<div
    x-data="{
        fontSize: parseInt(localStorage.getItem('quranFontSize')) || 30,
        showTransliteration: localStorage.getItem('quranTranslit') === '1',
        showTranslation: localStorage.getItem('quranShowTranslation') !== '0',
        viewMode: localStorage.getItem('quranReaderView') || 'verses',
        adjustFontSize(d) { this.fontSize = Math.min(56, Math.max(16, this.fontSize + d)); localStorage.setItem('quranFontSize', this.fontSize); },
        toggleTransliteration() { this.showTransliteration = !this.showTransliteration; localStorage.setItem('quranTranslit', this.showTransliteration ? '1' : '0'); },
        toggleTranslation() { this.showTranslation = !this.showTranslation; localStorage.setItem('quranShowTranslation', this.showTranslation ? '1' : '0'); },
        setViewMode(m) { this.viewMode = m; localStorage.setItem('quranReaderView', m); },
    }"
>
    {{-- Reading controls --}}
    <div class="mb-6 p-4 rounded-2xl shadow-lg flex flex-wrap items-center gap-3"
         :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">

        {{-- View mode: verse-by-verse vs continuous reading --}}
        <div class="flex items-center gap-1 rounded-xl p-1" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'">
            <button @click="setViewMode('verses')" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all"
                :class="viewMode === 'verses' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : ($store.theme.dark ? 'text-gray-300' : 'text-gray-700')"
            >{{ __('quran.public.view_by_verse') }}</button>
            <button @click="setViewMode('reading')" class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all"
                :class="viewMode === 'reading' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : ($store.theme.dark ? 'text-gray-300' : 'text-gray-700')"
            >{{ __('quran.public.view_reading') }}</button>
        </div>

        <div class="flex items-center gap-2">
            <button @click="adjustFontSize(-2)" class="p-2 rounded-lg transition-colors"
                :class="$store.theme.dark ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'"
                aria-label="{{ __('quran.public.decrease_font') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </button>
            <span class="px-3 py-1 rounded-lg text-sm font-semibold min-w-[3.5rem] text-center"
                  :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'"
                  x-text="fontSize + 'px'"></span>
            <button @click="adjustFontSize(2)" class="p-2 rounded-lg transition-colors"
                :class="$store.theme.dark ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'"
                aria-label="{{ __('quran.public.increase_font') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
        </div>

        <button @click="toggleTransliteration" x-show="viewMode === 'verses'" class="px-3 py-2 rounded-lg text-sm font-semibold transition-all"
            :class="showTransliteration
                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
        >{{ __('quran.public.transliteration') }}</button>

        @if($hasAnyTranslation)
        <button @click="toggleTranslation" x-show="viewMode === 'verses'" class="px-3 py-2 rounded-lg text-sm font-semibold transition-all"
            :class="showTranslation
                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
        >{{ __('quran.public.mode_translation') }}</button>
        @endif
    </div>

    {{-- ===== Verse-by-verse (cards) ===== --}}
    <div x-show="viewMode === 'verses'" class="space-y-8 mb-10">
        @foreach($grouped as $surahNumber => $surahVerses)
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                <a href="{{ route('public.quran.surah', $surahNumber) }}"
                   class="px-5 py-2 rounded-full font-['Amiri'] text-xl shadow-md transition-transform hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                    {{ $surahVerses->first()['surahName'] ?? (__('quran.nav.surah').' '.$surahNumber) }}
                </a>
                <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
            </div>

            <div class="space-y-4">
                @foreach($surahVerses as $verse)
                <article class="verse-card rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl"
                         :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <div class="flex items-center justify-between p-4"
                         :class="$store.theme.dark ? 'bg-gray-800/50 border-b border-gray-700' : 'bg-gray-50 border-b border-gray-200'">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full flex items-center justify-center shadow-lg flex-shrink-0">
                                <span class="text-white font-bold text-sm">{{ $verse['number'] }}</span>
                            </div>
                            <span class="text-xs font-mono" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ $verse['verseKey'] }}</span>
                            @if($verse['has_sajdah'])
                            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">{{ __('quran.public.sajdah') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        <p class="font-['Amiri'] leading-loose text-start"
                           dir="rtl"
                           :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                           :style="`font-size: ${fontSize}px`">{{ $verse['arabic'] }}</p>

                        @if(! empty($verse['transliteration']))
                        <p x-show="showTransliteration" class="text-sm italic mt-3"
                           :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ $verse['transliteration'] }}</p>
                        @endif

                        @if(! empty($verse['translation']))
                        <p x-show="showTranslation" class="text-lg leading-relaxed mt-3"
                           :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">{{ $verse['translation'] }}</p>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- ===== Continuous reading (whole surah flow) ===== --}}
    <div x-show="viewMode === 'reading'" x-cloak class="space-y-8 mb-10">
        @foreach($grouped as $surahNumber => $surahVerses)
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                <a href="{{ route('public.quran.surah', $surahNumber) }}"
                   class="px-5 py-2 rounded-full font-['Amiri'] text-xl shadow-md transition-transform hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                    {{ $surahVerses->first()['surahName'] ?? (__('quran.nav.surah').' '.$surahNumber) }}
                </a>
                <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
            </div>

            <div class="verse-card rounded-2xl shadow-lg p-6 sm:p-8" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                <p class="font-['Amiri'] leading-loose text-start" dir="rtl"
                   :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                   :style="`font-size: ${fontSize}px`">@foreach($surahVerses as $verse){{ $verse['arabic'] }}<span class="inline-flex items-center justify-center align-middle mx-1 w-7 h-7 text-xs font-sans font-bold rounded-full flex-shrink-0" :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'">{{ $verse['number'] }}</span> @endforeach</p>
            </div>
        @endforeach
    </div>
</div>
