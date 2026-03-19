<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#206bc4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MyApp">
    <title>MyApp</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/pwa-icon-192.svg">
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <style>
        .table-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }
        .table-actions form {
            display: inline-block;
            margin: 0;
        }
        .table-actions .btn.btn-icon {
            width: 2rem;
            height: 2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .table-actions .btn.btn-icon .icon,
        .table-actions .btn.btn-icon .ti {
            width: 1rem;
            height: 1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .sidebar-brand {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: .01em;
            color: var(--tblr-body-color, #1f2d3d);
        }
        .sidebar-brand-wrap {
            margin-bottom: .35rem;
        }
        .module-svg-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .module-svg-icon svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .mobile-nav-toggle {
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-topbar {
            backdrop-filter: blur(14px);
            background: color-mix(in srgb, var(--tblr-bg-surface, #fff) 88%, transparent);
        }
        .desktop-topbar {
            min-height: 3.7rem;
            border-bottom: 1px solid rgba(74, 96, 126, 0.12);
            background: var(--tblr-bg-surface, #fff);
        }
        .mobile-topbar-brand {
            font-size: .95rem;
            font-weight: 700;
            letter-spacing: .01em;
            color: inherit;
            text-decoration: none;
        }
        .desktop-topbar-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .01em;
            color: var(--tblr-body-color, #1f2d3d);
        }
        .app-shell {
            min-height: 100vh;
        }
        .page-body {
            margin-top: .9rem;
            margin-bottom: 1rem;
            padding-top: 0;
            padding-bottom: 0;
        }
        .page-body > .container-xl {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .page > .navbar-vertical {
            box-shadow: none;
        }
        @media (min-width: 992px) {
            .page > .navbar-vertical #sidebar-menu.collapse:not(.show) {
                display: block !important;
            }
        }
        @media (max-width: 991.98px) {
            .desktop-topbar {
                display: none;
            }
            .mobile-topbar {
                position: sticky;
                top: 0;
                z-index: 1030;
                border-bottom: 1px solid rgba(74, 96, 126, 0.14);
                min-height: 3.5rem;
            }
            .page-body {
                margin-top: .65rem;
            }
            .page-body > .container-xl {
                padding-left: .75rem;
                padding-right: .75rem;
            }
            .page > .navbar-vertical {
                position: static;
                width: 100%;
                min-height: auto;
                border-right: 0 !important;
                border-bottom: 1px solid rgba(74, 96, 126, 0.12);
            }
            .page > .navbar-vertical .container-fluid {
                padding-left: .7rem;
                padding-right: .7rem;
            }
            .page > .navbar-vertical .navbar-collapse {
                padding-bottom: .75rem;
            }
            #sidebar-menu .navbar-nav {
                padding-top: .15rem !important;
            }
            #sidebar-menu .nav-item {
                margin-top: .15rem;
            }
            #sidebar-menu .nav-link {
                padding-left: .7rem !important;
                padding-right: .7rem !important;
                min-height: 2.45rem;
                gap: .55rem !important;
                align-items: center !important;
                border-radius: .55rem !important;
                white-space: normal;
                line-height: 1.2;
            }
            #sidebar-menu .nav-link .nav-link-icon,
            #sidebar-menu .nav-link .icon {
                width: 1.1rem;
                min-width: 1.1rem;
                margin-top: 0;
            }
            #sidebar-menu .nav-link .nav-link-title {
                display: block;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
            #sidebar-menu .badge {
                margin-left: auto !important;
                font-size: .68rem;
                line-height: 1.1;
            }
            #sidebar-menu .dropdown-menu {
                margin-left: 0 !important;
                padding: .15rem 0 .2rem .45rem !important;
            }
            #sidebar-menu .dropdown-item {
                border-radius: .45rem;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
                padding-top: .36rem;
                padding-bottom: .36rem;
            }
            #sidebar-menu .text-uppercase {
                font-size: .68rem !important;
                letter-spacing: .05em;
            }
            .mobile-topbar .btn-outline-secondary {
                --tblr-btn-bg: transparent;
                --tblr-btn-border-color: rgba(74, 96, 126, 0.2);
                --tblr-btn-hover-bg: rgba(var(--tblr-primary-rgb), 0.08);
            }
        }
        @media (min-width: 992px) {
            .mobile-topbar {
                display: none;
            }
            .desktop-topbar {
                display: flex;
            }
        }
    </style>
