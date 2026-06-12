@extends('layouts.public')

@section('title', __('quran.public.reciters_page_title'))
@section('meta_description', __('quran.public.reciters_page_description'))

@php
    $featuredJson = json_encode($featuredReciters, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $allJson      = json_encode($reciters, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

@push('head')
<style>
    .reciter-card {
        position: relative;
        overflow: hidden;
    }

    .reciter-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(
            45deg,
            transparent 30%,
            rgba(100, 204, 197, 0.1) 50%,
            transparent 70%
        );
        opacity: 0;
        transition: opacity 0.5s ease;
        pointer-events: none;
    }

    .reciter-card:hover::before {
        opacity: 1;
        animation: reciterShimmer 2s infinite;
    }

    @keyframes reciterShimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    .reciter-image {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .reciter-card:hover .reciter-image {
        transform: scale(1.05);
        box-shadow: 0 0 40px rgba(0, 86, 179, 0.4);
    }

    .audio-waveform {
        background: linear-gradient(90deg,
            transparent 0%,
            rgba(0, 86, 179, 0.3) 20%,
            rgba(33, 136, 56, 0.3) 40%,
            rgba(0, 86, 179, 0.3) 60%,
            transparent 100%
        );
        background-size: 200% 100%;
        animation: waveAnimation 3s linear infinite;
    }

    @keyframes waveAnimation {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .stat-badge {
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .stat-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 86, 179, 0.2);
    }

    .listening-button {
        position: relative;
        overflow: hidden;
    }

    .listening-button::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .listening-button:hover::before {
        width: 300px;
        height: 300px;
    }

    .featured-badge {
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }
</style>
@endpush

@section('content')
<div
    x-data="{
        searchQuery: '',
        viewMode: localStorage.getItem('recitersViewMode') || 'grid',
        filterStyle: 'all',
        sortBy: 'popular',
        perPage: parseInt(localStorage.getItem('recitersPerPage')) || 12,
        currentPage: 1,
        playingReciterId: null,
        currentAudio: null,
        featuredReciters: {{ $featuredJson }},
        allReciters: {{ $allJson }},

        get filteredReciters() {
            let r = this.allReciters;
            if (this.filterStyle !== 'all') {
                r = r.filter(x => x.style === this.filterStyle);
            }
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                r = r.filter(x =>
                    (x.nameAr && x.nameAr.includes(this.searchQuery)) ||
                    (x.nameEn && x.nameEn.toLowerCase().includes(q)) ||
                    (x.country && x.country.toLowerCase().includes(q))
                );
            }
            if (this.sortBy === 'popular') {
                r = [...r].sort((a, b) => (b.followersCount || 0) - (a.followersCount || 0));
            } else if (this.sortBy === 'name') {
                r = [...r].sort((a, b) => (a.nameAr || '').localeCompare(b.nameAr || '', 'ar'));
            }
            return r;
        },

        get totalFilteredPages() {
            return Math.max(1, Math.ceil(this.filteredReciters.length / this.perPage));
        },

        get paginatedReciters() {
            const s = (this.currentPage - 1) * this.perPage;
            return this.filteredReciters.slice(s, s + this.perPage);
        },

        get pageNumbers() {
            const tp = this.totalFilteredPages;
            const cp = this.currentPage;
            const maxV = 5;
            let start = Math.max(1, cp - Math.floor(maxV / 2));
            let end = Math.min(tp, start + maxV - 1);
            if (end - start < maxV - 1) { start = Math.max(1, end - maxV + 1); }
            const pages = [];
            for (let i = start; i <= end; i++) { pages.push(i); }
            return pages;
        },

        setViewMode(m) { this.viewMode = m; localStorage.setItem('recitersViewMode', m); },

        setPerPage(v) {
            this.perPage = v;
            this.currentPage = 1;
            localStorage.setItem('recitersPerPage', v);
        },

        goToPage(p) {
            if (p < 1 || p > this.totalFilteredPages) { return; }
            this.currentPage = p;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        playPreview(reciter) {
            if (this.currentAudio) { this.currentAudio.pause(); }
            if (this.playingReciterId === reciter.id) {
                this.playingReciterId = null;
                return;
            }
            this.playingReciterId = reciter.id;
            if (reciter.firstSurahAudioUrl) {
                this.currentAudio = new Audio(reciter.firstSurahAudioUrl);
                this.currentAudio.play().catch(() => { this.playingReciterId = null; });
                this.currentAudio.addEventListener('ended', () => { this.playingReciterId = null; });
            }
        },

        profileImageSrc(reciter) {
            return reciter.profileImage || null;
        },

        initials(reciter) {
            const name = reciter.nameAr || reciter.nameEn || '';
            return name.charAt(0) || '؟';
        }
    }"
    :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'"
    class="min-h-screen font-['Cairo'] transition-colors duration-300"
>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex mb-8 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
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
                    {{ __('quran.public.reciters') }}
                </span>
            </li>
        </ol>
    </nav>

    {{-- Hero --}}
    <section class="relative mb-12 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
        <div class="absolute inset-0 bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="relative px-8 py-16 md:py-20 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold font-['Amiri'] mb-4">{{ __('quran.public.reciters_hero_title') }}</h1>
            <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto mb-6">
                {{ __('quran.public.reciters_hero_subtitle') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                <div class="flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                    </svg>
                    <span>{{ $totalReciters }} {{ __('quran.public.reciter_unit') }}</span>
                </div>
                <div class="flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                    </svg>
                    <span>{{ __('quran.public.high_quality_recitations') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured reciters --}}
    @if(count($featuredReciters) > 0)
    <section class="mb-12" aria-label="{{ __('quran.public.featured_reciters') }}">
        <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center gap-3"
            :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
            <svg class="w-8 h-8 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            {{ __('quran.public.featured_reciters') }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <template x-for="reciter in featuredReciters" :key="reciter.id">
                <div class="reciter-card rounded-3xl p-8 text-center shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-3 animate-fadeInUp relative"
                     :class="$store.theme.dark
                         ? 'bg-gradient-to-br from-gray-900 to-gray-800'
                         : 'bg-gradient-to-br from-white to-gray-50'">
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-[#0056b3] to-[#218838] opacity-0 hover:opacity-10 transition-opacity duration-500 pointer-events-none"></div>

                    <div class="relative inline-block mb-5">
                        <div class="relative w-44 h-44 mx-auto">
                            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] opacity-20 blur-xl"></div>
                            <template x-if="profileImageSrc(reciter)">
                                <img :src="profileImageSrc(reciter)" :alt="reciter.nameAr || reciter.nameEn"
                                     class="reciter-image relative w-full h-full rounded-full object-cover shadow-2xl ring-4 ring-white/10"
                                     loading="lazy">
                            </template>
                            <template x-if="!profileImageSrc(reciter)">
                                <div class="relative w-full h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center shadow-2xl ring-4 ring-white/10">
                                    <span class="text-white text-5xl font-['Amiri']" x-text="initials(reciter)"></span>
                                </div>
                            </template>
                            <div x-show="playingReciterId === reciter.id"
                                 class="absolute -bottom-2 start-1/2 transform -translate-x-1/2 px-3 py-1 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                                <span class="w-1 h-1 bg-white rounded-full animate-pulse"></span>
                                {{ __('quran.public.now_playing') }}
                            </div>
                        </div>
                        <div class="featured-badge absolute -top-2 -end-2 px-3 py-1.5 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            {{ __('site.featured') }}
                        </div>
                    </div>

                    <h3 class="text-2xl font-bold mb-2 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent font-['Amiri']" x-text="reciter.nameAr || reciter.nameEn"></h3>
                    <p class="text-sm mb-4" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="reciter.nameEn"></p>

                    <div class="flex items-center justify-center gap-3 mb-5 flex-wrap">
                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5"
                              :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <svg class="w-3.5 h-3.5 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="reciter.country || '—'"></span>
                        </span>
                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5"
                              :class="$store.theme.dark ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            <svg class="w-3.5 h-3.5 text-[#218838]" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                            </svg>
                            <span x-text="(reciter.recitationsCount || 114) + ' {{ __('quran.public.recording_unit') }}'"></span>
                        </span>
                    </div>

                    <p class="text-sm mb-6 line-clamp-2 leading-relaxed"
                       :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
                       x-text="reciter.description || ''"></p>

                    <button
                        @click="playPreview(reciter)"
                        class="listening-button relative w-full py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-center hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2 overflow-hidden"
                    >
                        <svg class="w-5 h-5 relative z-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                        </svg>
                        <span class="relative z-10">{{ __('quran.public.listen_now') }}</span>
                    </button>
                </div>
            </template>
        </div>
    </section>
    @endif

    {{-- Search & filters --}}
    <section class="mb-8 space-y-4">
        <div class="relative">
            <input
                type="search"
                x-model="searchQuery"
                @input="currentPage = 1"
                placeholder="{{ __('quran.public.reciters_search_placeholder') }}"
                class="w-full px-6 py-4 text-base rounded-2xl shadow-lg transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                :class="$store.theme.dark ? 'bg-gray-900 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                aria-label="{{ __('quran.public.reciters_search_placeholder') }}"
            >
            <svg class="absolute end-6 top-1/2 transform -translate-y-1/2 w-6 h-6"
                 :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <button @click="filterStyle = 'all'; currentPage = 1"
                    class="px-6 py-2.5 rounded-full font-semibold transition-all transform hover:scale-105"
                    :class="filterStyle === 'all'
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                >{{ __('quran.public.filter_all') }}</button>
                <button @click="filterStyle = 'hafs'; currentPage = 1"
                    class="px-6 py-2.5 rounded-full font-semibold transition-all transform hover:scale-105"
                    :class="filterStyle === 'hafs'
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                >{{ __('quran.reciters.hafs') }}</button>
            </div>

            <div class="flex items-center gap-4 flex-wrap">
                <select x-model="sortBy"
                    class="px-4 py-2.5 text-base sm:text-sm rounded-xl font-medium transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                    :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                >
                    <option value="popular">{{ __('quran.public.sort_popular') }}</option>
                    <option value="name">{{ __('quran.public.sort_name') }}</option>
                </select>

                <select x-model.number="perPage" @change="setPerPage(perPage)"
                    class="px-4 py-2.5 text-base sm:text-sm rounded-xl font-medium transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                    :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                >
                    <option :value="6">6</option>
                    <option :value="12">12</option>
                    <option :value="24">24</option>
                    <option :value="allReciters.length">{{ __('site.all') }}</option>
                </select>

                <div class="flex items-center p-1 rounded-xl" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'">
                    <button @click="setViewMode('grid')"
                        class="p-2.5 rounded-lg transition-all"
                        :class="viewMode === 'grid'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="viewMode === 'grid'"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                    <button @click="setViewMode('list')"
                        class="p-2.5 rounded-lg transition-all"
                        :class="viewMode === 'list'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                            : ($store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                        :aria-pressed="viewMode === 'list'"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Results count --}}
    <div class="mb-6 flex items-center justify-between flex-wrap gap-2"
         :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
        <p class="text-sm">
            {{ __('site.showing') }}
            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="paginatedReciters.length"></span>
            {{ __('site.of') }}
            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="filteredReciters.length"></span>
            {{ __('quran.public.reciter_unit') }}
        </p>
        <p class="text-sm">
            {{ __('quran.public.page') }} <span class="font-semibold" x-text="currentPage"></span>
            {{ __('site.of') }} <span class="font-semibold" x-text="totalFilteredPages"></span>
        </p>
    </div>

    {{-- ============================================================ --}}
    {{-- GRID --}}
    {{-- ============================================================ --}}
    <div x-show="viewMode === 'grid'"
         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
        <template x-for="(reciter, index) in paginatedReciters" :key="reciter.id">
            <div class="reciter-card rounded-2xl p-6 text-center shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 animate-fadeInUp relative"
                 :class="[$store.theme.dark ? 'bg-gradient-to-br from-gray-900 to-gray-800' : 'bg-gradient-to-br from-white to-gray-50', 'stagger-' + ((index % 4) + 1)]">
                <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-[#0056b3] to-[#218838] opacity-0 hover:opacity-5 transition-opacity duration-500 pointer-events-none"></div>
                <div class="relative">
                    <div class="relative w-32 h-32 mx-auto mb-4">
                        <template x-if="profileImageSrc(reciter)">
                            <img :src="profileImageSrc(reciter)" :alt="reciter.nameAr || reciter.nameEn"
                                 class="reciter-image relative w-full h-full rounded-full object-cover shadow-lg ring-2 ring-white/10"
                                 loading="lazy">
                        </template>
                        <template x-if="!profileImageSrc(reciter)">
                            <div class="relative w-full h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg ring-2 ring-white/10">
                                <span class="text-white text-4xl font-['Amiri']" x-text="initials(reciter)"></span>
                            </div>
                        </template>
                        <div x-show="playingReciterId === reciter.id"
                             class="audio-waveform absolute -bottom-1 start-0 end-0 h-1 rounded-full"></div>
                    </div>

                    <div x-show="reciter.isFeatured"
                         class="absolute top-0 end-0 px-2 py-1 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold rounded-full shadow-lg">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>

                    <h3 class="text-lg font-bold mb-1 transition-colors font-['Amiri']"
                        :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                        x-text="reciter.nameAr || reciter.nameEn"></h3>
                    <p class="text-xs mb-3" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="reciter.nameEn"></p>

                    <div class="flex items-center justify-center gap-2 mb-4 flex-wrap">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1"
                              :class="$store.theme.dark ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                            <svg class="w-3 h-3 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            <span x-text="reciter.country || '—'" class="text-xs"></span>
                        </span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1"
                              :class="$store.theme.dark ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                            <svg class="w-3 h-3 text-[#218838]" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>
                            <span x-text="(reciter.recitationsCount || 114) + ' {{ __('quran.public.recording_unit') }}'" class="text-xs"></span>
                        </span>
                    </div>

                    <button @click="playPreview(reciter)"
                        class="listening-button relative w-full py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold text-sm hover:shadow-xl transition-all transform hover:scale-105 overflow-hidden flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 relative z-10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                        <span class="relative z-10">{{ __('quran.public.listen') }}</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- ============================================================ --}}
    {{-- LIST --}}
    {{-- ============================================================ --}}
    <div x-show="viewMode === 'list'" class="space-y-4 mb-12">
        <template x-for="(reciter, index) in paginatedReciters" :key="reciter.id">
            <div class="reciter-card flex items-center gap-6 p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-1 animate-fadeInUp relative flex-wrap sm:flex-nowrap"
                 :class="[$store.theme.dark ? 'bg-gradient-to-r from-gray-900 to-gray-800 hover:from-gray-800 hover:to-gray-700' : 'bg-gradient-to-r from-white to-gray-50 hover:from-gray-50 hover:to-white', 'stagger-' + ((index % 4) + 1)]">
                <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-[#0056b3] to-[#218838] opacity-0 hover:opacity-5 transition-opacity duration-500 pointer-events-none"></div>

                <div class="flex-shrink-0">
                    <div class="relative w-24 h-24">
                        <template x-if="profileImageSrc(reciter)">
                            <img :src="profileImageSrc(reciter)" :alt="reciter.nameAr || reciter.nameEn"
                                 class="reciter-image w-full h-full rounded-full object-cover shadow-lg ring-2 ring-white/10"
                                 loading="lazy">
                        </template>
                        <template x-if="!profileImageSrc(reciter)">
                            <div class="w-full h-full rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg ring-2 ring-white/10">
                                <span class="text-white text-3xl font-['Amiri']" x-text="initials(reciter)"></span>
                            </div>
                        </template>
                        <div x-show="playingReciterId === reciter.id"
                             class="audio-waveform absolute -bottom-1 start-0 end-0 h-1.5 rounded-full"></div>
                    </div>
                </div>

                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="text-xl font-bold font-['Amiri'] transition-colors"
                            :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                            x-text="reciter.nameAr || reciter.nameEn"></h3>
                        <span x-show="reciter.isFeatured"
                              class="featured-badge px-3 py-1 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            {{ __('site.featured') }}
                        </span>
                        <span x-show="playingReciterId === reciter.id"
                              class="px-2.5 py-1 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1 animate-pulse">
                            <span class="w-1.5 h-1.5 bg-white rounded-full"></span>
                            {{ __('quran.public.now_playing') }}
                        </span>
                    </div>
                    <p class="text-sm mb-2" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'" x-text="reciter.nameEn"></p>
                    <p class="text-sm mb-3 line-clamp-2 leading-relaxed"
                       :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'"
                       x-text="reciter.description || ''"></p>
                    <div class="flex items-center flex-wrap gap-3">
                        <span class="px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-1.5"
                              :class="$store.theme.dark ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                            <svg class="w-3.5 h-3.5 text-[#0056b3]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            <span x-text="reciter.country || '—'"></span>
                        </span>
                        <span class="px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-1.5"
                              :class="$store.theme.dark ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                            <svg class="w-3.5 h-3.5 text-[#218838]" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>
                            <span x-text="(reciter.recitationsCount || 114) + ' {{ __('quran.public.recording_unit') }}'"></span>
                        </span>
                    </div>
                </div>

                <div class="flex-shrink-0 w-full sm:w-auto">
                    <button @click="playPreview(reciter)"
                        class="listening-button w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold hover:shadow-2xl transition-all transform hover:scale-110 flex items-center justify-center gap-2 overflow-hidden">
                        <svg class="w-5 h-5 relative z-10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                        <span class="relative z-10">{{ __('quran.public.listen') }}</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredReciters.length === 0" x-cloak class="text-center py-20">
        <i data-lucide="search-x" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
        <h3 class="text-2xl font-bold mb-2" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('site.no_results') }}</h3>
        <button @click="searchQuery = ''; filterStyle = 'all'"
            class="mt-4 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:shadow-lg transition-all">
            {{ __('site.reset_filters') }}
        </button>
    </div>

    {{-- Pagination --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-8">
        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
            class="px-6 py-3 rounded-xl font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            :class="currentPage === 1
                ? ($store.theme.dark ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400')
                : 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg transform hover:scale-105'"
        >
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
            {{ __('quran.public.prev_page') }}
        </button>

        <div class="flex items-center gap-2 flex-wrap justify-center">
            <template x-for="p in pageNumbers" :key="p">
                <button @click="goToPage(p)"
                    class="w-10 h-10 rounded-xl font-semibold transition-all"
                    :class="currentPage === p
                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                        : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                    :aria-current="currentPage === p ? 'page' : null"
                    x-text="p"
                ></button>
            </template>
        </div>

        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalFilteredPages"
            class="px-6 py-3 rounded-xl font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            :class="currentPage === totalFilteredPages
                ? ($store.theme.dark ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400')
                : 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg transform hover:scale-105'"
        >
            {{ __('quran.public.next_page') }}
            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>

</main>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
