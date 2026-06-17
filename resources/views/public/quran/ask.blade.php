@extends('layouts.public')

@section('title', __('quran.public.ask_page_title'))
@section('meta_description', __('quran.public.ask_ai_subtitle'))
{{-- Focused, ChatGPT-style full-screen app shell: suppress the site header, footer AND
     the shared recitation player (the chat shell shouldn't carry an audio bar). --}}
@section('hide_header', 'true')
@section('hide_footer', 'true')
@section('hide_player', 'true')

@push('scripts')
<script>
    {{-- Terminate any persisted recitation so it neither shows nor resumes on this page. --}}
    try {
        localStorage.removeItem('sitePlayerTrack');
        document.body.classList.remove('site-has-audio');
    } catch (e) { /* ignore */ }
</script>
@endpush

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur']);
@endphp

@push('head')
<style>
    .ask-fade { animation: askFadeUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) both; }
    @keyframes askFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) { .ask-fade { animation: none; } }
    .ask-skeleton { background: linear-gradient(90deg, rgba(148,163,184,0.10) 25%, rgba(148,163,184,0.20) 37%, rgba(148,163,184,0.10) 63%); background-size: 400% 100%; animation: askShimmer 1.4s ease infinite; }
    @keyframes askShimmer { 0% { background-position: 100% 0; } 100% { background-position: 0 0; } }
    .qa-caret::after { content: '▍'; margin-inline-start: 1px; color: #64CCC5; animation: qaBlink 1s steps(2) infinite; }
    @keyframes qaBlink { 0%, 50% { opacity: 1; } 50.01%, 100% { opacity: 0; } }
    @media (prefers-reduced-motion: reduce) { .qa-caret::after { animation: none; } }
    .ask-hist-active { position: relative; }
    .ask-hist-active::before { content: ''; position: absolute; inset-inline-start: 0; top: 8px; bottom: 8px; width: 3px; border-radius: 9999px; background: linear-gradient(180deg, #0056b3, #218838); }
    .ask-thin-scroll::-webkit-scrollbar { width: 6px; }
    .ask-thin-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 9999px; }
    .ask-thin-scroll { scrollbar-width: thin; }
</style>
@endpush

@section('content')
@include('components.quran.answer-renderer')

<div x-data="quranAsk()" x-init="boot()"
     class="h-dvh flex flex-col overflow-hidden"
     :class="$store.theme.dark ? 'bg-[#0b0f1a] text-gray-100' : 'bg-[#F8F9FA] text-gray-900'">

    {{-- ============================ Top bar (wide / full width) ============================ --}}
    <header class="shrink-0 h-14 sm:h-16 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 border-b z-30 backdrop-blur-md"
            :class="$store.theme.dark ? 'bg-[#0b0f1a]/90 border-white/10' : 'bg-white/95 border-gray-200'">

        {{-- Sidebar collapse / expand --}}
        @auth
        <button type="button" @click="toggleSidebar()"
                class="p-2 rounded-lg transition-colors shrink-0"
                :class="$store.theme.dark ? 'text-gray-300 hover:bg-white/10' : 'text-gray-600 hover:bg-gray-100'"
                :aria-expanded="sidebarOpen.toString()" aria-label="{{ __('quran.public.ask_history_title') }}">
            <i data-lucide="panel-left" class="w-5 h-5 {{ $isRtl ? '-scale-x-100' : '' }}"></i>
        </button>
        {{-- Quick "new question" — only when the sidebar is collapsed/closed --}}
        <button type="button" x-show="!sidebarOpen" @click="newChat()"
                class="p-2 rounded-lg transition-colors shrink-0"
                :class="$store.theme.dark ? 'text-gray-300 hover:bg-white/10' : 'text-gray-600 hover:bg-gray-100'"
                title="{{ __('quran.public.ask_new_question') }}" aria-label="{{ __('quran.public.ask_new_question') }}">
            <i data-lucide="square-pen" class="w-5 h-5"></i>
        </button>
        @endauth

        {{-- Brand --}}
        <div class="flex items-center gap-2 font-bold truncate" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] text-white shrink-0">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
            </span>
            <span class="truncate text-sm sm:text-base">{{ __('quran.public.ask_page_title') }}</span>
        </div>

        <div class="ms-auto flex items-center gap-1.5 sm:gap-2">
            @auth
            <span x-show="mode === 'ai'" class="hidden md:inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-semibold"
                  :class="$store.theme.dark ? 'bg-white/10 text-gray-200' : 'bg-gray-100 text-gray-600'">
                <i data-lucide="gauge" class="w-3.5 h-3.5 text-[#64CCC5]"></i><span x-text="quotaLabel()"></span>
            </span>
            @endauth

            {{-- Theme toggle (the site header that normally carries it is hidden here) --}}
            <button type="button" @click="$store.theme.toggle()"
                    class="p-2 rounded-lg transition-colors shrink-0"
                    :class="$store.theme.dark ? 'text-gray-300 hover:bg-white/10' : 'text-gray-600 hover:bg-gray-100'"
                    aria-label="{{ __('site.toggle_theme') }}">
                <i x-show="!$store.theme.dark" data-lucide="moon" class="w-5 h-5"></i>
                <i x-show="$store.theme.dark" data-lucide="sun" class="w-5 h-5" x-cloak></i>
            </button>

            {{-- Exit / back to Quran — arrow points outward (toward the edge it sits on) --}}
            <a href="{{ route('public.quran') }}" aria-label="{{ __('quran.public.ask_back') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold border transition-all shrink-0"
               :class="$store.theme.dark
                   ? 'bg-gray-900 border-white/10 text-gray-200 hover:text-white hover:border-[#64CCC5]/40'
                   : 'bg-white border-gray-200 text-gray-700 hover:text-gray-900 hover:border-[#0056b3]/40 shadow-sm'">
                <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                <span class="hidden sm:inline">{{ __('quran.public.ask_back_short') }}</span>
            </a>
        </div>
    </header>

    {{-- ============================ Body: sidebar + main ============================ --}}
    <div class="flex-1 flex min-h-0 relative">

        @auth
        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
             class="lg:hidden fixed inset-0 z-20 bg-black/50 backdrop-blur-sm"></div>

        {{-- History sidebar — collapsible on desktop, slide-over on mobile --}}
        <aside x-cloak
               class="absolute lg:relative inset-y-0 start-0 z-30 lg:z-auto flex flex-col shrink-0 border-e overflow-hidden transition-all duration-300 ease-in-out"
               :class="(sidebarOpen ? 'w-[85vw] max-w-[20rem] lg:w-72 translate-x-0' : 'w-[85vw] max-w-[20rem] lg:w-0 lg:translate-x-0 ' + closedTranslate) + ' ' + ($store.theme.dark ? 'bg-[#0b0f1a] border-white/10' : 'bg-white border-gray-200')"
               aria-label="{{ __('quran.public.ask_history_title') }}">
            <div class="p-3 shrink-0">
                <button type="button" @click="newChat()"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-md shadow-[#0056b3]/25 hover:shadow-lg active:scale-[0.98] transition-all whitespace-nowrap">
                    <i data-lucide="plus" class="w-4 h-4"></i>{{ __('quran.public.ask_new_question') }}
                </button>
            </div>
            <div class="px-3 pb-1 shrink-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider px-2" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">{{ __('quran.public.ask_history_title') }}</p>
            </div>
            <div class="flex-1 overflow-y-auto ask-thin-scroll px-2 pb-3 space-y-1">
                <template x-if="!history.length">
                    <p class="text-xs text-center py-6 px-3" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">{{ __('quran.public.ask_history_empty') }}</p>
                </template>
                <template x-for="h in history" :key="h.id">
                    <button type="button" @click="openHistory(h.id)"
                            :aria-current="h.id === activeId ? 'true' : 'false'"
                            class="group w-full text-start flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm transition-colors whitespace-nowrap"
                            :class="h.id === activeId
                                ? 'ask-hist-active font-semibold ' + ($store.theme.dark ? 'bg-white/10 text-white' : 'bg-blue-50 text-[#0056b3]')
                                : ($store.theme.dark ? 'text-gray-300 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100')">
                        {{-- AI indicator: every saved inquiry was answered by the AI assistant --}}
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-5 h-5 rounded-md"
                              :class="h.id === activeId
                                  ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] text-white'
                                  : ($store.theme.dark ? 'bg-white/10 text-[#64CCC5]' : 'bg-blue-50 text-[#0056b3]')"
                              title="{{ __('quran.public.ask_tab_ai') }}" aria-label="{{ __('quran.public.ask_tab_ai') }}">
                            <i data-lucide="sparkles" class="w-3 h-3"></i>
                        </span>
                        <span class="truncate" x-text="h.question" :title="h.question"></span>
                    </button>
                </template>
            </div>
        </aside>
        @endauth

        {{-- Main chat column --}}
        <main class="flex-1 flex flex-col min-w-0">

            {{-- Scrollable conversation --}}
            <div class="flex-1 overflow-y-auto ask-thin-scroll" x-ref="scroll">
                <div class="container mx-auto px-4 sm:px-6 max-w-3xl py-6">

                    {{-- Guest notice --}}
                    @guest
                    <div class="mb-6 ask-fade rounded-2xl border px-5 py-4 flex items-center justify-between gap-3 flex-wrap"
                         :class="$store.theme.dark ? 'bg-gray-900 border-white/10' : 'bg-white border-gray-200 shadow-sm'">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl"
                                  :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-blue-50 text-[#0056b3]'">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('quran.public.ask_login_required') }}</p>
                                <p class="text-xs" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.ask_login_note') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('public.login') }}" @click.prevent="$dispatch('open-login')"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-br from-[#0056b3] to-[#218838] shadow-md hover:shadow-lg transition-all">
                            <i data-lucide="log-in" class="w-4 h-4"></i>{{ __('quran.public.ask_login_cta') }}
                        </a>
                    </div>
                    @endguest

                    {{-- Empty / welcome state (centered, ChatGPT-style) --}}
                    <template x-if="!current">
                        <div class="ask-fade min-h-[55vh] flex flex-col items-center justify-center text-center px-2">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white shadow-lg shadow-[#0056b3]/25">
                                <i data-lucide="sparkles" class="w-9 h-9"></i>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-bold mb-2 font-['Amiri']" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('quran.public.ask_page_title') }}</h1>
                            <p class="text-sm md:text-base max-w-xl" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
                               x-text="mode === 'ai' ? @js(__('quran.public.ask_ai_disclaimer')) : @js(__('quran.public.ask_source_note'))"></p>
                            <p class="mt-4 text-xs" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">{{ __('quran.public.ask_start_hint') }}</p>
                        </div>
                    </template>

                    {{-- Single active Q&A (replaced on each ask / history open — never appended) --}}
                    <template x-if="current">
                        <div class="ask-fade pb-4" :key="current.viewId">

                            <div class="flex {{ $isRtl ? 'justify-start' : 'justify-end' }} mb-4" x-show="current.query">
                                <div dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="max-w-[85%] rounded-2xl {{ $isRtl ? 'rounded-ss-md' : 'rounded-se-md' }} px-4 py-3 shadow-md bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-start">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider opacity-80 mb-0.5">{{ __('quran.public.ask_you') }}</p>
                                    <p class="text-base font-medium break-words" x-text="current.query"></p>
                                </div>
                            </div>

                            <div class="ps-1">
                                {{-- Loading / streaming --}}
                                <template x-if="current.status === 'loading'">
                                    <div>
                                        <div class="flex items-center gap-2 mb-3 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-500'">
                                            <span class="w-4 h-4 border-2 border-[#64CCC5] border-t-transparent rounded-full animate-spin"></span>
                                            <span x-text="current.statusText || (current.kind === 'ai' ? @js(__('quran.public.ask_thinking')) : @js(__('quran.public.ask_loading')))"></span>
                                        </div>
                                        <template x-if="current.html">
                                            <div class="rounded-2xl p-5 border" :class="$store.theme.dark ? 'bg-gray-900 border-white/5' : 'bg-white border-gray-100 shadow-sm'">
                                                <div dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="qa-prose qa-caret" :class="($store.theme.dark ? 'qa-dark text-gray-100' : 'text-gray-800')" x-html="current.html"></div>
                                            </div>
                                        </template>
                                        <template x-if="!current.html">
                                            <div class="rounded-2xl p-5 border space-y-3" :class="$store.theme.dark ? 'bg-gray-900 border-white/5' : 'bg-white border-gray-100'">
                                                <div class="ask-skeleton h-4 w-full rounded"></div>
                                                <div class="ask-skeleton h-4 w-11/12 rounded"></div>
                                                <div class="ask-skeleton h-4 w-2/3 rounded"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Error --}}
                                <template x-if="current.status === 'error'">
                                    <div class="rounded-2xl p-5 border flex items-center justify-between gap-3 flex-wrap"
                                         :class="$store.theme.dark ? 'bg-red-950/30 border-red-900/40' : 'bg-red-50 border-red-100'">
                                        <div class="flex items-center gap-2 text-sm" :class="$store.theme.dark ? 'text-red-300' : 'text-red-600'">
                                            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span>{{ __('quran.public.ask_error') }}</span>
                                        </div>
                                        <button @click="retry()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-md transition-all">
                                            <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>{{ __('quran.public.ask_retry') }}
                                        </button>
                                    </div>
                                </template>

                                {{-- Quota --}}
                                <template x-if="current.status === 'quota'">
                                    <div class="rounded-2xl p-6 border text-center" :class="$store.theme.dark ? 'bg-amber-950/20 border-amber-900/40' : 'bg-amber-50 border-amber-100'">
                                        <i data-lucide="gauge" class="w-8 h-8 mx-auto mb-3" :class="$store.theme.dark ? 'text-amber-400' : 'text-amber-500'"></i>
                                        <p class="text-sm font-medium" :class="$store.theme.dark ? 'text-amber-200' : 'text-amber-700'">{{ __('quran.public.ask_quota_exceeded', ['limit' => $limit]) }}</p>
                                    </div>
                                </template>

                                {{-- Empty search --}}
                                <template x-if="current.status === 'empty'">
                                    <div class="rounded-2xl p-8 border text-center" :class="$store.theme.dark ? 'bg-gray-900 border-white/5' : 'bg-white border-gray-100'">
                                        <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-3" :class="$store.theme.dark ? 'text-gray-600' : 'text-gray-400'"></i>
                                        <p class="font-semibold mb-1" :class="$store.theme.dark ? 'text-gray-200' : 'text-gray-800'">{{ __('quran.public.ask_empty') }}</p>
                                        <p class="text-sm" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-500'">{{ __('quran.public.ask_empty_hint') }}</p>
                                    </div>
                                </template>

                                {{-- Done: AI answer --}}
                                <template x-if="current.status === 'done' && current.kind === 'ai'">
                                    <div class="rounded-2xl p-5 border" :class="$store.theme.dark ? 'bg-gray-900 border-white/5' : 'bg-white border-gray-100 shadow-sm'">
                                        <div dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="qa-prose" :class="($store.theme.dark ? 'qa-dark text-gray-100' : 'text-gray-800')" x-html="current.html"></div>
                                        <template x-if="current.source === 'cache'">
                                            <div class="mt-3 flex items-center gap-2 text-[11px]" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">
                                                <span class="inline-flex items-center gap-1"><i data-lucide="history" class="w-3 h-3"></i>{{ __('quran.public.ask_from_cache') }}</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Done: search results --}}
                                <template x-if="current.status === 'done' && current.kind === 'search'">
                                    <div>
                                        <p class="text-xs mb-3 flex items-center gap-1.5" :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-500'">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-[#64CCC5]"></i>
                                            <span x-text="current.results.length"></span><span>{{ __('quran.public.verse_unit') }}</span>
                                            <template x-if="current.translationName"><span class="opacity-70">· <span x-text="current.translationName"></span></span></template>
                                        </p>
                                        <div class="space-y-3">
                                            <template x-for="r in current.results" :key="r.verseKey">
                                                <a :href="r.url" class="group block rounded-2xl p-5 border transition-all hover:-translate-y-0.5"
                                                   :class="$store.theme.dark ? 'bg-gray-900 border-white/5 hover:border-[#64CCC5]/30' : 'bg-white border-gray-100 hover:border-[#0056b3]/25 hover:shadow-lg'">
                                                    <div class="flex items-center justify-between gap-3 mb-3">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold" :class="$store.theme.dark ? 'bg-gray-800 text-[#64CCC5]' : 'bg-blue-50 text-[#0056b3]'" x-text="r.verseKey"></span>
                                                        <span class="inline-flex items-center gap-1 text-xs font-medium opacity-0 group-hover:opacity-100 transition-opacity" :class="$store.theme.dark ? 'text-[#64CCC5]' : 'text-[#0056b3]'">
                                                            {{ __('quran.public.ask_open_verse') }}<i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-3.5 h-3.5"></i>
                                                        </span>
                                                    </div>
                                                    <p dir="rtl" class="font-['Amiri'] text-2xl leading-loose text-end mb-3" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'" x-text="r.arabic"></p>
                                                    <template x-if="r.translation">
                                                        <p class="text-sm leading-relaxed" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-600'" x-text="r.translation"></p>
                                                    </template>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Composer (in-flow at the bottom of the column) --}}
            <div class="shrink-0 border-t backdrop-blur-md" :class="$store.theme.dark ? 'bg-[#0b0f1a]/90 border-white/10' : 'bg-white/90 border-gray-200'">
                <div class="container mx-auto px-4 sm:px-6 max-w-3xl py-3">
                    {{-- Mode switch — segmented control (clearly toggled), aligned to the right (RTL start) --}}
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div role="tablist" aria-label="{{ __('quran.public.ask_mode') }}"
                             class="inline-flex items-center gap-1 p-1 rounded-xl border"
                             :class="$store.theme.dark ? 'bg-gray-900 border-white/10' : 'bg-gray-100 border-gray-200'">
                            @auth
                                <button type="button" role="tab" :aria-selected="(mode === 'ai').toString()" @click="setMode('ai')"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                                        :class="mode === 'ai'
                                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                                            : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>{{ __('quran.public.ask_tab_ai') }}
                                </button>
                            @else
                                <a href="{{ route('public.login') }}" role="tab" :aria-selected="false" @click.prevent="$dispatch('open-login')"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                                   :class="$store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>{{ __('quran.public.ask_tab_ai') }}<i data-lucide="lock" class="w-3 h-3 opacity-60"></i>
                                </a>
                            @endauth
                            <button type="button" role="tab" :aria-selected="(mode === 'search').toString()" @click="setMode('search')"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                                    :class="mode === 'search'
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                                        : ($store.theme.dark ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                                <i data-lucide="search" class="w-4 h-4"></i>{{ __('quran.public.ask_tab_search') }}
                            </button>
                        </div>
                        {{-- Detailed-answer toggle — a switch chip on the end side (AI mode only). --}}
                        <div>
                            @auth
                            <label x-show="mode === 'ai'" x-cloak x-effect="if (remaining < 2 && detailed) detailed = false"
                                   class="inline-flex items-center gap-2 ps-3 pe-2 py-1.5 rounded-full border transition-all select-none focus-within:ring-2 focus-within:ring-[#64CCC5]/50"
                                   :class="(busy || remaining < 2 ? 'opacity-50 cursor-not-allowed ' : 'cursor-pointer ') + (detailed
                                       ? ($store.theme.dark ? 'bg-[#64CCC5]/10 border-[#64CCC5]/40 text-[#64CCC5]' : 'bg-[#0056b3]/10 border-[#0056b3]/40 text-[#0056b3]')
                                       : ($store.theme.dark ? 'bg-gray-900 border-white/10 text-gray-300' : 'bg-white border-gray-200 text-gray-600'))"
                                   :title="@js(__('quran.public.ask_detailed_note'))">
                                <input type="checkbox" x-model="detailed" :disabled="busy || remaining < 2" class="sr-only"
                                       aria-label="{{ __('quran.public.ask_detailed_label') }}">
                                <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                                <span class="text-xs font-semibold whitespace-nowrap">{{ __('quran.public.ask_detailed_label') }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md shrink-0"
                                      :class="$store.theme.dark ? 'bg-amber-500/20 text-amber-300' : 'bg-amber-100 text-amber-700'">&times;2</span>
                                <span class="relative inline-block w-9 h-5 rounded-full transition-colors shrink-0"
                                      :class="detailed ? ($store.theme.dark ? 'bg-[#64CCC5]' : 'bg-[#0056b3]') : ($store.theme.dark ? 'bg-gray-600' : 'bg-gray-300')">
                                    <span class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-all"
                                          :class="detailed ? 'end-0.5' : 'start-0.5'"></span>
                                </span>
                            </label>
                            @endauth
                        </div>
                    </div>

                    {{-- Detailed-answer cost notes (the toggle itself is the switch chip in the controls bar above). --}}
                    @auth
                    <p x-show="mode === 'ai' && detailed" x-cloak
                       class="mb-2 text-[11px] flex items-center gap-1 {{ $isRtl ? 'justify-start' : 'justify-end' }}"
                       :class="$store.theme.dark ? 'text-amber-300/90' : 'text-amber-600'">
                        <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i><span>{{ __('quran.public.ask_detailed_note') }}</span>
                    </p>
                    <p x-show="mode === 'ai' && remaining < 2 && remaining > 0" x-cloak
                       class="mb-2 text-[11px] {{ $isRtl ? 'text-start' : 'text-end' }}"
                       :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'">{{ __('quran.public.ask_detailed_need_two') }}</p>
                    @endauth

                    <form @submit.prevent="submit()" class="flex items-end gap-2">
                        <div class="relative flex-1">
                            <textarea x-ref="input" x-model="input" rows="1"
                                      maxlength="{{ $maxChars }}"
                                      @keydown.enter.prevent="submit()"
                                      @input="autoGrow($event); sanitizeInput()"
                                      :disabled="busy"
                                      :placeholder="mode === 'ai' ? @js(__('quran.public.ask_ai_placeholder')) : @js(__('quran.public.ask_search_placeholder'))"
                                      class="w-full resize-none ps-4 pe-4 py-3 text-base sm:text-sm rounded-2xl border focus:outline-none focus:ring-2 focus:ring-[#64CCC5]/60 focus:border-[#64CCC5] transition-all max-h-32 disabled:opacity-60"
                                      :class="$store.theme.dark ? 'bg-gray-950 border-gray-800 text-white placeholder-gray-500' : 'bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-400'"
                                      :aria-label="mode === 'ai' ? @js(__('quran.public.ask_ai_placeholder')) : @js(__('quran.public.ask_search_placeholder'))"></textarea>
                            <span x-show="mode === 'ai'" class="absolute bottom-1.5 end-3 text-[10px] tabular-nums pointer-events-none"
                                  :class="tooLong ? 'text-red-500' : ($store.theme.dark ? 'text-gray-600' : 'text-gray-400')" x-text="input.length + '/' + maxChars"></span>
                        </div>
                        <button type="submit" :disabled="!canSend"
                                class="flex-shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center text-white bg-gradient-to-br from-[#0056b3] to-[#218838] shadow-md shadow-[#0056b3]/30 hover:scale-105 active:scale-95 transition-transform disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                                aria-label="{{ __('quran.public.ask_send') }}" title="{{ __('quran.public.ask_send') }}">
                            <span x-show="busy" class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                            <i x-show="!busy" data-lucide="send-horizontal" class="w-5 h-5 {{ $isRtl ? '-scale-x-100' : '' }}"></i>
                        </button>
                    </form>
                    <p class="mt-1.5 text-center text-[11px]" :class="$store.theme.dark ? 'text-gray-600' : 'text-gray-400'">
                        <template x-if="mode === 'ai'">
                            <span>
                                <span x-show="remaining > 0" x-text="quotaLabel()"></span>
                                <span x-show="remaining <= 0" class="text-amber-500">{{ __('quran.public.ask_quota_exceeded', ['limit' => $limit]) }}</span>
                            </span>
                        </template>
                        <template x-if="mode === 'search'"><span>{{ __('quran.public.ask_source_note') }}</span></template>
                    </p>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function quranAsk() {
        return {
            current: null,
            activeId: null,
            input: '',
            detailed: false,
            busy: false,
            seq: 0,
            sidebarOpen: true,
            closedTranslate: @json($isRtl ? 'translate-x-full' : '-translate-x-full'),
            mode: @json($isAuthenticated ? 'ai' : 'search'),
            isAuth: {{ $isAuthenticated ? 'true' : 'false' }},
            remaining: {{ (int) $remaining }},
            limit: {{ (int) $limit }},
            minChars: {{ (int) $minChars }},
            maxChars: {{ (int) $maxChars }},
            aiEndpoint: @json(route('public.quran.ask.submit')),
            streamEndpoint: @json(route('public.quran.ask.stream')),
            searchEndpoint: @json(route('public.quran.ask.search')),
            askUrl: @json(route('public.quran.ask')),
            verseBase: @json(url('/quran')),
            statusTpl: {
                search_quran: @json(__('quran.public.ask_status_searching')),
                search_translation: @json(__('quran.public.ask_status_searching')),
                fetch_quran: @json(__('quran.public.ask_status_reading')),
                fetch_translation: @json(__('quran.public.ask_status_reading')),
                fetch_tafsir: @json(__('quran.public.ask_status_tafsir')),
            },
            writingLabel: @json(__('quran.public.ask_status_writing')),
            initialQuery: @json($initialQuery),
            initialInquiryId: {{ $initialInquiryId ? (int) $initialInquiryId : 'null' }},
            quotaTpl: @json(__('quran.public.ask_quota_remaining', ['remaining' => '__R__', 'limit' => '__L__'])),
            history: @json($history),
            historyBase: @json(url('/quran/ask/history')),

            boot() {
                this.sidebarOpen = this.isDesktop();
                try {
                    const s = localStorage.getItem('askSidebar');
                    if (this.isDesktop() && s !== null) this.sidebarOpen = s === '1';
                } catch (e) {}

                this.refreshIcons();
                if (this.isAuth) this.$store.geo?.load();
                window.addEventListener('popstate', () => this.syncFromUrl());

                if (this.initialInquiryId) {
                    this.openHistory(this.initialInquiryId, false);
                } else if (this.initialQuery && this.initialQuery.trim().length >= this.currentMin) {
                    this.ask(this.initialQuery.trim(), this.mode);
                }
            },

            isDesktop() { return window.matchMedia('(min-width: 1024px)').matches; },

            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
                if (this.isDesktop()) {
                    try { localStorage.setItem('askSidebar', this.sidebarOpen ? '1' : '0'); } catch (e) {}
                }
                this.refreshIcons();
            },

            refreshIcons() { this.$nextTick(() => window.lucide?.createIcons()); },

            setMode(m) {
                if (m === 'ai' && !this.isAuth) return;
                this.mode = m;
            },

            get currentMin() { return this.mode === 'ai' ? this.minChars : 2; },
            get tooShort() { return this.input.trim().length < this.currentMin; },
            get tooLong() { return this.input.length > this.maxChars; },
            // A detailed AI answer costs 2 quota slots; everything else costs 1.
            get cost() { return (this.mode === 'ai' && this.detailed) ? 2 : 1; },
            get canSend() {
                if (this.busy || this.tooShort || this.tooLong) return false;
                return this.mode !== 'ai' || this.remaining >= this.cost;
            },

            quotaLabel() { return this.quotaTpl.replace('__R__', this.remaining).replace('__L__', this.limit); },

            scrollToBottom(smooth = true) {
                this.$nextTick(() => {
                    const el = this.$refs.scroll;
                    if (el) el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                });
            },

            newView(kind, query, detailed = false) {
                return {
                    viewId: ++this.seq, kind, query, status: 'loading',
                    answer: '', html: '', source: null, statusText: '',
                    results: [], translationName: null,
                    // Sticky per-view so retry() re-asks in the same mode.
                    detailed: !!detailed && kind === 'ai',
                };
            },

            renderAnswer(view) {
                view.html = window.renderQuranAnswer ? window.renderQuranAnswer(view.answer, this.verseBase) : (view.answer || '');
            },

            pushUrl(id) {
                const url = id ? (this.askUrl + '?inquiry=' + id) : this.askUrl;
                window.history.pushState({ inquiry: id || null }, '', url);
            },

            syncFromUrl() {
                const id = parseInt(new URLSearchParams(window.location.search).get('inquiry') || '0', 10);
                if (id && id !== this.activeId) {
                    this.openHistory(id, false);
                } else if (!id) {
                    this.current = null;
                    this.activeId = null;
                }
            },

            addHistory(id, question) {
                if (!id) return;
                this.history = this.history.filter(h => h.id !== id && h.question !== question);
                this.history.unshift({ id, question });
            },

            submit() {
                const q = this.input.trim();
                if (q.length < this.currentMin || q.length > this.maxChars || this.busy) return;
                if (this.mode === 'ai' && this.remaining < this.cost) return;
                this.input = '';
                this.$nextTick(() => { if (this.$refs.input) this.$refs.input.style.height = 'auto'; });
                this.ask(q, this.mode, this.detailed);
            },

            retry() { if (this.current) this.ask(this.current.query, this.current.kind, this.current.detailed); },

            ask(query, kind, detailed = false) {
                query = (query || '').trim();
                if (!query) return;
                this.activeId = null;
                this.current = this.newView(kind, query, detailed);
                this.scrollToBottom(false);
                this.run();
            },

            async run() {
                const view = this.current;
                this.busy = true;
                try {
                    if (view.kind === 'ai') await this.runAi(view);
                    else await this.runSearch(view);
                } catch (e) {
                    if (this.isCurrent(view)) view.status = 'error';
                } finally {
                    this.busy = false;
                    this.refreshIcons();
                }
            },

            isCurrent(view) { return !!this.current && this.current.viewId === view.viewId; },

            async runAi(view) {
                let res;
                try {
                    res = await window.apiFetch(this.streamEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream' },
                        body: JSON.stringify({ question: view.query, detailed: view.detailed }),
                    });
                } catch (e) {
                    return this.runAiFallback(view);
                }
                if (res.status === 429) { if (this.isCurrent(view)) { view.status = 'quota'; this.remaining = 0; } return; }
                if (!res.ok || !res.body || !res.body.getReader) return this.runAiFallback(view);

                view.statusText = this.writingLabel;
                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                const handle = (evt) => {
                    if (!this.isCurrent(view)) return;
                    if (evt.type === 'status') {
                        const label = this.statusTpl[evt.tool] || this.writingLabel;
                        view.statusText = label + (evt.ref ? ' · ' + evt.ref : '');
                    } else if (evt.type === 'delta') {
                        view.answer += (evt.text || '');
                        this.renderAnswer(view);
                        view.statusText = this.writingLabel;
                    } else if (evt.type === 'quota') {
                        view.status = 'quota'; this.remaining = 0;
                    } else if (evt.type === 'error') {
                        view.status = 'error';
                    } else if (evt.type === 'done') {
                        if (evt.html) view.html = evt.html; else this.renderAnswer(view);
                        view.source = evt.source || 'llm';
                        if (typeof evt.remaining === 'number') this.remaining = evt.remaining;
                        view.status = (view.html || view.answer) ? 'done' : 'error';
                        if (evt.id) { this.activeId = evt.id; this.addHistory(evt.id, view.query); this.pushUrl(evt.id); }
                    }
                };

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    let idx;
                    while ((idx = buffer.indexOf('\n\n')) !== -1) {
                        const frame = buffer.slice(0, idx);
                        buffer = buffer.slice(idx + 2);
                        for (const line of frame.split('\n')) {
                            const s = line.trim();
                            if (!s.startsWith('data:')) continue;
                            const json = s.slice(5).trim();
                            if (!json) continue;
                            try { handle(JSON.parse(json)); } catch (_) {}
                        }
                    }
                    if (this.isCurrent(view) && view.status === 'loading') this.scrollToBottom();
                }

                if (this.isCurrent(view) && view.status === 'loading') {
                    view.status = view.answer ? 'done' : 'error';
                }
            },

            async runAiFallback(view) {
                const res = await window.apiFetch(this.aiEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ question: view.query, detailed: view.detailed }),
                });
                const data = await res.json().catch(() => ({}));
                if (!this.isCurrent(view)) return;
                if (res.status === 429) { view.status = 'quota'; this.remaining = 0; return; }
                if (!res.ok) throw new Error('http ' + res.status);
                view.answer = data.answer || '';
                view.source = data.source || 'llm';
                if (typeof data.remaining === 'number') this.remaining = data.remaining;
                if (data.html) view.html = data.html; else this.renderAnswer(view);
                view.status = (view.html || view.answer) ? 'done' : 'error';
                if (data.id) { this.activeId = data.id; this.addHistory(data.id, view.query); this.pushUrl(data.id); }
            },

            async runSearch(view) {
                const url = this.searchEndpoint + '?q=' + encodeURIComponent(view.query);
                const res = await window.apiFetch(url, { headers: { Accept: 'application/json' } });
                if (!this.isCurrent(view)) return;
                if (!res.ok) throw new Error('http ' + res.status);
                const data = await res.json();
                view.results = Array.isArray(data.results) ? data.results : [];
                view.translationName = data.translationName || null;
                view.status = view.results.length ? 'done' : 'empty';
            },

            async openHistory(id, push = true) {
                if (!this.isDesktop()) this.sidebarOpen = false;
                this.mode = 'ai';
                this.activeId = id;
                this.current = this.newView('ai', '');
                const view = this.current;
                this.busy = true;
                if (push) this.pushUrl(id);
                try {
                    const res = await window.apiFetch(this.historyBase + '/' + id, { headers: { Accept: 'application/json' } });
                    if (!this.isCurrent(view)) return;
                    if (!res.ok) throw new Error('http ' + res.status);
                    const data = await res.json();
                    view.query = data.question || '';
                    view.answer = data.answer || '';
                    view.source = data.source || 'llm';
                    if (data.html) view.html = data.html; else this.renderAnswer(view);
                    view.status = (view.html || view.answer) ? 'done' : 'error';
                } catch (e) {
                    if (this.isCurrent(view)) view.status = 'error';
                } finally {
                    this.busy = false;
                    this.refreshIcons();
                    this.scrollToBottom(false);
                }
            },

            newChat() {
                this.current = null;
                this.activeId = null;
                this.input = '';
                if (!this.isDesktop()) this.sidebarOpen = false;
                if (this.mode !== 'search') this.mode = this.isAuth ? 'ai' : 'search';
                this.pushUrl(null);
                this.$nextTick(() => this.$refs.input?.focus());
            },

            autoGrow(e) {
                const el = e.target;
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 128) + 'px';
            },

            sanitizeInput() {
                const cleaned = this.input.replace(/[^\p{L}\p{M}\p{N}\s!?؟_-]+/gu, '');
                if (cleaned !== this.input) this.input = cleaned;
            },
        };
    }
</script>
@endpush
