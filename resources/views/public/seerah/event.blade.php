@extends('layouts.public')

@section('title', $event['title'] . ' — ' . __('seerah.public.page_title'))
@section('meta_description', excerpt_sentence($event['description'] ?? $event['title'], 155))
@section('og_type', 'article')

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": @json($event['title']),
    "description": @json(excerpt_sentence($event['description'] ?? '', 200)),
    "url": "{{ $event['url'] }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
@include('partials.module-embed-styles')
@include('partials.seerah-content-styles')
<style>
    [x-cloak] { display: none !important; }

    @keyframes fade-in-up {
        0% { opacity: 0; transform: translateY(24px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-12px); }
    }

    .gradient-text {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .reading-progress {
        position: fixed;
        top: 64px;
        inset-inline: 0;
        height: 4px;
        background: rgba(0, 0, 0, 0.08);
        z-index: 40;
    }
    .reading-progress-bar {
        height: 100%;
        border-radius: 0 2px 2px 0;
        background: linear-gradient(90deg, #0056b3 0%, #64CCC5 60%, #218838 100%);
        box-shadow: 0 0 10px rgba(100, 204, 197, .65);
        transition: width 0.2s ease-out;
    }

    /* ── Path of Light II ────────────────────────────────────────────
       The inner timeline carries a continuous track whose fill follows
       the reading line (--jp, paint-only clip-path); step chips ignite
       as they are passed. The TOC mirrors it as a lit dot trail. */
    #article-path { --jp: 0; }
    .ev-path-fill {
        clip-path: inset(0 0 calc((1 - var(--jp, 0)) * 100%) 0);
        transition: clip-path .18s ease-out;
    }
    .ev-step-chip { transition: box-shadow .5s ease, filter .5s ease; }
    [data-path-step]:not(.is-lit) .ev-step-chip { filter: grayscale(.4) brightness(.92); }
    [data-path-step].is-lit .ev-step-chip { box-shadow: 0 0 20px -4px rgba(100,204,197,.85); }

    .ev-toc-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        background: #d1d5db;
        transition: background .35s ease, box-shadow .35s ease;
    }
    .dark .ev-toc-dot { background: rgba(255, 255, 255, .22); }
    .ev-toc-dot.is-on { background: #64CCC5; box-shadow: 0 0 10px rgba(100, 204, 197, .8); }
    .ev-toc-fill {
        height: calc(100% - 1.5rem);
        clip-path: inset(0 0 calc((1 - var(--tp, 0)) * 100%) 0);
        transition: clip-path .4s ease-out;
    }

    @media (prefers-reduced-motion: reduce) {
        .ev-path-fill, .ev-step-chip, .ev-toc-dot, .ev-toc-fill { transition: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .ev-anim { animation: none !important; opacity: 1 !important; }
    }
</style>
@endpush

@section('content')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $hasContent = ! empty($event['content']);
    $hasDescription = ! empty($event['description']);
    $hasCharacters = ! empty($event['characters']);
    $lessons = collect($event['lessons'] ?? [])->filter(fn ($l) => ! empty($l['title']));
    $hasLessons = $lessons->isNotEmpty();
    $sources = collect($event['sources'] ?? [])->filter(fn ($s) => ! empty($s['title']));
    $hasSources = $sources->isNotEmpty();
    $eventTimeline = collect($event['timeline'] ?? []);
    $hasTimeline = $eventTimeline->isNotEmpty();
    $statistics = collect($event['statistics'] ?? [])->filter(fn ($v) => is_scalar($v))->take(4);
    $hasStatistics = $statistics->isNotEmpty();
    // Timeline is the default style now — plain URL, no query param.
    $timelineStyleUrl = route('public.seerah.event', $event['slug']);

    // Sidebar facts card — only rows that have data.
    $facts = collect([
        ['icon' => 'calendar', 'label' => __('seerah.public.hijri_year_label').' ('.__('seerah.public.hijri_suffix').')', 'value' => $event['hijriDate'] ?: ($event['hijriYear'] !== null ? abs($event['hijriYear']).' '.($event['hijriYear'] < 0 ? __('seerah.public.before_hijra_suffix') : __('seerah.public.hijri_suffix')) : null)],
        ['icon' => 'calendar-days', 'label' => __('seerah.public.gregorian_suffix'), 'value' => $event['gregorianYear'] ? $event['gregorianYear'].' '.__('seerah.public.gregorian_suffix') : null],
        ['icon' => 'map-pin', 'label' => __('seerah.public.location_label'), 'value' => $event['location']],
        ['icon' => 'landmark', 'label' => __('seerah.public.era_label'), 'value' => $event['eraLabel']],
        ['icon' => 'route', 'label' => __('seerah.public.phase_label'), 'value' => $event['phaseLabel']],
        ['icon' => 'flame', 'label' => __('seerah.public.importance_label'), 'value' => $event['importanceLabel'] ?? null],
    ])->filter(fn ($f) => ! empty($f['value']));

    $disputeNote = $event['disputeNote'] ?? null;

    // TOC entries drive both the sidebar list and the scroll-spy order.
    $toc = collect([
        ['id' => 'introduction', 'label' => __('seerah.public.section_intro'), 'show' => $hasDescription],
        ['id' => 'dispute', 'label' => __('seerah.public.dispute_heading'), 'show' => (bool) ($event['disputeNote'] ?? null)],
        ['id' => 'context', 'label' => __('seerah.public.section_context'), 'show' => $hasContent],
        ['id' => 'timeline', 'label' => __('seerah.public.event_timeline_heading'), 'show' => $hasTimeline],
        ['id' => 'characters', 'label' => __('seerah.public.section_characters'), 'show' => $hasCharacters],
        ['id' => 'lessons', 'label' => __('seerah.public.section_lessons'), 'show' => $hasLessons],
        ['id' => 'sources', 'label' => __('seerah.public.section_sources'), 'show' => $hasSources],
    ])->filter(fn ($t) => $t['show'])->values();
@endphp

<div class="bg-[#f5f7fc] dark:bg-[#0d1226] text-[#1c2238] dark:text-gray-100"
     x-data="{
    bookmarked: false,
    readingProgress: 0,
    activeSection: '',
    showFab: false,
    tocOpen: true,
    sections: @js($toc->pluck('id')->all()),

    init() {
        this.updateReadingProgress();
        this.updateActiveSection();
        this.updatePathLight();
        window.addEventListener('scroll', () => {
            this.updateReadingProgress();
            this.showFab = window.scrollY > 500;
            this.updateActiveSection();
            this.updatePathLight();
        }, { passive: true });
    },

    // Path of Light II: fill the inner timeline's track to the reading
    // line and ignite the step chips it has passed.
    updatePathLight() {
        const path = document.getElementById('article-path');
        if (! path) return;
        const line = window.innerHeight * 0.4;
        const rect = path.getBoundingClientRect();
        path.style.setProperty('--jp', Math.min(1, Math.max(0, (line - rect.top) / Math.max(rect.height, 1))).toFixed(4));
        path.querySelectorAll('[data-path-step]').forEach(step => {
            step.classList.toggle('is-lit', step.getBoundingClientRect().top < line);
        });
    },

    updateReadingProgress() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollPercent = (window.scrollY / Math.max(documentHeight - windowHeight, 1)) * 100;
        this.readingProgress = Math.min(scrollPercent, 100);
    },

    updateActiveSection() {
        for (const section of this.sections) {
            const element = document.getElementById(section);
            if (element) {
                const rect = element.getBoundingClientRect();
                if (rect.top <= 160 && rect.bottom >= 160) {
                    this.activeSection = section;
                    break;
                }
            }
        }
    },

    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    toggleBookmark() {
        this.bookmarked = !this.bookmarked;
        this.$dispatch('show-toast', {
            message: this.bookmarked ? @js(__('seerah.public.bookmark_added')) : @js(__('seerah.public.bookmark_removed'))
        });
    },

    shareArticle() {
        if (navigator.share) {
            navigator.share({
                title: @js($event['title']),
                text: @js(excerpt_sentence($event['description'] ?? $event['title'], 120)),
                url: window.location.href
            });
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href);
            this.$dispatch('show-toast', { message: @js(__('seerah.public.link_copied')) });
        }
    }
}">

    <!-- Toast Notification -->
    {{-- x-on: form is required here — Blade would otherwise compile @show as its own directive --}}
    <div x-data="{ show: false, message: '' }"
         x-on:show-toast.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed top-20 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-2xl bg-white text-gray-900 dark:bg-gray-800 dark:text-white"
         style="display: none;">
        <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
            <span x-text="message" class="font-medium"></span>
        </div>
    </div>

    <!-- Reading Progress Bar -->
    <div class="reading-progress">
        <div class="reading-progress-bar" :style="`width: ${readingProgress}%`"></div>
    </div>

    <main class="pb-12">
        <!-- Hero -->
        <section class="relative overflow-hidden">
            @if (! empty($event['featuredImage']))
            <img src="{{ $event['featuredImage'] }}"
                 alt="{{ $event['title'] }}"
                 width="1920"
                 height="1080"
                 class="absolute inset-0 w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-b from-[#003a78]/90 via-[#0d4d33]/85 to-[#0d1226]/80"></div>
            @else
            <div class="absolute inset-0 bg-gradient-to-br from-[#004a9c] via-[#116a3a] to-[#0a8079]"></div>
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-24 start-[12%] w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute bottom-0 end-[8%] w-72 h-72 rounded-full bg-[#64CCC5]/20 blur-3xl"></div>
            </div>
            @endif

            <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12 text-center text-white">
                @include('public.seerah._event-navigator')

                <!-- Badges -->
                <div class="flex flex-wrap items-center justify-center gap-2 mb-6 ev-anim animate-[fade-in-up_0.6s_ease-out]">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-sm font-bold">
                        <i data-lucide="{{ $event['icon'] }}" class="w-4 h-4"></i>{{ $event['typeLabel'] }}
                    </span>
                    @if ($event['isPivotal'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gradient-to-l from-[#0056b3] to-[#218838] text-white shadow-sm text-xs font-bold">
                            <i data-lucide="star" class="w-3.5 h-3.5"></i>{{ __('seerah.public.pivotal_event') }}
                        </span>
                    @endif
                    @if (($event['importance'] ?? null) === 'high')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-bold">
                            <i data-lucide="flame" class="w-3.5 h-3.5"></i>{{ $event['importanceLabel'] }}
                        </span>
                    @endif
                    @if ($disputeNote)
                        {{-- Anchor, not decoration: jumps to the note itself. --}}
                        <a href="#dispute" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-500/85 border border-red-300/50 text-white text-xs font-bold hover:bg-red-600 transition-colors">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>{{ __('seerah.public.dispute_badge') }}
                        </a>
                    @endif
                </div>

                <!-- Title (description lives in the intro section — not duplicated here) -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight break-words ev-anim animate-[fade-in-up_0.7s_ease-out]">
                    {{ $event['title'] }}
                </h1>

                <!-- Meta chips -->
                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm sm:text-base text-white/90 ev-anim animate-[fade-in-up_0.8s_ease-out]">
                    @if ($event['hijriDate'])
                        <span class="inline-flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4 sm:w-5 sm:h-5"></i>{{ $event['hijriDate'] }}</span>
                    @endif
                    @if ($event['gregorianYear'])
                        <span class="inline-flex items-center gap-2"><i data-lucide="calendar-days" class="w-4 h-4 sm:w-5 sm:h-5"></i>{{ $event['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}</span>
                    @endif
                    @if ($event['location'])
                        <span class="inline-flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4 sm:w-5 sm:h-5"></i>{{ $event['location'] }}</span>
                    @endif
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3 ev-anim animate-[fade-in-up_0.9s_ease-out]">
                    <button @click="document.getElementById('{{ $toc->first()['id'] ?? 'introduction' }}')?.scrollIntoView({ behavior: 'smooth' })"
                            class="px-6 sm:px-8 py-3 sm:py-3.5 bg-white text-[#0056b3] rounded-full font-bold shadow-xl hover:shadow-2xl hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="book-open" class="w-5 h-5"></i>{{ __('seerah.public.start_reading') }}
                    </button>
                    <button @click="toggleBookmark()"
                            class="px-5 sm:px-6 py-3 sm:py-3.5 rounded-full font-bold shadow-lg hover:scale-105 transition-all flex items-center gap-2"
                            :class="bookmarked ? 'bg-[#64CCC5] text-gray-900' : 'bg-white/15 backdrop-blur-md border border-white/20 text-white hover:bg-white/25'">
                        <i :data-lucide="bookmarked ? 'bookmark-check' : 'bookmark-plus'" class="w-5 h-5"></i>
                        <span class="hidden sm:inline" x-text="bookmarked ? @js(__('seerah.public.saved')) : @js(__('seerah.public.save_article'))"></span>
                    </button>
                    <button @click="shareArticle()" aria-label="{{ __('seerah.public.share') }}"
                            class="px-5 sm:px-6 py-3 sm:py-3.5 bg-white/15 backdrop-blur-md border border-white/20 text-white rounded-full font-bold shadow-lg hover:bg-white/25 hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="share-2" class="w-5 h-5"></i><span class="hidden sm:inline">{{ __('seerah.public.share') }}</span>
                    </button>
                    <a href="{{ $timelineStyleUrl }}"
                       class="px-5 sm:px-6 py-3 sm:py-3.5 bg-white/15 backdrop-blur-md border border-white/20 text-white rounded-full font-bold shadow-lg hover:bg-white/25 hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="git-commit-vertical" class="w-5 h-5"></i><span class="hidden sm:inline">{{ __('seerah.public.read_as_timeline') }}</span>
                    </a>
                </div>
            </div>
        </section>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 sm:mt-10">
            <!-- Stats Cards -->
            @if ($hasStatistics)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8 sm:mb-10">
                    @foreach ($statistics as $statLabel => $statValue)
                        <div class="rounded-2xl p-5 sm:p-6 text-center bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="text-2xl sm:text-4xl font-black mb-1 gradient-text break-words">{{ $statValue }}</div>
                            <div class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400">{{ is_string($statLabel) ? $statLabel : '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                <!-- Main Content -->
                <article class="lg:col-span-8 xl:col-span-9 space-y-6 sm:space-y-8 min-w-0">
                    <!-- Introduction -->
                    @if ($hasDescription)
                        <section id="introduction" class="scroll-mt-28 rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center gap-3 sm:gap-4 mb-6">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="info" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('seerah.public.section_intro') }}</h2>
                            </div>
                            <div class="seerah-content text-lg sm:text-xl leading-relaxed text-gray-700 dark:text-gray-300">
                                {!! str_contains($event['description'], '<')
                                    ? app(\App\Domain\Shared\Services\RichTextSanitizer::class)->clean($event['description'])
                                    : nl2br(e($event['description'])) !!}
                            </div>
                        </section>
                    @endif

                    <!-- Dating dispute — the reference chronology's «وقيل…» carried verbatim -->
                    @if ($disputeNote)
                        <section id="dispute" class="scroll-mt-28 flex items-start gap-4 rounded-3xl p-5 sm:p-6 border border-red-300/60 bg-red-50 dark:bg-red-500/10 dark:border-red-400/30">
                            <div class="w-10 h-10 rounded-2xl bg-red-500 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="alert-triangle" class="w-5 h-5 text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold mb-1 text-red-900 dark:text-red-200">{{ __('seerah.public.dispute_heading') }}</p>
                                <p class="leading-relaxed break-words text-red-900/90 dark:text-red-100/90">{{ $disputeNote }}</p>
                            </div>
                        </section>
                    @endif

                    <!-- Context -->
                    @if ($hasContent)
                        <section id="context" class="scroll-mt-28 rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center gap-3 sm:gap-4 mb-6">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="scroll-text" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('seerah.public.section_context') }}</h2>
                            </div>

                            {{-- Authored in the admin Quill editor and purified server-side by
                                 SanitizesRichText (allowlist: p/br/blockquote/h2-h4/inline/
                                 lists/a[href]), so it renders as HTML. No <p> wrapper: the
                                 value already contains block-level tags. --}}
                            <div class="seerah-content text-base sm:text-lg leading-relaxed text-gray-700 dark:text-gray-300 break-words">{!! $event['content'] !!}</div>
                        </section>
                    @endif

                    <!-- Empty content fallback -->
                    @if (! $hasDescription && ! $hasContent)
                        <section id="introduction" class="scroll-mt-28 rounded-3xl p-8 sm:p-12 text-center bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <i data-lucide="file-question" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                            <p class="text-gray-700 dark:text-gray-300">{{ __('seerah.public.no_content') }}</p>
                        </section>
                    @endif

                    <!-- Inner Event Timeline -->
                    @if ($hasTimeline)
                        <section id="timeline" class="scroll-mt-28 rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center gap-3 sm:gap-4 mb-8">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="clock" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('seerah.public.event_timeline_heading') }}</h2>
                            </div>

                            <div id="article-path" class="relative">
                                {{-- continuous track — replaces per-item connectors that
                                     never reached the next chip past a tall card --}}
                                <div class="absolute top-5 bottom-10 start-[17px] sm:start-[19px] w-0.5 rounded-full bg-gray-200 dark:bg-white/10" aria-hidden="true"></div>
                                <div class="ev-path-fill absolute top-5 bottom-10 start-[17px] sm:start-[19px] w-0.5 rounded-full bg-gradient-to-b from-[#0056b3] via-[#64CCC5] to-[#218838]" aria-hidden="true"></div>

                                <div class="space-y-6 sm:space-y-8">
                                    @foreach ($eventTimeline as $index => $timelineItem)
                                        <div class="relative flex items-start gap-3 sm:gap-5" data-path-step>
                                            <div class="ev-step-chip relative z-10 flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold text-sm shadow-md">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="flex-1 min-w-0 rounded-2xl p-4 sm:p-5 bg-gray-50/80 dark:bg-white/[.04] border border-gray-100 dark:border-white/5">
                                                <h3 class="text-lg sm:text-xl font-bold mb-1.5 break-words text-gray-900 dark:text-white">{{ $timelineItem['title'] }}</h3>
                                                @if (! empty($timelineItem['description']))
                                                    <p class="leading-relaxed break-words text-gray-700 dark:text-gray-300">{{ $timelineItem['description'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endif

                    <!-- Key Characters (single source of truth — not repeated in the sidebar) -->
                    @if ($hasCharacters)
                        <section id="characters" class="scroll-mt-28 rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center gap-3 sm:gap-4 mb-8">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="users" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('seerah.public.section_characters') }}</h2>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4 sm:gap-5">
                                @foreach ($event['characters'] as $character)
                                    <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-blue-50 to-green-50 dark:from-white/[.04] dark:to-white/[.02] border border-gray-100 dark:border-white/5">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                                <i data-lucide="user" class="w-5 h-5 text-white"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white break-words">{{ $character['name'] }}</h3>
                                                @if (! empty($character['roleLabel']))
                                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#0056b3]/10 text-[#0056b3] dark:bg-[#64CCC5]/15 dark:text-[#64CCC5]">{{ $character['roleLabel'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if (! empty($character['description']))
                                            <p class="text-sm sm:text-base leading-relaxed text-gray-700 dark:text-gray-300">{{ $character['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- Lessons -->
                    @if ($hasLessons)
                        <section id="lessons" class="scroll-mt-28 rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center gap-3 sm:gap-4 mb-8">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="lightbulb" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('seerah.public.section_lessons') }}</h2>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4 sm:gap-5">
                                @foreach ($lessons as $lesson)
                                    <div class="rounded-2xl p-5 sm:p-6 bg-amber-50/60 dark:bg-white/[.04]">
                                        <span class="block w-10 h-[3px] rounded-full bg-gradient-to-l from-amber-500 to-orange-600 mb-3" aria-hidden="true"></span>
                                        <h3 class="font-bold text-lg mb-2 break-words text-gray-900 dark:text-white">{{ $lesson['title'] }}</h3>
                                        @if (! empty($lesson['description']))
                                            <p class="leading-relaxed break-words text-gray-700 dark:text-gray-300">{{ $lesson['description'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- Sources -->
                    @if ($hasSources)
                        <section id="sources" class="scroll-mt-28 rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center gap-3 sm:gap-4 mb-6">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-slate-500 to-slate-700 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="library" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('seerah.public.section_sources') }}</h2>
                            </div>
                            <ul class="space-y-4">
                                @foreach ($sources as $source)
                                    <li class="flex items-start gap-3">
                                        <i data-lucide="book-marked" class="w-5 h-5 mt-1 flex-shrink-0 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                        <div class="min-w-0">
                                            <p class="font-semibold break-words text-gray-900 dark:text-white">{{ $source['title'] }}</p>
                                            @if (! empty($source['author']) || ! empty($source['reference']))
                                                <p class="text-sm break-words text-gray-600 dark:text-gray-400">
                                                    {{ trim(collect([$source['author'] ?? null, $source['reference'] ?? null])->filter()->implode(' — ')) }}
                                                </p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <!-- Gallery -->
                    @if (! empty($event['gallery']))
                        <section class="rounded-3xl p-6 sm:p-10 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <h2 class="text-2xl sm:text-3xl font-bold mb-6 flex items-center gap-3 text-gray-900 dark:text-white">
                                <i data-lucide="images" class="w-6 h-6 sm:w-7 sm:h-7 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                {{ __('seerah.public.event_gallery') }}
                            </h2>
                            <div class="grid sm:grid-cols-2 gap-4">
                                @foreach ($event['gallery'] as $image)
                                    <img src="{{ $image }}"
                                         alt="{{ $event['title'] }}"
                                         width="800"
                                         height="600"
                                         loading="lazy"
                                         class="w-full aspect-[4/3] object-cover rounded-2xl" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                </article>

                <!-- Sidebar -->
                <aside class="lg:col-span-4 xl:col-span-3 mt-8 lg:mt-0 min-w-0">
                    <div class="lg:sticky lg:top-24 space-y-5">
                        <!-- Table of Contents -->
                        @if ($toc->isNotEmpty())
                        <div class="rounded-2xl p-5 sm:p-6 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold flex items-center gap-2.5 text-gray-900 dark:text-white">
                                    <i data-lucide="list" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                    {{ __('seerah.public.on_this_page') }}
                                </h3>
                                <button @click="tocOpen = !tocOpen" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-white/10" :aria-expanded="tocOpen">
                                    <i :data-lucide="tocOpen ? 'chevron-up' : 'chevron-down'" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <ul x-show="tocOpen" x-transition class="relative space-y-1.5">
                                {{-- lit trail: rail + fill track the reading position --}}
                                <div class="absolute top-3 bottom-3 start-[20px] w-0.5 rounded-full bg-gray-200 dark:bg-white/10" aria-hidden="true"></div>
                                <div class="ev-toc-fill absolute top-3 start-[20px] w-0.5 rounded-full bg-gradient-to-b from-[#0056b3] via-[#64CCC5] to-[#218838]"
                                     :style="`--tp: ${activeSection ? Math.min(1, (sections.indexOf(activeSection) + 0.5) / sections.length) : 0}`"
                                     aria-hidden="true"></div>
                                @foreach ($toc as $tocItem)
                                    <li>
                                        <a href="#{{ $tocItem['id'] }}"
                                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all"
                                           :class="activeSection === '{{ $tocItem['id'] }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : 'hover:bg-gray-50 text-gray-700 dark:hover:bg-white/5 dark:text-gray-300'">
                                            <span class="ev-toc-dot"
                                                  :class="activeSection && sections.indexOf('{{ $tocItem['id'] }}') <= sections.indexOf(activeSection) ? 'is-on' : ''"></span>
                                            {{ $tocItem['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Event facts -->
                        @if ($facts->isNotEmpty())
                        <div class="rounded-2xl p-5 sm:p-6 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2.5 text-gray-900 dark:text-white">
                                <i data-lucide="badge-info" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                {{ __('seerah.public.section_details') }}
                            </h3>
                            <dl class="space-y-3">
                                @foreach ($facts as $fact)
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#0056b3]/8 dark:bg-white/5 text-[#0056b3] dark:text-[#64CCC5] flex-shrink-0">
                                            <i data-lucide="{{ $fact['icon'] }}" class="w-4 h-4"></i>
                                        </span>
                                        <dd class="text-sm font-semibold text-gray-800 dark:text-gray-200 break-words min-w-0">{{ $fact['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                        @endif

                        <!-- Event Location Map -->
                        @if ($event['mapUrl'])
                            <div class="rounded-2xl overflow-hidden bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                                <div class="p-5">
                                    <h3 class="text-lg font-bold flex items-center gap-2.5 text-gray-900 dark:text-white">
                                        <i data-lucide="map-pin" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                        {{ __('seerah.public.event_location') }}
                                    </h3>
                                </div>
                                <iframe src="{{ $event['mapUrl'] }}"
                                        title="{{ __('seerah.public.event_location_map') }}"
                                        width="600"
                                        height="256"
                                        loading="lazy"
                                        class="w-full h-56 border-0"></iframe>
                                {{-- The canonical, localized place name sits above the
                                     historian's descriptive note. Both are shown: the
                                     note carries detail no place record holds
                                     ("على نحو 8 أميال جنوب المدينة"). --}}
                                @if ($event['place'] || $event['location'])
                                    <div class="p-4 space-y-1.5">
                                        @if ($event['place'] && $event['place']['name'])
                                            <p class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-white">
                                                <i data-lucide="map-pin" class="w-4 h-4 flex-shrink-0"></i>{{ $event['place']['name'] }}
                                            </p>
                                        @endif
                                        @if ($event['location'])
                                            <p class="text-sm flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                                <i data-lucide="map" class="w-4 h-4 flex-shrink-0"></i>{{ $event['location'] }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($event['relatedArticle'])
                            <a href="{{ $event['relatedArticle']['url'] }}"
                               class="block rounded-2xl p-5 sm:p-6 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm transition-transform hover:-translate-y-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('seerah.public.related_article') }}</span>
                                <span class="mt-2 flex items-center gap-3 font-bold text-gray-900 dark:text-white line-clamp-2">
                                    <i data-lucide="newspaper" class="w-5 h-5 shrink-0 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                    {{ $event['relatedArticle']['title'] }}
                                </span>
                            </a>
                        @endif

                        <!-- Related Events -->
                        @if (! empty($related))
                            <div class="rounded-2xl p-5 sm:p-6 bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm">
                                <h3 class="text-lg font-bold mb-4 flex items-center gap-2.5 text-gray-900 dark:text-white">
                                    <i data-lucide="link" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                    {{ __('seerah.public.related_events') }}
                                </h3>

                                <ul class="space-y-1.5">
                                    @foreach ($related as $rel)
                                        <li>
                                            <a href="{{ $rel['url'] }}" class="block px-4 py-2.5 rounded-xl text-sm font-semibold transition-all hover:bg-gray-50 text-gray-700 dark:hover:bg-white/5 dark:text-gray-300 line-clamp-2">
                                                {{ $rel['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>

            @if (! empty($prev) || ! empty($next))
                <nav class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-4" aria-label="{{ __('seerah.public.event_navigation') }}">
                    @if (! empty($prev))
                        <a href="{{ $prev['url'] }}" class="group p-5 flex items-center gap-4 rounded-2xl bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <span class="w-11 h-11 rounded-full bg-gradient-to-l from-[#0056b3] to-[#218838] text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('seerah.public.previous_event') }}</span>
                                <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">{{ $prev['title'] }}</span>
                            </span>
                        </a>
                    @else
                        <span class="hidden md:block"></span>
                    @endif

                    @if (! empty($next))
                        <a href="{{ $next['url'] }}" class="group p-5 flex items-center gap-4 rounded-2xl bg-white dark:bg-[#161d38] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all text-end">
                            <span class="flex-1 min-w-0">
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('seerah.public.next_event') }}</span>
                                <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">{{ $next['title'] }}</span>
                            </span>
                            <span class="w-11 h-11 rounded-full bg-gradient-to-l from-[#0056b3] to-[#218838] text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
                            </span>
                        </a>
                    @endif
                </nav>
            @endif
        </div>
    </main>

    <!-- Browse stations (milestone navigator) -->
    <button type="button"
            @click="$dispatch('seerah-navigator-open')"
            class="fixed bottom-[9.5rem] end-5 sm:end-8 z-50 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:scale-110 transition-all flex items-center justify-center"
            title="{{ __('seerah.public.navigator_open') }}"
            aria-label="{{ __('seerah.public.navigator_open') }}">
        <i data-lucide="map" class="w-6 h-6"></i>
    </button>

    <!-- Style Switch: view as timeline -->
    <a href="{{ $timelineStyleUrl }}"
       class="fixed bottom-[5.5rem] end-5 sm:end-8 z-50 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:scale-110 transition-all flex items-center justify-center"
       title="{{ __('seerah.public.read_as_timeline') }}"
       aria-label="{{ __('seerah.public.read_as_timeline') }}">
        <i data-lucide="git-commit-vertical" class="w-6 h-6"></i>
    </a>

    <!-- Scroll to top -->
    <button x-show="showFab"
            @click="scrollToTop()"
            x-transition
            class="fixed bottom-6 end-5 sm:end-8 z-50 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:scale-110 transition-all flex items-center justify-center"
            aria-label="{{ __('seerah.public.scroll_to_top') }}"
            style="display: none;">
        <i data-lucide="arrow-up" class="w-6 h-6"></i>
    </button>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
