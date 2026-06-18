@extends('layouts.public')

@section('title', __('seerah.public.page_title'))
@section('meta_description', __('seerah.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('seerah.public.page_title') }}",
    "description": "{{ __('seerah.public.page_description') }}",
    "url": "{{ route('public.seerah') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    [x-cloak] { display: none !important; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Scroll-reveal — hidden until revealed; setMode()/reduced-motion force-show it */
    .seerah-reveal { opacity: 0; transform: translateY(26px); transition: opacity .6s ease-out, transform .6s cubic-bezier(.16,1,.3,1); }
    .seerah-reveal.is-visible { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .seerah-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
    }

    .seerah-hero-glow { background:
        radial-gradient(60% 55% at 20% 10%, rgba(100,204,197,.18), transparent 60%),
        radial-gradient(55% 50% at 85% 0%, rgba(0,86,179,.22), transparent 60%),
        radial-gradient(50% 60% at 50% 110%, rgba(33,136,56,.18), transparent 60%);
    }
    @keyframes seerah-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    .seerah-float { animation: seerah-float 6s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .seerah-float { animation: none; } }
</style>
@endpush

@section('content')
<div x-data="seerahJourney()" x-init="init()" class="overflow-x-hidden bg-[#F8F9FA] dark:bg-[#0f1226] text-[#212529] dark:text-gray-100">

    {{-- ───────────────────────── HERO ───────────────────────── --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-white to-[#F8F9FA] dark:from-[#141831] dark:to-[#0f1226]">
        <div class="absolute inset-0 seerah-hero-glow pointer-events-none"></div>
        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 pt-14 pb-10 sm:pt-20 sm:pb-14 text-center">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs sm:text-sm font-semibold
                         bg-white/70 dark:bg-white/5 backdrop-blur border border-gray-200 dark:border-white/10 text-[#0056b3] dark:text-[#64CCC5] shadow-sm">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                {{ __('seerah.public.journey_badge') }}
            </span>

            <h1 class="mt-6 font-['Amiri',serif] text-4xl sm:text-6xl font-bold leading-tight">
                <span class="bg-gradient-to-l from-[#0056b3] via-[#218838] to-[#64CCC5] bg-clip-text text-transparent">
                    {{ __('seerah.public.hero_heading') }}
                </span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-base sm:text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ __('seerah.public.journey_subtitle') }}
            </p>

            {{-- Stats --}}
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                @foreach([
                    ['value' => $stats['milestones'], 'label' => __('seerah.public.stat_milestones'), 'icon' => 'milestone'],
                    ['value' => $stats['events'], 'label' => __('seerah.public.stat_stations'), 'icon' => 'map-pin'],
                    ['value' => $stats['span'], 'label' => __('seerah.public.stat_span'), 'icon' => 'calendar-range', 'wide' => true],
                ] as $stat)
                <div class="flex items-center gap-3 px-4 sm:px-5 py-3 rounded-2xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 shadow-sm">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $stat['icon'] }}" class="w-4 h-4 text-white"></i>
                    </div>
                    <div class="text-start">
                        <div class="font-bold {{ ($stat['wide'] ?? false) ? 'text-sm sm:text-base' : 'text-xl' }} leading-none text-gray-900 dark:text-white">{{ $stat['value'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stat['label'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Mode toggle --}}
            <div class="mt-9 inline-flex p-1 rounded-2xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 shadow-sm" role="tablist">
                <button @click="setMode('journey')" role="tab" :aria-selected="mode==='journey'"
                        :class="mode==='journey' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white'"
                        class="px-5 sm:px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2">
                    <i data-lucide="route" class="w-4 h-4"></i>{{ __('seerah.public.mode_journey') }}
                </button>
                <button @click="setMode('articles')" role="tab" :aria-selected="mode==='articles'"
                        :class="mode==='articles' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white'"
                        class="px-5 sm:px-6 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>{{ __('seerah.public.mode_articles') }}
                </button>
            </div>
        </div>
    </section>

    @if(empty($journey))
        <div class="max-w-2xl mx-auto px-4 py-20 text-center">
            <i data-lucide="milestone" class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600"></i>
            <p class="text-lg text-gray-500 dark:text-gray-400">{{ __('seerah.public.empty_journey') }}</p>
        </div>
    @else

    {{-- ──────────────── MILESTONE RAIL (date-range nav) ──────────────── --}}
    <nav x-show="mode==='journey'" x-cloak
         class="sticky top-16 z-30 border-y border-gray-200 dark:border-white/10 bg-white/85 dark:bg-[#0f1226]/85 backdrop-blur-lg"
         aria-label="{{ __('seerah.public.jump_to_milestone') }}">
        <div class="max-w-6xl mx-auto flex items-stretch gap-2 overflow-x-auto no-scrollbar px-3 sm:px-4 py-3">
            @foreach($journey as $m)
            <a href="#{{ $m['anchor'] }}" @click.prevent="goTo('{{ $m['anchor'] }}')"
               :class="active==='{{ $m['slug'] }}' ? 'border-[#64CCC5] bg-[#64CCC5]/10 dark:bg-[#64CCC5]/15' : 'border-gray-200 dark:border-white/10 hover:border-gray-300 dark:hover:border-white/20'"
               class="group flex-shrink-0 flex items-center gap-2.5 ps-2 pe-4 py-1.5 rounded-xl border transition-all">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $m['gradient'] }} flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{{ $m['order'] }}</span>
                <span class="flex flex-col text-start min-w-0">
                    <span class="text-xs sm:text-[13px] font-semibold text-gray-800 dark:text-gray-100 truncate max-w-[8.5rem] sm:max-w-[11rem]">{{ \Illuminate\Support\Str::limit($m['title'], 34) }}</span>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $m['gregorianRange'] ?: $m['hijriRange'] }}</span>
                </span>
            </a>
            @endforeach
        </div>
    </nav>

    {{-- ───────────────────── JOURNEY MODE: spine timeline ───────────────────── --}}
    <div x-show="mode==='journey'" x-cloak class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        @foreach($journey as $m)
        @php($mColor = 'bg-gradient-to-br '.$m['gradient'])
        <section id="{{ $m['anchor'] }}" data-milestone="{{ $m['slug'] }}" class="scroll-mt-28">

            {{-- Chapter header --}}
            <div class="flex gap-3 sm:gap-5">
                <div class="relative w-10 sm:w-14 flex-shrink-0">
                    <div class="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-[3px] {{ $mColor }} opacity-30 rounded-full"></div>
                    <div class="seerah-float relative z-10 w-10 h-10 sm:w-14 sm:h-14 rounded-2xl {{ $mColor }} flex items-center justify-center shadow-lg ring-4 ring-[#F8F9FA] dark:ring-[#0f1226]">
                        <i data-lucide="{{ $m['icon'] }}" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0 pb-6">
                    <div class="relative overflow-hidden rounded-3xl {{ $mColor }} text-white p-5 sm:p-7 shadow-xl">
                        <div class="absolute -top-8 -end-8 w-32 h-32 rounded-full bg-white/10 blur-2xl"></div>
                        <div class="relative">
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur">
                                    {{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }}
                                </span>
                                @if($m['hijriRange'])
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-black/15">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>{{ $m['hijriRange'] }}
                                </span>
                                @endif
                                @if($m['gregorianRange'])
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-black/10">{{ $m['gregorianRange'] }}</span>
                                @endif
                            </div>
                            <h2 class="font-['Amiri',serif] text-2xl sm:text-3xl font-bold leading-snug">{{ $m['title'] }}</h2>
                            @if($m['subtitle'])
                            <p class="mt-2 text-white/85 text-sm sm:text-base leading-relaxed">{{ $m['subtitle'] }}</p>
                            @endif
                            <div class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-white/80">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>{{ $m['eventCount'] }} {{ __('seerah.public.stations_count') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Events along the spine --}}
            @foreach($m['events'] as $ev)
            @php($hijri = $ev['hijriYear'] !== null ? ($ev['hijriYear'] < 0 ? abs($ev['hijriYear']).' '.__('seerah.public.before_hijra_suffix') : $ev['hijriYear'].' '.__('seerah.public.hijri_suffix')) : null)
            <div class="flex gap-3 sm:gap-5 seerah-reveal">
                <div class="relative w-10 sm:w-14 flex-shrink-0">
                    <div class="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-[3px] {{ $mColor }} opacity-25 rounded-full"></div>
                    <div class="absolute top-6 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-gradient-to-br {{ $ev['gradient'] }} ring-4 ring-[#F8F9FA] dark:ring-[#0f1226] shadow"></div>
                </div>
                <div class="flex-1 min-w-0 pb-5">
                    <a href="{{ $ev['url'] }}"
                       class="group block rounded-2xl border border-gray-200/80 dark:border-white/10 bg-white dark:bg-[#16213e] p-4 sm:p-5 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold text-white bg-gradient-to-r {{ $ev['gradient'] }}">
                                <i data-lucide="{{ $ev['icon'] }}" class="w-3 h-3"></i>{{ $ev['typeLabel'] }}
                            </span>
                            @if($hijri)<span class="text-[11px] font-medium text-gray-400 dark:text-gray-500">{{ $hijri }}</span>@endif
                            @if($ev['gregorianYear'])<span class="text-[11px] text-gray-300 dark:text-gray-600">· {{ $ev['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}</span>@endif
                        </div>
                        <h3 class="font-bold text-base sm:text-lg text-gray-900 dark:text-white group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors leading-snug">{{ $ev['title'] }}</h3>
                        @if($ev['description'])
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-2">{{ $ev['description'] }}</p>
                        @endif
                        <div class="mt-3 flex items-center justify-between gap-2 text-xs">
                            @if($ev['location'])
                            <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 truncate"><i data-lucide="map-pin" class="w-3 h-3 flex-shrink-0"></i>{{ $ev['location'] }}</span>
                            @else<span></span>@endif
                            <span class="inline-flex items-center gap-1 text-[#0056b3] dark:text-[#64CCC5] font-semibold group-hover:gap-2 transition-all flex-shrink-0">
                                {{ __('seerah.public.read_more') }}<i data-lucide="arrow-left" class="w-3.5 h-3.5 ltr:rotate-180"></i>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
            @endforeach
        </section>
        @endforeach
    </div>

    {{-- ───────────────────── ARTICLES MODE: chronological grid ───────────────────── --}}
    <div x-show="mode==='articles'" x-cloak class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <div class="flex items-center gap-2 mb-6 text-sm text-gray-500 dark:text-gray-400">
            <i data-lucide="clock" class="w-4 h-4"></i>{{ __('seerah.public.articles_mode_hint') }}
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
            @foreach($articles as $ev)
            @php($hijriA = $ev['hijriYear'] !== null ? ($ev['hijriYear'] < 0 ? abs($ev['hijriYear']).' '.__('seerah.public.before_hijra_suffix') : $ev['hijriYear'].' '.__('seerah.public.hijri_suffix')) : null)
            <a href="{{ $ev['url'] }}"
               class="group flex flex-col rounded-2xl overflow-hidden border border-gray-200/80 dark:border-white/10 bg-white dark:bg-[#16213e] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="h-1.5 bg-gradient-to-r {{ $ev['gradient'] }}"></div>
                <div class="p-4 sm:p-5 flex flex-col flex-1">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold text-white bg-gradient-to-r {{ $ev['gradient'] }}">
                            <i data-lucide="{{ $ev['icon'] }}" class="w-3 h-3"></i>{{ $ev['typeLabel'] }}
                        </span>
                        @if($hijriA)<span class="text-[11px] font-medium text-gray-400 dark:text-gray-500">{{ $hijriA }}</span>@endif
                    </div>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors leading-snug line-clamp-2">{{ $ev['title'] }}</h3>
                    @if($ev['description'])
                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-3 flex-1">{{ $ev['description'] }}</p>
                    @endif
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/5 flex items-center justify-between gap-2 text-xs">
                        @if($ev['location'])
                        <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 truncate"><i data-lucide="map-pin" class="w-3 h-3 flex-shrink-0"></i>{{ $ev['location'] }}</span>
                        @else<span></span>@endif
                        <span class="inline-flex items-center gap-1 text-[#0056b3] dark:text-[#64CCC5] font-semibold group-hover:gap-2 transition-all flex-shrink-0">
                            {{ __('seerah.public.read_more') }}<i data-lucide="arrow-left" class="w-3.5 h-3.5 ltr:rotate-180"></i>
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- CTA --}}
    <section class="max-w-5xl mx-auto px-4 sm:px-6 pb-16">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white p-8 sm:p-10 text-center shadow-xl">
            <div class="absolute -top-10 -start-10 w-40 h-40 rounded-full bg-white/10 blur-3xl"></div>
            <h2 class="relative text-2xl sm:text-3xl font-bold">{{ __('seerah.public.cta_title') }}</h2>
            <p class="relative mt-3 max-w-2xl mx-auto text-white/85">{{ __('seerah.public.cta_subtitle') }}</p>
            <a href="{{ route('public.seerah.events') }}" class="relative mt-6 inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-[#0056b3] font-bold hover:scale-105 transition-transform">
                <i data-lucide="compass" class="w-5 h-5"></i>{{ __('seerah.public.browse_all_events') }}
            </a>
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function seerahJourney() {
        return {
            mode: (localStorage.getItem('seerahMode') === 'articles') ? 'articles' : 'journey',
            active: '',
            _reduce: false,

            init() {
                this._reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                if (this._reduce) {
                    document.querySelectorAll('.seerah-reveal').forEach(el => el.classList.add('is-visible'));
                } else {
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); } });
                    }, { threshold: 0.12 });
                    document.querySelectorAll('.seerah-reveal').forEach(el => io.observe(el));
                }

                // Scroll-spy: highlight the current milestone in the rail.
                const spy = new IntersectionObserver((entries) => {
                    entries.forEach(en => { if (en.isIntersecting) this.active = en.target.dataset.milestone; });
                }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });
                document.querySelectorAll('[data-milestone]').forEach(el => spy.observe(el));
            },

            setMode(m) {
                this.mode = m;
                localStorage.setItem('seerahMode', m);
                // Force-show reveal elements (covers switching back into a previously-hidden journey).
                this.$nextTick(() => {
                    document.querySelectorAll('.seerah-reveal').forEach(el => el.classList.add('is-visible'));
                    if (window.lucide) lucide.createIcons();
                });
            },

            goTo(anchor) {
                const el = document.getElementById(anchor);
                if (el) el.scrollIntoView({ behavior: this._reduce ? 'auto' : 'smooth', block: 'start' });
            },
        };
    }
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
