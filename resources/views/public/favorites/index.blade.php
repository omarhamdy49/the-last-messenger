@extends('layouts.public')

@section('title', __('profile.bookmarks_title') . ' — ' . __('site.site_name'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex flex-col gap-2 mb-8">
        <nav class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <a href="{{ route('public.profile') }}" class="hover:text-primary">{{ __('profile.profile') }}</a>
            <i data-lucide="chevron-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'left' : 'right' }}" class="w-4 h-4"></i>
            <span>{{ __('profile.bookmarks_title') }}</span>
        </nav>
        <h1 class="text-3xl font-bold gradient-text">{{ __('profile.bookmarks_title') }}</h1>
        <p class="text-gray-600 dark:text-gray-300">{{ __('profile.bookmarks_subtitle') }}</p>
    </div>

    {{-- Type filter pills --}}
    <div class="mb-8">
        <x-site.filter-pills :pills="$filterPills" :active="$activeType" />
    </div>

    @if ($bookmarks->isEmpty())
        {{-- Empty state --}}
        <x-site.empty-state
            icon="bookmark"
            :title="__('profile.no_bookmarks_title')"
            :message="__('profile.no_bookmarks_message')">
            <a href="{{ route('public.home') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary hover:opacity-90 transition-opacity">
                <i data-lucide="compass" class="w-4 h-4"></i>
                {{ __('profile.browse_content') }}
            </a>
        </x-site.empty-state>
    @else
        {{-- Bookmarks grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($bookmarks as $item)
                <x-site.bookmark-card :item="$item" />
            @endforeach
        </div>

        <x-site.pagination :paginator="$paginator" />
    @endif
</div>
@endsection
