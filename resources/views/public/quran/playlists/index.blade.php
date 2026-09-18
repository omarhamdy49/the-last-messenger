@extends('layouts.public')

@section('title', __('quran.playlists.title').' | '.__('quran.public.page_title'))
@section('meta_description', __('quran.playlists.subtitle'))

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $createUrl = auth()->check() ? route('public.quran.playlists.create') : route('public.login');
@endphp

<div class="sx min-h-[100dvh] pb-20"
     x-data
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.quran')" :eyebrow-label="__('quran.public.page_title')" eyebrow-icon="list-music"
            :title="__('quran.playlists.title')" :lead="__('quran.playlists.subtitle')"
            :stats="[
                ['icon' => 'list-music', 'label' => __('quran.playlists.totals_public'), 'value' => $num($totals['public'])],
                ['icon' => 'headphones', 'label' => __('quran.playlists.totals_plays'), 'value' => $num($totals['plays'])],
                ['icon' => 'mic', 'label' => __('quran.playlists.totals_reciters'), 'value' => $num($totals['reciters'])],
            ]">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $createUrl }}"
                   @if (! auth()->check()) @click.prevent="$dispatch('open-login')" @endif
                   class="sx-btn is-primary">
                    <i data-lucide="list-plus" aria-hidden="true"></i>{{ __('quran.playlists.create') }}
                </a>
                <a href="{{ route('public.quran.reciters') }}" class="sx-btn">
                    <i data-lucide="mic" aria-hidden="true"></i>{{ __('quran.public.reciters') }}
                </a>
            </div>
        </x-public.editorial.page-hero>

        {{-- One promoted playlist leads; with none, the three steps explain the tool. --}}
        @if ($featured)
            @php $fr = array_slice($featured['reciters'], 0, 4); @endphp
            <a href="{{ $featured['url'] }}" title="{{ $featured['name'] }}" class="sx-surface sx-spotlight group mb-8">
                <div class="sx-spotlight-media">
                    <span aria-hidden="true" class="absolute top-1 end-4 text-[72px] leading-none font-extrabold tabular-nums text-white/20 select-none">{{ $num($featured['items']) }}</span>
                    <div class="absolute inset-0 flex items-end gap-3 p-5">
                        <div class="flex {{ $isRtl ? 'space-x-reverse' : '' }} -space-x-4">
                            @forelse ($fr as $r)
                                <span class="sx-avatar ring-[3px] ring-white/85" title="{{ $r['name'] }}">
                                    @if ($r['image'])
                                        <img src="{{ $r['image'] }}" alt="" width="56" height="56" loading="lazy" decoding="async" onerror="this.remove()">
                                    @else
                                        <span class="px-1 text-center text-[9px] font-bold leading-tight sx-clamp-2">{{ $r['name'] }}</span>
                                    @endif
                                </span>
                            @empty
                                <span class="sx-avatar ring-[3px] ring-white/85"><i data-lucide="mic-off" aria-hidden="true"></i></span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="sx-spotlight-body">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="sx-chip is-active"><i data-lucide="star" aria-hidden="true"></i>{{ __('quran.playlists.featured') }}</span>
                        <span class="sx-chip"><i data-lucide="headphones" aria-hidden="true"></i><span class="tabular-nums">{{ $num($featured['plays']) }}</span></span>
                    </div>
                    <h2 class="sx-section-title sx-clamp-2 group-hover:text-[color:var(--sx-accent)] transition-colors">{{ $featured['name'] }}</h2>
                    <p class="text-sm text-[color:var(--sx-muted)] tabular-nums">
                        {{ __('quran.playlists.surahs_count', ['count' => $num($featured['items'])]) }}
                        <span aria-hidden="true" class="mx-1.5">·</span>
                        {{ __('quran.playlists.reciters_count', ['count' => $num(count($featured['reciters']))]) }}
                        <span aria-hidden="true" class="mx-1.5">·</span>
                        {{ __('quran.playlists.by') }} {{ $featured['owner']['name'] }}
                    </p>
                    <span class="sx-btn is-primary justify-self-start">
                        <i data-lucide="play" aria-hidden="true"></i>{{ __('quran.playlists.play_all') }}
                    </span>
                </div>
            </a>
        @else
            <section class="sx-surface p-5 sm:p-7 mb-8" aria-labelledby="pl-how-title">
                <h2 id="pl-how-title" class="sx-section-title mb-5">{{ __('quran.playlists.how_title') }}</h2>
                <ol class="sx-path">
                    @foreach ([['mic', 'how_1'], ['list-checks', 'how_2'], ['play', 'how_3']] as $i => [$icon, $key])
                        <li>
                            <span class="sx-path-step" aria-hidden="true">{{ $num($i + 1) }}</span>
                            <div class="sx-panel flex items-center gap-3 p-4">
                                <span class="sx-tile-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
                                <span class="font-semibold leading-relaxed text-[color:var(--sx-ink-2)]">{{ __('quran.playlists.'.$key) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- Search + sort. Sort is a link pair so the order survives a share or a reload. --}}
        <form method="GET" action="{{ route('public.quran.playlists.index') }}" class="sx-surface sx-filters">
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
                <div class="relative">
                    <label for="pl-search" class="sr-only">{{ __('quran.playlists.search') }}</label>
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="pl-search" type="search" name="q" value="{{ $search }}" autocomplete="off" enterkeyhint="search"
                           placeholder="{{ __('quran.playlists.search') }}" class="sx-input ps-12">
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('quran.playlists.sort_latest') }} / {{ __('quran.playlists.sort_plays') }}">
                    @foreach (['latest' => ['sort_latest', 'clock'], 'plays' => ['sort_plays', 'flame']] as $key => [$label, $icon])
                        <a href="{{ route('public.quran.playlists.index', array_filter(['q' => $search, 'sort' => $key])) }}"
                           class="sx-chip @if ($sort === $key) is-active @endif"
                           @if ($sort === $key) aria-current="true" @endif>
                            <i data-lucide="{{ $icon }}" aria-hidden="true"></i>{{ __('quran.playlists.'.$label) }}
                        </a>
                    @endforeach
                </div>
                <p class="text-sm text-[color:var(--sx-muted)] tabular-nums md:text-end" aria-live="polite">
                    {{ __('quran.playlists.results_count', ['count' => $num($playlists->total())]) }}
                </p>
            </div>
            <button type="submit" class="sr-only">{{ __('quran.playlists.search') }}</button>
        </form>

        @if ($playlists->count())
            <div class="sx-grid mt-8">
                @foreach ($playlists as $card)
                    <x-public.quran.playlist-card :card="$card" :eager="$loop->index < 3" />
                @endforeach
            </div>

            <div class="mt-12">
                <x-public.editorial.pagination :paginator="$playlists" />
            </div>
        @else
            <div class="mt-8 grid gap-4 justify-items-center">
                <x-public.editorial.empty-state icon="list-music" class="w-full"
                    :title="$search !== '' ? __('quran.playlists.empty_search') : __('quran.playlists.empty')"
                    :hint="$search !== '' ? __('site.try_adjusting_filters') : __('quran.playlists.empty_hint')" />
                <a href="{{ $createUrl }}"
                   @if (! auth()->check()) @click.prevent="$dispatch('open-login')" @endif
                   class="sx-btn is-primary">
                    <i data-lucide="list-plus" aria-hidden="true"></i>{{ __('quran.playlists.create') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
