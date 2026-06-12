@extends('layouts.public')

@section('title', __('quran.public.live_title'))
@section('robots', 'noindex, follow')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <x-site.breadcrumb
        class="mb-8"
        :items="[
            ['label' => __('quran.public.page_title'), 'url' => route('public.quran')],
            ['label' => __('quran.public.live_title')],
        ]"
    />

    <div class="card-premium max-w-2xl mx-auto">
        <x-site.empty-state
            icon="radio"
            :title="__('quran.public.live_coming_soon_title')"
            :message="__('quran.public.live_coming_soon_message')"
        >
            <a href="{{ route('public.quran') }}"
               class="mt-2 inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary shadow-md hover:shadow-lg transition-all">
                <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'right' : 'left' }}" class="w-4 h-4"></i>
                {{ __('quran.public.page_title') }}
            </a>
        </x-site.empty-state>
    </div>
</div>
@endsection
