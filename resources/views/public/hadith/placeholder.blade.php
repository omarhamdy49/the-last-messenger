@extends('layouts.public')

@section('title', $pageTitle)
@section('robots', 'noindex, follow')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <x-site.breadcrumb
        class="mb-8"
        :items="[
            ['label' => __('hadith.public.breadcrumb'), 'url' => route('public.hadith')],
            ['label' => $heading],
        ]"
    />

    <div class="card-premium max-w-2xl mx-auto">
        <x-site.empty-state
            icon="hammer"
            :title="__('hadith.public.coming_soon_title')"
            :message="__('hadith.public.coming_soon_message')"
        >
            <a href="{{ route('public.hadith') }}"
               class="mt-2 inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary shadow-md hover:shadow-lg transition-all">
                <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'right' : 'left' }}" class="w-4 h-4"></i>
                {{ __('hadith.public.page_title') }}
            </a>
        </x-site.empty-state>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush
@endsection
