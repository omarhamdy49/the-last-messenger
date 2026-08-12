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
    .seerah-reveal { opacity: 0; transform: translateY(22px); transition: opacity .55s ease-out, transform .6s cubic-bezier(.16,1,.3,1); }
    .seerah-reveal.is-visible { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .seerah-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
        .seerah-anim { animation: none !important; }
    }
    @keyframes seerah-soft-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(100,204,197,.45); } 50% { box-shadow: 0 0 0 6px rgba(100,204,197,0); } }
    .seerah-current-chip { animation: seerah-soft-pulse 2.2s ease-in-out infinite; }
</style>
@endpush

@section('content')
<div x-data="seerahQuest({{ count($journey) }})" x-init="init()"
     class="min-h-dvh overflow-x-clip bg-[#f5f7fc] dark:bg-[#0d1226] text-[#1c2238] dark:text-gray-100">

    {{-- ───────────────────────── HERO ───────────────────────── --}}
    <section class="relative overflow-hidden">
        {{-- layered premium backdrop — designed for light AND dark --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-[#e9f1fc] via-[#f1f6fc] to-[#f5f7fc] dark:from-[#0e1533] dark:via-[#0d1226] dark:to-[#0d1226]"></div>
        <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 start-[10%] w-80 h-80 rounded-full bg-[#0056b3]/15 dark:bg-[#0056b3]/25 blur-3xl"></div>
            <div class="absolute -top-10 end-[8%] w-72 h-72 rounded-full bg-[#218838]/12 dark:bg-[#64CCC5]/15 blur-3xl"></div>
            <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-[#0056b3]/25 dark:via-white/15 to-transparent"></div>
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 pt-12 pb-8 sm:pt-16 text-center">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs sm:text-sm font-bold
                         bg-white/90 dark:bg-white/[.06] border border-[#0056b3]/15 dark:border-white/10 text-[#0056b3] dark:text-[#64CCC5] shadow-sm backdrop-blur">
                <i data-lucide="sparkles" class="w-4 h-4"></i>{{ __('seerah.public.journey_badge') }}
            </span>
            <h1 class="mt-5 text-4xl sm:text-6xl font-extrabold tracking-tight leading-tight">
                <span class="bg-gradient-to-l from-[#004a9c] via-[#1a7a32] to-[#0a8079] dark:from-[#4aa3ff] dark:via-[#3fbf6b] dark:to-[#64CCC5] bg-clip-text text-transparent">{{ __('seerah.public.hero_heading') }}</span>
            </h1>
            <p class="mt-3 max-w-xl mx-auto text-base sm:text-lg text-gray-600 dark:text-gray-300/90 leading-relaxed">{{ __('seerah.public.journey_subtitle') }}</p>

            {{-- journey stats --}}
            <div class="mt-7 flex flex-wrap items-stretch justify-center gap-2.5 sm:gap-3">
                @foreach([
                    ['value' => $stats['milestones'], 'label' => __('seerah.public.stat_milestones'), 'icon' => 'flag'],
                    ['value' => $stats['events'], 'label' => __('seerah.public.stat_stations'), 'icon' => 'map-pin'],
                    ['value' => $stats['span'], 'label' => __('seerah.public.stat_span'), 'icon' => 'calendar-range'],
                ] as $stat)
                <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-white/85 dark:bg-white/[.05] border border-gray-200/80 dark:border-white/10 shadow-sm backdrop-blur">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white flex-shrink-0 shadow-sm">
                        <i data-lucide="{{ $stat['icon'] }}" class="w-4 h-4"></i>
                    </span>
                    <span class="text-start leading-tight">
                        <span class="block text-base sm:text-lg font-black text-gray-900 dark:text-white tabular-nums">{{ $stat['value'] }}</span>
                        <span class="block text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</span>
                    </span>
                </div>
                @endforeach
            </div>

            <div class="mt-6 inline-flex p-1 rounded-2xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 shadow-sm" role="tablist">
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

    {{-- The journey groups events under eras. When no eras are configured the
         journey is empty, but the events themselves still exist and must stay
         reachable — the articles grid below lists every published event. Only
         a genuinely empty corpus gets the empty state. --}}
    @if(empty($journey) && empty($articles))
        <div class="max-w-2xl mx-auto px-4 py-20 text-center">
            <i data-lucide="milestone" class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600"></i>
            <p class="text-lg text-gray-500 dark:text-gray-400">{{ __('seerah.public.empty_journey') }}</p>
        </div>
    @else

    {{-- ──────────── STICKY TIMELINE BAR (current era + progress rail) ──────────── --}}
    <div x-show="mode==='journey'" x-cloak class="sticky top-20 z-30 border-b border-gray-200/80 dark:border-white/10 bg-[#f5f7fc]/85 dark:bg-[#0d1226]/90 backdrop-blur-lg">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3">
            {{-- current era (title + its date range) + counter --}}
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-xs font-black tabular-nums shadow-sm" x-text="currentIndex+1"></span>
                    <div class="min-w-0 leading-tight">
                        <span class="block font-bold text-sm sm:text-base text-gray-800 dark:text-gray-100 truncate" x-text="currentTitle"></span>
                        <span class="block text-[11px] font-bold text-[#0056b3] dark:text-[#64CCC5] tabular-nums truncate" x-show="currentRange" x-text="currentRange"></span>
                    </div>
                </div>
                <span class="flex-shrink-0 inline-flex items-center gap-1 text-xs font-bold text-gray-500 dark:text-gray-400 tabular-nums self-start mt-0.5">
                    <i data-lucide="flag-triangle-right" class="w-3.5 h-3.5 text-[#0056b3] dark:text-[#64CCC5]"></i><span x-text="currentIndex+1"></span><span class="text-gray-300 dark:text-gray-600">/</span><span x-text="total"></span>
                </span>
            </div>
            {{-- evenly-distributed progress rail (no year labels) --}}
            <div class="relative h-5 flex items-center">
                <div class="absolute inset-x-1.5 h-1.5 rounded-full bg-gray-200 dark:bg-white/10"></div>
                <div class="absolute start-1.5 h-1.5 rounded-full bg-gradient-to-r from-[#0056b3] via-[#64CCC5] to-[#218838] transition-all duration-500 ease-out" :style="`width:calc((100% - 0.75rem) * ${total>1 ? currentIndex/(total-1) : 1})`"></div>
                <div class="relative flex justify-between w-full">
                    @foreach($journey as $m)
                    @php($i = $loop->index)
                    <button @click="goTo('{{ $m['anchor'] }}', {{ $i }})" type="button"
                            :aria-current="currentIndex==={{ $i }} ? 'step' : false"
                            aria-label="{{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }}"
                            title="{{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }} — {{ $m['title'] }}"
                            class="relative flex items-center justify-center w-5 h-5 -m-0.5 group">
                        <span class="block rounded-full ring-2 ring-[#f5f7fc] dark:ring-[#0d1226] transition-all duration-300"
                              :class="currentIndex==={{ $i }} ? 'w-4 h-4 bg-gradient-to-br {{ $m['gradient'] }} shadow-md' : ({{ $i }} < currentIndex ? 'w-2.5 h-2.5 bg-[#0056b3] dark:bg-[#64CCC5]' : 'w-2.5 h-2.5 bg-gray-300 dark:bg-white/25 group-hover:bg-gray-400 dark:group-hover:bg-white/40')"></span>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── JOURNEY: vertical timeline spine, date-range eras ───────── --}}
    <div x-show="mode==='journey'" x-cloak class="max-w-3xl mx-auto px-4 sm:px-6 pt-8 pb-10">
        <div class="relative">
            {{-- spine track --}}
            <div class="absolute top-3 bottom-12 start-[26px] sm:start-[30px] w-[3px] rounded-full bg-gradient-to-b from-gray-200 to-gray-200/40 dark:from-white/15 dark:to-white/[.04]"></div>
            {{-- spine progress fill --}}
            <div class="absolute top-3 start-[26px] sm:start-[30px] w-[3px] rounded-full bg-gradient-to-b from-[#0056b3] via-[#64CCC5] to-[#218838] transition-all duration-700 ease-out" :style="`height:${spineFill()}%`"></div>

            @foreach($journey as $m)
            @php($i = $loop->index)
            <article id="{{ $m['anchor'] }}" data-milestone="{{ $m['slug'] }}" data-index="{{ $i }}"
                     class="seerah-reveal scroll-mt-32 relative ps-16 sm:ps-[78px] pb-7 sm:pb-9">

                {{-- node on the spine --}}
                <div class="absolute start-0 top-1">
                    <div class="relative w-[54px] h-[54px] sm:w-[62px] sm:h-[62px] rounded-2xl bg-gradient-to-br {{ $m['gradient'] }} flex flex-col items-center justify-center text-white shadow-lg ring-4 ring-[#f5f7fc] dark:ring-[#0d1226] transition-all duration-300"
                         :class="currentIndex==={{ $i }} ? 'seerah-current-chip seerah-anim scale-[1.07]' : ({{ $i }} > currentIndex ? 'opacity-90' : '')">
                        <span class="text-lg sm:text-2xl font-black leading-none drop-shadow-sm">{{ $m['order'] }}</span>
                        <i data-lucide="{{ $m['icon'] }}" class="w-3.5 h-3.5 mt-0.5 opacity-90"></i>
                        <div x-show="{{ $i }} < currentIndex" x-cloak class="absolute -top-1.5 -end-1.5 w-5 h-5 rounded-full bg-emerald-500 ring-2 ring-[#f5f7fc] dark:ring-[#0d1226] flex items-center justify-center">
                            <i data-lucide="check" class="w-3 h-3 text-white"></i>
                        </div>
                    </div>
                </div>

                {{-- era card --}}
                <div class="rounded-3xl border border-gray-200/90 dark:border-white/[0.09] bg-white dark:bg-[#171f3c] shadow-[0_1px_3px_rgba(16,24,52,.06)] dark:shadow-[0_4px_24px_rgba(0,0,0,.35)] hover:shadow-lg dark:hover:border-white/20 transition-all overflow-hidden"
                     :class="currentIndex==={{ $i }} ? 'ring-2 ring-[#64CCC5]/40 dark:ring-[#64CCC5]/35' : ''">

                    {{-- date-range header (subtle era tint, big date) --}}
                    <div class="relative px-5 sm:px-6 pt-4 pb-4 border-b border-gray-100 dark:border-white/[0.06]">
                        <div class="absolute inset-0 opacity-[0.06] dark:opacity-[0.14] bg-gradient-to-l {{ $m['gradient'] }} pointer-events-none"></div>
                        <div class="relative flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-extrabold uppercase tracking-wide bg-white/80 dark:bg-white/10 text-[#0056b3] dark:text-[#64CCC5] ring-1 ring-black/5 dark:ring-white/10">
                                    <i data-lucide="flag" class="w-3 h-3"></i>{{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }}
                                </span>
                                <span x-show="currentIndex==={{ $i }}" x-cloak class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-[#64CCC5]/20 text-[#0a7d77] dark:text-[#64CCC5]">
                                    <i data-lucide="navigation" class="w-3 h-3"></i>{{ __('seerah.public.you_are_here') }}
                                </span>
                                <span x-show="{{ $i }} < currentIndex" x-cloak class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                    <i data-lucide="check" class="w-3 h-3"></i>{{ __('seerah.public.finish_label') }}
                                </span>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-500 dark:text-gray-400"><i data-lucide="map-pin" class="w-3 h-3"></i>{{ $m['eventCount'] }} {{ __('seerah.public.stations_count') }}</span>
                        </div>
                        {{-- the date range, the visual anchor of the era --}}
                        <div class="relative mt-2.5 flex items-baseline gap-2 flex-wrap">
                            <i data-lucide="calendar-range" class="w-5 h-5 self-center text-[#0056b3] dark:text-[#64CCC5] flex-shrink-0"></i>
                            @if($m['hijriRange'])
                            <span class="text-xl sm:text-3xl font-black text-gray-900 dark:text-white tabular-nums tracking-tight leading-none">{{ $m['hijriRange'] }}</span>
                            @endif
                            @if($m['gregorianRange'])<span class="text-xs sm:text-sm font-semibold text-gray-400 dark:text-gray-500 tabular-nums">· {{ $m['gregorianRange'] }}</span>@endif
                        </div>
                    </div>

                    {{-- body --}}
                    <div class="px-5 sm:px-6 py-5">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white leading-snug">{{ $m['title'] }}</h2>
                        @if($m['subtitle'])<p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $m['subtitle'] }}</p>@endif

                        {{-- events --}}
                        <div class="mt-4 grid sm:grid-cols-2 gap-2.5">
                            @foreach($m['events'] as $ev)
                            @php($hijri = $ev['hijriYear'] !== null ? ($ev['hijriYear'] < 0 ? abs($ev['hijriYear']).' '.__('seerah.public.before_hijra_suffix') : $ev['hijriYear'].' '.__('seerah.public.hijri_suffix')) : null)
                            <a href="{{ $ev['url'] }}"
                               class="group flex items-center gap-3 rounded-2xl border border-gray-100 dark:border-white/5 bg-gray-50/70 dark:bg-white/[.03] p-3 hover:bg-white dark:hover:bg-white/[.07] hover:border-[#64CCC5]/50 hover:shadow-sm transition-all">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $ev['gradient'] }} flex items-center justify-center flex-shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                    <i data-lucide="{{ $ev['icon'] }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-[14px] text-gray-900 dark:text-white leading-snug line-clamp-1 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">{{ $ev['title'] }}</h3>
                                    <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500 truncate">
                                        <span class="font-semibold text-[#0056b3]/70 dark:text-[#64CCC5]/80">{{ $ev['typeLabel'] }}</span>
                                        @if($hijri)<span>· {{ $hijri }}</span>@endif
                                        @if($ev['location'])<span class="truncate">· {{ $ev['location'] }}</span>@endif
                                    </div>
                                </div>
                                <i data-lucide="chevron-left" class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-[#64CCC5] ltr:rotate-180 flex-shrink-0 transition-colors"></i>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
            @endforeach

            {{-- finish node --}}
            <div class="relative ps-16 sm:ps-[78px] min-h-[54px] sm:min-h-[62px] flex flex-col justify-center">
                <div class="absolute start-0 top-0 w-[54px] h-[54px] sm:w-[62px] sm:h-[62px] rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg ring-4 ring-[#f5f7fc] dark:ring-[#0d1226]">
                    <i data-lucide="trophy" class="w-6 h-6"></i>
                </div>
                <div class="font-extrabold text-gray-800 dark:text-gray-100 text-base">{{ __('seerah.public.finish_label') }}</div>
                <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('seerah.public.finish_hint') }}</div>
            </div>
        </div>
    </div>

    {{-- ───────────────────── ARTICLES MODE ───────────────────── --}}
    <div x-show="mode==='articles'" x-cloak class="max-w-6xl mx-auto px-4 sm:px-6 py-9 sm:py-12">
        <div class="flex items-center gap-2 mb-6 text-sm text-gray-500 dark:text-gray-400">
            <i data-lucide="clock" class="w-4 h-4"></i>{{ __('seerah.public.articles_mode_hint') }}
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
            @foreach($articles as $ev)
            @php($hijriA = $ev['hijriYear'] !== null ? ($ev['hijriYear'] < 0 ? abs($ev['hijriYear']).' '.__('seerah.public.before_hijra_suffix') : $ev['hijriYear'].' '.__('seerah.public.hijri_suffix')) : null)
            <a href="{{ $ev['url'] }}" class="group flex flex-col rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 bg-white dark:bg-[#161d38] shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                <div class="h-1.5 bg-gradient-to-r {{ $ev['gradient'] }}"></div>
                <div class="p-4 sm:p-5 flex flex-col flex-1">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold text-white bg-gradient-to-r {{ $ev['gradient'] }}">
                            <i data-lucide="{{ $ev['icon'] }}" class="w-3 h-3"></i>{{ $ev['typeLabel'] }}
                        </span>
                        @if($hijriA)<span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 tabular-nums">{{ $hijriA }}</span>@endif
                    </div>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors leading-snug line-clamp-2">{{ $ev['title'] }}</h3>
                    @if($ev['description'])<p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-3 flex-1">{{ $ev['description'] }}</p>@endif
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/5 flex items-center justify-between gap-2 text-xs">
                        @if($ev['location'])<span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 truncate"><i data-lucide="map-pin" class="w-3 h-3 flex-shrink-0"></i>{{ $ev['location'] }}</span>@else<span></span>@endif
                        <span class="inline-flex items-center gap-1 text-[#0056b3] dark:text-[#64CCC5] font-semibold group-hover:gap-2 transition-all flex-shrink-0">{{ __('seerah.public.read_more') }}<i data-lucide="arrow-left" class="w-3.5 h-3.5 ltr:rotate-180"></i></span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- CTA --}}
    <section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white p-8 sm:p-10 text-center shadow-xl">
            <div class="absolute -top-12 -start-12 w-44 h-44 rounded-full bg-white/10 blur-3xl"></div>
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
    function seerahQuest(total) {
        return {
            // With no eras there is nothing for journey mode to draw, so start
            // in articles mode rather than opening on a blank tab.
            mode: (total === 0 || localStorage.getItem('seerahMode') === 'articles') ? 'articles' : 'journey',
            total: total,
            currentIndex: 0,
            currentTitle: @json($journey[0]['title'] ?? ''),
            currentRange: @json($journey[0]['hijriRange'] ?? ''),
            _titles: @json(array_column($journey, 'title')),
            _ranges: @json(array_column($journey, 'hijriRange')),
            _reduce: false,

            progress() { return this.total > 1 ? Math.round((this.currentIndex / (this.total - 1)) * 100) : 100; },
            // Fill the vertical spine down to the centre of the current era node.
            spineFill() { return this.total > 0 ? Math.min(100, Math.round(((this.currentIndex + 0.5) / this.total) * 100)) : 0; },

            init() {
                this._reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                if (this._reduce) {
                    document.querySelectorAll('.seerah-reveal').forEach(el => el.classList.add('is-visible'));
                } else {
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); } });
                    }, { threshold: 0.08 });
                    document.querySelectorAll('.seerah-reveal').forEach(el => io.observe(el));
                }

                const spy = new IntersectionObserver((entries) => {
                    entries.forEach(en => {
                        if (en.isIntersecting) {
                            const idx = parseInt(en.target.dataset.index, 10);
                            this.currentIndex = idx;
                            this.currentTitle = this._titles[idx] || '';
                            this.currentRange = this._ranges[idx] || '';
                        }
                    });
                }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });
                document.querySelectorAll('[data-milestone]').forEach(el => spy.observe(el));
            },

            setMode(m) {
                this.mode = m;
                localStorage.setItem('seerahMode', m);
                this.$nextTick(() => {
                    document.querySelectorAll('.seerah-reveal').forEach(el => el.classList.add('is-visible'));
                    if (window.lucide) lucide.createIcons();
                });
            },

            goTo(anchor, index) {
                if (typeof index === 'number') { this.currentIndex = index; this.currentTitle = this._titles[index] || ''; this.currentRange = this._ranges[index] || ''; }
                const el = document.getElementById(anchor);
                if (el) el.scrollIntoView({ behavior: this._reduce ? 'auto' : 'smooth', block: 'start' });
            },
        };
    }
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
