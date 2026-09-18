@extends('layouts.public')

@section('title', ($page['seo_title'] ?? null) ?: ($page['title'] ?? __('site.site_name')))
@section('meta_description', ($page['seo_description'] ?? null) ?: '')

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero :title="$page['title']" />

        {{-- Page body --}}
        <article class="sx-surface sx-article max-w-3xl">
            @if (!empty($page['body']))
                {{--
                    Invariant: page bodies are authored in the admin layer,
                    which sanitizes the HTML at write time — rendering raw
                    here is safe and intentional (trusted CMS content).
                --}}
                <div class="static-page-body sx-prose">
                    {!! $page['body'] !!}
                </div>
            @else
                {{-- Empty state: page exists but has no body for this locale yet --}}
                <x-site.empty-state icon="file-text" :title="__('site.page_empty')" message="">
                    <a href="{{ route('public.home') }}" class="sx-btn is-primary mt-2">
                        <i data-lucide="home" aria-hidden="true"></i>
                        {{ __('site.back_to_home') }}
                    </a>
                </x-site.empty-state>
            @endif
        </article>
    </div>
</div>

@push('head')
<style>
    /* Typographic rhythm for CMS-authored bodies (h2/p/ul from the admin editor).
       Colour, measure and leading come from `.sx-prose`; this only adds the
       block spacing and list markers the editor's raw HTML has no classes for. */
    .static-page-body h2 { font-size: 1.375rem; font-weight: 800; margin: 1.75rem 0 0.75rem; }
    .static-page-body h3 { font-size: 1.125rem; font-weight: 700; margin: 1.25rem 0 0.5rem; }
    .static-page-body p { margin-bottom: 1rem; }
    .static-page-body ul, .static-page-body ol { margin: 0 0 1rem; padding-inline-start: 1.5rem; list-style: disc; }
    .static-page-body ol { list-style: decimal; }
    .static-page-body li { margin-bottom: 0.5rem; }
    .static-page-body a { text-decoration: underline; text-underline-offset: 4px; }
</style>
@endpush
@endsection
