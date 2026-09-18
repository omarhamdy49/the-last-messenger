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
    /* The streaming caret is the one thing the system has no vocabulary for:
       a blinking block glyph tacked onto the tail of a token stream. Everything
       else on this page (entrance, skeleton, surfaces) uses `sx`. */
    .qa-caret::after { content: '▍'; margin-inline-start: 1px; color: var(--sx-teal); animation: qaBlink 1s steps(2) infinite; }
    @keyframes qaBlink { 0%, 50% { opacity: 1; } 50.01%, 100% { opacity: 0; } }
    @media (prefers-reduced-motion: reduce) { .qa-caret::after { animation: none; } }
</style>
@endpush

@section('content')
@include('components.quran.answer-renderer')

{{-- The shell stays `h-dvh … overflow-hidden`: the conversation scrolls inside
     itself and the composer is pinned to the bottom of the column. --}}
<div x-data="quranAsk()" x-init="boot()" class="sx h-dvh flex flex-col overflow-hidden">

    {{-- ============================ Top bar (wide / full width) ============================ --}}
    <header class="shrink-0 h-14 sm:h-16 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 z-30
                   border-b border-[color:var(--sx-line)] bg-[color:var(--sx-surface)]">

        {{-- Sidebar collapse / expand --}}
        @auth
        <button type="button" @click="toggleSidebar()"
                class="shrink-0 p-2.5 rounded-full text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-accent-soft)] hover:text-[color:var(--sx-accent)]"
                :aria-expanded="sidebarOpen.toString()"
                aria-label="{{ __('quran.public.ask_history_title') }}" title="{{ __('quran.public.ask_history_title') }}">
            <i data-lucide="panel-left" class="w-5 h-5 {{ $isRtl ? '-scale-x-100' : '' }}" aria-hidden="true"></i>
        </button>
        {{-- Quick "new question" — only when the sidebar is collapsed/closed --}}
        <button type="button" x-show="!sidebarOpen" @click="newChat()"
                class="shrink-0 p-2.5 rounded-full text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-accent-soft)] hover:text-[color:var(--sx-accent)]"
                title="{{ __('quran.public.ask_new_question') }}" aria-label="{{ __('quran.public.ask_new_question') }}">
            <i data-lucide="square-pen" class="w-5 h-5" aria-hidden="true"></i>
        </button>
        @endauth

        {{-- Brand --}}
        <div class="flex items-center gap-2 font-bold truncate text-[color:var(--sx-ink)]">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-white shrink-0" style="background: var(--sx-grad)">
                <i data-lucide="sparkles" class="w-5 h-5" aria-hidden="true"></i>
            </span>
            <span class="truncate text-sm sm:text-base">{{ __('quran.public.ask_page_title') }}</span>
        </div>

        <div class="ms-auto flex items-center gap-1.5 sm:gap-2">
            @auth
            <span x-show="mode === 'ai'" class="sx-chip hidden md:inline-flex">
                <i data-lucide="gauge" aria-hidden="true"></i><span class="tabular-nums" x-text="quotaLabel()"></span>
            </span>
            @endauth

            {{-- Language switcher (the site header that normally carries it is hidden here) --}}
            @php
                $askLanguages = config('app.supported_locales');
            @endphp
            <div class="relative shrink-0" x-data="{ langOpen: false }">
                <button type="button" @click="langOpen = !langOpen" :aria-expanded="langOpen.toString()"
                        class="inline-flex items-center gap-1 p-2.5 rounded-full text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-accent-soft)] hover:text-[color:var(--sx-accent)]"
                        aria-label="{{ __('site.language') }}" title="{{ __('site.language') }}">
                    <i data-lucide="globe" class="w-5 h-5" aria-hidden="true"></i>
                    <span class="hidden sm:block text-xs font-semibold uppercase">{{ app()->getLocale() }}</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
                </button>
                <div x-show="langOpen" @click.away="langOpen = false" x-transition x-cloak
                     class="sx-surface absolute end-0 mt-2 w-44 p-1.5 z-50">
                    @foreach ($askLanguages as $code => $lang)
                        <form method="POST" action="{{ route('language.switch') }}">
                            @csrf
                            <input type="hidden" name="language" value="{{ $code }}">
                            <button type="submit"
                                    class="w-full text-start px-3 py-2.5 flex items-center gap-3 text-sm rounded-xl transition-colors text-[color:var(--sx-ink-2)] hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-ink)] {{ app()->getLocale() === $code ? 'font-bold' : '' }}">
                                <span class="text-xl leading-none">{{ $lang['flag'] }}</span>
                                <span>{{ $lang['name'] }}</span>
                                @if (app()->getLocale() === $code)
                                    <i data-lucide="check" class="w-4 h-4 ms-auto text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            {{-- Theme toggle (the site header that normally carries it is hidden here) --}}
            <button type="button" @click="$store.theme.toggle()"
                    class="shrink-0 p-2.5 rounded-full text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-accent-soft)] hover:text-[color:var(--sx-accent)]"
                    aria-label="{{ __('site.toggle_theme') }}" title="{{ __('site.toggle_theme') }}">
                <i x-show="!$store.theme.dark" data-lucide="moon" class="w-5 h-5" aria-hidden="true"></i>
                <i x-show="$store.theme.dark" data-lucide="sun" class="w-5 h-5" x-cloak aria-hidden="true"></i>
            </button>

            {{-- Exit / back to Quran — arrow points outward (toward the edge it sits on) --}}
            <a href="{{ route('public.quran') }}" class="sx-btn shrink-0"
               aria-label="{{ __('quran.public.ask_back') }}" title="{{ __('quran.public.ask_back') }}">
                <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" aria-hidden="true"></i>
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
               class="absolute lg:relative inset-y-0 start-0 z-30 lg:z-auto flex flex-col shrink-0 overflow-hidden transition-all duration-300 ease-in-out
                      border-e border-[color:var(--sx-line)] bg-[color:var(--sx-surface)]"
               :class="sidebarOpen ? 'w-[85vw] max-w-[20rem] lg:w-72 translate-x-0' : 'w-[85vw] max-w-[20rem] lg:w-0 lg:translate-x-0 ' + closedTranslate"
               aria-label="{{ __('quran.public.ask_history_title') }}">
            <div class="p-3 shrink-0">
                <button type="button" @click="newChat()" class="sx-btn is-primary w-full">
                    <i data-lucide="plus" aria-hidden="true"></i>{{ __('quran.public.ask_new_question') }}
                </button>
            </div>
            <div class="px-3 pb-1 shrink-0">
                <p class="sx-label !mb-0 px-2 uppercase tracking-wider">{{ __('quran.public.ask_history_title') }}</p>
            </div>
            <div class="flex-1 overflow-y-auto px-2 pb-3">
                <template x-if="!history.length">
                    <p class="px-3 py-6 text-center text-xs text-[color:var(--sx-muted)]">{{ __('quran.public.ask_history_empty') }}</p>
                </template>
                <div class="sx-tile-list">
                    <template x-for="h in history" :key="h.id">
                        <button type="button" @click="openHistory(h.id)"
                                :aria-current="h.id === activeId ? 'true' : 'false'"
                                class="sx-tile group w-full text-start"
                                :class="h.id === activeId ? '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]' : ''">
                            {{-- AI indicator: every saved inquiry was answered by the AI assistant --}}
                            <span class="sx-tile-icon" title="{{ __('quran.public.ask_tab_ai') }}" aria-label="{{ __('quran.public.ask_tab_ai') }}">
                                <i data-lucide="sparkles" aria-hidden="true"></i>
                            </span>
                            <span class="sx-tile-title" x-text="h.question" :title="h.question"></span>
                            <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180 text-[color:var(--sx-muted)]"
                               x-show="h.id !== activeId" aria-hidden="true"></i>
                        </button>
                    </template>
                </div>
            </div>
        </aside>
        @endauth

        {{-- Main chat column --}}
        <main class="flex-1 flex flex-col min-w-0">

            {{-- Scrollable conversation --}}
            <div class="flex-1 overflow-y-auto" x-ref="scroll">
                <div class="sx-wrap max-w-3xl py-6">

                    {{-- Guest notice --}}
                    @guest
                    <div class="sx-rise mb-6">
                        <div class="sx-note">
                            <i data-lucide="lock" class="w-5 h-5 mt-0.5 shrink-0" aria-hidden="true"></i>
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div class="min-w-0">
                                    <p class="font-semibold text-[color:var(--sx-ink)]">{{ __('quran.public.ask_login_required') }}</p>
                                    <p class="text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.ask_login_note') }}</p>
                                </div>
                                <a href="{{ route('public.login') }}" @click.prevent="$dispatch('open-login')" class="sx-btn is-primary">
                                    <i data-lucide="log-in" aria-hidden="true"></i>{{ __('quran.public.ask_login_cta') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    @endguest

                    {{-- Empty / welcome state --}}
                    <template x-if="!current">
                        <div class="min-h-[55vh] grid content-center">
                            <x-public.editorial.page-hero :title="__('quran.public.ask_page_title')">
                                <p class="sx-lead"
                                   x-text="mode === 'ai' ? @js(__('quran.public.ask_ai_disclaimer')) : @js(__('quran.public.ask_source_note'))"></p>
                                <p class="text-sm text-[color:var(--sx-muted)]">{{ __('quran.public.ask_start_hint') }}</p>
                            </x-public.editorial.page-hero>
                        </div>
                    </template>

                    {{-- Single active Q&A (replaced on each ask / history open — never appended) --}}
                    <template x-if="current">
                        <div class="sx-rise pb-4" :key="current.viewId">

                            <div class="flex {{ $isRtl ? 'justify-start' : 'justify-end' }} mb-4" x-show="current.query">
                                <div dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                                     class="max-w-[85%] rounded-2xl {{ $isRtl ? 'rounded-ss-md' : 'rounded-se-md' }} px-4 py-3 text-white text-start shadow-[var(--sx-shadow)]"
                                     style="background: var(--sx-grad)">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider opacity-80 mb-0.5">{{ __('quran.public.ask_you') }}</p>
                                    <p class="text-base font-medium break-words" x-text="current.query"></p>
                                </div>
                            </div>

                            <div class="ps-1">
                                {{-- Loading / streaming --}}
                                <template x-if="current.status === 'loading'">
                                    <div>
                                        <div class="flex items-center gap-2 mb-3 text-sm text-[color:var(--sx-muted)]">
                                            <span class="w-4 h-4 rounded-full border-2 border-[color:var(--sx-teal)] border-t-transparent animate-spin" aria-hidden="true"></span>
                                            <span x-text="current.statusText || (current.kind === 'ai' ? @js(__('quran.public.ask_thinking')) : @js(__('quran.public.ask_loading')))"></span>
                                        </div>
                                        <template x-if="current.html">
                                            <div class="sx-surface p-5">
                                                <div dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="qa-prose qa-caret text-[color:var(--sx-ink-2)]"
                                                     :class="$store.theme.dark ? 'qa-dark' : ''" x-html="current.html"></div>
                                            </div>
                                        </template>
                                        <template x-if="!current.html">
                                            <div class="sx-surface p-5 space-y-3">
                                                <div class="sx-skeleton h-4 w-full"></div>
                                                <div class="sx-skeleton h-4 w-11/12"></div>
                                                <div class="sx-skeleton h-4 w-2/3"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Error --}}
                                <template x-if="current.status === 'error'">
                                    <div class="sx-dispute">
                                        <span class="sx-dispute-icon" aria-hidden="true"><i data-lucide="alert-triangle"></i></span>
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <p>{{ __('quran.public.ask_error') }}</p>
                                            <button type="button" @click="retry()" class="sx-btn is-primary">
                                                <i data-lucide="rotate-cw" aria-hidden="true"></i>{{ __('quran.public.ask_retry') }}
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                {{-- Quota --}}
                                <template x-if="current.status === 'quota'">
                                    <x-public.editorial.empty-state icon="gauge"
                                        :title="__('quran.public.ask_quota_exceeded', ['limit' => $limit])" />
                                </template>

                                {{-- Empty search --}}
                                <template x-if="current.status === 'empty'">
                                    <x-public.editorial.empty-state icon="search-x"
                                        :title="__('quran.public.ask_empty')"
                                        :hint="__('quran.public.ask_empty_hint')" />
                                </template>

                                {{-- Done: AI answer --}}
                                <template x-if="current.status === 'done' && current.kind === 'ai'">
                                    <div class="sx-surface p-5">
                                        <div dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="qa-prose text-[color:var(--sx-ink-2)]"
                                             :class="$store.theme.dark ? 'qa-dark' : ''" x-html="current.html"></div>
                                        <template x-if="current.source === 'cache'">
                                            <p class="mt-3 inline-flex items-center gap-1 text-[11px] text-[color:var(--sx-muted)]">
                                                <i data-lucide="history" class="w-3 h-3" aria-hidden="true"></i>{{ __('quran.public.ask_from_cache') }}
                                            </p>
                                        </template>
                                    </div>
                                </template>

                                {{-- Done: search results --}}
                                <template x-if="current.status === 'done' && current.kind === 'search'">
                                    <div>
                                        <p class="mb-3 flex items-center gap-1.5 text-xs text-[color:var(--sx-muted)]">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-[color:var(--sx-teal)]" aria-hidden="true"></i>
                                            <span class="tabular-nums" x-text="current.results.length"></span><span>{{ __('quran.public.verse_unit') }}</span>
                                            <template x-if="current.translationName"><span class="opacity-70">· <span x-text="current.translationName"></span></span></template>
                                        </p>
                                        <div class="grid gap-3">
                                            <template x-for="r in current.results" :key="r.verseKey">
                                                <a :href="r.url" class="sx-card group">
                                                    <div class="sx-card-body">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="sx-chip font-bold tabular-nums" x-text="r.verseKey"></span>
                                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-[color:var(--sx-accent)] opacity-0 transition-opacity group-hover:opacity-100">
                                                                {{ __('quran.public.ask_open_verse') }}<i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                                            </span>
                                                        </div>
                                                        <p dir="rtl" class="sx-scripture text-end" x-text="r.arabic"></p>
                                                        <template x-if="r.translation">
                                                            <p class="text-sm leading-relaxed text-[color:var(--sx-ink-2)]" x-text="r.translation"></p>
                                                        </template>
                                                    </div>
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
            <div class="shrink-0 border-t border-[color:var(--sx-line)] bg-[color:var(--sx-surface)]">
                <div class="sx-wrap max-w-3xl py-3">
                    {{-- Mode switch — segmented control (clearly toggled), aligned to the right (RTL start) --}}
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div role="tablist" aria-label="{{ __('quran.public.ask_mode') }}" class="sx-segment gap-1">
                            @auth
                                <button type="button" role="tab" :aria-selected="(mode === 'ai').toString()" @click="setMode('ai')"
                                        :disabled="remaining <= 0"
                                        :title="remaining <= 0 ? @js(__('quran.public.ask_quota_exceeded', ['limit' => $limit])) : ''"
                                        :aria-label="@js(__('quran.public.ask_tab_ai')) + ' — ' + quotaLabel()"
                                        class="sx-chip"
                                        :class="(mode === 'ai' ? 'is-active ' : '') + (remaining <= 0 ? 'opacity-40 cursor-not-allowed' : '')">
                                    <i data-lucide="sparkles" aria-hidden="true"></i>{{ __('quran.public.ask_tab_ai') }}
                                    {{-- Remaining quota, always visible on the tab. --}}
                                    <span class="sx-count" x-text="remaining" aria-hidden="true"></span>
                                </button>
                            @else
                                <a href="{{ route('public.login') }}" role="tab" :aria-selected="false" @click.prevent="$dispatch('open-login')"
                                   class="sx-chip !bg-transparent !border-transparent">
                                    <i data-lucide="sparkles" aria-hidden="true"></i>{{ __('quran.public.ask_tab_ai') }}<i data-lucide="lock" class="opacity-60" aria-hidden="true"></i>
                                </a>
                            @endauth
                            <button type="button" role="tab" :aria-selected="(mode === 'search').toString()" @click="setMode('search')"
                                    class="sx-chip" :class="mode === 'search' ? 'is-active' : ''">
                                <i data-lucide="search" aria-hidden="true"></i>{{ __('quran.public.ask_tab_search') }}
                            </button>
                        </div>
                        {{-- Detailed-answer toggle — a switch chip on the end side (AI mode only). --}}
                        <div>
                            @auth
                            <label x-show="mode === 'ai'" x-cloak x-effect="if (remaining < 2 && detailed) detailed = false"
                                   class="sx-chip select-none focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[color:var(--sx-teal)]"
                                   :class="(busy || remaining < 2 ? 'opacity-50 cursor-not-allowed ' : 'cursor-pointer ') + (detailed ? 'is-active' : '')"
                                   :title="@js(__('quran.public.ask_detailed_note'))">
                                <input type="checkbox" x-model="detailed" :disabled="busy || remaining < 2" class="sr-only"
                                       aria-label="{{ __('quran.public.ask_detailed_label') }}">
                                <i data-lucide="file-text" aria-hidden="true"></i>
                                <span class="whitespace-nowrap">{{ __('quran.public.ask_detailed_label') }}</span>
                                <span class="sx-count">&times;2</span>
                                {{-- The track/knob take their colour from the chip's own ink, so the
                                     switch flips with `.is-active` without naming a colour. --}}
                                <span class="relative inline-block w-9 h-5 rounded-full transition-colors shrink-0"
                                      :class="detailed ? 'bg-current/40' : 'bg-[color:var(--sx-line-strong)]'" aria-hidden="true">
                                    <span class="absolute top-0.5 w-4 h-4 rounded-full bg-current shadow transition-all"
                                          :class="detailed ? 'end-0.5' : 'start-0.5'"></span>
                                </span>
                            </label>
                            @endauth
                        </div>
                    </div>

                    {{-- Detailed-answer cost notes (the toggle itself is the switch chip in the controls bar above). --}}
                    @auth
                    <p x-show="mode === 'ai' && detailed" x-cloak class="sx-note mb-2 !py-2 !px-3.5 text-xs">
                        <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true"></i><span>{{ __('quran.public.ask_detailed_note') }}</span>
                    </p>
                    <p x-show="mode === 'ai' && remaining < 2 && remaining > 0" x-cloak
                       class="mb-2 text-[11px] text-[color:var(--sx-muted)] {{ $isRtl ? 'text-start' : 'text-end' }}">{{ __('quran.public.ask_detailed_need_two') }}</p>
                    @endauth

                    <form @submit.prevent="submit()" class="flex items-end gap-2">
                        <div class="relative flex-1">
                            <textarea x-ref="input" x-model="input" rows="1"
                                      maxlength="{{ $maxChars }}"
                                      @keydown.enter.prevent="submit()"
                                      @input="autoGrow($event); sanitizeInput()"
                                      :disabled="busy"
                                      :placeholder="mode === 'ai' ? @js(__('quran.public.ask_ai_placeholder')) : @js(__('quran.public.ask_search_placeholder'))"
                                      class="sx-input resize-none py-3 text-base sm:text-sm max-h-32 disabled:opacity-60"
                                      :aria-label="mode === 'ai' ? @js(__('quran.public.ask_ai_placeholder')) : @js(__('quran.public.ask_search_placeholder'))"></textarea>
                            <span x-show="mode === 'ai'" class="absolute bottom-1.5 end-3 text-[10px] tabular-nums pointer-events-none"
                                  :class="tooLong ? 'text-[color:var(--sx-danger)]' : 'text-[color:var(--sx-muted)]'" x-text="input.length + '/' + maxChars"></span>
                        </div>
                        <button type="submit" :disabled="!canSend"
                                class="sx-btn is-primary shrink-0 w-12 h-12 !px-0 disabled:opacity-40 disabled:cursor-not-allowed"
                                aria-label="{{ __('quran.public.ask_send') }}" title="{{ __('quran.public.ask_send') }}">
                            <span x-show="busy" class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" aria-hidden="true"></span>
                            <i x-show="!busy" data-lucide="send-horizontal" class="w-5 h-5 {{ $isRtl ? '-scale-x-100' : '' }}" aria-hidden="true"></i>
                        </button>
                    </form>
                    <p class="mt-1.5 text-center text-[11px] text-[color:var(--sx-muted)]">
                        <template x-if="mode === 'ai'">
                            <span>
                                <span x-show="remaining > 0" x-text="quotaLabel()"></span>
                                <span x-show="remaining <= 0" class="text-[color:var(--sx-danger)]">{{ __('quran.public.ask_quota_exceeded', ['limit' => $limit]) }}</span>
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

                // Quota exhausted → AI is unavailable: fall back to search (the AI tab is
                // dimmed/disabled). Deep-linked history still opens in AI view to read.
                if (this.isAuth && this.remaining <= 0 && this.mode === 'ai' && !this.initialInquiryId) {
                    this.mode = 'search';
                }
                // The moment the last slot is spent (remaining hits 0 after an ask), switch
                // to search automatically. Does not fire while reading history (activeId set).
                this.$watch('remaining', (val) => {
                    if (val <= 0 && this.mode === 'ai' && !this.activeId) this.mode = 'search';
                });

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
                if (m === 'ai' && this.remaining <= 0) return; // quota exhausted — AI tab is disabled
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
                if (this.mode !== 'search') this.mode = (this.isAuth && this.remaining > 0) ? 'ai' : 'search';
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
