<!doctype html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} · {{ $title }}</title>
    <style nonce="{{ $csp_nonce ?? '' }}">
        :root {
            color-scheme: light dark;
            font-family: "DM Sans", ui-sans-serif, system-ui, sans-serif;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            --error-accent: #64748b;
            --error-glow: rgb(100 116 139 / 14%);
        }
        :root[data-accent="danger"] { --error-accent: #ef4444; --error-glow: rgb(239 68 68 / 16%); }
        :root[data-accent="info"] { --error-accent: #3b82f6; --error-glow: rgb(59 130 246 / 16%); }
        :root[data-accent="success"] { --error-accent: #22c55e; --error-glow: rgb(34 197 94 / 16%); }
        :root[data-accent="warning"] { --error-accent: #f59e0b; --error-glow: rgb(245 158 11 / 16%); }
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
            place-items: center;
            color: #0f172a;
            background: radial-gradient(ellipse at 50% 0, var(--error-glow), transparent 55%), #f8fafc;
        }
        main {
            width: min(100%, 38rem);
            padding: clamp(2rem, 6vw, 3.5rem);
            border: 1px solid rgb(148 163 184 / 22%);
            border-radius: 1rem;
            background: rgb(255 255 255 / 88%);
            box-shadow: 0 24px 80px rgb(15 23 42 / 9%);
            text-align: center;
            backdrop-filter: blur(16px);
        }
        .error-code {
            margin: 0 0 1.25rem;
            color: var(--error-accent);
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .16em;
        }
        h1 { margin: 0; font-size: clamp(1.8rem, 5vw, 2.5rem); letter-spacing: -.04em; }
        .error-description { margin: 1rem 0 2rem; color: #64748b; line-height: 1.7; }
        .home-link {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            padding: .7rem 1.2rem;
            border-radius: .6rem;
            color: white;
            background: var(--error-accent);
            font-size: .9rem;
            font-weight: 650;
            text-decoration: none;
            transition: filter .15s ease, transform .15s ease;
        }
        .home-link:hover { filter: brightness(.92); transform: translateY(-1px); }
        @media (prefers-color-scheme: dark) {
            body { color: #f8fafc; background-color: #09090b; }
            main { border-color: rgb(255 255 255 / 9%); background: rgb(24 24 27 / 88%); box-shadow: 0 24px 80px rgb(0 0 0 / 35%); }
            .error-description { color: #a1a1aa; }
        }
        @media (max-width: 40rem) { body { padding: 1rem; } }
    </style>
</head>
<body>
    <main data-accent="{{ $accent }}">
        <p class="error-code">ERROR {{ $status }}</p>
        <h1>{{ $title }}</h1>
        <p class="error-description">{{ $description }}</p>
        <a class="home-link" href="/">{{ ['en' => 'Go to home', 'fr' => 'Retour à l’accueil', 'es' => 'Ir al inicio', 'ar' => 'العودة للرئيسية'][$locale] ?? 'Go to home' }}</a>

        @if ($exception_message !== null)
            <section aria-labelledby="exception-heading">
                <h2 id="exception-heading">Exception details</h2>
                <pre>{{ $exception_message }}</pre>
            </section>
        @endif
    </main>
</body>
</html>
