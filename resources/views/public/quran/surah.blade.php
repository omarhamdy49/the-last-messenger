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

        sidebarOpen: false,
        readingMode: localStorage.getItem('readingMode') || 'both',
        viewMode: localStorage.getItem('quranSurahView') || 'verses',
        fontSize: parseInt(localStorage.getItem('fontSize')) || 28,
        showTransliteration: localStorage.getItem('showTransliteration') !== 'false',
        currentLocale: '{{ $currentLocale }}',
        defaultTranslationId: {{ $defaultTranslationId }}, // inlined edition (Saheeh International)
        translationLang: '{{ $defaultTranslationLang }}', // default translation language (English / Saheeh)

        audioPlaying: false,
        currentVerseId: null,
        currentAudio: null,
        selectedReciterId: parseInt(localStorage.getItem('selectedReciterId')) || ({{ count($reciters) > 0 ? $reciters[0]['id'] : 0 }}),
        surahAudio: null,
        surahAudioUrl: null,
        surahPlaying: false,
        surahProgress: 0,
        surahCurrentTime: 0,
        surahDuration: 0,
        playbackRate: 1,
        nextSurahUrl: '{{ $nextSurah ? route('public.quran.surah', $nextSurah) : '' }}',
        firstRecitationUrl: '{{ $firstRecitationUrl }}',

        openTafsir: {},
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

        surahUrlForSelected() {
            const r = this.reciters.find(x => x.id === this.selectedReciterId);
            return (r && r.surahAudioUrl) ? r.surahAudioUrl : this.firstRecitationUrl;
        },

        toggleSurahAudio() {
            const url = this.surahUrlForSelected();
            if (!url) { return; }
            // Recreate the player when the selected reciter changed.
            if (this.surahAudio && this.surahAudioUrl !== url) {
                this.surahAudio.pause();
                this.surahAudio = null;
                this.surahPlaying = false;
                this.surahProgress = 0;
                this.surahCurrentTime = 0;
                this.surahDuration = 0;
            }
            if (!this.surahAudio) {
                this.surahAudio = new Audio(url);
                this.surahAudioUrl = url;
                this.surahAudio.playbackRate = this.playbackRate;
                this.surahAudio.addEventListener('loadedmetadata', () => { this.surahDuration = this.surahAudio.duration || 0; });
                this.surahAudio.addEventListener('timeupdate', () => {
                    this.surahCurrentTime = this.surahAudio.currentTime;
                    this.surahProgress = (this.surahAudio.currentTime / this.surahAudio.duration) * 100 || 0;
                });
                this.surahAudio.addEventListener('ended', () => {
                    this.surahPlaying = false; this.surahProgress = 0; this.surahCurrentTime = 0;
                    if (this.nextSurahUrl) { window.location.href = this.nextSurahUrl; } // auto-advance
                });
            }
            if (this.surahPlaying) { this.surahAudio.pause(); this.surahPlaying = false; }
            else { this.surahAudio.play().catch(() => {}); this.surahPlaying = true; }
        },

        seekSurah(event) {
            if (!this.surahAudio || !this.surahDuration) { return; }
            const rect = event.currentTarget.getBoundingClientRect();
            let ratio = (event.clientX - rect.left) / rect.width;
            if (document.documentElement.dir === 'rtl') { ratio = 1 - ratio; }
            this.surahAudio.currentTime = Math.min(1, Math.max(0, ratio)) * this.surahDuration;
        },

        cycleSpeed() {
            const rates = [1, 1.25, 1.5, 2, 0.75];
            this.playbackRate = rates[(rates.indexOf(this.playbackRate) + 1) % rates.length];
            if (this.surahAudio) { this.surahAudio.playbackRate = this.playbackRate; }
        },

        formatTime(s) {
            if (!s || isNaN(s)) { return '0:00'; }
            s = Math.floor(s);
            const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
            const pad = (n) => String(n).padStart(2, '0');
            return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${m}:${pad(sec)}`;
        },

        onReciterChange() {
            localStorage.setItem('selectedReciterId', this.selectedReciterId);
            // Stop any audio so the next play uses the newly selected reciter.
            if (this.surahAudio) { this.surahAudio.pause(); this.surahAudio = null; this.surahAudioUrl = null; this.surahPlaying = false; this.surahProgress = 0; this.surahCurrentTime = 0; this.surahDuration = 0; }
            if (this.currentAudio) { this.currentAudio.pause(); this.audioPlaying = false; this.currentVerseId = null; }
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
            // Lock body scroll while the mobile sidebar sheet is open.
            this.$watch('sidebarOpen', open => { document.body.style.overflow = open ? 'hidden' : ''; });
            this.$nextTick(() => {
                updateBar();
                if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            });
        }
    }"
    x-init="boot()"
    @keydown.escape.window="sidebarOpen = false"
    :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'"
    class="min-h-screen font-['Cairo'] transition-colors duration-300"
>

{{-- Mobile: floating button to open the sidebar sheet (player & tools) --}}
<button x-show="!sidebarOpen" x-cloak @click="sidebarOpen = true" type="button"
    class="lg:hidden fixed bottom-6 end-4 z-40 w-14 h-14 rounded-full shadow-2xl flex items-center justify-center text-white bg-gradient-to-r from-[#0056b3] to-[#218838] active:scale-95 transition-transform"
    aria-label="{{ __('quran.public.surah_tools') }}">
    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
</button>

{{-- Mobile drawer scrim --}}
<div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false"
     class="lg:hidden fixed inset-0 bg-black/50 z-40"></div>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

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

    <div class="lg:grid lg:grid-cols-3 lg:gap-8">

        {{-- ============================================================ --}}
        {{-- MAIN CONTENT --}}
        {{-- ============================================================ --}}
        <div class="lg:col-span-2">

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

                {{-- Bismillah (shown for all surahs except Al-Fatiha #1, where it is verse 1, and At-Tawbah #9, which has no Basmala) --}}
                @if($surah['bismillahPre'] && $surah['number'] != 1 && $surah['number'] != 9)
                <div class="p-8 text-center" :class="$store.theme.dark ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <p class="text-4xl font-['Amiri'] leading-loose" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        بِسۡمِ ٱللَّهِ ٱلرَّحۡمَٰنِ ٱلرَّحِيمِ
                    </p>
                </div>
                @endif
            </div>

            {{-- Reading Controls --}}
            <div class="mb-6 p-4 sm:p-6 rounded-2xl shadow-lg" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                @php
                    // Shared control tokens for a consistent, aligned toolbar (all controls 40px tall).
                    $lblCls  = 'block text-[11px] font-semibold tracking-wide mb-1.5 ps-0.5';
                    $segWrap = 'inline-flex items-center gap-1 p-1 rounded-xl h-10';
                    $segBtn  = 'inline-flex items-center px-3 h-8 rounded-lg text-sm font-semibold transition-all whitespace-nowrap';
                    $segOn   = 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm';
                    $selCls  = 'h-10 ps-3 pe-8 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3]/30 cursor-pointer min-w-[9.5rem] max-w-[14rem] transition-colors';
                @endphp
                <div class="flex flex-wrap items-end gap-x-5 gap-y-4">

                    {{-- View controls: how to read --}}
                    <div class="flex flex-wrap items-end gap-3 sm:gap-4">

                        {{-- Display: Arabic / translation / both --}}
                        <div>
                            <span class="{{ $lblCls }}" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.filter_display') }}</span>
                            <div class="{{ $segWrap }}" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-100'">
                                <button @click="setReadingMode('arabic')" class="{{ $segBtn }}"
                                    :class="readingMode === 'arabic' ? '{{ $segOn }}' : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">{{ __('quran.public.mode_arabic') }}</button>
                                <button @click="setReadingMode('translation')" class="{{ $segBtn }}"
                                    :class="readingMode === 'translation' ? '{{ $segOn }}' : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">{{ __('quran.public.mode_translation') }}</button>
                                <button @click="setReadingMode('both')" class="{{ $segBtn }}"
                                    :class="readingMode === 'both' ? '{{ $segOn }}' : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">{{ __('quran.public.mode_both') }}</button>
                            </div>
                        </div>

                        {{-- Layout: verse cards vs continuous mushaf flow --}}
                        <div>
                            <span class="{{ $lblCls }}" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.filter_layout') }}</span>
                            <div class="{{ $segWrap }}" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-100'">
                                <button @click="setViewMode('verses')" class="{{ $segBtn }}"
                                    :class="viewMode === 'verses' ? '{{ $segOn }}' : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">{{ __('quran.public.view_by_verse') }}</button>
                                <button @click="setViewMode('reading')" class="{{ $segBtn }}"
                                    :class="viewMode === 'reading' ? '{{ $segOn }}' : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">{{ __('quran.public.view_reading') }}</button>
                            </div>
                        </div>

                        {{-- Font size stepper --}}
                        <div>
                            <span class="{{ $lblCls }}" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.filter_font') }}</span>
                            <div class="inline-flex items-center h-10 rounded-xl overflow-hidden" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-100'">
                                <button @click="adjustFontSize(-2)" class="h-10 w-10 inline-flex items-center justify-center transition-colors"
                                    :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-gray-900'"
                                    aria-label="{{ __('quran.public.decrease_font') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M20 12H4"/></svg>
                                </button>
                                <span class="min-w-[3.25rem] text-center text-sm font-semibold tabular-nums" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="fontSize + 'px'"></span>
                                <button @click="adjustFontSize(2)" class="h-10 w-10 inline-flex items-center justify-center transition-colors"
                                    :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-700 hover:text-white' : 'text-gray-700 hover:bg-gray-200 hover:text-gray-900'"
                                    aria-label="{{ __('quran.public.increase_font') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Source controls: translation edition + tafsir source.
                         The language follows the site locale; select options are
                         server-rendered so x-model selects the right one on init. --}}
                    <div class="flex flex-wrap items-end gap-3 sm:gap-4">
                        <div x-show="editionsForLang.length > 1" x-cloak>
                            <span class="{{ $lblCls }}" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.translation_edition') }}</span>
                            <select x-model.number="selectedTranslationId" @change="setTranslationEdition(selectedTranslationId)"
                                class="{{ $selCls }}" :class="$store.theme.dark ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                @foreach($translationResources as $tr)
                                    @if($tr['language'] === $defaultTranslationLang)
                                    <option value="{{ $tr['id'] }}">{{ $tr['name'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div x-show="tafsirResources.length > 1" x-cloak>
                            <span class="{{ $lblCls }}" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.tafsir_source') }}</span>
                            <select x-model.number="selectedTafsirId" @change="setTafsirSource(selectedTafsirId)"
                                class="{{ $selCls }}" :class="$store.theme.dark ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                @foreach($tafsirResources as $tr)
                                <option value="{{ $tr['id'] }}">{{ $tr['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

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

            {{-- Continuous reading (whole-surah flow), separated by mushaf page --}}
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
                           :style="`font-size: ${fontSize}px`"><template x-for="verse in grp.items" :key="verse.id"><span><span x-text="verse.arabic"></span><span class="inline-flex items-center justify-center align-middle mx-1 w-7 h-7 text-xs font-sans font-bold rounded-full" :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-gray-100 text-[#0056b3]'" x-text="verse.number"></span> </span></template></p>
                    </div>
                </div>
                </template>
            </div>

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
                   class="flex items-center gap-3 px-5 py-3 rounded-xl max-w-[48%] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-[1.02] transition-all">
                    <svg class="w-5 h-5 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="flex flex-col text-start min-w-0">
                        <span class="text-xs opacity-80">{{ __('quran.public.previous_surah') }}</span>
                        <span class="font-['Amiri'] text-lg truncate">{{ $prevSurahName }}</span>
                    </span>
                </a>
                @else <span></span> @endif

                @if($nextSurah)
                <a href="{{ route('public.quran.surah', $nextSurah) }}"
                   class="flex items-center gap-3 px-5 py-3 rounded-xl max-w-[48%] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-[1.02] transition-all">
                    <span class="flex flex-col text-end min-w-0">
                        <span class="text-xs opacity-80">{{ __('quran.public.next_surah') }}</span>
                        <span class="font-['Amiri'] text-lg truncate">{{ $nextSurahName }}</span>
                    </span>
                    <svg class="w-5 h-5 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
            </div>

        </div>{{-- /main-content --}}

        {{-- ============================================================ --}}
        {{-- SIDEBAR --}}
        {{-- ============================================================ --}}
        <aside class="quran-sidebar-aside lg:col-span-1 mt-8 lg:mt-0"
               :class="[sidebarOpen ? 'is-open' : '', $store.theme.dark ? 'bg-gray-950' : 'bg-gray-50']"
               aria-label="{{ __('quran.public.sidebar_label') }}">
            {{-- Mobile sheet header (close handle) --}}
            <div class="lg:hidden sticky top-0 z-10 flex items-center justify-between px-4 py-3 backdrop-blur"
                 :class="$store.theme.dark ? 'bg-gray-950/95 border-b border-gray-800' : 'bg-gray-50/95 border-b border-gray-200'">
                <span class="font-bold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('quran.public.surah_tools') }}</span>
                <button @click="sidebarOpen = false" type="button" class="p-2 rounded-full"
                        :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-200'"
                        aria-label="{{ __('quran.public.close') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- Sticky/scrollable on desktop; flows inside the sheet on mobile. --}}
            <div class="quran-sidebar space-y-6 custom-scrollbar lg:pe-2 px-4 pb-6 lg:px-0 lg:pb-0">

                {{-- Surah info --}}
                <div class="rounded-2xl shadow-lg p-6" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-3" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        <svg class="w-6 h-6 text-[#0056b3] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                        </svg>
                        {{ __('quran.public.surah_info') }}
                    </h2>
                    <ul class="space-y-3 text-sm">
                        <li class="flex justify-between" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
                            <span>{{ __('quran.public.mushaf_order') }}:</span>
                            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $surah['number'] }}</span>
                        </li>
                        <li class="flex justify-between" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
                            <span>{{ __('quran.public.verse_count') }}:</span>
                            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $surah['versesCount'] }}</span>
                        </li>
                        <li class="flex justify-between" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
                            <span>{{ __('quran.public.revelation_place') }}:</span>
                            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                                {{ $isMakki ? __('quran.public.makki') : __('quran.public.madani') }}
                            </span>
                        </li>
                        <li class="flex justify-between" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
                            <span>{{ __('quran.public.revelation_order') }}:</span>
                            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $surah['revelationOrder'] }}</span>
                        </li>
                        <li class="flex justify-between" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
                            <span>{{ __('quran.public.juz') }}:</span>
                            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $surah['juz'] }}</span>
                        </li>
                    </ul>
                    @if($surah['info'])
                    <div class="mt-4 pt-4" :class="$store.theme.dark ? 'border-t border-gray-700' : 'border-t border-gray-200'">
                        <p class="text-sm leading-relaxed" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                            {{ Str::limit(strip_tags($surah['info']), 200) }}
                        </p>
                    </div>
                    @endif
                </div>

                {{-- Audio player (compact) --}}
                <div class="rounded-2xl shadow-lg overflow-hidden" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    {{-- Gradient header: now playing + seek --}}
                    <div class="p-4 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white">
                        {{-- "Now playing" label --}}
                        <div class="flex items-center gap-1.5 text-[11px] font-medium text-white/80 mb-2">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>
                            <span>{{ __('quran.public.listen') }}</span>
                        </div>
                        <p class="font-['Amiri'] text-xl leading-normal truncate">{{ $surah['nameAr'] }}</p>
                        <p class="text-xs text-white/70 truncate mt-0.5" x-text="(reciters.find(r => r.id === selectedReciterId) || {}).nameAr || ''"></p>

                        {{-- Seekable progress: darker groove + white fill for clear
                             visibility on the bright gradient --}}
                        <div class="mt-3">
                            <div class="h-2 rounded-full bg-black/25 cursor-pointer group" @click="seekSurah($event)"
                                 role="slider" :aria-valuenow="Math.round(surahProgress)" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('quran.public.listen') }}">
                                <div class="h-full rounded-full bg-white shadow-sm relative" :style="`width: ${surahProgress}%`">
                                    <span class="absolute end-0 top-1/2 -translate-y-1/2 translate-x-1/2 w-3.5 h-3.5 rounded-full bg-white shadow-md ring-2 ring-white/40 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1.5 text-[11px] text-white/90 tabular-nums">
                                <span x-text="formatTime(surahCurrentTime)">0:00</span>
                                <span x-text="formatTime(surahDuration)">0:00</span>
                            </div>
                        </div>
                    </div>

                    {{-- Transport controls + reciter --}}
                    <div class="p-4 pt-3">
                        <div class="flex items-center justify-center gap-3">
                            @if($prevSurah)
                            <a href="{{ route('public.quran.surah', $prevSurah) }}"
                               class="p-1.5 rounded-full transition-colors" :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'"
                               title="{{ $prevSurahName }}" aria-label="{{ __('quran.public.previous_surah') }}: {{ $prevSurahName }}">
                                <svg class="w-5 h-5 rtl:rotate-180" fill="currentColor" viewBox="0 0 20 20"><path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z"/></svg>
                            </a>
                            @else <span class="p-1.5 opacity-25"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z"/></svg></span> @endif

                            <button @click="firstRecitationUrl ? toggleSurahAudio() : null" :disabled="!firstRecitationUrl"
                                class="p-3 rounded-full text-white shadow-md transition-transform transform hover:scale-105 active:scale-95"
                                :class="firstRecitationUrl ? 'bg-gradient-to-r from-[#0056b3] to-[#218838]' : 'bg-gray-400 cursor-not-allowed'"
                                :aria-label="surahPlaying ? '{{ __('quran.public.pause') }}' : '{{ __('quran.public.play') }}'">
                                <svg x-show="!surahPlaying" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1 1 0 004 3.7v12.6a1 1 0 001.5.86l11-6.3a1 1 0 000-1.72l-11-6.3z"/></svg>
                                <svg x-show="surahPlaying" x-cloak class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1zm7 0a1 1 0 011 1v10a1 1 0 11-2 0V5a1 1 0 011-1z"/></svg>
                            </button>

                            @if($nextSurah)
                            <a href="{{ route('public.quran.surah', $nextSurah) }}"
                               class="p-1.5 rounded-full transition-colors" :class="$store.theme.dark ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100'"
                               title="{{ $nextSurahName }}" aria-label="{{ __('quran.public.next_surah') }}: {{ $nextSurahName }}">
                                <svg class="w-5 h-5 rtl:rotate-180" fill="currentColor" viewBox="0 0 20 20"><path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798L4.555 5.168z"/></svg>
                            </a>
                            @else <span class="p-1.5 opacity-25"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798L4.555 5.168z"/></svg></span> @endif

                            <button @click="cycleSpeed()" class="px-2 py-1 rounded-full text-xs font-bold tabular-nums transition-colors min-w-[2.5rem]"
                                :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5] hover:bg-gray-700' : 'bg-gray-100 text-[#0056b3] hover:bg-gray-200'"
                                aria-label="{{ __('quran.public.playback_speed') }}" x-text="playbackRate + '×'">1×</button>
                        </div>

                        {{-- Reciter select --}}
                        <select x-model.number="selectedReciterId" @change="onReciterChange()"
                            aria-label="{{ __('quran.public.choose_reciter') }}"
                            class="mt-3 w-full px-3 py-2 text-sm rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
                            :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'">
                            @foreach($reciters as $r)
                            <option value="{{ $r['id'] }}">{{ $r['nameAr'] ?: $r['nameEn'] }}</option>
                            @endforeach
                        </select>

                        @if(!$firstRecitationUrl)
                        <p class="text-xs text-center mt-2" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">{{ __('quran.public.audio_unavailable') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Surah navigation: next (prominent) + previous --}}
                <div class="rounded-2xl shadow-lg p-6" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <h2 class="text-xl font-bold mb-4" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        {{ __('quran.public.surah_navigation') }}
                    </h2>
                    <div class="space-y-3">
                        @if($nextSurah)
                        <a href="{{ route('public.quran.surah', $nextSurah) }}"
                           class="flex items-center justify-between gap-3 p-4 rounded-xl shadow-md hover:shadow-lg transition-all hover:scale-[1.02] bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                            <div class="min-w-0">
                                <div class="text-xs opacity-80">{{ __('quran.public.next_surah') }}</div>
                                <div class="font-['Amiri'] text-xl truncate">{{ $nextSurahName }}</div>
                            </div>
                            <svg class="w-7 h-7 rtl:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        @endif
                        @if($prevSurah)
                        <a href="{{ route('public.quran.surah', $prevSurah) }}"
                           class="flex items-center justify-between gap-3 p-4 rounded-xl transition-all hover:shadow-md"
                           :class="$store.theme.dark ? 'bg-gray-800 text-gray-200 hover:bg-gray-700' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'">
                            <div class="min-w-0">
                                <div class="text-xs opacity-70">{{ __('quran.public.previous_surah') }}</div>
                                <div class="font-['Amiri'] text-xl truncate">{{ $prevSurahName }}</div>
                            </div>
                            <svg class="w-7 h-7 rtl:rotate-180 flex-shrink-0" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Related links --}}
                <div class="rounded-2xl shadow-lg p-6" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <h2 class="text-xl font-bold mb-4" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        {{ __('quran.public.related_links') }}
                    </h2>
                    <div class="space-y-3">
                        <a href="{{ route('public.quran.reciters') }}"
                           class="block p-3 rounded-xl transition-all"
                           :class="$store.theme.dark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                </svg>
                                <span class="font-semibold">{{ __('quran.public.link_reciters') }}</span>
                            </div>
                        </a>
                        <a href="{{ route('public.quran.live') }}"
                           class="block p-3 rounded-xl transition-all"
                           :class="$store.theme.dark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                </svg>
                                <span class="font-semibold">{{ __('quran.public.link_live') }}</span>
                            </div>
                        </a>
                        <a href="{{ route('public.quran.asbab') }}"
                           class="block p-3 rounded-xl transition-all"
                           :class="$store.theme.dark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-semibold">{{ __('quran.public.link_asbab') }}</span>
                            </div>
                        </a>
                        <a href="{{ route('public.quran') }}"
                           class="block p-3 rounded-xl transition-all"
                           :class="$store.theme.dark ? 'bg-gray-800 hover:bg-gray-700 text-gray-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                                </svg>
                                <span class="font-semibold">{{ __('quran.public.all_surahs') }}</span>
                            </div>
                        </a>
                    </div>
                </div>

            </div>
        </aside>

    </div>{{-- /grid --}}

</main>

</div>{{-- /x-data --}}

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
