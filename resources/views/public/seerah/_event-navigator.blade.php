{{--
    Breadcrumb + milestone navigator for the single-event pages (article and
    timeline styles). Rendered inside the hero, over the gradient — text is
    white. The modal lazy-fetches each era's stations from the same JSON
    endpoint the /seerah journey uses, with a retry action on failure.

    Expects: $event (detail payload), $journeyNav (milestone shells array).
--}}
@php
    $navMilestone = $event['milestone'] ?? null;
@endphp

<div x-data="seerahNavigator({
        milestones: {{ Js::from(collect($journeyNav ?? [])->map(fn ($m) => [
            'slug' => $m['slug'],
            'order' => $m['order'],
            'title' => $m['title'],
            'range' => $m['hijriRange'],
            'count' => $m['eventCount'],
            'url' => $m['eventsUrl'],
        ])->values()->all()) }},
        currentMilestone: {{ Js::from($navMilestone['slug'] ?? null) }},
        currentUrl: {{ Js::from($event['url']) }},
     })"
     @seerah-navigator-open.window="openModal()">

    {{-- Breadcrumb bar --}}
    <nav aria-label="{{ __('seerah.public.breadcrumb_seerah') }}"
         class="flex items-center justify-between gap-3 mb-8 text-sm text-white/80">
        <ol class="flex items-center gap-1.5 min-w-0 flex-wrap">
            <li class="flex items-center gap-1.5">
                <a href="{{ route('public.seerah') }}" class="inline-flex items-center gap-1.5 hover:text-white transition-colors font-semibold">
                    <i data-lucide="milestone" class="w-4 h-4"></i>{{ __('seerah.public.breadcrumb_seerah') }}
                </a>
            </li>
            @if ($navMilestone)
                <li aria-hidden="true"><i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180 text-white/50"></i></li>
                <li class="min-w-0">
                    <a href="{{ $navMilestone['url'] }}" class="hover:text-white transition-colors font-semibold truncate block max-w-[11rem] sm:max-w-[16rem]">{{ $navMilestone['title'] }}</a>
                </li>
            @endif
            <li aria-hidden="true" class="hidden sm:block"><i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180 text-white/50"></i></li>
            <li aria-current="page" class="hidden sm:block text-white font-bold truncate max-w-[14rem] lg:max-w-[22rem]">{{ $event['title'] }}</li>
        </ol>

        @if (! empty($journeyNav))
            <button type="button" @click="openModal()"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-full text-sm font-bold bg-white/15 backdrop-blur-md border border-white/25 text-white hover:bg-white/25 transition-all">
                <i data-lucide="map" class="w-4 h-4"></i><span class="hidden sm:inline">{{ __('seerah.public.navigator_open') }}</span>
            </button>
        @endif
    </nav>

    {{-- Navigator modal — teleported to <body>: the include sits inside the
         hero's `relative z-10` stacking context, where no z-index can win. --}}
    <template x-teleport="body">
    <div x-show="open" x-cloak
         x-transition.opacity.duration.200ms
         @click.self="close()"
         @keydown.escape.window="close()"
         class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4 bg-black/60 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-label="{{ __('seerah.public.navigator_title') }}"
         style="display: none;">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="w-full sm:max-w-3xl max-h-[88vh] sm:max-h-[80vh] flex flex-col rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl bg-white dark:bg-[#131a33] text-[#1c2238] dark:text-gray-100">

            {{-- header --}}
            <div class="relative px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-white/10 bg-gradient-to-l from-[#0056b3]/[.06] to-[#218838]/[.06] dark:from-white/[.03] dark:to-white/[.03]">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white flex-shrink-0">
                                <i data-lucide="route" class="w-4 h-4"></i>
                            </span>
                            {{ __('seerah.public.navigator_title') }}
                        </h2>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('seerah.public.navigator_hint') }}</p>
                    </div>
                    <button type="button" @click="close()" aria-label="{{ __('seerah.public.close') }}"
                            class="p-2.5 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors flex-shrink-0">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            {{-- mobile: milestone chips --}}
            <div class="sm:hidden flex gap-2 overflow-x-auto no-scrollbar px-4 py-3 border-b border-gray-100 dark:border-white/10">
                <template x-for="(m, mi) in milestones" :key="m.slug">
                    <button type="button" @click="select(m.slug)"
                            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-all"
                            :class="active === m.slug
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white border-transparent shadow'
                                : 'border-gray-200 dark:border-white/15 text-gray-600 dark:text-gray-300'">
                        <span class="tabular-nums" x-text="mi + 1"></span>
                        <span class="max-w-[9rem] truncate" x-text="m.title"></span>
                    </button>
                </template>
            </div>

            {{-- body --}}
            <div class="flex-1 min-h-0 grid sm:grid-cols-[250px_1fr]">
                {{-- desktop: milestone list --}}
                <div class="hidden sm:block overflow-y-auto border-e border-gray-100 dark:border-white/10 p-3 space-y-1">
                    <template x-for="(m, mi) in milestones" :key="m.slug">
                        <button type="button" @click="select(m.slug)"
                                class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-start transition-all"
                                :class="active === m.slug
                                    ? 'bg-gradient-to-l from-[#0056b3] to-[#218838] text-white shadow'
                                    : 'hover:bg-gray-50 dark:hover:bg-white/5 text-gray-700 dark:text-gray-300'">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-black tabular-nums flex-shrink-0"
                                  :class="active === m.slug ? 'bg-white/20' : 'bg-gray-100 dark:bg-white/10'"
                                  x-text="mi + 1"></span>
                            <span class="min-w-0 leading-tight">
                                <span class="block text-[13px] font-bold truncate" x-text="m.title"></span>
                                <span class="block text-[10px] tabular-nums opacity-70" x-text="m.range || (m.count + ' {{ __('seerah.public.stations_count') }}')"></span>
                            </span>
                        </button>
                    </template>
                </div>

                {{-- stations of the active era --}}
                <div class="overflow-y-auto p-3 sm:p-4">
                    {{-- skeletons --}}
                    <div x-show="activeState.status === 'idle' || activeState.status === 'loading'" class="space-y-2" aria-hidden="true">
                        <template x-for="i in 5" :key="i">
                            <div class="flex items-center gap-3 rounded-xl border border-gray-100 dark:border-white/5 p-3 animate-pulse">
                                <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-white/10 flex-shrink-0"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-3 rounded bg-gray-200 dark:bg-white/10 w-3/4"></div>
                                    <div class="h-2 rounded bg-gray-200 dark:bg-white/10 w-1/3"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- load failure + retry --}}
                    <div x-show="activeState.status === 'error'" x-cloak
                         class="rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 p-4 flex flex-col items-center gap-3 text-center">
                        <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ __('seerah.public.load_failed') }}</p>
                        <button type="button" @click="load(active, true)"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow hover:opacity-90 transition-opacity">
                            <i data-lucide="rotate-cw" class="w-4 h-4"></i>{{ __('seerah.public.retry') }}
                        </button>
                    </div>

                    {{-- stations --}}
                    <div x-show="activeState.status === 'loaded'" x-cloak class="space-y-1.5">
                        <template x-for="(ev, i) in activeState.events" :key="ev.slug">
                            <a :href="ev.url"
                               class="group flex items-center gap-3 rounded-xl p-3 border transition-all"
                               :class="ev.url === currentUrl
                                   ? 'border-[#64CCC5]/60 bg-[#64CCC5]/10 dark:bg-[#64CCC5]/10'
                                   : 'border-transparent hover:border-[#64CCC5]/40 hover:bg-gray-50 dark:hover:bg-white/5'">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-black tabular-nums flex-shrink-0 bg-gradient-to-br from-[#0056b3] to-[#218838] text-white"
                                      x-text="i + 1"></span>
                                <span class="flex-1 min-w-0 leading-tight">
                                    <span class="block text-sm font-bold text-gray-900 dark:text-white group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors line-clamp-2" x-text="ev.title"></span>
                                    <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                        <span class="font-semibold text-[#0056b3]/70 dark:text-[#64CCC5]/80" x-text="ev.typeLabel"></span>
                                        <template x-if="ev.hijriLabel"><span class="tabular-nums" x-text="'· ' + ev.hijriLabel"></span></template>
                                    </span>
                                </span>
                                <span x-show="ev.url === currentUrl"
                                      class="flex-shrink-0 px-2 py-1 rounded-lg text-[10px] font-bold bg-[#64CCC5]/25 text-[#0a7d77] dark:text-[#64CCC5]">{{ __('seerah.public.you_are_here') }}</span>
                                <i x-show="ev.url !== currentUrl" data-lucide="chevron-left" class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-[#64CCC5] ltr:rotate-180 flex-shrink-0 transition-colors"></i>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>

@once
@push('head')
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
@push('scripts')
<script>
    function seerahNavigator(config) {
        return {
            open: false,
            milestones: config.milestones,
            active: config.currentMilestone ?? config.milestones[0]?.slug ?? null,
            currentUrl: config.currentUrl,
            // slug -> { status: 'idle' | 'loading' | 'loaded' | 'error', events: [] }
            cache: {},

            get activeState() {
                return this.cache[this.active] ?? { status: 'idle', events: [] };
            },

            openModal() {
                this.open = true;
                document.documentElement.style.overflow = 'hidden';
                if (this.active) this.load(this.active);
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            close() {
                this.open = false;
                document.documentElement.style.overflow = '';
            },

            select(slug) {
                this.active = slug;
                this.load(slug);
            },

            async load(slug, isRetry = false) {
                const state = this.cache[slug]?.status;
                if (state === 'loading' || state === 'loaded' || (state === 'error' && ! isRetry)) return;
                const milestone = this.milestones.find(m => m.slug === slug);
                if (! milestone) return;

                this.cache[slug] = { status: 'loading', events: [] };
                try {
                    const res = await fetch(milestone.url, { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    this.cache[slug] = { status: 'loaded', events: data.events ?? [] };
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } catch (e) {
                    this.cache[slug] = { status: 'error', events: [] };
                }
            },
        };
    }
</script>
@endpush
@endonce
