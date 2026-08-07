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
        body.site-has-audio .mushaf-rail { bottom: 5.25rem; }
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
        viewMode: (/^#v\d+$/.test(window.location.hash) ? 'verses' : (localStorage.getItem('quranSurahView') || 'verses')),
        fontSize: parseInt(localStorage.getItem('fontSize')) || 28,
        showTransliteration: localStorage.getItem('showTransliteration') !== 'false',
        currentLocale: '{{ $currentLocale }}',
        defaultTranslationId: {{ $defaultTranslationId }}, // inlined edition (Saheeh International)
        translationLang: '{{ $defaultTranslationLang }}', // default translation language (English / Saheeh)

        audioPlaying: false,
        currentVerseId: null,
        currentAudio: null,
        selectedReciterId: parseInt(localStorage.getItem('selectedReciterId')) || ({{ count($reciters) > 0 ? $reciters[0]['id'] : 0 }}),

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
        tafsirResources: {{ $tafsirResourcesJson }},
        selectedTafsirId: parseInt(localStorage.getItem('selectedTafsirId')) || ({{ $tafsirResources[0]['id'] ?? 0 }}),
        tafsirCache: {},        // resourceId -> { verseId: text } (filled per verse, on demand)
        tafsirLoadingVerse: null, // verse id currently being fetched

        translationResources: {{ $translationResourcesJson }},
        selectedTranslationId: parseInt(localStorage.getItem('selectedTranslationId')) || {{ $defaultTranslationId }},
        translationCache: {},   // resourceId -> { verseId: text } (only languages/editions other than the inlined locale default)

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
                surah: { nameAr: this.surah.nameAr, number: this.surah.number, versesCount: this.surah.versesCount },
            });

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
        openTafsirFromMushaf(event) {
            const el = event.target.closest('[data-verse]');
            if (!el) { return; }
            const number = parseInt(el.dataset.verse, 10);
            const verse = this.verses.find(v => v.number === number);
            if (!verse) { return; }
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

        playVerse(verse) {
            // Clicking the same verse again toggles pause/resume.
            if (this.currentVerseId === verse.id && this.currentAudio) {
                if (this.audioPlaying) { this.currentAudio.pause(); this.audioPlaying = false; }
                else { this.currentAudio.play().then(() => { this.audioPlaying = true; }).catch(() => {}); }
                return;
            }
            if (this.currentAudio) { this.currentAudio.pause(); }
            const reciter = this.reciters.find(r => r.id === this.selectedReciterId);
            if (!reciter || !reciter.everyayah_folder) { return; }
            const surahPad = String(this.surah.number).padStart(3, '0');
            const versePad = String(verse.number).padStart(3, '0');
            const url = `https://everyayah.com/data/${reciter.everyayah_folder}/${surahPad}${versePad}.mp3`;
            this.currentAudio = new Audio(url);
            this.currentVerseId = verse.id;
            this.audioPlaying = true;
            this.currentAudio.play().catch(() => { this.audioPlaying = false; this.currentVerseId = null; });
            this.currentAudio.addEventListener('pause', () => { this.audioPlaying = false; });
            this.currentAudio.addEventListener('play', () => { this.audioPlaying = true; });
            this.currentAudio.addEventListener('ended', () => { this.audioPlaying = false; this.currentVerseId = null; });
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
    class="min-h-screen font-['Cairo'] transition-colors duration-300"
>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
      :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">

    {{-- Breadcrumb --}}
    <nav class="flex mb-8 text-sm animate-fadeIn" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
         aria-label="{{ __('site.breadcrumb') }}">
        <ol class="inline-flex items-center gap-2 flex-wrap">
            <li><a href="{{ route('public.home') }}" class="hover:text-[#64CCC5] transition-colors">{{ __('site.home') }}</a></li>
            <li class="flex items-center gap-2">
                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <a href="{{ route('public.quran') }}" class="hover:text-[#64CCC5] transition-colors">{{ __('site.quran') }}</a>
            </li>
            <li class="flex items-center gap-2">
                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-medium" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                    {{ $surah['nameAr'] }}
                </span>
            </li>
        </ol>
    </nav>

    <div class="max-w-4xl mx-auto">

        {{-- ============================================================ --}}
        {{-- MAIN CONTENT (single centred column — sidebar replaced by the   --}}
        {{-- floating tools rail + shared bottom player + surah modals)      --}}
        {{-- ============================================================ --}}
        <div>

            {{-- Surah Header --}}
            <div class="mb-8 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp"
                 :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                <div class="relative p-8 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-center">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                    </div>
                    <div class="relative">
                        <h1 class="text-5xl font-bold font-['Amiri'] mb-2">{{ $surah['nameAr'] }}</h1>
                        <p class="text-xl text-white/90 mb-4">
                            {{ $surah['nameEn'] }}@if($surah['transliteration']) — {{ $surah['transliteration'] }}@endif
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                                {{ $isMakki ? __('quran.public.makki') : __('quran.public.madani') }}
                            </span>
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                                {{ $surah['versesCount'] }} {{ __('quran.public.verse_unit') }}
                            </span>
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                                {{ __('quran.public.juz') }} {{ $surah['juz'] }}
                            </span>
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                                {{ __('quran.public.revelation_order') }}: {{ $surah['revelationOrder'] }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Bismillah (shown for all surahs except Al-Fatiha #1, where it is verse 1, and At-Tawbah #9, which has no Basmala).
                     Hidden in mushaf reading mode, where the mushaf renders its own Bismillah inside the page. --}}
                @if($surah['bismillahPre'] && $surah['number'] != 1 && $surah['number'] != 9)
                <div class="p-8 text-center" x-show="viewMode !== 'reading'" x-cloak :class="$store.theme.dark ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <p class="text-4xl font-['Amiri'] leading-loose" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ
                    </p>
                </div>
                @endif
            </div>

            {{-- ============================================================ --}}
            {{-- FLOATING TOOLS RAIL — always-reachable reading tools.            --}}
            {{-- Desktop: vertical pill docked to the inline-end edge.            --}}
            {{-- Mobile: horizontal bar pinned bottom-centre.                     --}}
            {{-- Tools = how you read (layout, display, font) + settings + ask.   --}}
            {{-- ============================================================ --}}
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
                    <div class="mushaf-rail-item" x-show="editionsForLang.length > 1 || tafsirResources.length > 1" x-cloak>
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

            {{-- ============================================================ --}}
            {{-- VERSES --}}
            {{-- ============================================================ --}}
            <div x-show="viewMode === 'verses'" class="space-y-6 mb-12">
                <template x-for="grp in versesByPage" :key="'p' + grp.page">
                <div class="space-y-6">
                    {{-- Mushaf page divider --}}
                    <div class="flex items-center gap-3 pt-2">
                        <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-600'">
                            {{ __('quran.nav.page') }} <span x-text="grp.page"></span>
                        </span>
                        <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    </div>
                    <template x-for="verse in grp.items" :key="verse.id">
                    <article
                        :id="'v' + verse.number"
                        style="scroll-margin-top: 6rem"
                        class="verse-card rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl"
                        :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
                    >
                        {{-- Verse header --}}
                        <div class="flex items-center justify-between p-4"
                             :class="$store.theme.dark ? 'bg-gray-800/50 border-b border-gray-700' : 'bg-gray-50 border-b border-gray-200'">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-sm" x-text="verse.number"></span>
                                </div>
                                <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                                    {{ __('quran.public.verse_label') }} <span x-text="verse.number"></span>
                                </span>
                                <span x-show="verse.has_sajdah"
                                    class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">
                                    {{ __('quran.public.sajdah') }}
                                </span>
                            </div>

                            {{-- Verse actions (always visible) --}}
                            <div class="flex items-center gap-2">
                                <button @click="playVerse(verse)"
                                    class="p-2 rounded-lg transition-colors"
                                    :class="currentVerseId === verse.id
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                        : ($store.theme.dark ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900')"
                                    :title="'{{ __('quran.public.listen') }}'"
                                >
                                    {{-- play, or pause while this verse is playing --}}
                                    <svg x-show="!(currentVerseId === verse.id && audioPlaying)" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="currentVerseId === verse.id && audioPlaying" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 7a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1zm6 0a1 1 0 011 1v4a1 1 0 11-2 0V8a1 1 0 011-1z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <button @click="copyVerse(verse)"
                                    class="p-2 rounded-lg transition-colors"
                                    :class="$store.theme.dark ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900'"
                                    :title="'{{ __('site.share') }}'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                                <button x-show="selectedTafsirId" @click="toggleTafsir(verse)"
                                    class="p-2 rounded-lg transition-colors"
                                    :class="openTafsir[verse.id]
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
                                <p class="font-['Amiri'] leading-loose text-start"
                                   :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                                   :style="`font-size: ${fontSize}px`"
                                   x-text="verse.arabic"
                                   dir="rtl"
                                ></p>
                            </div>

                            {{-- Transliteration --}}
                            <div x-show="showTransliteration && (readingMode === 'arabic' || readingMode === 'both') && verse.transliteration" class="mb-3">
                                <p class="text-sm italic" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="verse.transliteration"></p>
                            </div>

                            {{-- Translation (hidden when it would duplicate the Arabic verse,
                                 e.g. an Arabic "translation" of the Arabic text) --}}
                            <div x-show="(readingMode === 'translation' || readingMode === 'both') && getTranslation(verse) && getTranslation(verse) !== verse.arabic">
                                <p class="text-lg leading-relaxed" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'" x-text="getTranslation(verse)"></p>
                            </div>

                            {{-- Tafsir accordion (loaded per verse, only when opened) --}}
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
                                <div x-show="openTafsir[verse.id]"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 p-4 rounded-xl"
                                     :class="$store.theme.dark ? 'bg-gray-800/50' : 'bg-gray-50'">
                                    {{-- Loading --}}
                                    <p x-show="tafsirLoadingVerse === verse.id" class="text-sm animate-pulse"
                                       :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.tafsir_loading') }}</p>
                                    {{-- Text --}}
                                    <p x-show="tafsirLoadingVerse !== verse.id && currentTafsir(verse)"
                                       class="leading-relaxed font-['Amiri'] text-lg" dir="auto"
                                       :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'"
                                       x-text="currentTafsir(verse)"></p>
                                    {{-- Loaded but this edition has no tafsir for the verse --}}
                                    <p x-show="tafsirLoadingVerse !== verse.id && tafsirLoaded(verse) && !currentTafsir(verse)"
                                       class="text-sm italic" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_tafsir') }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                    </template>
                </div>
                </template>
            </div>

            {{-- Mushaf reading (line-for-line, page-by-page — quran.com reading mode) --}}
            @if(! empty($mushaf))
            <div x-show="viewMode === 'reading'" x-cloak class="mb-12">
                {{-- Tapping any ayah (word or end medallion) opens its tafsir. --}}
                <div class="verse-card mushaf-card rounded-2xl shadow-lg px-5 sm:px-8 py-8 mx-auto"
                     @click="openTafsirFromMushaf($event)"
                     :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    @include('public.quran._mushaf')
                </div>
            </div>
            @else
            {{-- Mushaf layout not imported for this surah — fall back to the verse flow --}}
            <div x-show="viewMode === 'reading' && verses.length > 0" x-cloak class="space-y-6 mb-12">
                <template x-for="grp in versesByPage" :key="'rp' + grp.page">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold" :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-200 text-gray-600'">
                            {{ __('quran.nav.page') }} <span x-text="grp.page"></span>
                        </span>
                        <div class="flex-1 h-px" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    </div>
                    <div class="verse-card rounded-2xl shadow-lg p-6 sm:p-8" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                        <p class="font-['Amiri'] leading-loose text-start" dir="rtl"
                           :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                           :style="`font-size: ${fontSize}px`"><template x-for="verse in grp.items" :key="verse.id"><span class="cursor-pointer" @click="tafsirVerse = verse; loadVerseTafsir(verse)"><span x-text="verse.arabic"></span><span class="inline-flex items-center justify-center align-middle mx-1 w-7 h-7 text-xs font-sans font-bold rounded-full" :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'" x-text="verse.number"></span> </span></template></p>
                    </div>
                </div>
                </template>
            </div>
            @endif

            {{-- Empty state --}}
            <div x-show="verses.length === 0" class="text-center py-16">
                <i data-lucide="book-x" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
                <p class="text-xl" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('quran.empty.no_verses') }}
                </p>
            </div>

            {{-- ============================================================ --}}
            {{-- PREVIOUS / NEXT SURAH --}}
            {{-- ============================================================ --}}
            <div class="flex items-center justify-between gap-4 py-8">
                @if($prevSurah)
                <a href="{{ route('public.quran.surah', $prevSurah) }}"
                   class="flex items-center gap-2.5 px-5 py-3 rounded-xl max-w-[48%] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-[1.02] transition-all"
                   aria-label="{{ __('quran.public.previous_surah') }}: {{ $prevSurahName }}"
                   title="{{ __('quran.public.previous_surah') }}: {{ $prevSurahName }}">
                    <svg class="w-5 h-5 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="font-['Amiri'] text-lg truncate">{{ $prevSurahName }}</span>
                </a>
                @else <span></span> @endif

                @if($nextSurah)
                <a href="{{ route('public.quran.surah', $nextSurah) }}"
                   class="flex items-center gap-2.5 px-5 py-3 rounded-xl max-w-[48%] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-[1.02] transition-all"
                   aria-label="{{ __('quran.public.next_surah') }}: {{ $nextSurahName }}"
                   title="{{ __('quran.public.next_surah') }}: {{ $nextSurahName }}">
                    <span class="font-['Amiri'] text-lg truncate">{{ $nextSurahName }}</span>
                    <svg class="w-5 h-5 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
            </div>

        </div>{{-- /main-content --}}


    </div>{{-- /grid --}}

</main>

{{-- ============================================================ --}}
{{-- VERSE TAFSIR MODAL (reading view — tapping an ayah opens this)  --}}
{{-- ============================================================ --}}
<div x-show="tafsirVerse" x-cloak x-transition.opacity
     @keydown.escape.window="tafsirVerse = null"
     class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 70;">
    <div class="absolute inset-0 bg-black/50" @click="tafsirVerse = null"></div>
    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl flex flex-col"
         style="max-height: 80vh;" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
         role="dialog" aria-modal="true">
        <div class="p-5 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white relative flex-shrink-0">
            <button @click="tafsirVerse = null" type="button" class="absolute top-3 end-3 p-1.5 rounded-full hover:bg-white/20 transition-colors" aria-label="{{ __('quran.public.close') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="text-xs text-white/80">{{ $surah['nameAr'] }} · <span x-text="tafsirVerse ? tafsirVerse.verse_key : ''"></span></p>
            <p class="font-['Amiri'] text-2xl leading-relaxed mt-2 pe-8" dir="rtl" x-text="tafsirVerse ? tafsirVerse.arabic : ''"></p>
        </div>
        <div class="p-5 overflow-y-auto">
            <p class="mushaf-pop-title mb-2" x-text="currentTafsirName()"></p>
            {{-- Loading --}}
            <p x-show="tafsirVerse && tafsirLoadingVerse === tafsirVerse.id" class="text-sm animate-pulse"
               :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.tafsir_loading') }}</p>
            {{-- Text --}}
            <p x-show="tafsirVerse && tafsirLoadingVerse !== tafsirVerse.id && currentTafsir(tafsirVerse)"
               class="leading-relaxed font-['Amiri'] text-lg" dir="auto"
               :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'"
               x-text="tafsirVerse ? currentTafsir(tafsirVerse) : ''"></p>
            {{-- Loaded but this edition has no tafsir for the verse --}}
            <p x-show="tafsirVerse && tafsirLoadingVerse !== tafsirVerse.id && tafsirLoaded(tafsirVerse) && !currentTafsir(tafsirVerse)"
               class="text-sm italic" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_tafsir') }}</p>
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
    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl flex flex-col"
         style="max-height: 80vh;" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
         role="dialog" aria-modal="true">
        <div class="p-5 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white relative flex-shrink-0">
            <button @click="infoOpen = false" type="button" class="absolute top-3 end-3 p-1.5 rounded-full hover:bg-white/20 transition-colors" aria-label="{{ __('quran.public.pause') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <p class="font-['Amiri'] text-3xl leading-tight">{{ $surah['nameAr'] }}</p>
            <p class="text-sm text-white/80 mt-1">{{ $surah['nameEn'] }}</p>
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ $surah['type'] === 'makki' ? __('quran.public.makki') : __('quran.public.madani') }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ $surah['versesCount'] }} {{ __('quran.public.verse_count') }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ __('quran.public.juz') }} {{ $surah['juz'] }}</span>
                <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-xs font-semibold">{{ __('quran.public.revelation_order') }} {{ $surah['revelationOrder'] }}</span>
            </div>
        </div>
        <div class="p-5 overflow-y-auto" :class="$store.theme.dark ? 'text-gray-200' : 'text-gray-700'">
            @if(! empty($surah['info']))
                <p class="leading-relaxed text-start" style="word-break: break-word;">{{ $surah['info'] }}</p>
            @else
                <p class="text-sm text-center py-6" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ $surah['nameEn'] }} · {{ $surah['versesCount'] }} {{ __('quran.public.verse_count') }}</p>
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
    <div class="relative w-full max-w-md rounded-2xl shadow-2xl flex flex-col overflow-hidden"
         style="max-height: 80vh;" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'"
         role="dialog" aria-modal="true">
        {{-- Header + search --}}
        <div class="p-4 border-b flex-shrink-0" :class="$store.theme.dark ? 'border-gray-800' : 'border-gray-200'">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('quran.public.switch_surah') }}</h2>
                <button @click="switcherOpen = false" type="button" class="p-1.5 rounded-full transition-colors"
                    :class="$store.theme.dark ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-500'" aria-label="{{ __('quran.public.pause') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="relative">
                <svg class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3 pointer-events-none" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4-4"/></svg>
                <input type="text" x-model="switcherSearch" placeholder="{{ __('quran.public.search_surahs') }}"
                    class="w-full h-10 ps-9 pe-3 text-base sm:text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3]/30"
                    :class="$store.theme.dark ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900'">
            </div>
        </div>
        {{-- List --}}
        <div class="flex-1 overflow-y-auto p-2">
            {{-- Loading --}}
            <div x-show="switcherLoading" class="py-10 text-center">
                <svg class="w-6 h-6 mx-auto animate-spin text-[#0056b3]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <p class="text-sm mt-2" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.surahs_loading') }}</p>
            </div>
            {{-- Error --}}
            <div x-show="switcherError" x-cloak class="py-10 text-center">
                <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.surahs_load_error') }}</p>
                <button @click="switcherSurahs = []; openSwitcher()" type="button" class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white" aria-label="{{ __('quran.public.surahs_loading') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0111-3l3 3M19 15a7 7 0 01-11 3l-3-3"/></svg>
                </button>
            </div>
            {{-- Rows --}}
            <template x-for="s in filteredSurahs" :key="s.number">
                <button type="button" @click="goToSurah(s.number)"
                    class="w-full flex items-center gap-3 p-2.5 rounded-xl transition-colors text-start"
                    :class="s.number === {{ $surah['number'] }}
                        ? ($store.theme.dark ? 'bg-gray-800 ring-1 ring-[#64CCC5]/40' : 'bg-[#0056b3]/5 ring-1 ring-[#0056b3]/20')
                        : ($store.theme.dark ? 'hover:bg-gray-800' : 'hover:bg-gray-100')">
                    <span class="w-9 h-9 flex-shrink-0 rounded-lg flex items-center justify-center text-sm font-bold tabular-nums"
                          :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'" x-text="s.number"></span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-['Amiri'] text-lg truncate" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="s.nameAr"></span>
                        <span class="block text-xs truncate" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                            <span x-text="s.nameEn"></span> · <span x-text="s.verses"></span> {{ __('quran.public.verse_count') }}
                        </span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0"
                          :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'"
                          x-text="s.type === 'makki' ? '{{ __('quran.public.makki') }}' : '{{ __('quran.public.madani') }}'"></span>
                </button>
            </template>
            {{-- Empty --}}
            <p x-show="!switcherLoading && !switcherError && filteredSurahs.length === 0" x-cloak
               class="py-10 text-center text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.public.no_surahs_found') }}</p>
        </div>
    </div>
</div>

</div>{{-- /x-data --}}

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
