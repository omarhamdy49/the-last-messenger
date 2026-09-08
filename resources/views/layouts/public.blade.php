@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);

    // SEO defaults come from SiteSettingsService via ContentServiceProvider's
    // view composer; lang-file values remain the safety net when unseeded.
    // Pages that @section('title' / 'meta_description') keep precedence.
    $seoSiteName = $seoSiteName ?? __('site.site_name');
    $seoDefaultTitle = $seoDefaultTitle ?? $seoSiteName;
    $seoDefaultDescription = $seoDefaultDescription ?? __('site.footer_tagline');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}"
      dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
      :class="$store.theme.dark ? 'dark' : ''"
      class="antialiased scroll-smooth overflow-x-clip">
<head>
    @include('partials.theme-preference')
    <script>window.applyDarkMode();</script>

    @include('partials.google-analytics')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $seoDefaultTitle)</title>
    <meta name="description" content="@yield('meta_description', $seoDefaultDescription)">

    {{-- Favicon — the Seal of the Prophet ﷺ (محمد رسول الله). Scalable SVG for modern
         browsers, PNG apple-touch-icon for iOS home screens. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0056b3">

    {{-- SEO: canonical + locale alternates.
         hreflang is emitted ONLY when a page supplies $hreflangAlternates
         (locale => absolute URL) built from versions that actually exist — never
         identical URLs for different languages, which Google treats as an error. --}}
    @php $canonicalUrl = url()->current(); @endphp
    <link rel="canonical" href="@yield('canonical', $canonicalUrl)">
    @if (! empty($hreflangAlternates ?? []))
        @foreach ($hreflangAlternates as $hl => $hlUrl)
            <link rel="alternate" hreflang="{{ $hl }}" href="{{ $hlUrl }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $hreflangAlternates['ar'] ?? reset($hreflangAlternates) }}">
    @endif
    <meta name="robots" content="@yield('robots', 'index, follow')">

    {{-- Open Graph / Twitter --}}
    <meta property="og:site_name" content="{{ $seoSiteName }}">
    <meta property="og:title" content="@yield('title', $seoDefaultTitle)">
    <meta property="og:description" content="@yield('meta_description', $seoDefaultDescription)">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('canonical', $canonicalUrl)">
    <meta property="og:image" content="@yield('og_image', asset('images/site/rasool-allah-hero-section-bg.jpg'))">
    <meta property="og:locale" content="{{ ['ar' => 'ar_AR', 'en' => 'en_US', 'fr' => 'fr_FR', 'tr' => 'tr_TR', 'ur' => 'ur_PK'][$locale] ?? 'ar_AR' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $seoDefaultTitle)">
    <meta name="twitter:description" content="@yield('meta_description', $seoDefaultDescription)">
    <meta name="twitter:image" content="@yield('og_image', asset('images/site/rasool-allah-hero-section-bg.jpg'))">

    {{-- Structured data: WebSite + SearchAction --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "{{ $seoSiteName }}",
        "url": "{{ url('/') }}",
        "inLanguage": ["ar", "en"],
        "potentialAction": {
            "@@type": "SearchAction",
            "target": "{{ url('/search') }}?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    @stack('structured_data')

    {{-- Self-hosted fonts (no render-blocking 3rd-party request). @font-face +
         unicode-range live in app.css; preload the two most-used faces for the
         current script so the first paint swaps fast. --}}
    @php $fontSubset = $isRtl ? 'arabic' : 'latin'; @endphp
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/readex-pro-'.$fontSubset.'.woff2') }}" crossorigin>

    {{-- CSS is render-blocking on every page, homepage included.

         The homepage used to inline a penthouse-extracted critical set and load
         app.css async (rel=preload + onload swap) to save a ~440ms PSI round
         trip. That was removed: the generated critical-home.css had shrunk to
         17 rules with ZERO layout utilities (fonts, .dark overrides, .glass,
         .space-s-*), so first paint had no sizing or display rules at all and
         the whole page flashed unstyled — most visibly the nav's
         `<svg class="w-5 h-5">` icons, which fell back to the 300x150 intrinsic
         SVG size filled with the unstyled link colour (blue, purple once
         visited). app.css is 39KB brotli, content-hashed and CDN-cached, so
         blocking on it costs one ~0.3s request on a cold visit only.

         If the PSI number needs winning back, regenerate a critical set that
         actually covers above-the-fold at BOTH mobile and desktop widths and
         re-test for flashes — do not restore the async link on its own. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="antialiased bg-[#F8F9FA] text-[#212529] min-h-screen flex flex-col">

    {{-- Pages that want a focused, full-screen app shell (e.g. the ChatGPT-style
         Quran inquiry) declare @section('hide_header') to suppress the site header. --}}
    @hasSection('hide_header')
    @else
        <x-site.header />
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Pages that want a focused, full-height layout (e.g. the chat-style Quran
         inquiry) declare @section('hide_footer') to suppress the site footer. --}}
    @hasSection('hide_footer')
    @else
        <x-site.footer />
    @endif

    {{-- Global toast region --}}
    <div class="fixed bottom-4 end-4 z-[100] space-y-2" aria-live="polite">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div x-transition
                 class="card-premium flex items-center gap-3 px-4 py-3 min-w-[16rem] shadow-2xl">
                <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                      :class="t.type === 'success' ? 'bg-secondary/15 text-secondary' : 'bg-red-100 text-red-600'">
                    <i :data-lucide="t.type === 'success' ? 'check' : 'alert-triangle'" class="w-4 h-4"></i>
                </span>
                <p class="text-sm font-semibold flex-1" x-text="t.message"></p>
                <button @click="$store.toast.dismiss(t.id)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </template>
    </div>

    {{-- Surface flashed toast messages (e.g. after login/logout/register) --}}
    @if (session('toast'))
        <script>
            document.addEventListener('alpine:init', () => {
                window.toast(@json(session('toast')), 'success');
            });
        </script>
    @endif

    {{-- Surface flashed error messages (e.g. a failed bookmark removal) --}}
    @if (session('error'))
        <script>
            document.addEventListener('alpine:init', () => {
                window.toast(@json(session('error')), 'error');
            });
        </script>
    @endif

    {{-- Global login modal (guests only) — opened via the `open-login` event. --}}
    @guest
        @include('components.site.login-modal')
    @endguest

    {{-- Shared persistent audio player (Quran reader recitations). Hidden until a track loads.
         Pages that declare @section('hide_player') (e.g. the Ask-Quran chat shell) suppress it. --}}
    @hasSection('hide_player')
    @else
        <x-audio-player />
    @endif

    @stack('scripts')
</body>
</html>
