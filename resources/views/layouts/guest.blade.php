@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $siteName = $siteName ?? __('site.site_name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}"
      dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
      :class="$store.theme.dark ? 'dark' : ''"
      class="antialiased scroll-smooth overflow-x-clip">
<head>
    @include('partials.google-analytics')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $siteName)</title>
    {{-- Auth screens shouldn't be indexed. --}}
    <meta name="robots" content="noindex, follow">

    {{-- Favicon — Seal of the Prophet ﷺ --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0056b3">

    @include('partials.theme-preference')
    <script>window.applyDarkMode();</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh flex flex-col">

    {{-- Full living-geometry gradient background (fixed behind everything) --}}
    <x-site.auth-bg />

    {{-- Minimal chrome: brand (returns home) + theme toggle --}}
    <header class="relative z-10" x-data="{}">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-20">
            <a href="{{ route('public.home') }}"
               class="flex items-center gap-3 text-white hover:opacity-90 transition-opacity"
               aria-label="{{ $siteName }}">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm border border-white/25 shrink-0">
                    <x-site.brand-icon size="w-7 h-7" :eager="true" />
                </span>
                <span class="text-xl font-bold hidden sm:block">{{ $siteName }}</span>
            </a>

            <button @click="$store.theme.toggle()"
                    class="p-2.5 rounded-full text-white hover:bg-white/15 transition-colors"
                    aria-label="{{ __('site.toggle_theme') }}">
                <i x-show="!$store.theme.dark" data-lucide="moon" class="w-5 h-5"></i>
                <i x-show="$store.theme.dark" data-lucide="sun" class="w-5 h-5" x-cloak></i>
            </button>
        </div>
    </header>

    <main class="relative z-10 flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
        @yield('content')
    </main>

    {{-- Global toast region (login/register flash messages) --}}
    <div class="fixed bottom-4 end-4 z-[100] space-y-2" aria-live="polite">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div x-transition class="card-premium flex items-center gap-3 px-4 py-3 min-w-[16rem] shadow-2xl">
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

    @if (session('toast'))
        <script>document.addEventListener('alpine:init', () => window.toast(@json(session('toast')), 'success'));</script>
    @endif
    @if (session('error'))
        <script>document.addEventListener('alpine:init', () => window.toast(@json(session('error')), 'error'));</script>
    @endif

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => { if (typeof window.renderIcons === 'function') window.renderIcons(); });
    </script>
</body>
</html>
