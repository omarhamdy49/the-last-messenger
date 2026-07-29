@extends('layouts.public')

@php
    $localeNames = [
        'ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو',
    ];
    $translationCodes = array_keys($hadith['translations']);
    $defaultLang = in_array('ar', $translationCodes, true) ? 'ar' : ($translationCodes[0] ?? 'ar');

    // Build JS-safe translations object from real controller data.
    $jsTranslations = [];
    foreach ($hadith['translations'] as $code => $entry) {
        $jsTranslations[$code] = [
            'text' => $entry['text'] ?? ($entry['translation'] ?? ''),
            'title' => $localeNames[$code] ?? strtoupper($code),
        ];
    }

    $metaTitle = trim(($hadith['collectionName'] ?? '') . ' - ' . __('hadith.public.single_number') . ' ' . $hadith['number']);
@endphp

@section('title', $metaTitle)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($hadith['arabicText'] ?? $metaTitle), 155))

@push('head')
<style>
    [x-cloak] { display: none !important; }

    .font-amiri { font-family: 'Amiri', serif; }

    .arabic-text {
        font-family: 'Amiri', serif;
        line-height: 2.4;
    }

    /* Subtle Islamic geometric backdrop */
    .lib-pattern {
        background-image:
            radial-gradient(circle at 12% 18%, rgba(0, 86, 179, 0.05) 0%, transparent 45%),
            radial-gradient(circle at 88% 82%, rgba(33, 136, 56, 0.05) 0%, transparent 45%);
    }

    .hero-pattern {
        background-image: url("data:image/svg+xml,%3Csvg width='52' height='52' viewBox='0 0 52 52' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M26 0l6 14 14 6-14 6-6 14-6-14-14-6 14-6z' fill='%23ffffff' fill-opacity='0.06'/%3E%3C/svg%3E");
    }

    /* Premium card hover lift */
    .lib-card {
        transition: transform .28s cubic-bezier(.4,0,.2,1), box-shadow .28s cubic-bezier(.4,0,.2,1), border-color .28s ease;
    }
    .lib-card:hover { transform: translateY(-4px); }

    /* Narrator timeline line — logical direction */
    .narrator-line {
        position: absolute;
        top: 2.5rem;
        bottom: 2.5rem;
        inset-inline-end: 1.25rem;
        width: 2px;
        background: linear-gradient(to bottom, #0056b3, #218838);
    }

    /* Authenticity badge pulse */
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(0, 86, 179, 0.3); }
        50%       { box-shadow: 0 0 32px rgba(0, 86, 179, 0.55); }
    }
    .pulse-glow { animation: pulse-glow 2s infinite; }

    @keyframes lib-fade-up {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .lib-fade-up { animation: lib-fade-up .5s ease-out both; }

    /* Custom scrollbar */
    .lib-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .lib-scroll::-webkit-scrollbar-track { background: transparent; }
    .lib-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #0056b3, #218838);
        border-radius: 9999px;
    }

    /* Print */
    @media print {
        header, footer, aside, .no-print { display: none !important; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lib-card, .lib-fade-up, .pulse-glow {
            transition: none !important;
            animation: none !important;
        }
        .lib-card:hover { transform: none; }
    }
</style>
@endpush

@section('content')

<div
    x-data="{
        activeLanguage: '{{ $defaultLang }}',
        showNarratorDetails: false,
        selectedNarrator: null,
        bookmarked: false,
        showShareModal: false,
        fontSize: 100,
        copied: false,

        translations: @js($jsTranslations),
        narrators: @js($hadith['narrators'] ?? []),

        init() {
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },

        increaseFontSize() { if (this.fontSize < 150) this.fontSize += 10; },
        decreaseFontSize() { if (this.fontSize > 80)  this.fontSize -= 10; },

        setLanguage(lang) { this.activeLanguage = lang; },

        toggleBookmark() { this.bookmarked = !this.bookmarked; },

        shareOnTwitter() {
            const text = (this.translations['{{ $defaultLang }}']?.text || '').substring(0, 100) + '...';
            window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(text + ' - خاتَمُ المُرْسَلِين'), '_blank');
        },
        shareOnFacebook() {
            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank');
        },
        shareOnWhatsApp() {
            const text = this.translations['{{ $defaultLang }}']?.text || '';
            window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
        },
        copyHadithText() {
            navigator.clipboard.writeText(this.translations[this.activeLanguage]?.text || '');
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        },

        showNarratorInfo(narrator) {
            this.selectedNarrator = narrator;
            this.showNarratorDetails = true;
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        }
    }"
    x-init="init()"
    class="bg-[#F8F9FA] dark:bg-[#12152b] text-gray-900 dark:text-gray-100 min-h-screen"
