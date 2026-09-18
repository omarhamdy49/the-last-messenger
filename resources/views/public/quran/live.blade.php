@extends('layouts.public')

@section('title', __('quran.public.live_title'))
@section('robots', 'noindex, follow')

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.quran')" :eyebrow-label="__('quran.public.page_title')" eyebrow-icon="book-open"
            :title="__('quran.public.live_title')" />

        <div class="mx-auto grid max-w-2xl justify-items-center gap-4">
            <x-public.editorial.empty-state icon="radio" class="w-full"
                :title="__('quran.public.live_coming_soon_title')"
                :hint="__('quran.public.live_coming_soon_message')" />

            <a href="{{ route('public.quran') }}" class="sx-btn is-primary">
                <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('quran.public.page_title') }}
            </a>
        </div>
    </div>
</div>
@endsection
