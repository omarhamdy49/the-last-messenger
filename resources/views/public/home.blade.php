<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'The Last Messenger') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f7f8;
            --panel: rgba(255, 255, 255, 0.84);
            --text: #10212f;
            --muted: #5c7084;
            --line: rgba(16, 33, 47, 0.12);
            --brand-a: #0056b3;
            --brand-b: #218838;
            --accent: #64ccc5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Cairo", system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(0, 86, 179, 0.18), transparent 35%),
                radial-gradient(circle at bottom right, rgba(33, 136, 56, 0.15), transparent 32%),
                linear-gradient(135deg, #eef5ff 0%, #f8fbf8 48%, #eef8f6 100%);
            display: grid;
            place-items: center;
            padding: 32px;
        }

        .shell {
            width: min(1040px, 100%);
            border: 1px solid var(--line);
            border-radius: 28px;
            overflow: hidden;
            background: var(--panel);
            backdrop-filter: blur(14px);
            box-shadow: 0 30px 80px rgba(16, 33, 47, 0.12);
        }

        .hero {
            padding: 56px 56px 28px;
            background:
                linear-gradient(135deg, rgba(0, 86, 179, 0.96), rgba(33, 136, 56, 0.9)),
                linear-gradient(135deg, #0056b3, #218838);
            color: #fff;
        }

        .eyebrow {
            margin: 0 0 14px;
            font-size: 13px;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            opacity: 0.82;
        }

        h1 {
            margin: 0;
            font-size: clamp(2.3rem, 5vw, 4.4rem);
            line-height: 1.02;
            font-weight: 900;
            max-width: 10ch;
        }

        .subtitle {
            margin: 18px 0 0;
            max-width: 56ch;
            font-size: 1rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.86);
        }

        .body {
            padding: 32px 56px 56px;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 24px;
            background: rgba(255, 255, 255, 0.7);
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 1.1rem;
        }

        .panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.8;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 22px;
        }

        .button {
            appearance: none;
            border: 0;
            border-radius: 999px;
            padding: 14px 22px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
            box-shadow: 0 16px 32px rgba(0, 86, 179, 0.18);
        }

        .button-secondary {
            color: var(--text);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--line);
        }

        .list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .list li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            color: var(--muted);
            line-height: 1.65;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            margin-top: 9px;
            flex: 0 0 auto;
            background: linear-gradient(135deg, var(--brand-a), var(--accent));
        }

        @media (max-width: 840px) {
            .hero,
            .body {
                padding-inline: 24px;
            }

            .body {
                grid-template-columns: 1fr;
                padding-bottom: 24px;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
</head>
<body>
    <main class="shell">
        <section class="hero">
            <p class="eyebrow">Public Website</p>
            <h1>{{ config('app.name', 'The Last Messenger') }}</h1>
            <p class="subtitle">
                The root path is public. The admin workspace stays isolated under <strong>/su/portal</strong> and requires authenticated admin access.
            </p>
        </section>

        <section class="body">
            <article class="panel">
                <h2>Routing Split</h2>
                <p>
                    Public pages should live outside the admin prefix. Use the portal entry only for the dashboard and internal content management screens.
                </p>
                <div class="actions">
                    <a class="button button-primary" href="{{ route('login') }}">Admin Login</a>
                    <a class="button button-secondary" href="{{ route('portal.entry') }}">Admin Portal</a>
                </div>
            </article>

            <aside class="panel">
                <h2>Current Rules</h2>
                <ul class="list">
                    <li><span class="dot"></span><span><strong>/</strong> is public.</span></li>
                    <li><span class="dot"></span><span><strong>/su/portal</strong> is protected by the admin middleware stack.</span></li>
                    <li><span class="dot"></span><span><strong>/su/portal/v1/admin/*</strong> remains the authenticated admin workspace.</span></li>
                </ul>
            </aside>
        </section>
    </main>
</body>
</html>