>

    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-[#0056b3] to-[#218838]">
        <div class="absolute inset-0 hero-pattern opacity-60"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>

        <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
            {{-- Breadcrumb --}}
            <nav aria-label="{{ __('hadith.public.breadcrumb') }}" class="mb-6">
                <ol class="inline-flex flex-wrap items-center gap-1.5 text-sm text-white/75">
                    <li>
                        <a href="{{ url('/') }}" class="hover:text-white transition-colors">{{ __('site.home') }}</a>
                    </li>
                    <li aria-hidden="true"><i data-lucide="chevron-left" class="w-3.5 h-3.5 ltr:rotate-180"></i></li>
                    <li>
                        <a href="{{ route('public.hadith') }}" class="hover:text-white transition-colors">{{ __('hadith.public.breadcrumb') }}</a>
                    </li>
                    <li aria-hidden="true"><i data-lucide="chevron-left" class="w-3.5 h-3.5 ltr:rotate-180"></i></li>
                    <li class="text-white font-semibold">
                        {{ __('hadith.public.single_number') }} {{ $hadith['number'] }}
                    </li>
                </ol>
            </nav>

            <div class="max-w-3xl lib-fade-up">
                {{-- Grading badge --}}
                <div class="flex flex-wrap items-center gap-2.5 mb-5">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold bg-white/20 backdrop-blur-sm ring-1 ring-white/30 text-white pulse-glow">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        {{ $hadith['grading']['label'] }}
                    </span>
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-white/15 backdrop-blur-sm ring-1 ring-white/25 text-white">
                        <i data-lucide="book-marked" class="w-3.5 h-3.5"></i>
                        {{ $hadith['collectionName'] }}
                    </span>
                    @if ($hadith['gradingAgreement'])
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-amber-400/90 text-amber-950">
                            <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                            {{ __('hadith.public.grading_agreement') }}
                        </span>
                    @endif
                </div>

                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white font-amiri leading-tight">
                    {{ $hadith['bookTitle'] ?: $hadith['collectionName'] }}
                </h1>
                <p class="mt-2 text-base text-white/80">
                    {{ __('hadith.public.single_number') }} <span class="font-bold tabular-nums">{{ $hadith['number'] }}</span>
                    @if ($hadith['bookTitle'])
                        &mdash; {{ $hadith['bookTitle'] }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    {{-- ===================== BODY ===================== --}}
    <div class="lib-pattern">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">

                {{-- ---- MAIN COLUMN ---- --}}
                <div class="lg:col-span-8 xl:col-span-9 space-y-8">

                    {{-- ===================== HADITH CARD ===================== --}}
                    <article class="lib-card bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden lib-fade-up">

                        {{-- Action toolbar --}}
                        <div class="flex flex-wrap items-center gap-2.5 px-5 sm:px-7 py-4 border-b border-gray-100 dark:border-white/10 no-print">
                            {{-- Language tabs --}}
                            <div class="flex flex-wrap gap-1.5 flex-1 min-w-0">
                                @foreach ($translationCodes as $code)
                                    <button
                                        @click="setLanguage('{{ $code }}')"
                                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                        :class="activeLanguage === '{{ $code }}'
                                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                                            : 'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20'"
                                        :aria-pressed="activeLanguage === '{{ $code }}'"
                                    >
                                        {{ $localeNames[$code] ?? strtoupper($code) }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Font size --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline">{{ __('hadith.public.single_font_size') }}</span>
                                <button @click="decreaseFontSize()" :disabled="fontSize <= 80"
                                    class="w-7 h-7 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                    :aria-label="'{{ __('hadith.public.single_font_decrease') }}'">
                                    <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="text-xs tabular-nums font-semibold text-gray-600 dark:text-gray-300 w-9 text-center" x-text="fontSize + '%'"></span>
                                <button @click="increaseFontSize()" :disabled="fontSize >= 150"
                                    class="w-7 h-7 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                    :aria-label="'{{ __('hadith.public.single_font_increase') }}'">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Hadith text body --}}
                        <div class="px-5 sm:px-7 py-8">
                            <div class="p-6 sm:p-8 rounded-2xl bg-gradient-to-br from-blue-50/70 to-green-50/70 dark:from-white/5 dark:to-white/3 border border-blue-100/80 dark:border-white/10"
                                 :style="`font-size: ${fontSize}%`">
                                <blockquote class="arabic-text text-center text-gray-900 dark:text-gray-100 break-words" dir="rtl">
                                    &ldquo;<span x-text="translations[activeLanguage]?.text"></span>&rdquo;
                                </blockquote>
                                <div class="mt-6 pt-4 border-t border-gray-200/80 dark:border-white/10 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-amiri">
                                        {{ __('hadith.public.single_source_label') }}
                                        <strong class="text-gray-800 dark:text-gray-200">{{ $hadith['collectionName'] }}</strong>
                                        ({{ $hadith['number'] }})
                                    </span>
                                    @if (count($hadith['themes'] ?? []) > 0)
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($hadith['themes'] as $theme)
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#64CCC5]/15 text-[#0d7d75] dark:text-[#64CCC5]">
                                                    {{ is_array($theme) ? ($theme['name'] ?? $theme['nameAr'] ?? '') : $theme }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex flex-wrap items-center gap-2.5 px-5 sm:px-7 pb-6 no-print">
                            {{-- Bookmark --}}
                            <button @click="toggleBookmark()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                :class="bookmarked
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                    : 'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20'"
                                :aria-pressed="bookmarked">
                                <i data-lucide="bookmark" class="w-4 h-4" :class="bookmarked ? 'fill-current' : ''"></i>
                                <span>{{ __('hadith.public.single_bookmark') }}</span>
                            </button>

                            {{-- Share --}}
                            <button @click="showShareModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                <i data-lucide="share-2" class="w-4 h-4"></i>
                                <span>{{ __('hadith.public.single_share') }}</span>
                            </button>

                            {{-- Copy --}}
                            <button @click="copyHadithText()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                <i :data-lucide="copied ? 'check' : 'copy'" class="w-4 h-4" :class="copied ? 'text-[#218838]' : ''"></i>
                                <span x-text="copied ? '{{ __('hadith.public.single_copied') }}' : '{{ __('hadith.public.single_copy') }}'"></span>
                            </button>

                            {{-- Print --}}
                            <button onclick="window.print()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                                <span>{{ __('hadith.public.single_print') }}</span>
                            </button>

                            @if (($hadith['parallelsCount'] ?? 0) > 0)
                                {{-- Compare narrations --}}
                                <a href="{{ route('public.hadith.narrations', $hadith['id']) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md hover:shadow-lg transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                    <i data-lucide="git-compare" class="w-4 h-4"></i>
                                    <span>{{ __('hadith.public.narrations_compare_link', ['count' => $hadith['parallelsCount']]) }}</span>
                                </a>
                            @endif
                        </div>
                    </article>

                    {{-- ===================== SIGNIFICANCE / CONTEXT ===================== --}}
                    @if (! empty($hadith['significance']) || ! empty($hadith['historicalContext']))
                        <div class="space-y-4" x-data="{ showSignificance: true, showContext: false }">

                            @if (! empty($hadith['significance']))
                                <div class="lib-card bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                                    <button
                                        @click="showSignificance = !showSignificance"
                                        class="w-full flex items-center justify-between gap-3 px-5 sm:px-7 py-4 hover:bg-gray-50/70 dark:hover:bg-white/5 transition-colors focus:outline-none focus-visible:ring-inset focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                        :aria-expanded="showSignificance">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shrink-0 shadow-sm shadow-blue-500/20">
                                                <i data-lucide="book-open" class="w-4 h-4 text-white"></i>
                                            </span>
                                            <div class="text-start min-w-0">
                                                <h2 class="text-base font-bold truncate">{{ __('hadith.public.single_significance_heading') }}</h2>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('hadith.public.single_significance_subtitle') }}</p>
                                            </div>
                                        </div>
                                        <i :data-lucide="showSignificance ? 'chevron-up' : 'chevron-down'" class="w-5 h-5 text-gray-400 shrink-0"></i>
                                    </button>
                                    <div x-show="showSignificance" x-collapse>
                                        <div class="px-5 sm:px-7 pb-6 pt-2 text-gray-700 dark:text-gray-300 leading-relaxed break-words">
                                            {{ $hadith['significance'] }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if (! empty($hadith['historicalContext']))
                                <div class="lib-card bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden">
                                    <button
                                        @click="showContext = !showContext"
                                        class="w-full flex items-center justify-between gap-3 px-5 sm:px-7 py-4 hover:bg-gray-50/70 dark:hover:bg-white/5 transition-colors focus:outline-none focus-visible:ring-inset focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                        :aria-expanded="showContext">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shrink-0 shadow-sm shadow-amber-500/20">
                                                <i data-lucide="scroll-text" class="w-4 h-4 text-white"></i>
                                            </span>
                                            <div class="text-start min-w-0">
                                                <h2 class="text-base font-bold truncate">{{ __('hadith.public.single_context_heading') }}</h2>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('hadith.public.single_context_subtitle') }}</p>
                                            </div>
                                        </div>
                                        <i :data-lucide="showContext ? 'chevron-up' : 'chevron-down'" class="w-5 h-5 text-gray-400 shrink-0"></i>
                                    </button>
                                    <div x-show="showContext" x-collapse>
                                        <div class="px-5 sm:px-7 pb-6 pt-2 text-gray-700 dark:text-gray-300 leading-relaxed break-words">
                                            {{ $hadith['historicalContext'] }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- ===================== SCHOLARLY COMMENTARIES ===================== --}}
                    @if (count($hadith['commentaryGroups'] ?? []) > 0)
                        <section>
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg shadow-blue-500/15 shrink-0">
                                    <i data-lucide="library" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-bold font-amiri leading-tight">{{ __('hadith.public.single_commentaries_heading') }}</h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ __('hadith.public.single_commentaries_subtitle') }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @foreach ($hadith['commentaryGroups'] as $group)
                                    <div class="lib-card bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm overflow-hidden"
                                         x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                                        <button
                                            @click="open = !open"
                                            class="w-full flex items-center justify-between gap-3 px-5 sm:px-7 py-4 hover:bg-gray-50/70 dark:hover:bg-white/5 transition-colors focus:outline-none focus-visible:ring-inset focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                            :aria-expanded="open">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <i data-lucide="book-open-text" class="w-5 h-5 text-[#64CCC5] shrink-0"></i>
                                                <div class="text-start min-w-0">
                                                    <h3 class="text-base font-bold truncate">{{ $group['author'] }}</h3>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('hadith.public.single_commentaries_subtitle') }}</p>
                                                </div>
                                            </div>
                                            <i :data-lucide="open ? 'chevron-up' : 'chevron-down'" class="w-5 h-5 text-gray-400 shrink-0"></i>
                                        </button>
                                        <div x-show="open" x-collapse>
                                            <div class="px-5 sm:px-7 pb-6 space-y-4">
                                                @foreach ($group['items'] as $commentary)
                                                    <div class="pt-4 first:pt-0 border-t first:border-t-0 border-gray-100 dark:border-white/10">
                                                        <p class="leading-relaxed break-words text-gray-700 dark:text-gray-300"
                                                           @if (in_array($commentary['languageCode'] ?? '', ['ar', 'ur'], true)) dir="rtl" @endif>
                                                            {{ $commentary['text'] }}
                                                        </p>
                                                        @if (! empty($commentary['verified']))
                                                            <span class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-[#218838] dark:text-emerald-400">
                                                                <i data-lucide="badge-check" class="w-3.5 h-3.5"></i>
                                                                {{ __('hadith.public.single_verified') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- ===================== RELATED HADITHS ===================== --}}
                    @if (count($related) > 0)
                        <section class="mt-2">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#64CCC5] to-[#218838] flex items-center justify-center shadow-lg shadow-teal-500/15 shrink-0">
                                    <i data-lucide="link" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-bold font-amiri leading-tight">{{ __('hadith.public.single_related_heading') }}</h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ __('hadith.public.single_related_subtitle') }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($related as $item)
                                    <a href="{{ route('public.hadith.show', $item['id']) }}"
                                       class="lib-card group flex items-start gap-4 p-5 rounded-2xl bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white shrink-0 group-hover:scale-110 transition-transform">
                                            <i data-lucide="scroll-text" class="w-5 h-5"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-sm group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors mb-1.5">
                                                {{ __('hadith.public.single_number') }} <span class="tabular-nums">{{ $item['number'] }}</span>
                                                @if ($item['collectionName'])
                                                    &mdash; {{ $item['collectionName'] }}
                                                @endif
                                            </h3>
                                            @if ($item['text'])
                                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 break-words font-amiri leading-relaxed" dir="rtl">
                                                    {{ \Illuminate\Support\Str::limit($item['text'], 120) }}
                                                </p>
                                            @endif
                                            <div class="flex items-center gap-1.5 mt-2 text-xs text-[#64CCC5]">
                                                <i data-lucide="shield-check" class="w-3.5 h-3.5 shrink-0"></i>
                                                <span class="truncate">{{ $item['grading']['label'] }}</span>
                                            </div>
                                        </div>
                                        <i data-lucide="arrow-left" class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-[#64CCC5] transition-colors shrink-0 mt-0.5 ltr:rotate-180"></i>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                </div>{{-- /MAIN COLUMN --}}

                {{-- ---- SIDEBAR ---- --}}
                <aside class="lg:col-span-4 xl:col-span-3 mt-8 lg:mt-0">
                    <div class="sticky top-24 space-y-5">

                        {{-- Narrator chain --}}
                        <template x-if="narrators.length > 0">
                            <div class="bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm p-5">
                                <h3 class="text-base font-bold flex items-center gap-2 mb-5">
                                    <i data-lucide="users" class="w-5 h-5 text-[#64CCC5]"></i>
                                    {{ __('hadith.public.single_isnad_heading') }}
                                </h3>

                                <div class="relative">
                                    <div class="narrator-line"></div>
                                    <div class="space-y-5">
                                        <template x-for="(narrator, index) in narrators" :key="narrator.id ?? index">
                                            <button
                                                @click="showNarratorInfo(narrator)"
                                                class="relative w-full flex items-center gap-3 text-start hover:bg-gray-50 dark:hover:bg-white/5 rounded-xl p-2 -mx-2 transition-colors group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white shrink-0 ring-2 ring-white dark:ring-[#1b1f3b] transition-transform group-hover:scale-110 z-10"
                                                     :class="narrator.type === 'companion'
                                                         ? 'bg-gradient-to-br from-amber-400 to-amber-600'
                                                         : narrator.type === 'compiler'
                                                             ? 'bg-gradient-to-br from-[#0056b3] to-[#218838]'
                                                             : 'bg-gradient-to-br from-gray-400 to-gray-600'">
                                                    <i :data-lucide="narrator.type === 'companion' ? 'star' : narrator.type === 'compiler' ? 'shield-check' : 'user'" class="w-4 h-4"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-bold truncate" x-text="narrator.name"></p>
                                                    <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                                        <template x-if="narrator.reliability">
                                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-400 truncate" x-text="narrator.reliability"></span>
                                                        </template>
                                                        <template x-if="narrator.deathYearHijri">
                                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 tabular-nums">
                                                                <span x-text="narrator.deathYearHijri"></span> {{ __('hadith.public.single_hijri_suffix') }}
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <i data-lucide="chevron-left" class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-[#64CCC5] transition-colors shrink-0 ltr:rotate-180"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Hadith info --}}
                        <div class="bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm p-5">
                            <h3 class="text-base font-bold flex items-center gap-2 mb-4">
                                <i data-lucide="info" class="w-5 h-5 text-[#64CCC5]"></i>
                                {{ __('hadith.public.single_info_heading') }}
                            </h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('hadith.public.single_info_collection') }}</dt>
                                    <dd class="font-semibold text-end">{{ $hadith['collectionName'] }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-2">
                                    <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('hadith.public.single_info_number') }}</dt>
                                    <dd class="font-semibold tabular-nums">{{ $hadith['number'] }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-2">
                                    <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('hadith.public.single_info_grading') }}</dt>
                                    <dd class="font-semibold text-[#64CCC5]">{{ $hadith['grading']['label'] }}</dd>
                                </div>
                                @if ($hadith['bookTitle'])
                                    <div class="flex items-start justify-between gap-2">
                                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('hadith.public.single_info_book') }}</dt>
                                        <dd class="font-semibold text-end">{{ $hadith['bookTitle'] }}</dd>
                                    </div>
                                @endif
                                @if ($hadith['gradingAgreement'])
                                    <div class="flex items-center gap-2 pt-1">
                                        <i data-lucide="check-check" class="w-4 h-4 text-[#218838]"></i>
                                        <span class="font-semibold text-[#218838] dark:text-emerald-400">{{ __('hadith.public.grading_agreement') }}</span>
                                    </div>
                                @endif
                            </dl>

                            @if (($hadith['parallelsCount'] ?? 0) > 0)
                                <a href="{{ route('public.hadith.narrations', $hadith['id']) }}"
                                   class="mt-4 flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                    <i data-lucide="git-compare" class="w-4 h-4"></i>
                                    {{ __('hadith.public.narrations_compare_link', ['count' => $hadith['parallelsCount']]) }}
                                </a>
                            @endif
                        </div>

                        {{-- Scholar gradings --}}
                        @if (count($hadith['scholarGradings'] ?? []) > 0)
                            <div class="bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm p-5">
                                <h3 class="text-base font-bold flex items-center gap-2 mb-4">
                                    <i data-lucide="scale" class="w-5 h-5 text-[#64CCC5]"></i>
                                    {{ __('hadith.public.single_gradings_heading') }}
                                </h3>
                                <ul class="space-y-2.5">
                                    @foreach (array_slice($hadith['scholarGradings'], 0, 6) as $grading)
                                        <li class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-[55%]">
                                                {{ $grading['scholar'] ?: __('hadith.public.single_commentary_unknown_author') }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#64CCC5]/15 text-[#0d7d75] dark:text-[#64CCC5]">
                                                {{ $grading['grade'] }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Share widget --}}
                        <div class="bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl shadow-sm p-5">
                            <h3 class="text-base font-bold flex items-center gap-2 mb-4">
                                <i data-lucide="share-2" class="w-5 h-5 text-[#64CCC5]"></i>
                                {{ __('hadith.public.single_share_heading') }}
                            </h3>
                            <div class="grid grid-cols-2 gap-2.5">
                                <button @click="shareOnTwitter()"
                                    class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-[#1DA1F2] text-white text-sm font-medium hover:shadow-md hover:scale-[1.03] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1DA1F2]">
                                    <i data-lucide="twitter" class="w-4 h-4"></i>
                                    <span>{{ __('hadith.public.single_share_twitter') }}</span>
                                </button>
                                <button @click="shareOnFacebook()"
                                    class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-[#1877F2] text-white text-sm font-medium hover:shadow-md hover:scale-[1.03] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1877F2]">
                                    <i data-lucide="facebook" class="w-4 h-4"></i>
                                    <span>{{ __('hadith.public.single_share_facebook') }}</span>
                                </button>
                                <button @click="shareOnWhatsApp()"
                                    class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-[#25D366] text-white text-sm font-medium hover:shadow-md hover:scale-[1.03] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#25D366]">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    <span>{{ __('hadith.public.single_share_whatsapp') }}</span>
                                </button>
                                <button @click="copyHadithText()"
                                    class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium hover:shadow-md hover:scale-[1.03] transition bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                                    <i :data-lucide="copied ? 'check' : 'copy'" class="w-4 h-4" :class="copied ? 'text-[#218838]' : ''"></i>
                                    <span x-text="copied ? '{{ __('hadith.public.single_copied') }}' : '{{ __('hadith.public.single_copy') }}'"></span>
                                </button>
                            </div>
                        </div>

                    </div>
                </aside>

            </div>
        </div>
    </div>

    {{-- ===================== SHARE MODAL ===================== --}}
    <div
        x-show="showShareModal"
        x-cloak
        @keydown.escape.window="showShareModal = false"
        @click.self="showShareModal = false"
        class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 sm:p-6 bg-black/70 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="share-modal-title">
        <div
            class="bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl max-h-[80vh] overflow-y-auto lib-scroll"
            @click.stop>
            <div class="flex items-center justify-between mb-5">
                <h3 id="share-modal-title" class="text-lg font-bold">{{ __('hadith.public.single_share_heading') }}</h3>
                <button @click="showShareModal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500 dark:text-gray-400 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                    :aria-label="'{{ __('hadith.public.single_modal_close') }}'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button @click="shareOnTwitter()"
                    class="flex flex-col items-center gap-2.5 p-5 rounded-xl bg-[#1DA1F2] text-white hover:shadow-lg hover:scale-[1.04] transition">
                    <i data-lucide="twitter" class="w-7 h-7"></i>
                    <span class="text-sm font-medium">{{ __('hadith.public.single_share_twitter') }}</span>
                </button>
                <button @click="shareOnFacebook()"
                    class="flex flex-col items-center gap-2.5 p-5 rounded-xl bg-[#1877F2] text-white hover:shadow-lg hover:scale-[1.04] transition">
                    <i data-lucide="facebook" class="w-7 h-7"></i>
                    <span class="text-sm font-medium">{{ __('hadith.public.single_share_facebook') }}</span>
                </button>
                <button @click="shareOnWhatsApp()"
                    class="flex flex-col items-center gap-2.5 p-5 rounded-xl bg-[#25D366] text-white hover:shadow-lg hover:scale-[1.04] transition">
                    <i data-lucide="message-circle" class="w-7 h-7"></i>
                    <span class="text-sm font-medium">{{ __('hadith.public.single_share_whatsapp') }}</span>
                </button>
                <button @click="copyHadithText(); showShareModal = false"
                    class="flex flex-col items-center gap-2.5 p-5 rounded-xl bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/20 hover:shadow-lg hover:scale-[1.04] transition">
                    <i :data-lucide="copied ? 'check' : 'copy'" class="w-7 h-7" :class="copied ? 'text-[#218838]' : ''"></i>
                    <span class="text-sm font-medium" x-text="copied ? '{{ __('hadith.public.single_copied') }}' : '{{ __('hadith.public.single_copy') }}'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===================== NARRATOR DETAIL MODAL ===================== --}}
    <div
        x-show="showNarratorDetails"
        x-cloak
        @keydown.escape.window="showNarratorDetails = false"
        @click.self="showNarratorDetails = false"
        class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 sm:p-6 bg-black/70 backdrop-blur-sm"
        role="dialog" aria-modal="true" :aria-labelledby="selectedNarrator ? 'narrator-modal-title' : undefined">
        <div
            class="bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 rounded-2xl p-6 w-full max-w-lg shadow-2xl max-h-[80vh] overflow-y-auto lib-scroll"
            @click.stop>
            <template x-if="selectedNarrator">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-5">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0"
                                 :class="selectedNarrator.type === 'companion'
                                     ? 'bg-gradient-to-br from-amber-400 to-amber-600'
                                     : selectedNarrator.type === 'compiler'
                                         ? 'bg-gradient-to-br from-[#0056b3] to-[#218838]'
                                         : 'bg-gradient-to-br from-gray-400 to-gray-600'">
                                <i :data-lucide="selectedNarrator.type === 'companion' ? 'star' : selectedNarrator.type === 'compiler' ? 'shield-check' : 'user'" class="w-5 h-5"></i>
                            </div>
                            <h3 id="narrator-modal-title" class="text-lg font-bold truncate" x-text="selectedNarrator.name"></h3>
                        </div>
                        <button @click="showNarratorDetails = false"
                            class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-white/10 text-gray-500 dark:text-gray-400 transition shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                            :aria-label="'{{ __('hadith.public.single_modal_close') }}'">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-if="selectedNarrator.kunya">
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-white/5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('hadith.public.single_narrator_kunya') }}</p>
                                <p class="font-semibold" x-text="selectedNarrator.kunya"></p>
                            </div>
                        </template>
                        <template x-if="selectedNarrator.reliability">
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-white/5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">{{ __('hadith.public.single_narrator_reliability') }}</p>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white" x-text="selectedNarrator.reliability"></span>
                            </div>
                        </template>
                        <template x-if="selectedNarrator.deathYearHijri">
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-white/5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('hadith.public.single_narrator_death') }}</p>
                                <p class="font-semibold tabular-nums">
                                    <span x-text="selectedNarrator.deathYearHijri"></span> {{ __('hadith.public.single_hijri_suffix') }}
                                </p>
                            </div>
                        </template>
                        <template x-if="selectedNarrator.bio">
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-white/5">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">{{ __('hadith.public.single_narrator_bio') }}</p>
                                <p class="leading-relaxed break-words text-gray-700 dark:text-gray-300" x-text="selectedNarrator.bio"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endpush

@endsection
