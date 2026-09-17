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

    @keyframes tl-fade-in-up {
        0% { opacity: 0; transform: translateY(30px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes tl-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-12px); }
    }

    @keyframes tl-gradient-shift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .tl-fade-in-up { animation: tl-fade-in-up 0.8s ease-out both; }
    .tl-float { animation: tl-float 5s ease-in-out infinite; }

    .tl-hero-gradient {
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.92) 0%, rgba(33, 136, 56, 0.88) 60%, rgba(100, 204, 197, 0.85) 100%);
        background-size: 200% 200%;
        animation: tl-gradient-shift 12s ease-in-out infinite;
    }

    .tl-reading-progress {
        position: fixed;
        top: 64px;
        inset-inline: 0;
        height: 4px;
        background: rgba(0, 0, 0, 0.1);
        z-index: 40;
    }

    .tl-reading-progress-bar {
        height: 100%;
        border-radius: 0 2px 2px 0;
        background: linear-gradient(90deg, #0056b3 0%, #64CCC5 60%, #218838 100%);
        box-shadow: 0 0 10px rgba(100, 204, 197, .65);
        transition: width 0.2s ease-out;
    }

    .tl-item { transition: opacity 0.5s ease-out, transform 0.5s ease-out; }
    .tl-item[data-revealed="false"] { opacity: 0; transform: translateY(24px); }
    .tl-item[data-revealed="true"] { opacity: 1; transform: translateY(0); }

    .tl-card-hover { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease; }
    .tl-card-hover:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0, 86, 179, 0.2); }

    .tl-stagger-1 { animation-delay: 0.1s; }
    .tl-stagger-2 { animation-delay: 0.2s; }
    .tl-stagger-3 { animation-delay: 0.3s; }
    .tl-stagger-4 { animation-delay: 0.4s; }

    @media (prefers-reduced-motion: reduce) {
        .tl-fade-in-up, .tl-float, .tl-hero-gradient { animation: none !important; }
        .tl-item { transition: none; }
        .tl-item[data-revealed="false"] { opacity: 1; transform: none; }
        .tl-card-hover:hover { transform: none; }
        .tl-parallax-img { transform: none !important; }
    }
</style>
@endpush

@section('content')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $hasDescription = ! empty($event['description']);
    $hasContent = ! empty($event['content']);
    $details = collect($event['details'] ?? [])->filter(fn ($d) => ! empty($d['key']));
    $hasDetails = $details->isNotEmpty();
    $hasMedia = ! empty($event['media']);
    $statistics = collect($event['statistics'] ?? [])->filter(fn ($v) => is_scalar($v))->take(4);
    $hasStatistics = $statistics->isNotEmpty();
    $sources = collect($event['sources'] ?? [])->filter(fn ($x) => ! empty($x['title']));
    $hasSources = $sources->isNotEmpty();
    $disputeNote = $event['disputeNote'] ?? null;
    // كل علم باسمه.
    $facts = collect([
        ['icon' => 'calendar', 'label' => __('seerah.public.hijri_date_label'), 'value' => $event['hijriDate']],
        ['icon' => 'calendar-days', 'label' => __('seerah.public.gregorian_year_label'), 'value' => $event['gregorianYear'] ? $event['gregorianYear'].' '.__('seerah.public.gregorian_suffix') : null],
        ['icon' => $event['icon'], 'label' => __('seerah.public.type_label'), 'value' => $event['typeLabel']],
        ['icon' => 'map-pin', 'label' => __('seerah.public.location_label'), 'value' => $event['place']['name'] ?? null],
        ['icon' => 'landmark', 'label' => __('seerah.public.era_label'), 'value' => $event['eraLabel']],
        ['icon' => 'flag', 'label' => __('seerah.public.milestone_label'), 'value' => $event['milestone']['title'] ?? null],
    ])->filter(fn ($fact) => ! empty($fact['value']))->values();
    // Timeline is the default style now — the switch link opts into the article view.
    $articleStyleUrl = route('public.seerah.event', ['slug' => $event['slug'], 'view' => 'article']);
@endphp

