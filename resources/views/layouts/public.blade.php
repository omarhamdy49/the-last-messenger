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
      class="antialiased scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $seoDefaultTitle)</title>
    <meta name="description" content="@yield('meta_description', $seoDefaultDescription)">

    {{-- SEO: canonical + locale alternates (ar default, en) --}}
    @php $canonicalUrl = url()->current(); @endphp
    <link rel="canonical" href="@yield('canonical', $canonicalUrl)">
    <link rel="alternate" hreflang="ar" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">

    {{-- Open Graph / Twitter --}}
    <meta property="og:site_name" content="{{ $seoSiteName }}">
    <meta property="og:title" content="@yield('title', $seoDefaultTitle)">
    <meta property="og:description" content="@yield('meta_description', $seoDefaultDescription)">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="@yield('og_image', asset('images/site/rasool-allah-hero-section-bg.jpg'))">
    <meta property="og:locale" content="{{ ['ar' => 'ar_AR', 'en' => 'en_US', 'fr' => 'fr_FR', 'tr' => 'tr_TR', 'ur' => 'ur_PK'][$locale] ?? 'ar_AR' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $seoDefaultTitle)">
    <meta name="twitter:description" content="@yield('meta_description', $seoDefaultDescription)">

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

    {{-- Prevent dark-mode flash before Alpine boots. The prototypes default to DARK
         (index-enhanced.html: darkMode = localStorage !== 'false') — match that. --}}
    <script>
        if (localStorage.getItem('darkMode') !== 'false') {
            document.documentElement.classList.add('dark');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="antialiased bg-[#F8F9FA] text-[#212529] min-h-screen flex flex-col">

    <x-site.header />

    <main class="flex-1">
        @yield('content')
    </main>

    <x-site.footer />

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

    @stack('scripts')
</body>
</html>
