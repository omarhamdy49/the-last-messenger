@extends('layouts.public')

@section('title', $event['title'] . ' — ' . __('seerah.public.page_title'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($event['description'] ?? $event['title'])), 155))
@section('og_type', 'article')

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": @json($event['title']),
    "description": @json(\Illuminate\Support\Str::limit(strip_tags((string) ($event['description'] ?? '')), 200)),
    "url": "{{ $event['url'] }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    /* Was <style type="text/tailwindcss"> with a v4 @theme{} block — inert under this
       project's Tailwind v3 build (the browser never parsed it), so every rule below
       silently failed. Switched to plain CSS and bound the animation utilities the
       page uses (the @theme map used to generate them in v4). */
    @keyframes fade-in-up {
        0% { opacity: 0; transform: translateY(30px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes fade-in {
        0% { opacity: 0; }
        100% { opacity: 1; }
    }

    @keyframes slide-in-right {
        0% { opacity: 0; transform: translateX(-40px); }
        100% { opacity: 1; transform: translateX(0); }
    }

    @keyframes scale-in {
        0% { opacity: 0; transform: scale(0.95); }
        100% { opacity: 1; transform: scale(1); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    @keyframes pulse-slow {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(100, 204, 197, 0.3); }
        50% { box-shadow: 0 0 40px rgba(100, 204, 197, 0.6); }
    }

    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
    .stagger-4 { animation-delay: 0.4s; }

    /* animate-fade-in-up and animate-float are provided globally by app.css. */
    .animate-fade-in { animation: fade-in 1s ease-out; }
    .animate-slide-in-right { animation: slide-in-right 0.6s ease-out; }
    .animate-scale-in { animation: scale-in 0.5s ease-out; }
    .animate-pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }
    .animate-glow { animation: glow 2s ease-in-out infinite; }

    .glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .glass-light {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .gradient-text {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        border-radius: 10px;
    }

    /* Timeline Styles */
    .timeline-item {
        position: relative;
        padding-inline-start: 2rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: -2rem;
        width: 2px;
        background: linear-gradient(180deg, #0056b3 0%, #218838 100%);
    }

    .timeline-item:last-child::before {
        display: none;
    }

    .timeline-dot {
        position: absolute;
        inset-inline-start: -0.5rem;
        top: 0.5rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.2);
        z-index: 10;
    }

    .timeline-dot.active {
        animation: glow 2s ease-in-out infinite;
    }

    /* Hero Parallax Effect */
    .hero-parallax {
        position: relative;
        overflow: hidden;
    }

    .hero-parallax::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.9) 0%, rgba(33, 136, 56, 0.85) 100%);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    /* Stats Counter Animation */
    @keyframes countUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stat-card {
        animation: countUp 0.8s ease-out;
    }

    /* Interactive Map Container */
    .map-container {
        position: relative;
        overflow: hidden;
        border-radius: 1rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .map-container::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
        pointer-events: none;
    }

    /* Character Card Hover */
    .character-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .character-card:hover {
        transform: translateY(-8px) scale(1.05);
    }

    /* Reading Progress Bar */
    .reading-progress {
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(0, 0, 0, 0.1);
        z-index: 40;
    }

    .reading-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #0056b3 0%, #218838 100%);
        transition: width 0.2s ease-out;
    }

    /* Floating Action Button */
    .fab {
        position: fixed;
        bottom: 2rem;
        inset-inline-end: 2rem;
        z-index: 50;
    }

    /* Quote Styling */
    .islamic-quote {
        position: relative;
        padding: 2rem;
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.05) 0%, rgba(33, 136, 56, 0.05) 100%);
        border-inline-start: 4px solid;
        border-image: linear-gradient(135deg, #0056b3 0%, #218838 100%) 1;
    }

    .islamic-quote::before {
        content: '"';
        position: absolute;
        top: 0;
        inset-inline-start: 1rem;
        font-size: 4rem;
        font-family: 'Amiri', serif;
        color: #0056b3;
        opacity: 0.2;
    }

    /* Section Divider */
    .section-divider {
        height: 2px;
        background: linear-gradient(90deg, transparent 0%, #0056b3 20%, #218838 80%, transparent 100%);
        margin: 3rem 0;
    }

    /* Tooltip */
    .tooltip {
        position: relative;
    }

    .tooltip:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 0.5rem 1rem;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        border-radius: 0.5rem;
        white-space: nowrap;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')

@php
    $event = $event;
    $hasContent = ! empty($event['content']);
    $hasDescription = ! empty($event['description']);
    $hasCharacters = ! empty($event['characters']);
    $eventTimeline = collect($event['timeline'] ?? []);
    $hasTimeline = $eventTimeline->isNotEmpty();
    $statistics = collect($event['statistics'] ?? [])->filter(fn ($v) => is_scalar($v))->take(4);
    $hasStatistics = $statistics->isNotEmpty();
    $timelineStyleUrl = route('public.seerah.event', ['slug' => $event['slug'], 'view' => 'timeline']);
@endphp

<div x-data="{
    darkMode: $store.theme.dark,
    bookmarked: false,
    readingProgress: 0,
    activeSection: '',
    showFab: false,
    tocOpen: true,

    init() {
        this.updateReadingProgress();
        window.addEventListener('scroll', () => {
            this.updateReadingProgress();
            this.showFab = window.scrollY > 500;
            this.updateActiveSection();
        });
        this.$watch('$store.theme.dark', v => this.darkMode = v);
    },

    updateReadingProgress() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.scrollY;
        const scrollPercent = (scrollTop / (documentHeight - windowHeight)) * 100;
        this.readingProgress = Math.min(scrollPercent, 100);
    },

    updateActiveSection() {
        const sections = ['introduction', 'context', 'timeline', 'lessons', 'sources'];
        for (const section of sections) {
            const element = document.getElementById(section);
            if (element) {
                const rect = element.getBoundingClientRect();
                if (rect.top <= 150 && rect.bottom >= 150) {
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
                text: @js(\Illuminate\Support\Str::limit(strip_tags((string) ($event['description'] ?? $event['title'])), 120)),
                url: window.location.href
            });
        } else {
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
         class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-full shadow-2xl"
         :class="darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'"
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
        <!-- Hero Section with Parallax -->
        <section class="hero-parallax h-[60vh] min-h-[500px] flex items-center justify-center">
            <img src="{{ asset('assets/event/event-img.png') }}"
                 alt="{{ $event['title'] }}"
                 class="absolute inset-0 w-full h-full object-cover"
                 style="filter: brightness(0.7);" />

            <div class="hero-content container mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
                <!-- Category Badge -->
                <div class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white/20 backdrop-blur-sm mb-6 animate-[fade-in-up_0.8s_ease-out]">
                    <i data-lucide="{{ $event['icon'] }}" class="w-5 h-5"></i>
                    <span class="font-semibold">{{ $event['typeLabel'] }}</span>
                </div>

                <!-- Title -->
                <h1 class="text-4xl md:text-6xl lg:text-7xl font-black mb-6 leading-tight animate-[fade-in-up_0.8s_ease-out_0.1s] opacity-0" style="animation-fill-mode: forwards;">
                    {{ $event['title'] }}
                    @if ($hasDescription)
                        <div class="text-2xl md:text-4xl font-normal mt-4 text-white/90">{{ \Illuminate\Support\Str::limit(strip_tags((string) $event['description']), 80) }}</div>
                    @endif
                </h1>

                <!-- Meta Info -->
                <div class="flex flex-wrap items-center justify-center gap-6 text-lg mb-8 animate-[fade-in-up_0.8s_ease-out_0.2s] opacity-0" style="animation-fill-mode: forwards;">
                    @if ($event['hijriDate'])
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-5 h-5"></i>
                            <span>{{ $event['hijriDate'] }}</span>
                        </div>
                        <div class="w-1 h-1 rounded-full bg-white/50"></div>
                    @endif
                    @if ($event['location'])
                        <div class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                            <span>{{ $event['location'] }}</span>
                        </div>
                        <div class="w-1 h-1 rounded-full bg-white/50"></div>
                    @endif
                    @if ($event['gregorianYear'])
                        <div class="flex items-center gap-2">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                            <span>{{ $event['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center justify-center gap-4 animate-[fade-in-up_0.8s_ease-out_0.3s] opacity-0" style="animation-fill-mode: forwards;">
                    <button @click="document.getElementById('introduction')?.scrollIntoView({ behavior: 'smooth' })"
                            class="px-8 py-4 bg-white text-[#0056b3] rounded-full font-bold shadow-2xl hover:shadow-3xl hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        {{ __('seerah.public.start_reading') }}
                    </button>
                    <button @click="toggleBookmark()"
                            class="px-8 py-4 rounded-full font-bold shadow-lg hover:scale-105 transition-all flex items-center gap-2"
                            :class="bookmarked ? 'bg-[#64CCC5] text-gray-900' : 'bg-white/20 backdrop-blur-sm text-white hover:bg-white/30'">
                        <i :data-lucide="bookmarked ? 'bookmark-check' : 'bookmark-plus'" class="w-5 h-5"></i>
                        <span x-text="bookmarked ? @js(__('seerah.public.saved')) : @js(__('seerah.public.save_article'))"></span>
                    </button>
                    <button @click="shareArticle()"
                            class="px-8 py-4 bg-white/20 backdrop-blur-sm text-white rounded-full font-bold shadow-lg hover:bg-white/30 hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="share-2" class="w-5 h-5"></i>
                        {{ __('seerah.public.share') }}
                    </button>
                    <a href="{{ $timelineStyleUrl }}"
                       class="px-8 py-4 bg-white/20 backdrop-blur-sm text-white rounded-full font-bold shadow-lg hover:bg-white/30 hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="git-commit-vertical" class="w-5 h-5"></i>
                        {{ __('seerah.public.read_as_timeline') }}
                    </a>
                </div>

                <!-- Scroll Indicator -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-[float_6s_ease-in-out_infinite]">
                    <i data-lucide="chevron-down" class="w-8 h-8 text-white"></i>
                </div>
            </div>
        </section>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 -mt-20 relative z-10">
            <!-- Stats Cards -->
            @if ($hasStatistics)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                    @foreach ($statistics as $statLabel => $statValue)
                        <div class="stat-card rounded-2xl p-6 text-center shadow-2xl stagger-{{ $loop->index }}" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="text-4xl font-black mb-2 gradient-text">{{ $statValue }}</div>
                            <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ is_string($statLabel) ? $statLabel : '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                <!-- Main Content -->
                <article class="lg:col-span-8 xl:col-span-9">
                    <!-- Introduction -->
                    @if ($hasDescription)
                        <section id="introduction" class="rounded-3xl p-8 md:p-12 shadow-2xl mb-8" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    <i data-lucide="info" class="w-6 h-6 text-white"></i>
                                </div>
                                <h2 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.section_intro') }}</h2>
                            </div>

                            <div class="prose prose-lg max-w-none" :class="darkMode ? 'prose-invert' : ''">
                                <p class="text-xl leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {!! nl2br(e($event['description'])) !!}
                                </p>
                            </div>
                        </section>
                    @endif

                    <!-- Context Section -->
                    @if ($hasContent)
                        <section id="context" class="rounded-3xl p-8 md:p-12 shadow-2xl mb-8" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    <i data-lucide="scroll-text" class="w-6 h-6 text-white"></i>
                                </div>
                                <h2 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.section_context') }}</h2>
                            </div>

                            <div class="prose prose-lg max-w-none" :class="darkMode ? 'prose-invert' : ''">
                                <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {!! nl2br(e($event['content'])) !!}
                                </p>
                            </div>
                        </section>
                    @endif

                    <!-- Inner Event Timeline -->
                    @if ($hasTimeline)
                        <section id="timeline" class="rounded-3xl p-8 md:p-12 shadow-2xl mb-8" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                                </div>
                                <h2 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.event_timeline_heading') }}</h2>
                            </div>

                            <div class="space-y-8">
                                @foreach ($eventTimeline as $index => $timelineItem)
                                    <div class="relative flex items-start gap-4 sm:gap-6">
                                        <div class="relative flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white font-bold text-sm shadow-lg">
                                                {{ $index + 1 }}
                                            </div>
                                            @unless ($loop->last)
                                                <div class="absolute left-1/2 top-10 bottom-[-2rem] w-0.5 -translate-x-1/2 bg-gradient-to-b from-[#0056b3] to-[#218838] opacity-40"></div>
                                            @endunless
                                        </div>
                                        <div class="flex-1 min-w-0 rounded-2xl p-5" :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                                            <h3 class="text-xl font-bold mb-2 break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $timelineItem['title'] }}</h3>
                                            @if (! empty($timelineItem['description']))
                                                <p class="leading-relaxed break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $timelineItem['description'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- Empty content fallback -->
                    @if (! $hasDescription && ! $hasContent)
                        <section id="introduction" class="rounded-3xl p-8 md:p-12 shadow-2xl mb-8 text-center" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <i data-lucide="file-question" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                            <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('seerah.public.no_content') }}</p>
                        </section>
                    @endif

                    @if ($hasCharacters || ! empty($related))
                        <div class="section-divider"></div>
                    @endif

                    <!-- Key Characters Section -->
                    @if ($hasCharacters)
                        <section id="lessons" class="rounded-3xl p-8 md:p-12 shadow-2xl mb-8" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                                </div>
                                <h2 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.section_characters') }}</h2>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                @foreach ($event['characters'] as $character)
                                    <div class="rounded-2xl p-6" :class="darkMode ? 'bg-gray-800' : 'bg-gradient-to-br from-blue-50 to-green-50'">
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center mb-4">
                                            <i data-lucide="user" class="w-6 h-6 text-white"></i>
                                        </div>
                                        <h3 class="text-xl font-bold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $character['name'] }}
                                        </h3>
                                        @if (! empty($character['roleLabel']))
                                            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-[#0056b3]/10 text-[#0056b3]">{{ $character['roleLabel'] }}</span>
                                        @endif
                                        @if (! empty($character['description']))
                                            <p :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                                {{ $character['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </article>

                <!-- Sidebar -->
                <aside class="lg:col-span-4 xl:col-span-3 mt-8 lg:mt-0">
                    <div class="sticky top-24 space-y-6">
                        <!-- Table of Contents -->
                        <div class="rounded-2xl p-6 shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <i data-lucide="list" class="w-5 h-5 text-[#0056b3]"></i>
                                    {{ __('seerah.public.on_this_page') }}
                                </h3>
                                <button @click="tocOpen = !tocOpen" class="p-2 rounded-full" :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-100'">
                                    <i :data-lucide="tocOpen ? 'chevron-up' : 'chevron-down'" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <ul x-show="tocOpen" x-transition class="space-y-3">
                                @if ($hasDescription)
                                    <li>
                                        <a href="#introduction"
                                           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all"
                                           :class="activeSection === 'introduction' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                                            <i data-lucide="circle-dot" class="w-4 h-4"></i>
                                            {{ __('seerah.public.section_intro') }}
                                        </a>
                                    </li>
                                @endif
                                @if ($hasContent)
                                    <li>
                                        <a href="#context"
                                           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all"
                                           :class="activeSection === 'context' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                                            <i data-lucide="circle-dot" class="w-4 h-4"></i>
                                            {{ __('seerah.public.section_context') }}
                                        </a>
                                    </li>
                                @endif
                                @if ($hasTimeline)
                                    <li>
                                        <a href="#timeline"
                                           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all"
                                           :class="activeSection === 'timeline' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                                            <i data-lucide="circle-dot" class="w-4 h-4"></i>
                                            {{ __('seerah.public.event_timeline_heading') }}
                                        </a>
                                    </li>
                                @endif
                                @if ($hasCharacters)
                                    <li>
                                        <a href="#lessons"
                                           class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all"
                                           :class="activeSection === 'lessons' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700')">
                                            <i data-lucide="circle-dot" class="w-4 h-4"></i>
                                            {{ __('seerah.public.section_characters') }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <!-- Key Characters -->
                        @if ($hasCharacters)
                            <div class="rounded-2xl p-6 shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <i data-lucide="users" class="w-5 h-5 text-[#0056b3]"></i>
                                    {{ __('seerah.public.section_characters') }}
                                </h3>

                                <div class="space-y-4">
                                    @foreach ($event['characters'] as $character)
                                        <div class="character-card rounded-xl p-4 cursor-pointer" :class="darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-50 hover:bg-gray-100'">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                                                    <i data-lucide="user" class="w-6 h-6 text-white"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $character['name'] }}</p>
                                                    @if (! empty($character['roleLabel']))
                                                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $character['roleLabel'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Battle Location Map -->
                        @if ($event['location'])
                            <div class="rounded-2xl overflow-hidden shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                <div class="p-6">
                                    <h3 class="text-xl font-bold mb-4 flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        <i data-lucide="map-pin" class="w-5 h-5 text-[#0056b3]"></i>
                                        {{ __('seerah.public.event_location') }}
                                    </h3>
                                </div>
                                <div class="map-container">
                                    <img src="{{ asset('assets/event/event-map.png') }}"
                                         alt="{{ __('seerah.public.event_location_map') }}"
                                         class="w-full h-64 object-cover" />
                                </div>
                                <div class="p-6">
                                    <p class="text-sm flex items-center gap-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        <i data-lucide="map" class="w-4 h-4"></i>
                                        {{ $event['location'] }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Related Articles -->
                        @if (! empty($related))
                            <div class="rounded-2xl p-6 shadow-xl" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                <h3 class="text-xl font-bold mb-6 flex items-center gap-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    <i data-lucide="link" class="w-5 h-5 text-[#0056b3]"></i>
                                    {{ __('seerah.public.related_events') }}
                                </h3>

                                <ul class="space-y-2">
                                    @foreach ($related as $rel)
                                        <li>
                                            <a href="{{ $rel['url'] }}" class="block px-4 py-3 rounded-xl transition-all" :class="darkMode ? 'hover:bg-gray-800 text-gray-300' : 'hover:bg-gray-50 text-gray-700'">
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
        </div>
    </main>

    <!-- Style Switch: view as timeline -->
    {{-- Pinned with logical `end` to stack above the existing .fab (inset-inline-end: 2rem) --}}
    <a href="{{ $timelineStyleUrl }}"
       class="fixed bottom-[5.5rem] end-8 z-50 w-14 h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:shadow-3xl hover:scale-110 transition-all flex items-center justify-center"
       title="{{ __('seerah.public.read_as_timeline') }}"
       aria-label="{{ __('seerah.public.read_as_timeline') }}">
        <i data-lucide="git-commit-vertical" class="w-6 h-6"></i>
    </a>

    <!-- Floating Action Button -->
    <button x-show="showFab"
            @click="scrollToTop()"
            x-transition
            class="fab w-14 h-14 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-2xl hover:shadow-3xl hover:scale-110 transition-all flex items-center justify-center"
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