<div x-data="{
    darkMode: $store.theme.dark,
    readingProgress: 0,
    parallaxOffset: 0,
    showShareMenu: false,
    isLiked: false,
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    stats: @js($statistics->map(fn ($value) => [
        'target' => is_numeric($value) ? (float) $value : null,
        'display' => (string) $value,
        'current' => is_numeric($value) ? 0 : (string) $value,
    ])->values()->all()),
    statsAnimated: false,

    init() {
        this.$watch('$store.theme.dark', v => this.darkMode = v);
        this.onScroll();
        window.addEventListener('scroll', () => this.onScroll(), { passive: true });

        if (this.reducedMotion) {
            this.revealAllTimelineItems();
            this.finishStats();
        } else {
            this.revealTimelineItems();
            this.animateStatsWhenVisible();
        }
    },

    onScroll() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.scrollY;
        this.readingProgress = Math.min((scrollTop / Math.max(documentHeight - windowHeight, 1)) * 100, 100);

        if (! this.reducedMotion) {
            this.parallaxOffset = scrollTop * 0.35;
            this.revealTimelineItems();
            this.animateStatsWhenVisible();
        }
    },

    revealTimelineItems() {
        document.querySelectorAll('.tl-item').forEach((el, index) => {
            if (el.dataset.revealed === 'true') return;
            if (el.getBoundingClientRect().top < window.innerHeight * 0.85) {
                setTimeout(() => { el.dataset.revealed = 'true'; }, index * 80);
            }
        });
    },

    revealAllTimelineItems() {
        document.querySelectorAll('.tl-item').forEach(el => { el.dataset.revealed = 'true'; });
    },

    animateStatsWhenVisible() {
        if (this.statsAnimated || this.stats.length === 0) return;
        const section = document.getElementById('event-stats');
        if (! section || section.getBoundingClientRect().top > window.innerHeight * 0.85) return;
        this.statsAnimated = true;

        const steps = 50;
        this.stats.forEach(stat => {
            if (stat.target === null) { stat.current = stat.display; return; }
            let step = 0;
            const increment = stat.target / steps;
            const interval = setInterval(() => {
                step++;
                if (step >= steps) {
                    stat.current = stat.target.toLocaleString();
                    clearInterval(interval);
                } else {
                    stat.current = Math.round(increment * step).toLocaleString();
                }
            }, 30);
        });
    },

    finishStats() {
        this.statsAnimated = true;
        this.stats.forEach(stat => {
            stat.current = stat.target === null ? stat.display : stat.target.toLocaleString();
        });
    },

    shareEvent(platform) {
        const text = @js($event['title']);
        const url = window.location.href;
        let shareUrl = '';

        switch (platform) {
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text + ' ' + url)}`;
                break;
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
                break;
        }

        if (shareUrl) window.open(shareUrl, '_blank', 'noopener,width=600,height=400');
        this.showShareMenu = false;
    },

    copyLink() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href);
        }
        this.$dispatch('show-toast', { message: @js(__('seerah.public.link_copied')) });
        this.showShareMenu = false;
    },

    scrollToSection(sectionId) {
        document.getElementById(sectionId)?.scrollIntoView({ behavior: this.reducedMotion ? 'auto' : 'smooth' });
    }
}"
@keydown.escape.window="showShareMenu = false">

    <!-- Toast Notification -->
    {{-- x-on: form is required here — Blade would otherwise compile @show as its own directive --}}
    <div x-data="{ show: false, message: '' }"
         x-on:show-toast.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition
         class="fixed top-20 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-2xl"
         :class="darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'"
         style="display: none;">
        <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
            <span x-text="message" class="font-medium"></span>
        </div>
    </div>

    <!-- Reading Progress Bar -->
    <div class="tl-reading-progress">
        <div class="tl-reading-progress-bar" :style="`width: ${readingProgress}%`"></div>
    </div>

    <main class="pb-12">
        <!-- Parallax Hero -->
        <section id="event-hero" class="relative min-h-[70vh] flex items-center justify-center overflow-hidden">
            {{-- See event.blade.php: the fallback asset does not exist. --}}
            @if (! empty($event['coverImage']))
            <img src="{{ $event['coverImage'] }}"
                 alt="{{ $event['title'] }}"
                 width="1920"
                 height="1080"
                 loading="lazy"
                 class="tl-parallax-img absolute inset-0 w-full h-[120%] object-cover"
                 :style="`transform: translateY(${parallaxOffset}px)`" />
            @endif
            <div class="absolute inset-0 tl-hero-gradient"></div>

            <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 text-center text-white">
                @include('public.seerah._event-navigator')

                <!-- Date Badge -->
                @if ($event['hijriDate'] || $event['gregorianYear'])
                    <div class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white/10 backdrop-blur-md border border-white/20 mb-8 tl-float">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                        <span class="font-semibold">
                            {{ $event['hijriDate'] }}
                            @if ($event['hijriDate'] && $event['gregorianYear']) / @endif
                            @if ($event['gregorianYear']){{ $event['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}@endif
                        </span>
                    </div>
                @endif

                <!-- Type Badge -->
                <div class="flex items-center justify-center gap-2 mb-6 tl-fade-in-up">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 backdrop-blur-md text-sm font-semibold">
                        <i data-lucide="{{ $event['icon'] }}" class="w-4 h-4"></i>
                        {{ $event['typeLabel'] }}
                    </span>
                    @if ($disputeNote)
                        {{-- Anchor, not decoration: jumps to the note itself. --}}
                        <a href="#event-dispute"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-500/85 border border-red-300/50 text-white text-xs font-bold hover:bg-red-600 transition-colors">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                            {{ __('seerah.public.dispute_badge') }}
                        </a>
                    @endif
                </div>

                <h1 class="text-4xl md:text-6xl lg:text-7xl font-black mb-6 leading-tight break-words tl-fade-in-up">{{ $event['title'] }}</h1>

                @if ($hasDescription)
                    <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto mb-8 line-clamp-3 tl-fade-in-up tl-stagger-1">
                        {{ excerpt_sentence($event['description'], 180) }}
                    </p>
                @endif

                @if ($event['place'])
                    <div class="flex items-center justify-center gap-2 text-lg mb-10 tl-fade-in-up tl-stagger-2">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                        <span class="break-words">{{ $event['place']['name'] }}</span>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-center gap-4 tl-fade-in-up tl-stagger-3">
                    <button @click="scrollToSection('{{ $hasStatistics ? 'event-stats' : 'event-story' }}')"
                            class="px-8 py-4 rounded-full bg-white text-[#0056b3] font-bold text-base sm:text-lg shadow-2xl hover:scale-105 transition-all flex items-center gap-2">
                        <span>{{ __('seerah.public.explore_story') }}</span>
                        <i data-lucide="arrow-down" class="w-5 h-5"></i>
                    </button>
                    <a href="{{ $articleStyleUrl }}"
                       class="px-8 py-4 rounded-full bg-white/10 backdrop-blur-md border-2 border-white/30 text-white font-bold text-base sm:text-lg hover:bg-white/20 transition-all flex items-center gap-2">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>{{ __('seerah.public.read_as_article') }}</span>
                    </a>
                </div>
            </div>

            <!-- Scroll Indicator -->
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 tl-float">
                <i data-lucide="chevron-down" class="w-8 h-8 text-white/70"></i>
            </div>
        </section>

        <!-- Dating dispute — the reference chronology's «وقيل…» carried verbatim -->
        @if ($disputeNote)
            <section id="event-dispute" class="scroll-mt-28 pt-10 sm:pt-12">
                <div class="max-w-4xl mx-auto px-4 sm:px-6">
                    <div class="flex items-start gap-4 rounded-3xl p-5 sm:p-6 border border-red-300/60 bg-red-50 dark:bg-red-500/10 dark:border-red-400/30">
                        <div class="w-10 h-10 rounded-2xl bg-red-500 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold mb-1 text-red-900 dark:text-red-200">{{ __('seerah.public.dispute_heading') }}</p>
                            <p class="leading-relaxed break-words text-red-900/90 dark:text-red-100/90">{{ $disputeNote }}</p>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <!-- بطاقة الحقائق: كل علم باسمه، لا أيقونة مجرّدة -->
        @if ($facts->isNotEmpty())
            <section id="event-facts" class="scroll-mt-28 pt-10 sm:pt-12">
                <div class="max-w-4xl mx-auto px-4 sm:px-6">
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($facts as $fact)
                            <div class="rounded-2xl p-4 border" :class="darkMode ? 'bg-gray-900/50 border-white/10' : 'bg-white border-gray-200'">
                                <dt class="flex items-center gap-1.5 text-xs font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    <i data-lucide="{{ $fact['icon'] }}" class="w-3.5 h-3.5"></i>{{ $fact['label'] }}
                                </dt>
                                <dd class="mt-1 font-bold break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $fact['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>
        @endif

        <!-- Animated Stats -->
        @if ($hasStatistics)
            <section id="event-stats" class="py-16 sm:py-20" :class="darkMode ? 'bg-gray-900/40' : 'bg-white'">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl md:text-5xl font-bold text-center mb-12 flex items-center justify-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="bar-chart-3" class="w-9 h-9 text-[#0056b3]"></i>
                        {{ __('seerah.public.event_stats_heading') }}
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-8 max-w-5xl mx-auto">
                        @foreach ($statistics as $statLabel => $statValue)
                            <div class="tl-card-hover p-6 sm:p-8 rounded-3xl text-center shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                <div class="text-4xl sm:text-5xl font-black mb-2 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent break-words"
                                     x-text="stats[{{ $loop->index }}]?.current ?? @js((string) $statValue)"></div>
                                <div class="text-sm sm:text-base font-semibold truncate" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ is_string($statLabel) ? $statLabel : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Story / Context (real content) -->
        @if ($hasDescription || $hasContent)
            <section id="event-story" class="py-16 sm:py-20" :class="darkMode ? 'bg-gray-900/40' : 'bg-white'">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
                    <h2 class="text-3xl md:text-5xl font-bold text-center mb-12 flex items-center justify-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="scroll-text" class="w-9 h-9 text-[#0056b3]"></i>
                        {{ __('seerah.public.section_context') }}
                    </h2>
                    <div class="prose prose-lg max-w-none" :class="darkMode ? 'prose-invert' : ''">
                        {{-- The hero already shows the description excerpt — repeat the
                             full description here only when there is no rich content,
                             otherwise the page opens with the same text twice. --}}
                        @if ($hasDescription && ! $hasContent)
                            <div class="seerah-content text-lg sm:text-xl leading-relaxed break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {!! str_contains($event['description'], '<')
                                    ? app(\App\Domain\Shared\Services\RichTextSanitizer::class)->clean($event['description'])
                                    : nl2br(e($event['description'])) !!}
                            </div>
                        @endif
                        @if ($hasContent)
                            {{-- Purified admin HTML (see SanitizesRichText). Not wrapped in <p> —
                                 the value already contains block tags. --}}
                            <div class="seerah-content text-base sm:text-lg leading-relaxed break-words"
                                 :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{!! $event['content'] !!}</div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <!-- Empty content fallback -->
        @if (! $hasDescription && ! $hasContent)
            <section class="py-20 text-center">
                <i data-lucide="file-question" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('seerah.public.no_content') }}</p>
            </section>
        @endif

        @if ($hasDetails)
            <section id="event-details" class="py-16 sm:py-20" :class="darkMode ? 'bg-gray-900/40' : 'bg-white'">
                <div class="max-w-4xl mx-auto px-4 sm:px-6">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                            <i data-lucide="list" class="w-6 h-6 text-white"></i>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.section_more_details') }}</h2>
                    </div>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($details as $detail)
                            <div class="tl-item rounded-2xl p-5" data-revealed="false" :class="darkMode ? 'bg-gray-800/60' : 'bg-gray-50'">
                                <dt class="text-sm break-words" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $detail['key'] }}</dt>
                                <dd class="mt-1 font-bold break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $detail['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>
        @endif

            @if ($hasSources)
            <section id="event-sources" class="py-16 sm:py-20">
                <div class="max-w-4xl mx-auto px-4 sm:px-6">
                    <div class="flex items-center gap-4 mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-500 to-slate-700 flex items-center justify-center">
                            <i data-lucide="library" class="w-6 h-6 text-white"></i>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.section_sources') }}</h2>
                    </div>
                    <ul class="space-y-4">
                        @foreach ($sources as $source)
                            <li class="tl-item flex items-start gap-3" data-revealed="false">
                                <i data-lucide="book-marked" class="w-5 h-5 mt-1 flex-shrink-0 text-[#0056b3]"></i>
                                <div class="min-w-0">
                                    <p class="font-semibold break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $source['title'] }}</p>
                                    @if (! empty($source['author']) || ! empty($source['reference']))
                                        <p class="text-sm break-words" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ trim(collect([$source['author'] ?? null, $source['reference'] ?? null])->filter()->implode(' — ')) }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
            @endif

        @if ($hasMedia)
            <section id="event-media" class="py-16 sm:py-20">
                <div class="max-w-4xl mx-auto px-4 sm:px-6">
                    <h2 class="text-3xl md:text-5xl font-bold text-center mb-12 flex items-center justify-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="play-circle" class="w-9 h-9 text-[#0056b3]"></i>
                        {{ __('seerah.public.section_media') }}
                    </h2>
                    <div class="space-y-6">
                        @foreach ($event['media'] as $item)
                            @if ($item['type'] === 'video')
                                <video controls preload="metadata" class="w-full rounded-3xl shadow-xl bg-black" src="{{ $item['url'] }}" aria-label="{{ $item['title'] }}"></video>
                            @elseif ($item['type'] === 'audio')
                                <div class="rounded-3xl p-6 shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                    <p class="mb-3 font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $item['title'] }}</p>
                                    <audio controls preload="metadata" class="w-full" src="{{ $item['url'] }}"></audio>
                                </div>
                            @else
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                   class="tl-card-hover flex items-center gap-3 rounded-3xl p-6 shadow-xl font-bold"
                                   :class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'">
                                    <i data-lucide="file" class="w-6 h-6 shrink-0 text-[#0056b3]"></i>{{ $item['title'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if (! empty($event['gallery']))
            <section class="py-16 sm:py-20" :class="darkMode ? 'bg-gray-900/40' : 'bg-white'">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
                    <h2 class="text-3xl md:text-5xl font-bold text-center mb-12 flex items-center justify-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="images" class="w-9 h-9 text-[#0056b3]"></i>
                        {{ __('seerah.public.event_gallery') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($event['gallery'] as $image)
                            <figure>
                                <img src="{{ $image['url'] }}"
                                     alt="{{ $image['caption'] ?: $event['title'] }}"
                                     width="800"
                                     height="600"
                                     loading="lazy"
                                     class="w-full aspect-[4/3] object-cover rounded-3xl shadow-xl" />
                                @if ($image['caption'])
                                    <figcaption class="mt-2 text-sm text-center" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $image['caption'] }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($event['mapUrl'] || ! empty($related))
            <section class="py-16 sm:py-20">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl grid lg:grid-cols-2 gap-6">
                    @if ($event['mapUrl'])
                        <div class="rounded-3xl overflow-hidden shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="p-6">
                                <h2 class="text-2xl font-bold flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <i data-lucide="map-pin" class="w-6 h-6 text-[#0056b3]"></i>
                                    {{ __('seerah.public.event_location') }}
                                </h2>
                            </div>
                            <iframe src="{{ $event['mapUrl'] }}"
                                    title="{{ __('seerah.public.event_location_map') }}"
                                    width="600"
                                    height="320"
                                    loading="lazy"
                                    class="w-full h-80 border-0"></iframe>
                        </div>
                    @endif

                    <div class="space-y-6">
                        @if (! empty($related))
                            <div class="rounded-3xl p-6 shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                <h2 class="text-2xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.related_events') }}</h2>
                                <ul class="space-y-2">
                                    @foreach ($related as $rel)
                                        <li>
                                            <a href="{{ $rel['url'] }}" class="block px-4 py-3 rounded-xl line-clamp-2" :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">{{ $rel['title'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if (! empty($prev) || ! empty($next))
            <nav class="container mx-auto px-4 sm:px-6 lg:px-8 mt-10 grid grid-cols-1 md:grid-cols-2 gap-4" aria-label="{{ __('seerah.public.event_navigation') }}">
                @if (! empty($prev))
                    <a href="{{ $prev['url'] }}" class="group card-premium p-5 flex items-center gap-4 hover-lift">
                        <span class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('seerah.public.previous_event') }}</span>
                            <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors">{{ $prev['title'] }}</span>
                        </span>
                    </a>
                @else
                    <span class="hidden md:block"></span>
                @endif

                @if (! empty($next))
                    <a href="{{ $next['url'] }}" class="group card-premium p-5 flex items-center gap-4 hover-lift text-end">
                        <span class="flex-1 min-w-0">
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('seerah.public.next_event') }}</span>
                            <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors">{{ $next['title'] }}</span>
                        </span>
                        <span class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
                        </span>
                    </a>
                @endif
            </nav>
        @endif
    </main>

    <!-- FABs -->
    <div class="fixed bottom-8 start-8 z-50 flex flex-col gap-3 items-center">
        <!-- Browse stations (milestone navigator) -->
        <button type="button"
                @click="$dispatch('seerah-navigator-open')"
                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:scale-110 transition-all flex items-center justify-center"
                title="{{ __('seerah.public.navigator_open') }}"
                aria-label="{{ __('seerah.public.navigator_open') }}">
            <i data-lucide="map" class="w-6 h-6"></i>
        </button>

        <!-- Read as Article -->
        <a href="{{ $articleStyleUrl }}"
           class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:scale-110 transition-all flex items-center justify-center"
           title="{{ __('seerah.public.read_as_article') }}"
           aria-label="{{ __('seerah.public.read_as_article') }}">
            <i data-lucide="file-text" class="w-6 h-6"></i>
        </a>

        <!-- Like -->
        <button type="button"
                @click="isLiked = !isLiked"
                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full text-white shadow-2xl hover:scale-110 transition-all flex items-center justify-center"
                :class="isLiked ? 'bg-red-500' : 'bg-gray-600'"
                title="{{ __('seerah.public.like') }}"
                aria-label="{{ __('seerah.public.like') }}">
            <i data-lucide="heart" class="w-6 h-6" :class="isLiked ? 'fill-current' : ''"></i>
        </button>

        <!-- Share menu -->
        <div class="relative flex flex-col items-center">
            <div x-show="showShareMenu"
                 x-cloak
                 x-transition
                 @click.outside="showShareMenu = false"
                 class="absolute bottom-full mb-3 flex flex-col gap-3 items-center">
                <button type="button" @click="shareEvent('twitter')" class="p-3 rounded-full bg-[#1DA1F2] text-white shadow-lg hover:scale-110 transition-transform" aria-label="Twitter">
                    <i data-lucide="twitter" class="w-5 h-5"></i>
                </button>
                <button type="button" @click="shareEvent('facebook')" class="p-3 rounded-full bg-[#4267B2] text-white shadow-lg hover:scale-110 transition-transform" aria-label="Facebook">
                    <i data-lucide="facebook" class="w-5 h-5"></i>
                </button>
                <button type="button" @click="shareEvent('whatsapp')" class="p-3 rounded-full bg-[#25D366] text-white shadow-lg hover:scale-110 transition-transform" aria-label="WhatsApp">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                </button>
                <button type="button" @click="copyLink()" class="p-3 rounded-full bg-gray-700 text-white shadow-lg hover:scale-110 transition-transform" title="{{ __('seerah.public.link_copied') }}" aria-label="{{ __('seerah.public.share') }}">
                    <i data-lucide="link" class="w-5 h-5"></i>
                </button>
            </div>

            <button type="button"
                    @click="showShareMenu = !showShareMenu"
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl transition-all flex items-center justify-center"
                    :class="showShareMenu ? 'rotate-45' : ''"
                    title="{{ __('seerah.public.share') }}"
                    aria-label="{{ __('seerah.public.share') }}">
                <i data-lucide="share-2" class="w-6 h-6"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
