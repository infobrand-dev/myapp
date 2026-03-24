<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akun Dinonaktifkan — {{ config('app.name') }}</title>
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
        .wrap { max-width: 520px; width: 100%; }
        .app-brand { margin-bottom: 2.5rem; font-size: 1rem; font-weight: 700; color: #206bc4; letter-spacing: 0.01em; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        .title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: #1a2030; }
        .desc { color: #6b7280; font-size: 0.95rem; line-height: 1.7; margin-bottom: 2rem; }
        .notice {
            background: #fef9c3;
            border: 1.5px solid #fde047;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
            color: #713f12;
            text-align: left;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .notice strong { display: block; margin-bottom: 0.25rem; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.55rem 1.4rem; border-radius: 6px; font-size: 0.875rem;
            font-weight: 500; text-decoration: none; border: 1.5px solid transparent;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-primary { background: #206bc4; color: #fff; }
        .btn-primary:hover { background: #1a58a4; }
        footer { margin-top: 3rem; color: #9ca3af; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="app-brand">{{ config('app.name') }}</div>

        <div class="icon">⚠️</div>
        <div class="title">Akun Anda Dinonaktifkan</div>
        <p class="desc">
            Akses ke workspace ini telah dinonaktifkan sementara oleh administrator.
            Semua data Anda tetap aman dan tersimpan.
        </p>

        <div class="notice">
            <strong>Apa yang perlu dilakukan?</strong>
            Hubungi tim support untuk mengaktifkan kembali akun Anda atau
            menyelesaikan tagihan yang mungkin tertunda.
        </div>

        <a href="mailto:{{ config('mail.from.address', 'support@' . config('multitenancy.saas_domain', 'example.com')) }}"
           class="btn btn-primary">
            Hubungi Support
        </a>
    </div>

    <footer>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</footer>
</body>
</html>
