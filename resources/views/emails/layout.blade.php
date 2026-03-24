<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('subject', config('app.name'))</title>
    <style>
        body, html { margin: 0; padding: 0; background: #f4f6fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        .wrapper { width: 100%; padding: 40px 16px; background: #f4f6fb; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .header { background: #206bc4; padding: 28px 40px; text-align: center; }
        .header .app-name { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.02em; text-decoration: none; }
        .body { padding: 40px; color: #1a2030; font-size: 15px; line-height: 1.7; }
        .body h1 { font-size: 20px; font-weight: 700; margin: 0 0 16px; color: #1a2030; }
        .body p { margin: 0 0 16px; color: #374151; }
        .body p:last-child { margin-bottom: 0; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: #206bc4;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
        }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .info-box { background: #f8fafc; border-left: 3px solid #206bc4; border-radius: 0 6px 6px 0; padding: 16px 20px; margin: 20px 0; font-size: 14px; color: #374151; }
        .info-box strong { display: block; margin-bottom: 4px; color: #1a2030; }
        .footer { background: #f4f6fb; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer a { color: #6b7280; text-decoration: underline; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <span class="app-name">{{ config('app.name') }}</span>
        </div>

        <div class="body">
            @yield('content')
        </div>

        <div class="footer">
            <p style="margin:0 0 4px;">&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</p>
            <p style="margin:0;">Email ini dikirim secara otomatis. Mohon jangan membalas email ini.</p>
        </div>
    </div>
</div>
</body>
</html>
