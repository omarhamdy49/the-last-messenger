@extends('layouts.public')

@section('title', __('quran.public.surah_page_title', ['name' => $surah['nameAr'], 'number' => $surah['number']]))
@section('meta_description', __('quran.public.surah_page_description', ['name' => $surah['nameAr'], 'nameEn' => $surah['nameEn'], 'verses' => $surah['versesCount']]))

@push('head')
<style>
    /* Page-scoped CSS only for what the design system has no shape for: the
       printed-mushaf line layout, the ayah rosette ornament, and the floating
       tools rail. Every colour comes from an sx token. */

    .mushaf,
    .mushaf-rail {
        /* The rosette gold is an ornament, not a brand colour — it is the one
           value here that has no token, so it is named once and reused. */
        --mushaf-gold: #c9a227;
        --mushaf-halo: color-mix(in srgb, var(--sx-teal) 55%, transparent);
    }
    .dark .mushaf,
    .dark .mushaf-rail { --mushaf-gold: #e7c66a; }

    /* Brief highlight for a deep-linked verse (#v65). */
    .verse-target { animation: verseTargetPulse 2.4s ease-out 1; border-radius: 1rem; }
    @keyframes verseTargetPulse {
        0% { box-shadow: 0 0 0 0 transparent; }
        15% { box-shadow: 0 0 0 4px var(--mushaf-halo); }
        100% { box-shadow: 0 0 0 4px transparent; }
    }
    @media (prefers-reduced-motion: reduce) { .verse-target { animation: none; } }

    /* ---- Mushaf reading view (line-for-line, like a printed mushaf) ---- */
    /* Width cap lives here (not a Tailwind arbitrary class) so it works without a
       Vite rebuild and keeps lines narrow enough to fill via fit-to-width. */
    .mushaf-card { max-width: 40rem; }
    .mushaf {
        font-family: 'Amiri', serif;
        color: var(--sx-ink);
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
        transition: background-color 0.18s var(--sx-ease), color 0.18s var(--sx-ease);
    }
    /* Whole-ayah lift on hover (set by the partial's delegated listener). */
    .mushaf .qw.qw-verse-active {
        background-color: color-mix(in srgb, var(--sx-teal) 16%, transparent);
        color: var(--sx-accent);
    }

    /* Ayah being recited — stronger than the hover lift, and it stays put. */
    .mushaf .qw.qw-verse-playing {
        background-color: color-mix(in srgb, var(--sx-green) 16%, transparent);
        color: var(--sx-green);
    }
    .dark .mushaf .qw.qw-verse-playing {
        background-color: color-mix(in srgb, var(--sx-teal) 20%, transparent);
        color: var(--sx-teal);
    }
    .mushaf .ayah-end.ayah-end-playing { filter: drop-shadow(0 0 4px color-mix(in srgb, var(--sx-green) 50%, transparent)); }
    .dark .mushaf .ayah-end.ayah-end-playing { filter: drop-shadow(0 0 4px var(--mushaf-halo)); }
    .verse-card.is-playing { box-shadow: 0 0 0 2px var(--sx-green), 0 10px 30px -10px color-mix(in srgb, var(--sx-green) 45%, transparent); }
    .dark .verse-card.is-playing { box-shadow: 0 0 0 2px var(--sx-teal), 0 10px 30px -10px var(--mushaf-halo); }

    /* Verse-end rosette — our ornament (not a glyph font), holds the verse number.
       The outline is a mask, so the gold is a token and can brighten in dark mode. */
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
    }
    .mushaf .ayah-end::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--mushaf-gold);
        -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.1'%3E%3Ccircle cx='24' cy='24' r='15'/%3E%3Ccircle cx='24' cy='24' r='20' stroke-width='1' stroke-dasharray='0.6 2.6' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.1'%3E%3Ccircle cx='24' cy='24' r='15'/%3E%3Ccircle cx='24' cy='24' r='20' stroke-width='1' stroke-dasharray='0.6 2.6' stroke-linecap='round'/%3E%3C/g%3E%3C/svg%3E");
        -webkit-mask-size: contain;
        mask-size: contain;
        -webkit-mask-repeat: no-repeat;
        mask-repeat: no-repeat;
        -webkit-mask-position: center;
        mask-position: center;
    }
    .mushaf .ayah-end-num {
        position: relative;
        /* The numeral is chrome inside the scripture, so it steps out of Amiri
           back to the interface stack — as it did before the re-skin. */
        font-family: "Readex Pro", "Cairo", system-ui, sans-serif;
        font-size: 0.46em;
        font-weight: 700;
        line-height: 1;
        font-variant-numeric: tabular-nums;
        color: var(--mushaf-gold);
    }

    .mushaf-pageno {
        text-align: center;
        margin-top: 1.5rem;
    }
    .mushaf-pageno-badge {
        display: inline-block;
        min-width: 2.25rem;
        padding: 0.15rem 0.6rem;
        font-family: "Readex Pro", "Cairo", system-ui, sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--mushaf-gold);
        border: 1px solid color-mix(in srgb, var(--mushaf-gold) 40%, transparent);
        border-radius: 9999px;
    }
    .mushaf-page:not(:last-child) .mushaf-pageno {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--sx-line);
    }

    /* ---- Floating tools rail ----
       Same frosted paper as the system dock, with a gradient lozenge for the
       active tool and the rosette gold as its second, non-colour-only cue.
       All motion is gated behind prefers-reduced-motion. */
    .mushaf-rail {
        position: fixed;
        z-index: 45;
    }
    .mushaf-rail-inner {
        display: flex;
        gap: 0.25rem;
        padding: 0.4rem;
        border-radius: 9999px;
        border: 1px solid var(--sx-line-strong);
        background: color-mix(in srgb, var(--sx-surface) 82%, transparent);
        box-shadow: 0 24px 44px -24px rgb(0 30 70 / 0.55), inset 0 1px 0 rgb(255 255 255 / 0.2);
        -webkit-backdrop-filter: blur(16px) saturate(150%);
        backdrop-filter: blur(16px) saturate(150%);
    }
    .mushaf-rail-item { position: relative; display: flex; }
    /* Every action stacks its icon over an always-visible label, on both
       breakpoints — the only difference between them is how the rail is placed. */
    .mushaf-rail-btn {
        position: relative;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.22rem;
        height: auto;
        min-width: 0;
        border-radius: 0.8rem;
        color: var(--sx-ink-2);
        -webkit-tap-highlight-color: transparent;
        transition:
            background-color 0.22s var(--sx-ease),
            color 0.22s var(--sx-ease),
            box-shadow 0.22s var(--sx-ease),
            transform 0.16s var(--sx-ease);
    }
    .mushaf-rail-btn > svg { flex-shrink: 0; }
    /* No opacity dimming on inactive labels — at this size it would drop contrast
       below 4.5:1, and the active state is already carried by the gradient
       lozenge and the gold underline. */
    .mushaf-rail-label {
        font-weight: 600;
        line-height: 1.15;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mushaf-rail-btn:hover {
        background: var(--sx-accent-soft);
        color: var(--sx-accent);
    }

    /* Keyboard focus must be visible on its own, not only via the label chip. */
    .mushaf-rail-btn:focus-visible {
        outline: 2px solid var(--sx-teal);
        outline-offset: 2px;
    }

    /* Tactile press — scale only, so nothing around it reflows. */
    .mushaf-rail-btn:active { transform: scale(0.93); }

    /* Active tool: brand gradient lozenge lifted off the glass, edged in gold. */
    .mushaf-rail-btn.is-active {
        color: #fff;
        background: var(--sx-grad);
        box-shadow:
            0 14px 28px -16px rgb(0 86 179 / 0.8),
            inset 0 1px 0 rgb(255 255 255 / 0.28),
            inset 0 0 0 1px color-mix(in srgb, var(--mushaf-gold) 45%, transparent);
    }
    .mushaf-rail-btn.is-active:hover { color: #fff; }
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
        background: var(--mushaf-gold);
    }

    /* Hairline that fades at both ends instead of a flat bar. */
    .mushaf-rail-divider {
        display: block;
        background: linear-gradient(to right, transparent, var(--sx-line-strong), transparent);
    }

    /* Popovers anchored off each rail button (the paper is .sx-surface). */
    .mushaf-rail-pop {
        position: absolute;
        min-width: 11rem;
        padding: 0.65rem;
        z-index: 46;
    }
    .mushaf-rail-pop-wide { min-width: 14rem; }
    .mushaf-pop-opt {
        display: block;
        width: 100%;
        text-align: start;
        padding: 0.45rem 0.6rem;
        border-radius: 0.55rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--sx-ink-2);
        transition: background-color 0.15s var(--sx-ease), color 0.15s var(--sx-ease);
    }
    .mushaf-pop-opt:hover { background: var(--sx-surface-2); color: var(--sx-ink); }
    .mushaf-pop-opt.is-on { color: #fff; background: var(--sx-grad); }

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
{{-- Reading progress bar (the script below scales the inner span) --}}
<div class="sx-progress" aria-hidden="true"><span id="reading-progress-bar" style="transform: scaleX(0)"></span></div>

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
    class="sx min-h-[100dvh] pb-20"
>

<main class="sx-wrap py-8"
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
     class="sx-surface fixed p-1 overflow-hidden"
     {{-- one :style, not a static style plus a bound one: a bound string replaces the attribute --}}
     :style="`left: ${verseMenuPos.x}px; top: ${verseMenuPos.y}px; width: 216px; z-index: 65`"
     role="menu">
    <p class="px-3 py-1.5 text-[11px] font-bold tabular-nums text-[color:var(--sx-muted)]">
        {{ __('quran.public.verse_label') }} <span x-text="verseMenu ? fmtNum(verseMenu.number) : ''"></span>
    </p>
    <button type="button" role="menuitem" @click="listenFromVerse(verseMenu)"
            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-start text-sm font-semibold text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-ink)]">
        <i data-lucide="play" class="w-4 h-4 flex-shrink-0 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
        {{ __('quran.public.listen_from_here') }}
    </button>
    <button type="button" role="menuitem" @click="showTafsirFor(verseMenu)"
            class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-start text-sm font-semibold text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-ink)]">
        <i data-lucide="book-open" class="w-4 h-4 flex-shrink-0 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
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
