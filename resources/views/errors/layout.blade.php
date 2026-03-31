<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f4f6fb;
            color: #1a2030;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        .error-wrap { max-width: 520px; width: 100%; }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -3px;
            color: #206bc4;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #1a2030;
        }
        .error-desc {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.4rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-primary { background: #206bc4; color: #fff; }
        .btn-primary:hover { background: #1a58a4; color: #fff; }
        .btn-outline { background: transparent; color: #6b7280; border-color: #d1d5db; }
        .btn-outline:hover { background: #f3f4f6; color: #374151; }
        .divider { width: 48px; height: 3px; background: #206bc4; border-radius: 2px; margin: 1.5rem auto; opacity: .35; }
        .app-brand {
            margin-bottom: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        footer {
            margin-top: 3rem;
            color: #9ca3af;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="error-wrap">
        <div class="app-brand">
            <img src="{{ asset('brand/logo-default.png') }}" alt="{{ config('app.name') }}" style="height:36px;width:auto;display:block;">
        </div>

        <div class="error-code">@yield('code')</div>
        <div class="divider"></div>
        <div class="error-title">@yield('title')</div>
        <p class="error-desc">@yield('description')</p>

        <div class="actions">@yield('actions')</div>
    </div>

    <footer>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</footer>
</body>
</html>
