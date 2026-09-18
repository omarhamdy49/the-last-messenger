@extends('layouts.public')

@section('title', __('sahaba.page_title'))
@section('meta_description', __('sahaba.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('sahaba.page_title') }}",
    "description": "{{ __('sahaba.page_description') }}",
    "url": "{{ route('public.sahaba') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-url="url('/')" :eyebrow-label="__('site.home')" eyebrow-icon="home"
            :title="__('sahaba.hero_title')" :lead="__('sahaba.hero_subtitle')"
            :stats="[
                ['icon' => 'users', 'label' => __('sahaba.stat_total'), 'value' => number_format($stats['total'])],
                ['icon' => 'star', 'label' => __('sahaba.stat_featured'), 'value' => number_format($stats['featured'])],
                ['icon' => 'user-round', 'label' => __('sahaba.stat_female'), 'value' => number_format($stats['female'])],
            ]">

            {{-- Search stays the directory's primary action, and keeps the
                 active filter so searching does not silently widen it. --}}
            <form method="GET" action="{{ route('public.sahaba') }}" class="max-w-xl">
                <input type="hidden" name="filter" value="{{ $activeFilter }}">
                <label for="sahaba-q" class="sr-only">{{ __('sahaba.search_placeholder') }}</label>
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="sahaba-q" type="search" name="q" value="{{ $search }}" enterkeyhint="search"
                           placeholder="{{ __('sahaba.search_placeholder') }}" class="sx-input ps-12 pe-28">
                    <button type="submit" class="sx-btn is-primary absolute end-1.5 top-1/2 -translate-y-1/2">
                        {{ __('site.search') }}
                    </button>
                </div>
            </form>
        </x-public.editorial.page-hero>

        {{-- Filters: server-side links, one chip per pill, counts included. --}}
        <nav class="sx-scroller" aria-label="{{ __('sahaba.filter_all') }}">
            @foreach ($filterPills as $pill)
                <a href="{{ $pill['url'] }}" @class(['sx-chip shrink-0', 'is-active' => $activeFilter === $pill['value']])
                   @if ($activeFilter === $pill['value']) aria-current="page" @endif>
                    <i data-lucide="{{ $pill['icon'] }}" aria-hidden="true"></i>{{ $pill['label'] }}
                    <span class="sx-count">{{ number_format($pill['count']) }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-8 mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-[color:var(--sx-muted)]" aria-live="polite">
                {{ __('site.showing') }}
                <strong class="tabular-nums text-[color:var(--sx-ink)]">{{ number_format($companions->count()) }}</strong>
                {{ __('site.of') }}
                <span class="tabular-nums">{{ number_format($paginator->total()) }}</span>
                {{ __('sahaba.companions_unit') }}
            </p>

            @if ($search !== '' || $activeFilter !== 'all')
                <a href="{{ route('public.sahaba') }}" class="sx-chip is-danger">
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i>{{ __('site.reset_filters') }}
                </a>
            @endif
        </div>

        @if ($companions->isEmpty())
            <x-public.editorial.empty-state
                icon="users"
                :title="__('sahaba.empty_title')"
                :hint="__('sahaba.empty_message')"
                :action-url="route('public.sahaba')"
                :action-label="__('site.reset_filters')" />
        @else
            <div class="sx-grid is-tight">
                @foreach ($companions as $sahabi)
                    <x-public.people.companion-card :sahabi="$sahabi" :eager="$loop->index < 4" />
                @endforeach
            </div>

            <div class="mt-12">
                <x-public.editorial.pagination :paginator="$paginator->withQueryString()" />
            </div>
        @endif
    </div>
</div>
@endsection
