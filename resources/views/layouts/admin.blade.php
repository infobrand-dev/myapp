<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyApp</title>
    <script>
        // Early apply saved theme to avoid FOUC
        (function() {
            const mode = localStorage.getItem('theme-mode') || 'light';
            const color = localStorage.getItem('theme-color');
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', mode);
            if (color) {
                root.style.setProperty('--tblr-primary', color);
                const rgb = color.match(/[0-9a-f]{2}/gi)?.map(h => parseInt(h, 16)) ?? [32,107,196];
                root.style.setProperty('--tblr-primary-rgb', rgb.join(','));
            }
        })();
    </script>
    <link id="dynamic-favicon" rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='%2314b8a6'/></svg>">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="bg-body">
    <div class="page">
        @include('shared.sidebar')

        <div class="page-wrapper">
            <header class="navbar navbar-expand-md">
                <div class="container-fluid">
                    <div class="d-flex align-items-center gap-3 ms-auto">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="page-body">
                <div class="container-xl">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    <script src="{{ mix('js/app.js') }}" defer></script>
    <script>
        // Dynamic favicon: green by default, turns red if tab hidden for 30 minutes.
        const faviconEl = document.getElementById('dynamic-favicon');
        const faviconGreen = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='%2314b8a6'/></svg>";
        const faviconRed = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='%23ef4444'/></svg>";
        let hideTimer = null;
        const THRESHOLD = 30 * 60 * 1000; // 30 minutes

        function setFavicon(uri) {
            if (faviconEl) faviconEl.setAttribute('href', uri);
        }
        function handleVisibility() {
            if (document.hidden) {
                hideTimer = setTimeout(() => setFavicon(faviconRed), THRESHOLD);
            } else {
                if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                setFavicon(faviconGreen);
            }
        }
        document.addEventListener('visibilitychange', handleVisibility);
        handleVisibility();
    </script>
    @stack('scripts')
</body>
</html>
