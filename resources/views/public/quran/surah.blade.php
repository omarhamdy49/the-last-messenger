@extends('layouts.public')

@section('title', __('quran.public.surah_page_title', ['name' => $surah['nameAr'], 'number' => $surah['number']]))
@section('meta_description', __('quran.public.surah_page_description', ['name' => $surah['nameAr'], 'nameEn' => $surah['nameEn'], 'verses' => $surah['versesCount']]))

@push('head')
<style>
    .progress-bar {
        position: fixed;
        top: 64px;
        inset-inline-start: 0;
        inset-inline-end: 0;
        height: 3px;
        background: linear-gradient(90deg, #0056b3, #218838);
        transform-origin: left;
        z-index: 40;
        transition: transform 0.2s ease;
    }
    /* Desktop: let the sticky sidebar scroll on its own so all sections
       (player, surah nav, related links) are reachable without scrolling
       the full verse list. */
    @media (min-width: 1024px) {
        .quran-sidebar {
            position: sticky;
            top: 6rem;
            max-height: calc(100vh - 7rem);
            overflow-y: auto;
        }
    }
    /* Mobile: the sidebar becomes a bottom sheet opened via the floating button. */
    @media (max-width: 1023px) {
        .quran-sidebar-aside {
            position: fixed;
            inset-inline: 0;
            bottom: 0;
            z-index: 50;
            margin-top: 0;
            max-height: 85vh;
            overflow-y: auto;
            border-top-left-radius: 1.5rem;
            border-top-right-radius: 1.5rem;
            box-shadow: 0 -12px 40px rgba(0, 0, 0, 0.35);
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        .quran-sidebar-aside.is-open {
            transform: translateY(0);
        }
    }
    /* Brief highlight for a deep-linked verse (#v65). */
    .verse-target { animation: verseTargetPulse 2.4s ease-out 1; border-radius: 1rem; }
    @keyframes verseTargetPulse {
        0% { box-shadow: 0 0 0 0 rgba(100,204,197,0.0); }
        15% { box-shadow: 0 0 0 4px rgba(100,204,197,0.55); }
        100% { box-shadow: 0 0 0 4px rgba(100,204,197,0.0); }
    }
    @media (prefers-reduced-motion: reduce) { .verse-target { animation: none; } }

    /* ---- Mushaf reading view (line-for-line, like a printed mushaf) ---- */
    /* Width cap lives here (not a Tailwind arbitrary class) so it works without a
       Vite rebuild and keeps lines narrow enough to fill via fit-to-width. */
    .mushaf-card { max-width: 40rem; }
    .mushaf {
        font-family: 'Amiri', serif;
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }
    .mushaf-bismillah {
        text-align: center;
        font-size: 1.4em;
        line-height: 2;
        margin-bottom: 1.75rem;
        opacity: 0.92;
    }
    .mushaf-page + .mushaf-page { margin-top: 0.25rem; }
    /* Each printed-mushaf line stays on one line; a script scales its font so the
       line fills the column width with natural word spacing (no justify gaps). */
    .mushaf-line {
        white-space: nowrap;
        text-align: center;
        line-height: 2.5;
    }
    /* The last (partial) line of a page wraps/centres naturally, no fit-scaling. */
    .mushaf-line.mushaf-line-end {
        white-space: normal;
        text-align: center;
    }
    .mushaf .qw {
        cursor: pointer;
        border-radius: 0.25em;
        padding: 0.02em 0.04em;
        transition: background-color 0.18s ease, color 0.18s ease;
    }
    /* Whole-ayah lift on hover (set by the partial's delegated listener). */
    .mushaf .qw.qw-verse-active {
        background-color: rgba(100, 204, 197, 0.16);
        color: #0056b3;
    }
    .mushaf-dark .qw.qw-verse-active { color: #a7f3ef; }

    /* Ayah being recited — stronger than the hover lift, and it stays put. */
    .mushaf .qw.qw-verse-playing {
        background-color: rgba(33, 136, 56, 0.16);
        color: #17693a;
    }
    .mushaf-dark .qw.qw-verse-playing {
        background-color: rgba(100, 204, 197, 0.2);
        color: #7ee7dd;
    }
    .mushaf .ayah-end.ayah-end-playing { filter: drop-shadow(0 0 4px rgba(33, 136, 56, 0.5)); }
    .mushaf-dark .ayah-end.ayah-end-playing { filter: drop-shadow(0 0 4px rgba(100, 204, 197, 0.55)); }
    .verse-card.is-playing { box-shadow: 0 0 0 2px #218838, 0 10px 30px -10px rgba(33, 136, 56, 0.45); }

    /* Verse-end rosette — our ornament (not a glyph font), holds the verse number. */
    .mushaf .ayah-end {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2em;
        height: 2em;
        margin: 0 0.12em;
        vertical-align: middle;
        user-select: none;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cg fill='none' stroke='%23c9a227' stroke-width='1.1'%3E%3Ccircle cx='24' cy='24' r='15'/%3E%3Ccircle cx='24' cy='24' r='20' stroke-width='1' stroke-dasharray='0.6 2.6' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }
    .mushaf .ayah-end-num {
        font-family: 'Cairo', sans-serif;
        font-size: 0.46em;
        font-weight: 700;
        line-height: 1;
        color: #b08515;
    }
    .mushaf-dark .ayah-end-num { color: #e7c66a; }

    .mushaf-pageno {
        text-align: center;
        margin-top: 1.5rem;
    }
    .mushaf-pageno-badge {
        display: inline-block;
        min-width: 2.25rem;
        padding: 0.15rem 0.6rem;
        font-family: 'Cairo', sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        color: #9a7b1f;
        border: 1px solid rgba(201, 162, 39, 0.4);
        border-radius: 9999px;
    }
    .mushaf-dark .mushaf-pageno-badge { color: #e7c66a; border-color: rgba(201, 162, 39, 0.35); }
    .mushaf-page:not(:last-child) .mushaf-pageno {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(120, 120, 120, 0.16);
    }

    /* ---- Floating tools rail ----
       Frosted-glass panel over a 4-step elevation scale. The gold (#c9a227) is the
       same accent the ayah rosette uses, so the rail reads as part of the mushaf
       rather than generic chrome. All motion is gated behind prefers-reduced-motion. */
    .mushaf-rail {
        position: fixed;
        z-index: 45;
        --rail-ease: cubic-bezier(0.22, 1, 0.36, 1); /* spring-like ease-out */
        --rail-gold: #c9a227;
    }
    .mushaf-rail-inner {
        display: flex;
        gap: 0.25rem;
        padding: 0.4rem;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.7);
        /* Two layers: a soft top-light sheen over the frosted base. */
        background:
            linear-gradient(160deg, rgba(255, 255, 255, 0.78), rgba(255, 255, 255, 0.42)),
            rgba(248, 250, 252, 0.66);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.06),
            0 8px 20px rgba(15, 23, 42, 0.09),
            0 24px 48px rgba(15, 23, 42, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        backdrop-filter: blur(20px) saturate(180%);
    }
    .mushaf-rail-inner.mushaf-rail-dark {
        border-color: rgba(255, 255, 255, 0.09);
        background:
            linear-gradient(160deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)),
            rgba(15, 23, 42, 0.72);
        box-shadow:
            0 1px 2px rgba(0, 0, 0, 0.4),
            0 8px 20px rgba(0, 0, 0, 0.45),
            0 24px 48px rgba(0, 0, 0, 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.07);
    }
    .mushaf-rail-item { position: relative; display: flex; }
    .mushaf-rail-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        color: #475569;
        -webkit-tap-highlight-color: transparent;
        transition:
            background-color 0.22s var(--rail-ease),
            color 0.22s var(--rail-ease),
            box-shadow 0.22s var(--rail-ease),
            transform 0.16s var(--rail-ease);
    }
    .mushaf-rail-btn > svg { flex-shrink: 0; }
    .mushaf-rail-label {
        font-weight: 600;
        line-height: 1.15;
        white-space: nowrap;
    }
    .mushaf-rail-dark .mushaf-rail-btn { color: #cbd5e1; }

    .mushaf-rail-btn:hover {
        background: rgba(0, 86, 179, 0.09);
        color: #0056b3;
    }
    .mushaf-rail-dark .mushaf-rail-btn:hover { background: rgba(100, 204, 197, 0.13); color: #64CCC5; }

    /* Keyboard focus must be visible on its own, not only via the label chip. */
    .mushaf-rail-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.95), 0 0 0 4px #0056b3;
    }
    .mushaf-rail-dark .mushaf-rail-btn:focus-visible {
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.95), 0 0 0 4px #64CCC5;
    }

    /* Tactile press — scale only, so nothing around it reflows. */
    .mushaf-rail-btn:active { transform: scale(0.93); }

    /* Active tool: brand gradient lozenge lifted off the glass, edged in gold. */
    .mushaf-rail-btn.is-active {
        color: #fff;
        background: linear-gradient(135deg, #0056b3 0%, #14746b 55%, #218838 100%);
        box-shadow:
            0 2px 6px rgba(0, 86, 179, 0.32),
            0 6px 16px rgba(33, 136, 56, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.28),
            inset 0 0 0 1px rgba(201, 162, 39, 0.45);
    }
    .mushaf-rail-btn.is-active:hover { color: #fff; }

    /* Hairline that fades at both ends instead of a flat grey bar. */
    .mushaf-rail-divider {
        display: block;
        background: linear-gradient(to right, transparent, rgba(100, 116, 139, 0.34), transparent);
    }
    .mushaf-rail-dark .mushaf-rail-divider {
        background: linear-gradient(to right, transparent, rgba(148, 163, 184, 0.28), transparent);
    }

    /* Popovers anchored off each rail button */
    .mushaf-rail-pop {
        position: absolute;
        min-width: 11rem;
        padding: 0.65rem;
        border-radius: 0.9rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        background: #fff;
        box-shadow: 0 12px 34px rgba(0, 0, 0, 0.18);
        z-index: 46;
    }
    .mushaf-rail-pop.mushaf-pop-dark { background: #111827; border-color: rgba(255, 255, 255, 0.1); }
    .mushaf-rail-pop-wide { min-width: 14rem; }
    .mushaf-pop-title {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        margin-bottom: 0.4rem;
        color: #6b7280;
    }
    .mushaf-pop-dark .mushaf-pop-title { color: #9ca3af; }
    .mushaf-pop-opt {
        display: block;
        width: 100%;
        text-align: start;
        padding: 0.45rem 0.6rem;
        border-radius: 0.55rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .mushaf-pop-dark .mushaf-pop-opt { color: #d1d5db; }
    .mushaf-pop-opt:hover { background: rgba(0, 0, 0, 0.05); }
    .mushaf-pop-dark .mushaf-pop-opt:hover { background: rgba(255, 255, 255, 0.06); }
    .mushaf-pop-opt.is-on { color: #fff; background: linear-gradient(135deg, #0056b3, #218838); }
    .mushaf-step-btn {
        width: 2.25rem; height: 2.25rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 0.6rem; color: #374151;
        background: rgba(0, 0, 0, 0.05);
    }
    .mushaf-pop-dark .mushaf-step-btn { color: #e5e7eb; background: rgba(255, 255, 255, 0.08); }
    .mushaf-pop-select {
        width: 100%; height: 2.5rem; padding: 0 0.6rem;
        font-size: 0.85rem; border-radius: 0.6rem;
        border: 1px solid rgba(0, 0, 0, 0.15); background: #fff; color: #111827;
    }
    .mushaf-pop-dark .mushaf-pop-select { background: #1f2937; border-color: rgba(255,255,255,0.12); color: #f3f4f6; }

    /* Every action stacks its icon over an always-visible label, on both
       breakpoints — the only difference between them is how the rail is placed. */
    .mushaf-rail-btn {
        flex-direction: column;
        gap: 0.22rem;
        height: auto;
        min-width: 0;
        border-radius: 0.8rem;
    }
    /* No opacity dimming on inactive labels — at this size it would drop contrast
       below 4.5:1, and the active state is already carried by the gradient
       lozenge and the gold underline. */
    .mushaf-rail-label {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Gold underline marks the active tool — a second cue beyond the gradient,
       so the state is not carried by colour alone. */
    .mushaf-rail-btn.is-active::after {
        content: '';
        position: absolute;
        bottom: 0.22rem;
        left: 50%;
        transform: translateX(-50%);
        width: 1.1rem;
        height: 2px;
        border-radius: 2px;
        background: var(--rail-gold);
    }

    /* Desktop: vertical rail on the inline-end edge; popovers open toward content. */
    @media (min-width: 1024px) {
        .mushaf-rail { inset-inline-end: 1.25rem; top: 50%; transform: translateY(-50%); }
        .mushaf-rail-inner { flex-direction: column; align-items: stretch; border-radius: 1.25rem; }
        .mushaf-rail-item > .mushaf-rail-btn { width: 100%; }
        .mushaf-rail-btn { width: 4.6rem; padding: 0.55rem 0.3rem 0.5rem; }
        .mushaf-rail-label { font-size: 0.68rem; }
        .mushaf-rail-divider { width: auto; height: 1px; margin: 0.25rem 0.55rem; }
        .mushaf-rail-pop { inset-inline-end: calc(100% + 0.6rem); top: 0; }
    }
    /* Mobile: horizontal bar pinned bottom. Items flex-share the width so the bar
       never overflows narrow screens — the popovers are absolutely positioned
       inside the bar, so it must not scroll. */
    @media (max-width: 1023px) {
        .mushaf-rail { inset-inline-start: 0; inset-inline-end: 0; bottom: 1rem; display: flex; justify-content: center; padding-inline: 0.5rem; transition: bottom 0.2s ease; }
        .mushaf-rail-inner { flex-direction: row; align-items: stretch; width: 100%; max-width: 30rem; border-radius: 1.15rem; gap: 0.1rem; }
        .mushaf-rail-item { flex: 1 1 0; min-width: 0; }
        .mushaf-rail-inner > .mushaf-rail-btn { flex: 1 1 0; }
        .mushaf-rail-btn { width: 100%; padding: 0.5rem 0.15rem 0.45rem; }
        .mushaf-rail-label { font-size: 0.625rem; }
        /* No room for a separator once every action carries a label. */
        .mushaf-rail-divider { display: none; }
        .mushaf-rail-pop { bottom: calc(100% + 0.6rem); inset-inline-end: 0; }
        /* Lift the rail above the shared player bar when it's visible. */
        body.site-has-audio .mushaf-rail { bottom: 6.5rem; }
    }

    /* Motion is decoration here — the rail stays fully usable without it. */
    @media (prefers-reduced-motion: reduce) {
        .mushaf-rail,
        .mushaf-rail-inner,
        .mushaf-rail-btn { transition: none !important; }
        .mushaf-rail-btn:active { transform: none; }
    }
</style>
@endpush

@php
    $surahJson  = json_encode($surah,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $versesJson = json_encode($verses,  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $recitersJson = json_encode($reciters, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $tafsirResourcesJson = json_encode($tafsirResources, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $translationResourcesJson = json_encode($translationResources ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $isMakki = ($surah['type'] ?? '') === 'makki';
    // Default reciter: Al-Husary (murattal); first in the list as fallback.
    $defaultReciterId = collect($reciters)->firstWhere('slug', config('quran-audio.default_recording_slug'))['id']
        ?? ($reciters[0]['id'] ?? 0);
@endphp

@section('content')
{{-- Reading progress bar --}}
<div class="progress-bar" id="reading-progress-bar" style="transform: scaleX(0)"></div>

<div
    x-data="{
        surah: {{ $surahJson }},
        verses: {{ $versesJson }},
        reciters: {{ $recitersJson }},

        readingMode: localStorage.getItem('readingMode') || 'both',
        // A #v<n> deep link forces verse view from the start (no flash of reading
        // view); otherwise honour the saved preference. The forced switch is not
        // persisted, so the user's chosen view is kept for normal visits.
        viewMode: (/^#v\d+$/.test(window.location.hash) ? 'verses' : (localStorage.getItem('quranSurahView') || 'reading')),
        fontSize: parseInt(localStorage.getItem('fontSize')) || 28,
        showTransliteration: localStorage.getItem('showTransliteration') !== 'false',
        currentLocale: '{{ $currentLocale }}',
        defaultTranslationId: {{ $defaultTranslationId }}, // inlined edition (Saheeh International)
        translationLang: '{{ $defaultTranslationLang }}', // default translation language (English / Saheeh)

        {{-- Page default only; the shared audioPlayer store owns the live choice
             (a stored siteReciterId wins over this in configure()). --}}
        selectedReciterId: {{ $defaultReciterId }},

        // Surah info + lazy switcher modals (opened from the floating rail)
        infoOpen: false,
        switcherOpen: false,
        switcherUrl: '{{ route('public.quran.surahs.json') }}',
        surahUrlBase: '{{ url('/quran/surah') }}',
        switcherSurahs: [],
        switcherLoading: false,
        switcherError: false,
        switcherSearch: '',

        openTafsir: {},
        tafsirVerse: null,      // verse shown in the reading-mode tafsir modal (null = closed)
        verseMenu: null,        // verse tapped in reading mode (null = menu closed)
        verseMenuPos: { x: 0, y: 0 },
        tafsirResources: {{ $tafsirResourcesJson }},
        selectedTafsirId: parseInt(localStorage.getItem('selectedTafsirId')) || ({{ $tafsirResources[0]['id'] ?? 0 }}),
        tafsirCache: {},        // resourceId -> { verseId: text } (filled per verse, on demand)
        tafsirLoadingVerse: null, // verse id currently being fetched

        translationResources: {{ $translationResourcesJson }},
        selectedTranslationId: parseInt(localStorage.getItem('selectedTranslationId')) || {{ $defaultTranslationId }},
        translationCache: {},   // resourceId -> { verseId: text } (only languages/editions other than the inlined locale default)

        // Arabic locale shows Arabic-Indic digits (٠-٩) for verse/page numbers
        fmtNum(n) {
            if (this.currentLocale !== 'ar' || n === null || n === undefined) { return n; }
            return String(n).replace(/[0-9]/g, d => '٠١٢٣٤٥٦٧٨٩'[d]);
        },

        boot() {
            // Keep the persisted edition valid for the active language; otherwise fall
            // back to the default (Saheeh International).
            if (this.selectedTranslationId !== this.defaultTranslationId
                && !this.editionsForLang.some(r => r.id === this.selectedTranslationId)) {
                this.selectedTranslationId = this.defaultEditionIdFor(this.translationLang) || this.defaultTranslationId;
            }
            // Reset a stale tafsir selection (e.g. an edition that was removed) so a
            // persisted id never points at a missing resource.
            if (!this.tafsirResources.some(r => r.id === this.selectedTafsirId)) {
                this.selectedTafsirId = this.tafsirResources.length ? this.tafsirResources[0].id : 0;
                localStorage.setItem('selectedTafsirId', this.selectedTafsirId);
            }
            // Nothing eager: tafsir loads per verse on open; the locale-default
            // translation is inlined. Only a non-locale selection needs a fetch.
            this.loadTranslation(this.selectedTranslationId);

            // Hand this surah's reciters to the shared bottom player (rail Play
            // button + reciter picker route through the global audioPlayer store).
            this.$store.audioPlayer.configure({
                reciters: this.reciters,
                reciterId: this.selectedReciterId,
                // number + versesCount let the player chain per-verse audio for
                // reciters that have no whole-surah recording.
                surah: {
                    nameAr: this.surah.nameAr,
                    number: this.surah.number,
                    versesCount: this.surah.versesCount,
                    // Letters per ayah. Only a fallback now: the player prefers the
                    // real mp3quran timings and uses these to map an offset in a
                    // whole-surah recording onto an ayah for the reciters that have
                    // none, so switching audio modes keeps the listener's place.
                    weights: this.verses.map((v) => ((v.arabic || '').match(/[\u0621-\u064A\u0671]/g) || []).length),
                },
                // Lets the player's next/previous-surah buttons move the page.
                urlBase: this.surahUrlBase,
            });

            // Arrived here from the player's surah transport: keep playing. The
            // gesture was on the previous page, so a strict autoplay policy can
            // still refuse — play() swallows that.
            if (sessionStorage.getItem('siteAutoplaySurah')) {
                sessionStorage.removeItem('siteAutoplaySurah');
                this.$store.audioPlayer.playSurah();
            }

            // Deep link to a specific verse (#v65): the native jump fires before
            // Alpine renders the cards, so scroll after render + on later hash changes.
            this.$nextTick(() => this.scrollToHashVerse());
            window.addEventListener('hashchange', () => this.scrollToHashVerse());
        },

        // Open the surah switcher; fetch the 114-surah list once (lazy), cached for the session.
        openSwitcher() {
            this.switcherOpen = true;
            this.switcherSearch = '';
            if (this.switcherSurahs.length > 0 || this.switcherLoading) { return; }
            this.switcherLoading = true;
            this.switcherError = false;
            fetch(this.switcherUrl, { headers: { 'Accept': 'application/json' } })
                .then(r => { if (!r.ok) { throw new Error('http'); } return r.json(); })
                .then(d => { this.switcherSurahs = d.surahs || []; })
                .catch(() => { this.switcherError = true; })
                .finally(() => { this.switcherLoading = false; });
        },

        // Strip Arabic diacritics/tatweel + lowercase so search is diacritic-insensitive
        // (so a bare surah name matches a fully-vowelled one).
        _normalize(text) {
            return (text || '').toLowerCase().replace(/[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED\u0640]/g, '');
        },

        // Client-side filter over number / Arabic name / English name / transliteration.
        get filteredSurahs() {
            const q = this._normalize(this.switcherSearch.trim());
            if (!q) { return this.switcherSurahs; }
            return this.switcherSurahs.filter(s =>
                String(s.number).includes(q)
                || this._normalize(s.nameAr).includes(q)
                || this._normalize(s.nameEn).includes(q)
                || this._normalize(s.transliteration).includes(q)
            );
        },

        goToSurah(number) {
            window.location = this.surahUrlBase + '/' + number;
        },

        // Scroll the #v<n> target verse into view (switching to verse view if needed)
        // and briefly highlight it. Safe no-op when there is no verse hash.
        scrollToHashVerse() {
            const match = window.location.hash.match(/^#v(\d+)$/);
            if (!match) { return; }
            // The verse cards only exist in the verse view. Force it (without
            // persisting) so a deep link never gets stuck on the reading layout.
            if (this.viewMode !== 'verses') { this.viewMode = 'verses'; }
            this.$nextTick(() => {
                setTimeout(() => {
                    const el = document.getElementById('v' + match[1]);
                    if (!el) { return; }
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('verse-target');
                    setTimeout(() => el.classList.remove('verse-target'), 2400);
                }, 120);
            });
        },

        // Fetch one verse's tafsir for the selected edition, only when its accordion
        // is opened. Avoids shipping a whole-surah blob for verbose editions.
        async loadVerseTafsir(verse) {
            const id = this.selectedTafsirId;
            if (!id) { return; }
            if (this.tafsirCache[id] && (verse.id in this.tafsirCache[id])) { return; } // already fetched
            this.tafsirLoadingVerse = verse.id;
            let text = '';
            try {
                const res = await fetch(`/quran/verse/${verse.id}/tafsir/${id}`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) { text = (await res.json()).text || ''; }
            } catch (e) { /* keep empty on failure */ }
            // Reassign for Alpine reactivity (nested mutation isn't always tracked).
            this.tafsirCache[id] = Object.assign({}, this.tafsirCache[id], { [verse.id]: text });
            this.tafsirLoadingVerse = null;
        },

        tafsirLoaded(verse) {
            const edition = this.tafsirCache[this.selectedTafsirId];
            return !!edition && (verse.id in edition);
        },

        get editionsForLang() {
            return this.translationResources.filter(r => r.language === this.translationLang);
        },

        defaultEditionIdFor(lang) {
            const list = this.translationResources.filter(r => r.language === lang);
            const def = list.find(r => r.is_default) || list[0];
            return def ? def.id : 0;
        },

        get versesByPage() {
            const groups = [];
            for (const v of this.verses) {
                const last = groups[groups.length - 1];
                if (!last || last.page !== v.page) { groups.push({ page: v.page, items: [v] }); }
                else { last.items.push(v); }
            }
            return groups;
        },

        setReadingMode(m) {
            this.readingMode = m;
            localStorage.setItem('readingMode', m);
        },

        setViewMode(m) {
            this.viewMode = m;
            localStorage.setItem('quranSurahView', m);
            // Reading view has no per-verse anchors — drop a stale #v<n> deep link
            // so the URL stays clean and a refresh won't force verse view again.
            if (m === 'reading' && /^#v\d+$/.test(window.location.hash)) {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        },

        adjustFontSize(d) {
            this.fontSize = Math.max(16, Math.min(48, this.fontSize + d));
            localStorage.setItem('fontSize', this.fontSize);
        },

        setTranslationEdition(id) {
            this.selectedTranslationId = parseInt(id) || 0;
            localStorage.setItem('selectedTranslationId', this.selectedTranslationId);
            this.loadTranslation(this.selectedTranslationId);
        },

        currentTranslationName() {
            const r = this.translationResources.find(x => x.id === this.selectedTranslationId);
            return r ? r.name : '';
        },

        toggleTafsir(verse) {
            this.openTafsir[verse.id] = !this.openTafsir[verse.id];
            if (this.openTafsir[verse.id]) { this.loadVerseTafsir(verse); } // fetch on open
        },

        // Reading (mushaf) view has no per-verse accordion, so tapping an ayah —
        // any of its words or its end medallion — opens its tafsir in a modal.
        // Delegated from the card wrapper so the server-rendered mushaf markup
        // (and its own hover script) stay untouched.
        // Reading mode has no per-verse buttons, so a tap offers the two things
        // there is to do with an ayah: hear it, or read its tafsir.
        openVerseMenu(event, verse = null) {
            // Keep the click off document, or the menu's own click-outside
            // listener would fire on the very click that opened it.
            event.stopPropagation();
            if (!verse) {
                const el = event.target.closest('[data-verse]');
                if (!el) { return; }
                verse = this.verses.find((v) => v.number === parseInt(el.dataset.verse, 10));
            }
            if (!verse) { return; }

            const width = 216, height = 116;
            this.verseMenu = verse;
            this.verseMenuPos = {
                x: Math.min(Math.max(event.clientX - width / 2, 8), window.innerWidth - width - 8),
                y: event.clientY + height + 16 > window.innerHeight ? event.clientY - height - 8 : event.clientY + 8,
            };
        },

        listenFromVerse(verse) {
            this.verseMenu = null;
            this.$store.audioPlayer.playAyah(verse.number);
        },

        showTafsirFor(verse) {
            this.verseMenu = null;
            this.tafsirVerse = verse;
            this.loadVerseTafsir(verse);
        },

        currentTafsir(verse) {
            const edition = this.tafsirCache[this.selectedTafsirId];
            return (edition && edition[verse.id]) ? edition[verse.id] : '';
        },

        currentTafsirName() {
            const r = this.tafsirResources.find(x => x.id === this.selectedTafsirId);
            return r ? r.name : '';
        },

        setTafsirSource(id) {
            this.selectedTafsirId = parseInt(id);
            localStorage.setItem('selectedTafsirId', this.selectedTafsirId);
            // Re-fetch only the verses whose tafsir is currently open, for the new edition.
            this.verses.forEach(v => { if (this.openTafsir[v.id]) { this.loadVerseTafsir(v); } });
            if (this.tafsirVerse) { this.loadVerseTafsir(this.tafsirVerse); }
        },

        getTranslation(verse) {
            // 1) a lazily-loaded edition, once it arrives; 2) the inlined default
            // (Saheeh International); 3) Arabic chosen explicitly = the verse text.
            const edition = this.translationCache[this.selectedTranslationId];
            if (edition && edition[verse.id]) { return edition[verse.id]; }
            if (this.selectedTranslationId === this.defaultTranslationId) { return verse.translation || ''; }
            if (this.translationLang === 'ar') { return verse.arabic || ''; }
            return ''; // a non-default edition is still loading
        },

        async loadTranslation(id) {
            id = parseInt(id) || 0;
            // The default edition is inlined; Arabic (0) is the verse text — neither fetches.
            if (!id || id === this.defaultTranslationId || this.translationCache[id]) { return; }
            try {
                const res = await fetch(`/quran/surah/${this.surah.number}/translation/${id}`, { headers: { 'Accept': 'application/json' } });
                this.translationCache[id] = res.ok ? (await res.json()) : {};
            } catch (e) {
                this.translationCache[id] = {};
            }
        },

        copyVerse(verse) {
            const arabic = verse.arabic || '';
            const trans  = this.getTranslation(verse);
            const ref    = '(' + this.surah.nameAr + ' - {{ __('quran.public.verse_label') }} ' + verse.number + ')';
            navigator.clipboard.writeText(arabic + '\n' + trans + '\n' + ref)
                .then(() => {
                    if ($store.toast) { $store.toast.add('{{ __('quran.public.copied') }}', 'success'); }
                })
                .catch(() => {});
        },

        // Verse play buttons run through the shared bottom player, so a verse and
        // a surah can never recite over each other and the bar always shows what
        // is playing. The store starts its per-verse queue at this ayah.
        playVerse(verse) {
            this.$store.audioPlayer.playAyah(verse.number);
        },

        isVerseLoaded(verse) { return this.$store.audioPlayer.playingAyah === verse.number; },
        isVersePlaying(verse) { return this.isVerseLoaded(verse) && this.$store.audioPlayer.playing; },

        // Paint the ayah being recited and keep it in view. Reading mode marks
        // every word of the ayah (same shape as the hover highlight); verse mode
        // marks the card.
        syncPlayingVerse(number) {
            document.querySelectorAll('.mushaf .qw-verse-playing, .mushaf .ayah-end-playing')
                .forEach((el) => el.classList.remove('qw-verse-playing', 'ayah-end-playing'));
            if (!number) { return; }

            // Single quotes: this whole x-data is one double-quoted HTML attribute.
            const parts = document.querySelectorAll(`.mushaf [data-verse='${number}']`);
            parts.forEach((el) => el.classList.add(
                el.classList.contains('ayah-end') ? 'ayah-end-playing' : 'qw-verse-playing'
            ));

            const target = this.viewMode === 'verses' ? document.getElementById('v' + number) : parts[0];
            if (!target) { return; }
            // ponytail: scroll only when the ayah is off-screen; no tracking of
            // whether the reader has scrolled away — off-screen is the case that matters.
            const box = target.getBoundingClientRect();
            if (box.top > 80 && box.bottom < window.innerHeight - 120) { return; }
            target.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'center',
            });
        },


        init() {
            const updateBar = () => {
                const bar = document.getElementById('reading-progress-bar');
                if (!bar) { return; }
                const max = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const pct = max > 0 ? (window.scrollY / max) : 0;
                bar.style.transform = 'scaleX(' + pct + ')';
            };
            window.addEventListener('scroll', updateBar, { passive: true });
            this.$nextTick(() => {
                updateBar();
                if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            });
        }
    }"
    x-init="boot()"
    :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'"
    class="min-h-dvh font-['Cairo'] transition-colors duration-300"
>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
      :style="$store.audioPlayer.track ? 'padding-bottom: 8rem' : ''"
      x-effect="syncPlayingVerse($store.audioPlayer.playingAyah)">

    {{-- Breadcrumb --}}
    <x-public.quran.breadcrumb :surah="$surah" />

    <div class="max-w-4xl mx-auto">

        {{-- ============================================================ --}}
        {{-- MAIN CONTENT (single centred column — sidebar replaced by the   --}}
        {{-- floating tools rail + shared bottom player + surah modals)      --}}
        {{-- ============================================================ --}}
        <div>

            {{-- Surah Header --}}
            <x-public.quran.surah-header :surah="$surah" :isMakki="$isMakki" />

            {{-- ============================================================ --}}
            {{-- FLOATING TOOLS RAIL — always-reachable reading tools.            --}}
            {{-- ============================================================ --}}
            <x-public.quran.tools-rail :surah="$surah" :tafsirResources="$tafsirResources" :translationResources="$translationResources" :defaultTranslationLang="$defaultTranslationLang" :reciters="$reciters" />

            {{-- Pre-render skeleton: visible in static HTML, hidden the moment
                 Alpine boots (x-show="false") and the real verses render. --}}
            <x-public.quran.skeleton-loader />

            {{-- ============================================================ --}}
            {{-- VERSES --}}
            {{-- ============================================================ --}}
            <x-public.quran.verse-list />

            {{-- Mushaf reading (line-for-line, page-by-page — quran.com reading mode) --}}
            <x-public.quran.mushaf-view :mushaf="$mushaf" :surah="$surah" />

            {{-- Empty state --}}
            <x-public.quran.empty-state />

            {{-- ============================================================ --}}
            {{-- PREVIOUS / NEXT SURAH --}}
            {{-- ============================================================ --}}
            <x-public.quran.surah-navigation :prevSurah="$prevSurah" :prevSurahName="$prevSurahName" :nextSurah="$nextSurah" :nextSurahName="$nextSurahName" />

        </div>{{-- /main-content --}}


    </div>{{-- /grid --}}

</main>

{{-- ============================================================ --}}
{{-- VERSE ACTIONS (reading view — tapping an ayah opens this)       --}}
{{-- ============================================================ --}}
<div x-show="verseMenu" x-cloak x-transition.opacity
     @click.outside="verseMenu = null"
     @keydown.escape.window="verseMenu = null"
     @scroll.window="verseMenu = null"
     class="fixed rounded-xl border shadow-2xl py-1 overflow-hidden"
     {{-- one :style, not a static style plus a bound one: a bound string replaces the attribute --}}
     :style="`left: ${verseMenuPos.x}px; top: ${verseMenuPos.y}px; width: 216px; z-index: 65`"
     :class="$store.theme.dark ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'"
     role="menu">
    <p class="px-3 py-1.5 text-[11px] font-semibold"
       :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">
        {{ __('quran.public.verse_label') }} <span x-text="verseMenu ? fmtNum(verseMenu.number) : ''"></span>
    </p>
    <button type="button" role="menuitem" @click="listenFromVerse(verseMenu)"
            class="w-full text-start px-3 py-2.5 text-sm font-medium flex items-center gap-2.5 transition-colors"
            :class="$store.theme.dark ? 'text-gray-200 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100'">
        <svg class="w-4 h-4 flex-shrink-0 text-[#218838]" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
        {{ __('quran.public.listen_from_here') }}
    </button>
    <button type="button" role="menuitem" @click="showTafsirFor(verseMenu)"
            class="w-full text-start px-3 py-2.5 text-sm font-medium flex items-center gap-2.5 transition-colors"
            :class="$store.theme.dark ? 'text-gray-200 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100'">
        <svg class="w-4 h-4 flex-shrink-0 text-[#0056b3]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5S10 5 7 5H4v12h3c3 0 5 1.5 5 1.5m0-12S14 5 17 5h3v12h-3c-3 0-5 1.5-5 1.5m0-12v12"/></svg>
        {{ __('quran.public.show_tafsir') }}
    </button>
</div>

{{-- ============================================================ --}}
{{-- MODALS (Tafsir, Info, Switcher) --}}
{{-- ============================================================ --}}
<x-public.quran.modals :surah="$surah" :tafsirResources="$tafsirResources" :translationResources="$translationResources" :defaultTranslationLang="$defaultTranslationLang" />

</div>{{-- /x-data --}}

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
