@extends('layouts.public')

@section('title', __('seerah.public.events_title'))
@section('meta_description', __('seerah.public.events_description'))

@php
    $items = collect($events->items());
    // Timeline view groups the page by Hijri year, in the order the server sorted it.
    $years = $items->groupBy(fn (array $event) => $event['hijriYear'] === null ? '—' : (string) $event['hijriYear']);
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20"
     x-data="{
        loading: false,
        view: (() => { try { return localStorage.getItem('sx-events-view') || 'timeline'; } catch (e) { return 'timeline'; } })(),
        setView(value) { this.view = value; try { localStorage.setItem('sx-events-view', value); } catch (e) {} this.$nextTick(() => window.lucide?.createIcons()); },
     }"
     @sx-loading.window="loading = true">

    <div class="sx-wrap">
        <x-public.seerah.directory-hero :total="$totalEvents" :categories="max(0, count($categoryPills) - 1)" />

        <x-public.seerah.filter-panel
            :filters="$filters" :search-query="$searchQuery"
            :milestone-options="$milestoneOptions" :era-options="$eraOptions"
            :facets="$facets" :filter-url="$filterUrl" :active-chips="$activeChips"
            :category-pills="$categoryPills" :active-category="$activeCategory" />

        <div class="mt-8 mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-[color:var(--sx-muted)]" aria-live="polite">
                {!! __('seerah.public.showing_results', [
                    'shown' => '<strong class="text-[color:var(--sx-ink)] tabular-nums">'.$items->count().'</strong>',
                    'total' => '<strong class="text-[color:var(--sx-ink)] tabular-nums">'.number_format($totalEvents).'</strong>',
                ]) !!}
            </p>
            @if ($items->isNotEmpty())
                <div class="sx-segment" role="group" aria-label="{{ __('seerah.public.view_label') }}">
                    <button type="button" @click="setView('timeline')" :aria-pressed="(view === 'timeline').toString()">
                        <i data-lucide="git-commit-vertical" aria-hidden="true"></i>{{ __('seerah.public.view_timeline') }}
                    </button>
                    <button type="button" @click="setView('grid')" :aria-pressed="(view === 'grid').toString()">
                        <i data-lucide="layout-grid" aria-hidden="true"></i>{{ __('seerah.public.view_grid') }}
                    </button>
                </div>
            @endif
        </div>

        {{-- Skeleton while a filter submit navigates. --}}
        <div x-show="loading" x-cloak class="sx-grid" aria-hidden="true">
            @for ($i = 0; $i < 6; $i++)
                <div class="sx-card animate-pulse">
                    <div class="sx-card-media !bg-[color:var(--sx-surface-2)]"></div>
                    <div class="sx-card-body">
                        <div class="h-5 w-3/4 rounded-lg bg-[color:var(--sx-surface-2)]"></div>
                        <div class="h-4 w-full rounded-lg bg-[color:var(--sx-surface-2)]"></div>
                        <div class="h-4 w-1/2 rounded-lg bg-[color:var(--sx-surface-2)]"></div>
                    </div>
                </div>
            @endfor
        </div>

        <div x-show="!loading">
            @if ($items->isEmpty())
                <x-public.seerah.empty-state
                    :title="__('seerah.public.no_results_title')"
                    :hint="__('seerah.public.no_results_hint')"
                    :action-url="route('public.seerah.events')"
                    :action-label="__('seerah.public.reset_search')" />
            @else
                <div x-show="view === 'timeline'" class="sx-timeline">
                    @foreach ($years as $year => $group)
                        <section class="sx-year" aria-label="{{ $year === '—' ? '—' : __('seerah.public.year_heading', ['year' => abs((int) $year)]) }}">
                            <div class="sx-year-head">
                                <strong>{{ $year === '—' ? '—' : abs((int) $year) }}</strong>
                                <span>
                                    @if ($year !== '—'){{ (int) $year >= 0 ? __('seerah.public.hijri_suffix') : __('seerah.public.before_hijra_suffix') }} · @endif
                                    {{ __('seerah.public.events_in_year', ['count' => $group->count()]) }}
                                </span>
                            </div>
                            <div class="sx-year-list">
                                @foreach ($group as $event)
                                    <x-public.seerah.event-card :event="$event" variant="row" :eager="$loop->parent->first && $loop->first" />
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div x-show="view === 'grid'" x-cloak class="sx-grid">
                    @foreach ($items as $event)
                        <x-public.seerah.event-card :event="$event" />
                    @endforeach
                </div>
            @endif

            <div class="mt-12">
                <x-public.seerah.pagination :paginator="$events" />
            </div>
        </div>
    </div>
</div>
@endsection
