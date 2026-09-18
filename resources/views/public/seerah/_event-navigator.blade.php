{{--
    Breadcrumb + milestone navigator for the single-event pages (story and
    reading styles). The breadcrumb is rendered inside the hero, over the
    artwork — so it keeps glass/white treatment. The modal is teleported to
    <body> and speaks the `sx` vocabulary. It lazy-fetches each era's stations
    from the same JSON endpoint the /seerah journey uses, with a retry action.

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

    {{-- Breadcrumb bar (over hero imagery) --}}
    <nav aria-label="{{ __('seerah.public.breadcrumb_seerah') }}"
         class="mb-8 flex items-center justify-between gap-3 text-sm text-white/80">
        <ol class="flex min-w-0 flex-wrap items-center gap-1.5">
            <li class="flex items-center gap-1.5">
                <a href="{{ route('public.seerah') }}" class="inline-flex items-center gap-1.5 font-semibold transition-colors hover:text-white">
                    <i data-lucide="milestone" aria-hidden="true" class="h-4 w-4"></i>{{ __('seerah.public.breadcrumb_seerah') }}
                </a>
            </li>
            @if ($navMilestone)
                <li aria-hidden="true"><i data-lucide="chevron-left" class="h-4 w-4 text-white/50 ltr:rotate-180"></i></li>
                <li class="min-w-0">
                    <a href="{{ $navMilestone['url'] }}" title="{{ $navMilestone['title'] }}"
                       class="sx-clamp-1 block max-w-[11rem] font-semibold transition-colors hover:text-white sm:max-w-[16rem]">{{ $navMilestone['title'] }}</a>
                </li>
            @endif
            <li aria-hidden="true" class="hidden sm:block"><i data-lucide="chevron-left" class="h-4 w-4 text-white/50 ltr:rotate-180"></i></li>
            <li aria-current="page" title="{{ $event['title'] }}" class="sx-clamp-1 hidden max-w-[14rem] font-bold text-white sm:block lg:max-w-[22rem]">{{ $event['title'] }}</li>
        </ol>

        @if (! empty($journeyNav))
            {{-- Label collapses to an icon under sm, so the accessible name is explicit. --}}
            <button type="button" @click="openModal()" class="sx-btn is-glass flex-shrink-0"
                    aria-label="{{ __('seerah.public.navigator_open') }}" title="{{ __('seerah.public.navigator_open') }}">
                <i data-lucide="map" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('seerah.public.navigator_open') }}</span>
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
         class="fixed inset-0 z-[70] flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center sm:p-4"
         role="dialog" aria-modal="true" aria-label="{{ __('seerah.public.navigator_title') }}"
         style="display: none;">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="sx-surface flex max-h-[88vh] w-full flex-col overflow-hidden !rounded-b-none sm:max-h-[80vh] sm:max-w-3xl sm:!rounded-3xl">

            {{-- header --}}
            <div class="relative border-b border-[color:var(--sx-line)] bg-[color:var(--sx-accent-soft)] px-5 py-4 sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2.5 text-lg font-extrabold text-[color:var(--sx-ink)] sm:text-xl">
                            <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-[image:var(--sx-grad)] text-white">
                                <i data-lucide="route" aria-hidden="true" class="h-4 w-4"></i>
                            </span>
                            {{ __('seerah.public.navigator_title') }}
                        </h2>
                        <p class="mt-1 text-xs text-[color:var(--sx-muted)] sm:text-sm">{{ __('seerah.public.navigator_hint') }}</p>
                    </div>
                    <button type="button" @click="close()" aria-label="{{ __('seerah.public.close') }}" title="{{ __('seerah.public.close') }}"
                            class="sx-btn flex-shrink-0 !min-w-11 !px-3">
                        <i data-lucide="x" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            {{-- mobile: milestone chips --}}
            <div class="sx-scroller border-b border-[color:var(--sx-line)] px-4 py-3 sm:hidden">
                <template x-for="(m, mi) in milestones" :key="m.slug">
                    <button type="button" @click="select(m.slug)" :title="m.title"
                            class="sx-chip flex-shrink-0"
                            :class="active === m.slug ? 'is-active' : ''">
                        <span class="tabular-nums" x-text="mi + 1"></span>
                        <span class="sx-clamp-1 max-w-[9rem]" x-text="m.title"></span>
                    </button>
                </template>
            </div>

            {{-- body --}}
            <div class="grid min-h-0 flex-1 sm:grid-cols-[250px_1fr]">
                {{-- desktop: milestone list --}}
                <div class="sx-tile-list hidden overflow-y-auto border-e border-[color:var(--sx-line)] p-3 sm:grid">
                    <template x-for="(m, mi) in milestones" :key="m.slug">
                        <button type="button" @click="select(m.slug)" :title="m.title"
                                class="sx-tile text-start hover:border-[color:var(--sx-line-strong)]"
                                :class="active === m.slug ? '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]' : ''">
                            <span class="sx-tile-icon text-xs font-extrabold tabular-nums" x-text="mi + 1"></span>
                            <span class="min-w-0 leading-tight">
                                <span class="sx-tile-title text-[13px]" x-text="m.title"></span>
                                <span class="sx-tile-sub tabular-nums" x-text="m.range || (m.count + ' {{ __('seerah.public.stations_count') }}')"></span>
                            </span>
                            <span></span>
                        </button>
                    </template>
                </div>

                {{-- stations of the active era --}}
                <div class="overflow-y-auto p-3 sm:p-4">
                    {{-- skeletons --}}
                    <div x-show="activeState.status === 'idle' || activeState.status === 'loading'" class="sx-tile-list" aria-hidden="true">
                        <template x-for="i in 5" :key="i">
                            <div class="sx-tile">
                                <span class="sx-skeleton block h-8 w-8 !rounded-lg"></span>
                                <span class="grid gap-2">
                                    <span class="sx-skeleton block h-3 w-3/4"></span>
                                    <span class="sx-skeleton block h-2 w-1/3"></span>
                                </span>
                                <span></span>
                            </div>
                        </template>
                    </div>

                    {{-- load failure + retry --}}
                    <div x-show="activeState.status === 'error'" x-cloak role="alert"
                         class="sx-note items-center !bg-[color:var(--sx-danger-soft)] !text-[color:var(--sx-danger)]">
                        <i data-lucide="wifi-off" aria-hidden="true" class="h-5 w-5 !text-[color:var(--sx-danger)]"></i>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="font-semibold">{{ __('seerah.public.load_failed') }}</p>
                            <button type="button" @click="load(active, true)" class="sx-btn">
                                <i data-lucide="rotate-cw" aria-hidden="true"></i>{{ __('seerah.public.retry') }}
                            </button>
                        </div>
                    </div>

                    {{-- stations --}}
                    <div x-show="activeState.status === 'loaded'" x-cloak class="sx-tile-list">
                        <template x-for="(ev, i) in activeState.events" :key="ev.slug">
                            <a :href="ev.url" :title="ev.title"
                               class="sx-tile"
                               :class="ev.url === currentUrl ? '!border-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]' : ''">
                                <span class="sx-tile-icon !bg-[image:var(--sx-grad)] text-xs font-extrabold tabular-nums !text-white"
                                      x-text="i + 1"></span>
                                <span class="min-w-0 leading-tight">
                                    <span class="sx-tile-title text-sm" x-text="ev.title"></span>
                                    <span class="sx-tile-sub mt-0.5 flex items-center gap-1.5">
                                        <span class="font-semibold text-[color:var(--sx-accent)]" x-text="ev.typeLabel"></span>
                                        <template x-if="ev.hijriLabel"><span class="tabular-nums" x-text="'· ' + ev.hijriLabel"></span></template>
                                    </span>
                                </span>
                                <span x-show="ev.url === currentUrl" class="sx-chip is-active flex-shrink-0 !text-[11px]">{{ __('seerah.public.you_are_here') }}</span>
                                <i x-show="ev.url !== currentUrl" data-lucide="chevron-left" aria-hidden="true"
                                   class="h-4 w-4 flex-shrink-0 text-[color:var(--sx-muted)] ltr:rotate-180"></i>
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
