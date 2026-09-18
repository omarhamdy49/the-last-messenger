@extends('layouts.public')

@section('title', __('profile.bookmarks_title') . ' — ' . __('site.site_name'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-url="route('public.profile')" :eyebrow-label="__('profile.profile')" eyebrow-icon="user"
            :title="__('profile.bookmarks_title')" :lead="__('profile.bookmarks_subtitle')"
            :stats="$paginator->total() > 0 ? [
                ['icon' => 'bookmark', 'label' => __('profile.bookmarks_title'), 'value' => number_format($paginator->total())],
            ] : []" />

        {{-- Type filter pills --}}
        <div class="mb-8">
            <x-site.filter-pills :pills="$filterPills" :active="$activeType" />
        </div>

        @if ($bookmarks->isEmpty())
            {{-- Empty state --}}
            <x-site.empty-state
                class="sx-surface"
                icon="bookmark"
                :title="__('profile.no_bookmarks_title')"
                :message="__('profile.no_bookmarks_message')">
                <a href="{{ route('public.home') }}" class="sx-btn is-primary mt-2">
                    <i data-lucide="compass" aria-hidden="true"></i>
                    {{ __('profile.browse_content') }}
                </a>
            </x-site.empty-state>
        @else
            {{-- Bookmarks grid --}}
            <div class="sx-grid">
                @foreach ($bookmarks as $item)
                    <x-site.bookmark-card :item="$item" />
                @endforeach
            </div>

            <x-site.pagination :paginator="$paginator" />
        @endif
    </div>
</div>
@endsection
