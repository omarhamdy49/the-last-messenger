{{--
    Shared shell for every HTTP error page.

    Deliberately self-contained: no @vite, no view composers, no site header/footer,
    no DB reads. A 500 page that depends on the app layout renders a second 500 —
    these must survive a missing build manifest, a dead database and a broken cache.

    Children provide: @section('code'), @section('heading'), @section('message'),
    and optionally @section('actions_extra') / @section('extras').
--}}
@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $heading = trim($__env->yieldContent('heading'));
    $code = trim($__env->yieldContent('code'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.theme-preference')
    <script>window.applyDarkMode();</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $heading }} — {{ __('site.site_name') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="theme-color" content="#0056b3">
    <style>
        /* Fonts are declared locally: app.css is not loaded on this page. */
        @font-face {
            font-family: "Readex Pro";
            font-style: normal;
            font-weight: 160 700;
            font-display: swap;
            src: url("/fonts/readex-pro-arabic.woff2") format("woff2");
            unicode-range: U+0600-06FF, U+0750-077F, U+0870-088E, U+FB50-FDFF, U+FE70-FE74, U+FE76-FEFC;
        }
        @font-face {
            font-family: "Readex Pro";
            font-style: normal;
            font-weight: 160 700;
            font-display: swap;
            src: url("/fonts/readex-pro-latin.woff2") format("woff2");
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+2000-206F, U+20AC, U+2122;
        }

        :root {
            --bg: #f4f6fa;
            --surface: rgba(255, 255, 255, .78);
            --surface-solid: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --border: rgba(15, 23, 42, .10);
            --ring: #0056b3;
            --grad-a: #0056b3;
            --grad-b: #218838;
            --glow: rgba(0, 86, 179, .18);
            --pattern: rgba(0, 86, 179, .10);
            --ghost-bg: rgba(15, 23, 42, .04);
            --shadow: 0 24px 60px -24px rgba(15, 23, 42, .28);
            color-scheme: light;
        }

        html.dark {
            --bg: #080d1a;
            --surface: rgba(22, 33, 62, .62);
            --surface-solid: #16213e;
            --text: #f1f5f9;
            --muted: #a9b6cc;
            --border: rgba(255, 255, 255, .12);
            --ring: #64CCC5;
            --grad-a: #4d94ff;
            --grad-b: #64CCC5;
            --glow: rgba(100, 204, 197, .22);
            --pattern: rgba(100, 204, 197, .14);
            --ghost-bg: rgba(255, 255, 255, .06);
            --shadow: 0 30px 70px -30px rgba(0, 0, 0, .75);
            color-scheme: dark;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            color: var(--text);
            font-family: "Readex Pro", "Cairo", system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Ambient mesh + geometric watermark. Purely decorative → aria-hidden. */
        .backdrop {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(60rem 40rem at 15% 12%, var(--glow), transparent 60%),
                radial-gradient(50rem 36rem at 85% 82%, var(--glow), transparent 62%);
        }

        .backdrop::after {
            content: "";
            position: absolute;
            inset: -10%;
            opacity: .16;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%230056b3' stroke-width='1'%3E%3Cpath d='M60 6 78 24 102 24 102 48 120 66 102 84 102 108 78 108 60 126 42 108 18 108 18 84 0 66 18 48 18 24 42 24Z'/%3E%3Cpath d='M60 30 76 46 76 70 60 86 44 70 44 46Z'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 120px 120px;
            /* The tile is authored in brand blue; mask it so it inherits the token. */
            -webkit-mask-image: radial-gradient(70rem 50rem at 50% 40%, #000 0%, transparent 75%);
            mask-image: radial-gradient(70rem 50rem at 50% 40%, #000 0%, transparent 75%);
            filter: none;
        }

        html.dark .backdrop::after { opacity: .22; }

        .page {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 64rem;
            margin: 0 auto;
            padding: clamp(1.25rem, 4vw, 2.5rem);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            align-self: flex-start;
            text-decoration: none;
            color: inherit;
            padding: .5rem;
            margin: -.5rem;
            border-radius: 1rem;
            min-height: 44px;
        }

        .brand img { width: 44px; height: 44px; border-radius: 12px; }

        .brand span {
            font-weight: 700;
            font-size: 1.05rem;
            background: linear-gradient(135deg, var(--grad-a), var(--grad-b));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: clamp(1.5rem, 4vw, 2.5rem) 0;
        }

        .code-wrap { position: relative; display: inline-grid; place-items: center; }

        /* Slowly rotating eight-point ring behind the numeral. */
        .ring {
            position: absolute;
            width: clamp(14rem, 40vw, 19rem);
            height: clamp(14rem, 40vw, 19rem);
            color: var(--pattern);
            animation: spin 60s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .code {
            position: relative;
            font-size: clamp(5rem, 17vw, 8.5rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.04em;
            font-variant-numeric: tabular-nums;
            direction: ltr;
            background: linear-gradient(135deg, var(--grad-a), var(--grad-b));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .badge {
            display: inline-block;
            margin-bottom: .5rem;
            padding: .35rem .85rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface);
            color: var(--muted);
            font-size: .8125rem;
            font-weight: 600;
            letter-spacing: .02em;
        }

        h1 {
            margin: .75rem 0 .5rem;
            font-size: clamp(1.5rem, 4.5vw, 2.25rem);
            font-weight: 700;
            line-height: 1.3;
        }

        .message {
            margin: 0 auto;
            max-width: 42ch;
            color: var(--muted);
            font-size: 1.0625rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: center;
            margin-top: 1.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 48px;
            padding: 0 1.35rem;
            border: 1px solid transparent;
            border-radius: 999px;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s cubic-bezier(.4, 0, .2, 1), box-shadow .2s, background-color .2s;
        }

        .btn svg { width: 18px; height: 18px; flex: none; }

        /* Directional glyphs mirror with the writing direction. */
        [dir="rtl"] .icon-flip { transform: scaleX(-1); }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #0056b3, #218838);
            box-shadow: 0 12px 28px -12px rgba(0, 86, 179, .7);
        }

        .btn-ghost {
            color: var(--text);
            background: var(--ghost-bg);
            border-color: var(--border);
        }

        .btn:hover { transform: translateY(-2px); }
        .btn:active { transform: translateY(0) scale(.98); }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible {
            outline: 3px solid var(--ring);
            outline-offset: 3px;
        }

        /* Search + section links (404 only) */
        .panel {
            width: 100%;
            max-width: 36rem;
            margin-top: 2rem;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            background: var(--surface);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow);
        }

        .search { display: flex; gap: .5rem; }

        .search input {
            flex: 1;
            min-width: 0;
            min-height: 48px;
            padding: 0 1rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface-solid);
            color: var(--text);
            font: inherit;
        }

        .search input::placeholder { color: var(--muted); }

        .links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .5rem;
            margin-top: .75rem;
        }

        .links a {
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            padding: 0 1rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--text);
            text-decoration: none;
            font-size: .9375rem;
            font-weight: 600;
            transition: background-color .2s, border-color .2s;
        }

        .links a:hover { background: var(--ghost-bg); border-color: var(--ring); }

        .label { color: var(--muted); font-size: .875rem; font-weight: 600; }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            clip-path: inset(50%);
            white-space: nowrap;
        }

        footer {
            position: relative;
            z-index: 1;
            padding: 1.25rem;
            text-align: center;
            color: var(--muted);
            font-size: .8125rem;
        }

        /* Narrow phones: a side-by-side field + button squeezes the placeholder
           to a few characters, so stack them. */
        @media (max-width: 30rem) {
            .search { flex-direction: column; }
            .search .btn { width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
            .btn:hover { transform: none; }
        }
    </style>
</head>
<body>
    <div class="backdrop" aria-hidden="true"></div>

    <div class="page">
        <a class="brand" href="{{ url('/') }}">
            <img src="/images/site/rasool-allah-seal.webp" alt="" width="44" height="44">
            <span>{{ __('site.site_name') }}</span>
        </a>

        <main>
            <div class="code-wrap">
                <svg class="ring" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                    <circle cx="100" cy="100" r="92" />
                    <circle cx="100" cy="100" r="74" />
                    <path d="M100 18 145 55 182 100 145 145 100 182 55 145 18 100 55 55Z" />
                    <path d="M100 26 174 100 100 174 26 100Z" />
                    <path d="M46 46 154 46 154 154 46 154Z" />
                </svg>
                <div class="code">@yield('code')</div>
            </div>

            <p class="badge">{{ __('errors.badge') }} {{ $code }}</p>

            <h1>@yield('heading')</h1>
            <p class="message">@yield('message')</p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>
                    </svg>
                    {{ __('errors.back_home') }}
                </a>
                @yield('actions_extra')
                <button type="button" class="btn btn-ghost" onclick="history.back()">
                    <svg class="icon-flip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                    </svg>
                    {{ __('errors.go_back') }}
                </button>
            </div>

            @yield('extras')
        </main>
    </div>

    <footer>&copy; {{ date('Y') }} {{ __('site.site_name') }}</footer>
</body>
</html>
