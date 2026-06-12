@extends('layouts.public')

@section('title', ($page['seo_title'] ?? null) ?: ($page['title'] ?? __('site.site_name')))
@section('meta_description', ($page['seo_description'] ?? null) ?: '')

@section('content')
<section class="relative py-16 sm:py-20 overflow-hidden">
    {{-- Decorative brand backdrop (matches auth pages) --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-10 end-10 w-72 h-72 bg-gradient-to-br from-[#0056b3]/10 to-[#218838]/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 start-10 w-96 h-96 bg-gradient-to-br from-[#218838]/10 to-[#64CCC5]/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            {{-- Page header --}}
            <header class="text-center mb-10 animate-fade-in-up motion-reduce:animate-none">
                <h1 class="text-3xl md:text-4xl font-black bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent break-words">
                    {{ $page['title'] }}
                </h1>
            </header>

            {{-- Page body --}}
            <article class="bg-white dark:bg-[#16213e] rounded-2xl shadow-xl p-6 sm:p-10 animate-fade-in-up motion-reduce:animate-none">
                @if (!empty($page['body']))
                    {{--
                        Invariant: page bodies are authored in the admin layer,
                        which sanitizes the HTML at write time — rendering raw
                        here is safe and intentional (trusted CMS content).
                    --}}
                    <div class="static-page-body text-gray-700 dark:text-gray-300 leading-relaxed break-words">
                        {!! $page['body'] !!}
                    </div>
                @else
                    {{-- Empty state: page exists but has no body for this locale yet --}}
                    <div class="text-center py-12">
                        <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-4 text-gray-400"></i>
                        <p class="text-gray-600 dark:text-gray-300">{{ __('site.page_empty') }}</p>
                        <a href="{{ route('public.home') }}"
                           class="mt-6 inline-flex items-center gap-2 text-[#0056b3] dark:text-[#4a9eff] font-semibold hover:underline">
                            {{ __('site.back_to_home') }}
                        </a>
                    </div>
                @endif
            </article>
        </div>
    </div>
</section>

@push('head')
<style>
    /* Typographic rhythm for CMS-authored bodies (h2/p/ul from the admin editor) */
    .static-page-body h2 { font-size: 1.375rem; font-weight: 800; margin: 1.75rem 0 0.75rem; color: inherit; }
    .static-page-body h3 { font-size: 1.125rem; font-weight: 700; margin: 1.25rem 0 0.5rem; }
    .static-page-body p { margin-bottom: 1rem; }
    .static-page-body ul, .static-page-body ol { margin: 0 0 1rem; padding-inline-start: 1.5rem; list-style: disc; }
    .static-page-body ol { list-style: decimal; }
    .static-page-body li { margin-bottom: 0.5rem; }
    .static-page-body a { color: #0056b3; text-decoration: underline; }
    .dark .static-page-body a { color: #4a9eff; }
</style>
@endpush
@endsection
