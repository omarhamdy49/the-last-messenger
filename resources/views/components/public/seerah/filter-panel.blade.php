{{--
    One GET form for every filter (a filtered view is a shareable URL; back restores it),
    plus category chips, removable active-filter chips and the timeline/cards view switch.

    @props filters, searchQuery, milestoneOptions, eraOptions, facets, filterUrl (callable),
           activeChips, categoryPills, activeCategory
--}}
@props(['filters', 'searchQuery' => '', 'milestoneOptions' => [], 'eraOptions' => [], 'facets' => [], 'filterUrl', 'activeChips' => [], 'categoryPills' => [], 'activeCategory' => 'all'])

<section class="grid gap-4" aria-label="{{ __('seerah.public.browse_and_filter') }}">
    <form method="GET" action="{{ route('public.seerah.events') }}" role="search"
          x-data="{ advanced: {{ ($filters['milestone'] || $filters['era'] || $filters['disputed']) ? 'true' : 'false' }} }"
          @submit="$dispatch('sx-loading')"
          class="sx-surface sx-filters">
        @if ($filters['type'] !== '')
            <input type="hidden" name="category" value="{{ $filters['type'] }}">
        @endif

        <div class="sx-filter-row">
            <div class="relative">
                <label for="sx-q" class="sr-only">{{ __('seerah.public.search_placeholder') }}</label>
                <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                <input id="sx-q" type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('seerah.public.search_placeholder') }}" class="sx-input ps-12" enterkeyhint="search">
            </div>
            <div>
                <label for="sx-sort" class="sr-only">{{ __('seerah.public.sort_label') }}</label>
                <select id="sx-sort" name="sort" class="sx-input" @change="$el.form.requestSubmit()">
                    <option value="asc" @selected($filters['sort'] === 'asc')>{{ __('seerah.public.sort_oldest_first') }}</option>
                    <option value="desc" @selected($filters['sort'] === 'desc')>{{ __('seerah.public.sort_newest_first') }}</option>
                </select>
            </div>
            <button type="button" class="sx-btn" @click="advanced = !advanced" :aria-expanded="advanced.toString()" aria-controls="sx-advanced">
                <i data-lucide="sliders-horizontal" aria-hidden="true"></i>{{ __('seerah.public.more_filters') }}
            </button>
            <button type="submit" class="sx-btn is-primary">
                <i data-lucide="search" aria-hidden="true"></i>{{ __('seerah.public.apply_filters') }}
            </button>
        </div>

        <div id="sx-advanced" x-show="advanced" x-cloak x-collapse>
            <div class="grid gap-3 sm:grid-cols-2 pt-3 border-t border-[color:var(--sx-line)]">
                <div>
                    <label for="sx-milestone" class="sx-label">{{ __('seerah.public.milestone_label') }}</label>
                    <select id="sx-milestone" name="milestone" class="sx-input">
                        <option value="">{{ __('seerah.public.filter_all') }}</option>
                        @foreach ($milestoneOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($filters['milestone'] === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sx-era" class="sx-label">{{ __('seerah.public.era_label') }}</label>
                    <select id="sx-era" name="era" class="sx-input">
                        <option value="">{{ __('seerah.public.filter_all') }}</option>
                        @foreach ($eraOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($filters['era'] === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                @if (($facets['disputed'] ?? 0) > 0)
                    <div class="sm:col-span-2">
                        {{-- A link, not a checkbox: one tap applies and the state is a real URL. --}}
                        <a href="{{ $filterUrl(['disputed' => $filters['disputed'] ? null : 1]) }}"
                           aria-pressed="{{ $filters['disputed'] ? 'true' : 'false' }}"
                           @class(['sx-chip is-danger', '!bg-red-600 !text-white' => $filters['disputed']])>
                            <i data-lucide="scale" aria-hidden="true"></i>{{ __('seerah.public.dispute_badge') }}
                            <span class="sx-count">{{ $facets['disputed'] }}</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </form>

    <nav class="sx-scroller" aria-label="{{ __('seerah.public.categories_label') }}">
        @foreach ($categoryPills as $cat)
            <a href="{{ $cat['url'] }}" @class(['sx-chip whitespace-nowrap', 'is-active' => $activeCategory === $cat['value']])
               @if ($activeCategory === $cat['value']) aria-current="page" @endif>
                {{ $cat['label'] }}<span class="sx-count">{{ $cat['count'] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($activeChips !== [])
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-[color:var(--sx-muted)]">{{ __('seerah.public.active_filters') }}</span>
            @foreach ($activeChips as $chip)
                <a href="{{ $chip['url'] }}" class="sx-chip" aria-label="{{ __('seerah.public.remove_filter', ['filter' => $chip['label']]) }}">
                    {{ $chip['label'] }}<i data-lucide="x" aria-hidden="true"></i>
                </a>
            @endforeach
            <a href="{{ route('public.seerah.events') }}" class="sx-chip !bg-transparent !border-transparent !text-[color:var(--sx-accent)]">
                <i data-lucide="rotate-ccw" aria-hidden="true"></i>{{ __('seerah.public.reset_filters') }}
            </a>
        </div>
    @endif
</section>
