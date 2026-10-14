<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') | {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #22c55e;
            --accent-2: #0ea5e9;
            --border: rgba(148, 163, 184, 0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: radial-gradient(circle at 20% 20%, rgba(14, 165, 233, 0.12), transparent 25%),
                        radial-gradient(circle at 80% 10%, rgba(34, 197, 94, 0.14), transparent 22%),
                        linear-gradient(135deg, #0b1221 0%, #0f172a 100%);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        .card {
            width: min(680px, 100%);
            padding: 32px;
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border: 1px solid var(--border);
            box-shadow:
                0 20px 50px rgba(15, 23, 42, 0.45),
                0 1px 0 rgba(255, 255, 255, 0.03) inset;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: var(--muted);
            text-transform: uppercase;
        }

        .eyebrow .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.08);
        }

        .title {
            margin: 12px 0 10px 0;
            font-size: clamp(28px, 4vw, 36px);
            letter-spacing: -0.02em;
            font-weight: 800;
            color: #f1f5f9;
        }

        .message {
            margin: 0 0 20px 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.6;
        }

        .meta {
            padding: 12px 14px;
            margin-top: 16px;
            border-radius: 12px;
            background: rgba(148, 163, 184, 0.08);
            color: #cbd5e1;
            font-size: 14px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 12px;
            border: 1px solid var(--border);
            color: #e2e8f0;
            background: rgba(255, 255, 255, 0.02);
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: transform 150ms ease, box-shadow 150ms ease, border-color 150ms ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: rgba(148, 163, 184, 0.35);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .btn.primary {
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            color: #0b1221;
            border: none;
        }

        .btn.secondary {
            color: #cbd5e1;
        }

        .hint {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            color: #0b1221;
            background: linear-gradient(120deg, var(--accent), var(--accent-2));
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.35);
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="eyebrow">
            <span class="dot"></span>
            <span>Error @yield('code')</span>
            <span class="badge">{{ config('app.name') }}</span>
        </div>

        <h1 class="title">@yield('title')</h1>
        <p class="message">@yield('message')</p>

        <div class="actions">
            @yield('actions')
        </div>

        <div class="meta">
            Este mensaje es informativo y no expone datos técnicos. Si el problema persiste, contacta al administrador del sistema.
        </div>
    </main>
</body>
</html>
