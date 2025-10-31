{{--
    SEO Meta Tags Component

    Renders complete SEO metadata including:
    - Standard meta tags (title, description, keywords)
    - OpenGraph tags for social sharing
    - Twitter Card tags
    - JSON-LD structured data
    - Canonical URL

    Usage in Blade templates:
    <x-seo-meta-tags :metadata="$seo" />

    Props:
    - metadata: SeoMetadata object (required)
    - title: Override page title (optional)
    - noIndex: Disable search engine indexing (optional, default: false)
--}}

@props([
    'metadata',
    'title' => null,
    'noIndex' => false,
])

@php
    // Use provided title or fall back to metadata title
    $pageTitle = $title ?? $metadata->title;
@endphp

{{-- Page Title --}}
<title>{{ $pageTitle }}</title>

{{-- Standard Meta Tags --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content="{{ $metadata->description }}">
<meta name="keywords" content="{{ $metadata->keywords }}">

{{-- Robots Meta Tag --}}
@if($noIndex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
@endif

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $metadata->canonicalUrl }}">

{{-- Language and Locale --}}
<meta name="language" content="{{ $metadata->locale }}">
<meta http-equiv="content-language" content="{{ $metadata->locale }}">

{{-- Additional Meta Tags --}}
@foreach($metadata->metaTags as $name => $content)
    @if(str_starts_with($name, 'property:'))
        <meta property="{{ str_replace('property:', '', $name) }}" content="{{ $content }}">
    @else
        <meta name="{{ $name }}" content="{{ $content }}">
    @endif
@endforeach

{{-- OpenGraph Meta Tags --}}
@foreach($metadata->openGraph as $property => $content)
    @if(is_array($content))
        @foreach($content as $value)
            <meta property="{{ $property }}" content="{{ $value }}">
        @endforeach
    @else
        <meta property="{{ $property }}" content="{{ $content }}">
    @endif
@endforeach

{{-- Twitter Card Meta Tags --}}
@foreach($metadata->twitterCard as $name => $content)
    <meta name="{{ $name }}" content="{{ $content }}">
@endforeach

{{-- JSON-LD Structured Data --}}
@if(!empty($metadata->jsonLd))
    <script type="application/ld+json">
        {!! json_encode($metadata->jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endif

{{-- Favicon and App Icons (if needed) --}}
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Theme Color for Mobile Browsers --}}
<meta name="theme-color" content="#0056b3">
<meta name="msapplication-TileColor" content="#0056b3">

{{-- Prefetch DNS for External Resources --}}
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">

{{-- Preconnect for Performance --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://cdn.jsdelivr.net">
