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
    .verse-card:hover .verse-actions {
        opacity: 1;
        transform: translateY(0);
    }
</style>
@endpush

@php
    $surahJson  = json_encode($surah,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $versesJson = json_encode($verses,  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $paginJson  = json_encode($pagination, JSON_UNESCAPED_UNICODE);
    $recitersJson = json_encode($reciters, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $isMakki = ($surah['type'] ?? '') === 'makki';
@endphp

@section('content')
{{-- Reading progress bar --}}
<div class="progress-bar" id="reading-progress-bar" style="transform: scaleX(0)"></div>

<div
    x-data="{
        surah: {{ $surahJson }},
        verses: {{ $versesJson }},
        pagination: {{ $paginJson }},
        reciters: {{ $recitersJson }},

        readingMode: localStorage.getItem('readingMode') || 'both',
        fontSize: parseInt(localStorage.getItem('fontSize')) || 28,
        showTransliteration: localStorage.getItem('showTransliteration') !== 'false',
        translationLang: localStorage.getItem('translationLang') || 'en',

        audioPlaying: false,
        currentVerseId: null,
        currentAudio: null,
        selectedReciterId: parseInt(localStorage.getItem('selectedReciterId')) || ({{ count($reciters) > 0 ? $reciters[0]['id'] : 0 }}),
        surahAudio: null,
        surahPlaying: false,
        surahProgress: 0,
        firstRecitationUrl: '{{ $firstRecitationUrl }}',

        openTafsir: {},

        get readingProgress() {
            const p = this.pagination;
            return ((p.current_page - 1) * p.per_page + this.verses.length) / this.surah.versesCount * 100;
        },

        get pageNumbers() {
            const tp = this.pagination.total_pages;
            const cp = this.pagination.current_page;
            const maxV = 5;
            let start = Math.max(1, cp - Math.floor(maxV / 2));
            let end = Math.min(tp, start + maxV - 1);
            if (end - start < maxV - 1) { start = Math.max(1, end - maxV + 1); }
            const pages = [];
            for (let i = start; i <= end; i++) { pages.push(i); }
            return pages;
        },

        setReadingMode(m) {
            this.readingMode = m;
            localStorage.setItem('readingMode', m);
        },

        adjustFontSize(d) {
            this.fontSize = Math.max(16, Math.min(48, this.fontSize + d));
            localStorage.setItem('fontSize', this.fontSize);
        },

        toggleTransliteration() {
            this.showTransliteration = !this.showTransliteration;
            localStorage.setItem('showTransliteration', this.showTransliteration);
        },

        setTranslationLang(l) {
            this.translationLang = l;
            localStorage.setItem('translationLang', l);
        },

        toggleTafsir(id) {
            this.openTafsir[id] = !this.openTafsir[id];
        },

        getTranslation(verse) {
            const key = 'translation_' + this.translationLang;
            return verse[key] || verse.translation_ar || '';
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
            this.currentAudio.addEventListener('ended', () => { this.audioPlaying = false; this.currentVerseId = null; });
        },

        toggleSurahAudio() {
            if (!this.surahAudio && this.firstRecitationUrl) {
                this.surahAudio = new Audio(this.firstRecitationUrl);
                this.surahAudio.addEventListener('timeupdate', () => {
                    this.surahProgress = (this.surahAudio.currentTime / this.surahAudio.duration) * 100 || 0;
                });
                this.surahAudio.addEventListener('ended', () => { this.surahPlaying = false; this.surahProgress = 0; });
            }
            if (!this.surahAudio) { return; }
            if (this.surahPlaying) { this.surahAudio.pause(); this.surahPlaying = false; }
            else { this.surahAudio.play().catch(() => {}); this.surahPlaying = true; }
        },

        goToPage(p) {
            if (p < 1 || p > this.pagination.total_pages) { return; }
            const url = new URL(window.location.href);
            url.searchParams.set('page', p);
            url.searchParams.set('per_page', this.pagination.per_page);
            window.location.href = url.toString();
        },

        setPerPage(v) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', 1);
            url.searchParams.set('per_page', v);
            window.location.href = url.toString();
        },

        init() {
            this.$watch('readingProgress', val => {
                const bar = document.getElementById('reading-progress-bar');
                if (bar) { bar.style.transform = 'scaleX(' + (val / 100) + ')'; }
            });
            this.$nextTick(() => {
                const bar = document.getElementById('reading-progress-bar');
                if (bar) { bar.style.transform = 'scaleX(' + (this.readingProgress / 100) + ')'; }
                if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            });
        }
    }"
    :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'"
    class="min-h-screen font-['Cairo'] transition-colors duration-300"
>

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
                <div class="flex flex-wrap items-center justify-between gap-3">

                    {{-- Reading mode --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <button @click="setReadingMode('arabic')"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="readingMode === 'arabic'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                        >{{ __('quran.public.mode_arabic') }}</button>
                        <button @click="setReadingMode('translation')"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="readingMode === 'translation'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                        >{{ __('quran.public.mode_translation') }}</button>
                        <button @click="setReadingMode('both')"
                            class="px-3 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="readingMode === 'both'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                        >{{ __('quran.public.mode_both') }}</button>
                    </div>

                    {{-- Font controls --}}
                    <div class="flex items-center gap-2">
                        <button @click="adjustFontSize(-2)"
                            class="p-2 rounded-lg transition-colors"
                            :class="$store.theme.dark ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'"
                            aria-label="{{ __('quran.public.decrease_font') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        </button>
                        <span class="px-3 py-1 rounded-lg text-sm font-semibold min-w-[3.5rem] text-center"
                              :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'"
                              x-text="fontSize + 'px'"></span>
                        <button @click="adjustFontSize(2)"
                            class="p-2 rounded-lg transition-colors"
                            :class="$store.theme.dark ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'"
                            aria-label="{{ __('quran.public.increase_font') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>

                    {{-- Transliteration toggle --}}
                    <button @click="toggleTransliteration"
                        class="px-3 py-2 rounded-lg text-sm font-semibold transition-all"
                        :class="showTransliteration
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                    >{{ __('quran.public.transliteration') }}</button>

                    {{-- Translation language --}}
                    <select x-model="translationLang" @change="setTranslationLang(translationLang)"
                        class="px-3 py-2 text-base sm:text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0056b3]/20"
                        :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'"
                    >
                        <option value="ar">{{ __('quran.public.lang_ar') }}</option>
                        <option value="en">{{ __('quran.public.lang_en') }}</option>
                        <option value="fr">{{ __('quran.public.lang_fr') }}</option>
                        <option value="tr">{{ __('quran.public.lang_tr') }}</option>
                        <option value="ur">{{ __('quran.public.lang_ur') }}</option>
                    </select>

                    {{-- Per page --}}
                    <select @change="setPerPage($event.target.value)"
                        class="px-3 py-2 text-base sm:text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0056b3]/20"
                        :class="$store.theme.dark ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-900'"
                    >
                        <option value="5"  {{ $perPage == 5  ? 'selected' : '' }}>5 {{ __('quran.public.verse_unit') }}</option>
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 {{ __('quran.public.verse_unit') }}</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 {{ __('quran.public.verse_unit') }}</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 {{ __('quran.public.verse_unit') }}</option>
                    </select>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- VERSES --}}
            {{-- ============================================================ --}}
            <div class="space-y-6 mb-12">
                <template x-for="(verse, index) in verses" :key="verse.id">
                    <article
                        class="verse-card rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl animate-fadeInUp"
                        :class="[$store.theme.dark ? 'bg-gray-900' : 'bg-white', 'stagger-' + ((index % 4) + 1)]"
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

                            {{-- Verse actions --}}
                            <div class="verse-actions flex items-center gap-2 opacity-0 translate-y-2 transition-all duration-300">
                                <button @click="playVerse(verse)"
                                    class="p-2 rounded-lg transition-colors"
                                    :class="currentVerseId === verse.id
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                        : ($store.theme.dark ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900')"
                                    :title="'{{ __('quran.public.listen') }}'"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
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
                            </div>
                        </div>

                        {{-- Verse content --}}
                        <div class="p-6">
                            {{-- Arabic text --}}
                            <div x-show="readingMode === 'arabic' || readingMode === 'both'" class="mb-4">
                                <p class="font-['Amiri'] leading-loose text-end"
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

                            {{-- Translation --}}
                            <div x-show="readingMode === 'translation' || readingMode === 'both'">
                                <p class="text-lg leading-relaxed" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'" x-text="getTranslation(verse)"></p>
                            </div>

                            {{-- Tafsir accordion --}}
                            <div class="mt-4 pt-4" :class="$store.theme.dark ? 'border-t border-gray-700' : 'border-t border-gray-200'"
                                 x-show="verse.tafsir">
                                <button @click="toggleTafsir(verse.id)"
                                    class="flex items-center justify-between w-full text-end font-semibold transition-colors"
                                    :class="$store.theme.dark ? 'text-white hover:text-[#64CCC5]' : 'text-gray-900 hover:text-[#0056b3]'"
                                    :aria-expanded="openTafsir[verse.id] ? 'true' : 'false'"
                                >
                                    <span>{{ __('quran.public.tafsir_muyassar') }}</span>
                                    <svg class="w-5 h-5 transition-transform duration-300"
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
                                    <p class="leading-relaxed font-['Amiri'] text-lg" dir="rtl"
                                       :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'"
                                       x-text="verse.tafsir"></p>
                                </div>
                            </div>
                        </div>
                    </article>
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
            {{-- PAGINATION --}}
            {{-- ============================================================ --}}
            @if($pagination['total_pages'] > 1)
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-8">
                {{-- Prev --}}
                <button
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="!pagination.has_prev"
                    class="px-6 py-3 rounded-xl font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    :class="!pagination.has_prev
                        ? ($store.theme.dark ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400')
                        : 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-105'"
                >
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    {{ __('quran.public.prev_page') }}
                </button>

                {{-- Page numbers --}}
                <div class="flex items-center gap-2 flex-wrap justify-center">
                    <template x-for="p in pageNumbers" :key="p">
                        <button
                            @click="goToPage(p)"
                            class="w-10 h-10 rounded-xl font-semibold transition-all"
                            :class="pagination.current_page === p
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                            :aria-current="pagination.current_page === p ? 'page' : null"
                            x-text="p"
                        ></button>
                    </template>
                </div>

                {{-- Next --}}
                <button
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="!pagination.has_next"
                    class="px-6 py-3 rounded-xl font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    :class="!pagination.has_next
                        ? ($store.theme.dark ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400')
                        : 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg hover:scale-105'"
                >
                    {{ __('quran.public.next_page') }}
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
            </div>
            @endif

        </div>{{-- /main-content --}}

        {{-- ============================================================ --}}
        {{-- SIDEBAR --}}
        {{-- ============================================================ --}}
        <aside class="lg:col-span-1 mt-8 lg:mt-0" aria-label="{{ __('quran.public.sidebar_label') }}">
            <div class="sticky top-24 space-y-6">

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

                {{-- Audio player --}}
                <div class="rounded-2xl shadow-lg p-6" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <h2 class="text-xl font-bold mb-4 flex items-center gap-3" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        <svg class="w-6 h-6 text-[#0056b3] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                        </svg>
                        {{ __('quran.public.listen') }}
                    </h2>

                    {{-- Reciter select --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.public.choose_reciter') }}:
                        </label>
                        <select
                            x-model.number="selectedReciterId"
                            @change="localStorage.setItem('selectedReciterId', selectedReciterId)"
                            class="w-full p-3 text-base sm:text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0056b3]/20"
                            :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                        >
                            <template x-for="r in reciters" :key="r.id">
                                <option :value="r.id" x-text="r.nameAr || r.nameEn"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Play controls --}}
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <button
                            @click="firstRecitationUrl ? toggleSurahAudio() : null"
                            class="p-5 rounded-full text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105"
                            :class="firstRecitationUrl
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] cursor-pointer'
                                : 'bg-gray-400 cursor-not-allowed'"
                            :disabled="!firstRecitationUrl"
                            :aria-label="surahPlaying ? '{{ __('quran.public.pause') }}' : '{{ __('quran.public.play') }}'"
                        >
                            <svg x-show="!surahPlaying" class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                            <svg x-show="surahPlaying" class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Audio progress --}}
                    <div x-show="surahPlaying || surahProgress > 0" class="mt-2">
                        <div class="h-1.5 rounded-full overflow-hidden" :class="$store.theme.dark ? 'bg-gray-700' : 'bg-gray-200'">
                            <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-300"
                                 :style="`width: ${surahProgress}%`"></div>
                        </div>
                    </div>

                    @if(!$firstRecitationUrl)
                    <p class="text-xs text-center mt-2" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">
                        {{ __('quran.public.audio_unavailable') }}
                    </p>
                    @endif
                </div>

                {{-- Quick verse navigation --}}
                <div class="rounded-2xl shadow-lg p-6" :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                    <h2 class="text-xl font-bold mb-4" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                        {{ __('quran.public.quick_nav') }}
                    </h2>
                    <div class="grid grid-cols-5 gap-2 max-h-48 overflow-y-auto custom-scrollbar">
                        @for($v = 1; $v <= $surah['versesCount']; $v++)
                        @php $vPage = (int) ceil($v / $perPage); @endphp
                        <a
                            href="{{ route('public.quran.surah', $surah['number']) }}?page={{ $vPage }}&per_page={{ $perPage }}"
                            class="aspect-square rounded-lg font-semibold text-sm flex items-center justify-center transition-all hover:shadow-lg hover:scale-110"
                            :class="{{ $vPage === $currentPage ? 'true' : 'false' }}
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                            aria-label="{{ __('quran.public.verse_label') }} {{ $v }}"
                        >{{ $v }}</a>
                        @endfor
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
