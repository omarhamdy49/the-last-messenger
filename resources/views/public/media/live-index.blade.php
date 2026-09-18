@extends('layouts.public')

@section('title', __('media.public.live_channels').' | '.config('app.name'))
@section('meta_description', __('media.public.live_channels_description'))

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-url="route('public.media')" :eyebrow-label="__('media.public.page_title')" eyebrow-icon="clapperboard"
            :title="__('media.public.live_channels')" :lead="__('media.public.live_channels_description')"
            :stats="$channels->total() > 0 ? [
                ['icon' => 'radio', 'label' => __('media.public.live_channels'), 'value' => number_format($channels->total())],
            ] : []" />

        @if ($channels->isEmpty())
            <x-public.editorial.empty-state icon="radio"
                :title="__('media.public.live_channels')" :hint="__('media.public.no_live_channels')"
                :action-url="route('public.media')" :action-label="__('media.public.back_to_programs')" />
        @else
            <div class="sx-grid is-wide">
                @foreach ($channels as $index => $card)
                    <x-public.media.live-card :card="$card" :eager="$index < 3" />
                @endforeach
            </div>

            <div class="mt-12">
                <x-public.editorial.pagination :paginator="$channels" />
            </div>
        @endif
    </div>
</div>
@endsection
