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
    /* Tajweed colouring (QF <tajweed class> markup, sanitised server-side). */
    .tajweed-text tajweed.ham_wasl, .tajweed-text tajweed.laam_shamsiyah, .tajweed-text tajweed.slnt { color: #aaaaaa; }
    .tajweed-text tajweed.madda_normal { color: #537fff; }
    .tajweed-text tajweed.madda_permissible { color: #4050ff; }
    .tajweed-text tajweed.madda_necessary { color: #000ebc; }
    .tajweed-text tajweed.madda_obligatory { color: #2144c1; }
    .tajweed-text tajweed.qalaqah { color: #dd0008; }
    .tajweed-text tajweed.ikhafa_shafawi { color: #d500b7; }
    .tajweed-text tajweed.ikhafa { color: #9400a8; }
    .tajweed-text tajweed.idgham_shafawi { color: #58b800; }
    .tajweed-text tajweed.iqlab { color: #26bffd; }
    .tajweed-text tajweed.idgham_ghunnah { color: #169777; }
    .tajweed-text tajweed.idgham_wo_ghunnah { color: #169200; }
    .tajweed-text tajweed.idgham_mutajanisayn, .tajweed-text tajweed.idgham_mutaqaribayn { color: #a1a1a1; }
    .tajweed-text tajweed.ghunnah { color: #ff7e1e; }
    /* Word-by-word: each word is a focusable chip with its gloss underneath. */
    .wbw-word { display: inline-flex; flex-direction: column; align-items: center; margin: 0 0.15em 0.6em; padding: 0.1em 0.25em; border-radius: 0.5rem; cursor: pointer; vertical-align: top; }
    .wbw-word:hover, .wbw-word:focus-visible { background: rgba(100, 204, 197, 0.16); outline: none; }
    .wbw-gloss { font-family: 'Cairo', sans-serif; font-size: 0.72rem; line-height: 1.3; direction: ltr; opacity: 0.8; max-width: 9rem; text-align: center; }


    /* Brief highlight for a deep-linked verse (#v65). */
    .verse-target { animation: verseTargetPulse 2.4s ease-out 1; border-radius: 1rem; }
    @keyframes verseTargetPulse {
        0% { box-shadow: 0 0 0 0 rgba(100,204,197,0.0); }
        15% { box-shadow: 0 0 0 4px rgba(100,204,197,0.55); }
        100% { box-shadow: 0 0 0 4px rgba(100,204,197,0.0); }
    }
    @media (prefers-reduced-motion: reduce) { .verse-target { animation: none; } }

    /* The whole verse is tappable; which word was hit is worked out from the caret,
       and the word that was hit is painted with the Custom Highlight API — no wrapper
       element, so it works inside the tajweed markup too. */
    .qw-tappable { cursor: pointer; }
    ::highlight(quran-word) { background-color: rgba(33, 136, 56, 0.18); color: #17693a; }
    .dark ::highlight(quran-word) { background-color: rgba(100, 204, 197, 0.22); color: #7ee7dd; }

    /* The tajweed script carries its own ayah number as <span class=end>; give it the
       same rosette the mushaf view draws instead of leaving a bare digit in the line. */
    .tajweed-text span.end {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2em;
        height: 2em;
        margin: 0 0.12em;
        vertical-align: middle;
        user-select: none;
        font-family: 'Cairo', sans-serif;
        font-size: 0.46em;
        font-weight: 700;
        line-height: 1;
        color: #b08515;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cg fill='none' stroke='%23c9a227' stroke-width='1.1'%3E%3Ccircle cx='24' cy='24' r='15'/%3E%3Ccircle cx='24' cy='24' r='20' stroke-width='1' stroke-dasharray='0.6 2.6' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
    }
    .dark .tajweed-text span.end { color: #e7c66a; }

    /* ---- Quran text fonts ----
       IndoPak and the two Nastaleeq scripts carry their waqf marks in the Private
       Use Area (U+E01E, U+F500, U+F501, U+F650 …), so a general Arabic face has no
       glyph for them and the browser draws a tofu box. Only Quran Foundation's own
       IndoPak Nastaleeq font has them, so the script picks the face. Both files are
       local (quran:qf:download-assets --fonts=hafs,indopak); `swap` keeps the verse
       readable in the fallback while the face loads. */
    @font-face {
        font-family: 'QuranHafs';
        src: url('/fonts/quran/hafs/UthmanicHafs1Ver18.woff2') format('woff2');
        font-display: swap;
    }
    @font-face {
        font-family: 'QuranIndoPak';
        src: url('/fonts/quran/indopak/indopak-nastaleeq-waqf-lazim-v4.2.1.woff2') format('woff2');
        font-display: swap;
    }
    /* Every element that renders verse text reads this one variable, so the script
       switcher only has to set it in one place. */
    .quran-text, .mushaf { font-family: var(--quran-font, 'Amiri', serif); }
    [data-quran-script='indopak'],
    [data-quran-script='indopak_nastaleeq'],
    [data-quran-script='qpc_nastaleeq'] { --quran-font: 'QuranIndoPak', 'Amiri', serif; }
    /* Nastaleeq sits lower and needs the room, or the marks clip into the next line. */
    [data-quran-script='indopak_nastaleeq'] .quran-text,
    [data-quran-script='qpc_nastaleeq'] .quran-text { line-height: 2.4; }

    @include('partials.quran.mushaf-styles')

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
    $scriptsJson = json_encode($scripts ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    // Default reciter (the admin-marked default, else the first) comes from the controller.
    $defaultReciterId = (int) ($defaultReciterId ?? 0);
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
        {{-- Playlist hand-off (?reciter=): wins over the stored choice for this visit only. --}}
        forcedReciterId: {{ $forcedReciterId ?? 'null' }},

        // Surah info + lazy switcher modals (opened from the floating rail)
        infoOpen: false,
        switcherOpen: false,
        switcherUrl: '{{ route('public.quran.surahs.json') }}',
        surahUrlBase: '{{ url('/quran/surah') }}',
        switcherSurahs: [],
        switcherLoading: false,
        switcherError: false,
        switcherSearch: '',

        tafsirVerse: null,      // verse shown in the reading-mode tafsir modal (null = closed)
        verseMenu: null,        // verse tapped in reading mode (null = menu closed)
        verseMenuPos: { x: 0, y: 0 },
        tafsirResources: {{ $tafsirResourcesJson }},
        // The reader's own pick wins; otherwise the edition the admin marked default
        // (التفسير الميسر), and only then whatever sorts first.
        selectedTafsirId: parseInt(localStorage.getItem('selectedTafsirId'))
            || ({{ collect($tafsirResources)->firstWhere('is_default', true)['id'] ?? ($tafsirResources[0]['id'] ?? 0) }}),
        verseTab: 'translation',   // which section of the verse modal is open
        verseWords: {},            // verseId -> words, fetched when the tab is opened
        wordsLoadingVerse: null,
        chapterInfos: null,        // [{language, text, source, resource}] once fetched
        chapterInfoLang: null,     // which language tab is showing
        chapterInfoLoading: false,
        tafsirCache: {},        // resourceId -> { verseId: text } (filled per verse, on demand)
        tafsirLoadingVerse: null, // verse id currently being fetched

        translationResources: {{ $translationResourcesJson }},
        scripts: {{ $scriptsJson }},
        selectedScript: @js($selectedScript ?? 'uthmani'),
        wordByWord: {{ ! empty($wordByWord) ? 'true' : 'false' }},
        glyphMode: localStorage.getItem('quran:glyphs') === '1',
        setGlyphMode(on) {
            this.glyphMode = on;
            try { localStorage.setItem('quran:glyphs', on ? '1' : '0'); } catch (e) {}
            if (on && window.quranLoadGlyphFonts) { window.quranLoadGlyphFonts(); }
        },
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
                const fallback = this.tafsirResources.find(r => r.is_default) || this.tafsirResources[0];
                this.selectedTafsirId = fallback ? fallback.id : 0;
                localStorage.setItem('selectedTafsirId', this.selectedTafsirId);
            }
            // Nothing eager: tafsir loads per verse on open; the locale-default
            // translation is inlined. Only a non-locale selection needs a fetch.
            this.loadTranslation(this.selectedTranslationId);

            // Hand this surah's reciters to the shared bottom player (rail Play
            // button + reciter picker route through the global audioPlayer store).
            this.$store.audioPlayer.configure({
                reciters: this.reciters,
                reciterId: this.forcedReciterId ?? this.selectedReciterId,
                force: this.forcedReciterId !== null,
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

            try { localStorage.setItem('quran:last', JSON.stringify({ number: this.surah.number, name: this.surah.nameAr })); } catch (e) {}
            if (this.glyphMode) { this.$nextTick(() => window.quranLoadGlyphFonts && window.quranLoadGlyphFonts()); }

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

        // Every active edition is selectable (any language); editions load lazily.
        get editionsForLang() {
            return this.translationResources;
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

        // Display mode describes a verse card. Reading view is one continuous
        // mushaf page with no translation in it, so asking for a translation there
        // used to change nothing at all — move to the view that can show it.
        setReadingMode(m) {
            this.readingMode = m;
            localStorage.setItem('readingMode', m);
            if (m !== 'arabic' && this.viewMode === 'reading') { this.setViewMode('verses'); }
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
            // Picking a translation is a request to read it, and only the verse view
            // shows one — with the display mode set so it is actually on screen.
            if (this.viewMode === 'reading') { this.setViewMode('verses'); }
            if (this.readingMode === 'arabic') { this.setReadingMode('both'); }
        },

        currentTranslationName() {
            const r = this.translationResources.find(x => x.id === this.selectedTranslationId);
            return r ? r.name : '';
        },

        // Switching to tafsir is what triggers its fetch: translation and words are
        // already on the page, so nothing is requested until the reader asks for it.
        setVerseTab(tab) {
            this.verseTab = tab;
            if (!this.tafsirVerse) { return; }
            if (tab === 'tafsir') { this.loadVerseTafsir(this.tafsirVerse); }
            if (tab === 'words') { this.loadVerseWords(this.tafsirVerse); }
        },

        // Words for one ayah. The reading view ships no word data, so the modal asks
        // for it the first time the tab is opened and keeps it for the session.
        async loadVerseWords(verse) {
            if (this.verseWords[verse.id]) { return; }
            this.wordsLoadingVerse = verse.id;
            try {
                const res = await fetch(`/quran/verse/${verse.id}/words`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const words = (await res.json()).words || [];
                    this.verseWords = Object.assign({}, this.verseWords, { [verse.id]: words });
                }
            } catch (e) { /* leave it unset so a retry can refetch */ }
            this.wordsLoadingVerse = null;
        },

        // Words already on the page (word-by-word mode) beat a second request.
        wordsFor(verse) {
            if (!verse) { return []; }

            return (verse.words && verse.words.length) ? verse.words : (this.verseWords[verse.id] || []);
        },

        // Chapter introduction, in every language it exists in. Fetched once per page.
        async loadChapterInfos() {
            if (this.chapterInfos !== null || this.chapterInfoLoading) { return; }
            this.chapterInfoLoading = true;
            try {
                const res = await fetch(`/quran/surah/${this.surah.number}/info`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    this.chapterInfos = (await res.json()).infos || [];
                    // Start on the site's language when it has an introduction, else the first.
                    const here = document.documentElement.lang || 'ar';
                    this.chapterInfoLang = (this.chapterInfos.find(i => i.language === here)
                        || this.chapterInfos[0] || {}).language || null;
                }
            } catch (e) { this.chapterInfos = []; }
            this.chapterInfoLoading = false;
        },

        currentChapterInfo() {
            return (this.chapterInfos || []).find(i => i.language === this.chapterInfoLang) || null;
        },

        // Which word was tapped, worked out from where the caret lands rather than
        // from a span around every word: al-Baqarah is ~6,600 words, and wrapping each
        // one cost that many DOM nodes and Alpine effects. It also works on the tajweed
        // script, whose markup wraps letters inside and across words, so no span-per-word
        // split could survive it.
        tapWordAt(event, verse) {
            const root = event.currentTarget;
            const caret = document.caretPositionFromPoint
                ? document.caretPositionFromPoint(event.clientX, event.clientY)
                : null;
            const legacy = !caret && document.caretRangeFromPoint
                ? document.caretRangeFromPoint(event.clientX, event.clientY)
                : null;
            const node = caret ? caret.offsetNode : (legacy ? legacy.startContainer : null);
            const offset = caret ? caret.offset : (legacy ? legacy.startOffset : 0);
            if (!node || !root.contains(node)) { return; }
            // The ayah-number rosette sits in the same text flow; it is a marker, not a
            // word, so a tap on it is not a tap on the word beside it.
            const el = node.nodeType === 1 ? node : node.parentElement;
            if (el && el.closest('span.end')) { return; }

            // Where the caret landed, as an offset into the whole verse's text.
            const head = document.createRange();
            head.setStart(root, 0);
            head.setEnd(node, offset);
            const text = root.textContent || '';
            let at = head.toString().length;

            // Grow to the token around it. A tap in the gap between two words belongs
            // to the one that follows it in reading order.
            if (at >= text.length || /\s/.test(text[at] || '')) { at = Math.max(0, at - 1); }
            let start = at, end = at;
            while (start > 0 && !/\s/.test(text[start - 1])) { start--; }
            while (end < text.length && !/\s/.test(text[end])) { end++; }
            if (start === end) { return; }

            this.highlightRange(root, start, end);

            return this.tapWord(verse, text.slice(0, start).split(/\s+/).filter(Boolean).length);
        },

        // Paint the tapped word without wrapping it: the tajweed script's markup starts
        // and ends inside words, so a wrapper element cannot always be inserted. The
        // Custom Highlight API paints a Range instead, and is simply skipped where the
        // browser has no support.
        highlightRange(root, start, end) {
            if (typeof Highlight === 'undefined' || !CSS.highlights) { return; }

            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
            const range = document.createRange();
            let seen = 0, node;
            while ((node = walker.nextNode())) {
                const len = node.textContent.length;
                if (seen <= start && start < seen + len) { range.setStart(node, start - seen); }
                if (seen < end && end <= seen + len) { range.setEnd(node, end - seen); break; }
                seen += len;
            }
            CSS.highlights.set('quran-word', new Highlight(range));
        },

        // The word behind the i-th whitespace token of the verse text. Not the i-th
        // word: a corpus entry can hold more than one token (15:1's first word is the
        // letters plus a pause mark, with a space between them), so counting tokens
        // straight into the word list drifts by one from there on. Build the
        // token-to-word map from the words themselves, and clamp, so a tap past the
        // end of a short line still lands on its last word.
        // NB: no double quotes anywhere in this attribute — it is the x-data value,
        // and one quote ends it and takes the rest of the component with it.
        wordAt(verse, index) {
            const words = this.wordsFor(verse).filter((w) => w.type === 'word');
            if (!words.length) { return null; }

            const map = [];
            words.forEach((w, i) => {
                const tokens = String(w.text || '').split(/\s+/).filter(Boolean).length || 1;
                for (let k = 0; k < tokens; k++) { map.push(i); }
            });

            return words[map[Math.min(Math.max(index, 0), map.length - 1)]] || null;
        },

        async tapWord(verse, index) {
            await this.loadVerseWords(verse);
            const word = this.wordAt(verse, index);
            if (word) { this.playWord(word); }
        },

        // One word, read aloud. The URL is the per-word recording that came with the
        // page, so this never waits on the reciter the player happens to be using.
        playWord(word) {
            if (!word?.audio_url) { return; }
            this._wordAudio?.pause();
            this._wordAudio = new Audio(word.audio_url);
            this._wordAudio.play().catch(() => {});
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

        // The one way into the verse modal, from the card's icon and the reading
        // view's menu alike. The tab does the fetching, so opening on `tafsir` loads
        // the tafsir and opening on `translation` loads nothing.
        openVerseModal(verse, tab = 'translation') {
            this.verseMenu = null;
            this.tafsirVerse = verse;
            this.setVerseTab(tab);
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
            // Only the open modal needs the new edition; everything else is fetched
            // when it is next opened.
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

        // Script and word-by-word change the server payload, so they reload the page
        // with the choice in the query string (and remember it for the next surah).
        reloadWith(key, value) {
            const url = new URL(window.location.href);
            if (value === null || value === '' || value === false) { url.searchParams.delete(key); } else { url.searchParams.set(key, value); }
            try { localStorage.setItem('quran:' + key, value === null || value === false ? '' : String(value)); } catch (e) {}
            window.location.href = url.toString();
        },
        setScript(value) { this.reloadWith('script', value === 'uthmani' ? null : value); },
        toggleWordByWord() { this.reloadWith('wbw', this.wordByWord ? null : '1'); },

        // Footnotes of the edition on screen, when that edition was inlined.
        footNotes(verse) {
            const row = (verse.translations || {})[this.selectedTranslationId];
            return row && row.foot_notes ? Object.entries(row.foot_notes) : [];
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

            // Scrolling is what the follow toggle controls: highlighting always
            // happens, but only a reader who asked to be carried along gets moved.
            if (!this.$store.audioPlayer.follow) { return; }

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
    :data-quran-script="selectedScript"
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
    <button type="button" role="menuitem" @click="openVerseModal(verseMenu, 'tafsir')"
            class="w-full text-start px-3 py-2.5 text-sm font-medium flex items-center gap-2.5 transition-colors"
            :class="$store.theme.dark ? 'text-gray-200 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100'">
        <svg class="w-4 h-4 flex-shrink-0 text-[#0056b3]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.5S10 5 7 5H4v12h3c3 0 5 1.5 5 1.5m0-12S14 5 17 5h3v12h-3c-3 0-5 1.5-5 1.5m0-12v12"/></svg>
        {{ __('quran.public.show_tafsir') }}
    </button>
</div>

{{-- ============================================================ --}}
{{-- MODALS (Tafsir, Info, Switcher) --}}
{{-- ============================================================ --}}
<x-public.quran.modals :surah="$surah" :chapterInfo="$chapterInfo ?? null" :tafsirResources="$tafsirResources" :translationResources="$translationResources" :defaultTranslationLang="$defaultTranslationLang" />

</div>{{-- /x-data --}}

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