</head>
<body class="bg-body">
    <div class="page app-shell">
        @include('shared.sidebar')

        <div class="page-wrapper">
            <header class="navbar desktop-topbar d-none d-lg-flex">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between w-100 gap-3">
                        <div class="desktop-topbar-title">MyApp</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            </header>
            <header class="navbar navbar-expand-md mobile-topbar d-lg-none">
                <div class="container-fluid">
                    <div class="d-flex align-items-center gap-3 w-100">
                        <button
                            type="button"
                            class="btn btn-outline-secondary mobile-nav-toggle"
                            data-bs-toggle="collapse"
                            data-bs-target="#sidebar-menu"
                            aria-controls="sidebar-menu"
                            aria-label="Open menu"
                        >
                            <i class="ti ti-menu-2" aria-hidden="true"></i>
                        </button>
                        <a href="{{ route('dashboard') }}" class="mobile-topbar-brand">MyApp</a>
                        <div class="ms-auto">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary" type="submit">Logout</button>
                            </form>
                        </div>
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
        window.MyAppNotifier = (() => {
            let swRegistrationPromise = null;

            const supportsNotifications = () => ('Notification' in window);
            const supportsServiceWorker = () => ('serviceWorker' in navigator);

            const registerServiceWorker = () => {
                if (!supportsServiceWorker()) return Promise.resolve(null);
                if (!swRegistrationPromise) {
                    swRegistrationPromise = navigator.serviceWorker.register('/sw.js')
                        .then(() => navigator.serviceWorker.ready)
                        .catch(() => null);
                }
                return swRegistrationPromise;
            };

            const permission = () => supportsNotifications() ? Notification.permission : 'denied';

            const ensurePermission = async (prompt = false) => {
                if (!supportsNotifications()) return false;
                if (Notification.permission === 'granted') return true;
                if (Notification.permission === 'denied') return false;
                if (!prompt) return false;
                try {
                    const next = await Notification.requestPermission();
                    return next === 'granted';
                } catch (_) {
                    return false;
                }
            };

            const show = async (title, body, url, tag = 'myapp-notify') => {
                const granted = await ensurePermission(false);
                if (!granted) return false;

                const notificationOptions = {
                    body: (body || '').toString().slice(0, 180),
                    tag,
                    data: { url: url || window.location.href },
                    icon: '/pwa-icon-192.svg',
                    badge: '/pwa-icon-192.svg',
                };

                const reg = await registerServiceWorker();
                if (reg && typeof reg.showNotification === 'function') {
                    reg.showNotification((title || 'MyApp').toString(), notificationOptions);
                    return true;
                }

                try {
                    const fallback = new Notification((title || 'MyApp').toString(), notificationOptions);
                    fallback.onclick = () => {
                        window.focus();
                        if (url) window.location.href = url;
                    };
                    return true;
                } catch (_) {
                    return false;
                }
            };

            registerServiceWorker();

            return {
                registerServiceWorker,
                supportsNotifications,
                supportsServiceWorker,
                permission,
                ensurePermission,
                show,
            };
        })();

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

        const sidebarEl = document.getElementById('sidebar-menu');
        sidebarEl?.querySelectorAll('a.nav-link, a.dropdown-item').forEach((a) => {
            a.addEventListener('click', () => {
                if (!window.matchMedia('(max-width: 991.98px)').matches) {
                    return;
                }

                const sidebarInstance = window.bootstrap?.Collapse.getInstance(sidebarEl);
                sidebarInstance?.hide();
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
