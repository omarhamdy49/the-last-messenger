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
    <div class="sx-surface mb-6 flex flex-wrap items-center gap-3 p-3 sm:p-4">

        {{-- View mode: verse-by-verse vs continuous reading --}}
        <div class="sx-segment" role="group" aria-label="{{ __('quran.public.filter_layout') }}">
            <button type="button" @click="setViewMode('verses')" :aria-pressed="viewMode === 'verses'">
                <i data-lucide="list" aria-hidden="true"></i>{{ __('quran.public.view_by_verse') }}
            </button>
            <button type="button" @click="setViewMode('reading')" :aria-pressed="viewMode === 'reading'">
                <i data-lucide="book-open" aria-hidden="true"></i>{{ __('quran.public.view_reading') }}
            </button>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="adjustFontSize(-2)" class="sx-btn w-11 px-0"
                    aria-label="{{ __('quran.public.decrease_font') }}" title="{{ __('quran.public.decrease_font') }}">
                <i data-lucide="a-arrow-down" aria-hidden="true"></i>
            </button>
            <span class="sx-chip min-w-[4rem] justify-center tabular-nums" x-text="fontSize + 'px'"></span>
            <button type="button" @click="adjustFontSize(2)" class="sx-btn w-11 px-0"
                    aria-label="{{ __('quran.public.increase_font') }}" title="{{ __('quran.public.increase_font') }}">
                <i data-lucide="a-arrow-up" aria-hidden="true"></i>
            </button>
        </div>

        <button type="button" @click="toggleTransliteration" x-show="viewMode === 'verses'" class="sx-chip"
                :class="showTransliteration ? 'is-active' : ''" :aria-pressed="showTransliteration">
            <i data-lucide="languages" aria-hidden="true"></i>{{ __('quran.public.transliteration') }}
        </button>

        @if($hasAnyTranslation)
        <button type="button" @click="toggleTranslation" x-show="viewMode === 'verses'" class="sx-chip"
                :class="showTranslation ? 'is-active' : ''" :aria-pressed="showTranslation">
            <i data-lucide="book-open-text" aria-hidden="true"></i>{{ __('quran.public.mode_translation') }}
        </button>
        @endif
    </div>

    {{-- ===== Verse-by-verse (cards) ===== --}}
    <div x-show="viewMode === 'verses'" class="space-y-8 mb-10">
        @foreach($grouped as $surahNumber => $surahVerses)
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-[color:var(--sx-line)]"></div>
                <a href="{{ route('public.quran.surah', $surahNumber) }}"
                   class="sx-btn is-primary font-arabic text-xl"
                   title="{{ $surahVerses->first()['surahName'] ?? (__('quran.nav.surah').' '.$surahNumber) }}">
                    {{ $surahVerses->first()['surahName'] ?? (__('quran.nav.surah').' '.$surahNumber) }}
                </a>
                <div class="flex-1 h-px bg-[color:var(--sx-line)]"></div>
            </div>

            <div class="space-y-4">
                @foreach($surahVerses as $verse)
                <article class="verse-card sx-surface overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-[color:var(--sx-line)] bg-[color:var(--sx-surface-2)] p-4">
                        <div class="flex items-center gap-3">
                            <span class="sx-avatar w-10 h-10 text-sm font-bold tabular-nums">{{ $verse['number'] }}</span>
                            <span class="text-xs tabular-nums text-[color:var(--sx-muted)]" dir="ltr">{{ $verse['verseKey'] }}</span>
                            @if($verse['has_sajdah'])
                            <span class="sx-chip">{{ __('quran.public.sajdah') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6">
                        <p class="sx-scripture text-start"
                           dir="rtl"
                           :style="`font-size: ${fontSize}px`">{{ $verse['arabic'] }}</p>

                        @if(! empty($verse['transliteration']))
                        <p x-show="showTransliteration" class="mt-3 text-sm italic text-[color:var(--sx-muted)]">{{ $verse['transliteration'] }}</p>
                        @endif

                        @if(! empty($verse['translation']))
                        <p x-show="showTranslation" class="sx-prose mt-3 !text-lg">{{ $verse['translation'] }}</p>
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
                <div class="flex-1 h-px bg-[color:var(--sx-line)]"></div>
                <a href="{{ route('public.quran.surah', $surahNumber) }}"
                   class="sx-btn is-primary font-arabic text-xl"
                   title="{{ $surahVerses->first()['surahName'] ?? (__('quran.nav.surah').' '.$surahNumber) }}">
                    {{ $surahVerses->first()['surahName'] ?? (__('quran.nav.surah').' '.$surahNumber) }}
                </a>
                <div class="flex-1 h-px bg-[color:var(--sx-line)]"></div>
            </div>

            <div class="verse-card sx-surface p-6 sm:p-8">
                <p class="sx-scripture text-start" dir="rtl"
                   :style="`font-size: ${fontSize}px`">@foreach($surahVerses as $verse){{ $verse['arabic'] }}<span class="mx-1 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full align-middle text-xs font-bold tabular-nums bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)]">{{ $verse['number'] }}</span> @endforeach</p>
            </div>
        @endforeach
    </div>
</div>
