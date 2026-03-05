<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .mobile-nav-toggle {
            width: 2rem;
            height: 2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-backdrop {
            display: none;
        }
        @media (max-width: 991.98px) {
            .page-wrapper > .navbar {
                position: sticky;
                top: 0;
                z-index: 1030;
                background: var(--tblr-bg-surface, #fff);
                border-bottom: 1px solid rgba(74, 96, 126, 0.14);
                min-height: 3.25rem;
            }
            .page-body {
                padding-top: .7rem;
            }
            .page-body > .container-xl {
                padding-left: .75rem;
                padding-right: .75rem;
            }
            .page > aside.navbar-vertical {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(78vw, 300px);
                z-index: 1045;
                transform: translateX(-100%);
                transition: transform .2s ease-in-out;
                background: var(--tblr-bg-surface, #fff);
                overflow-y: auto;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
            }
            .page > aside.navbar-vertical .container-fluid {
                padding-left: .7rem;
                padding-right: .7rem;
            }
            .page > aside.navbar-vertical .navbar-nav {
                padding-top: .15rem !important;
            }
            .page > aside.navbar-vertical .nav-item {
                margin-top: .15rem;
            }
            .page > aside.navbar-vertical .nav-link {
                padding-left: .7rem !important;
                padding-right: .7rem !important;
                min-height: 2.2rem;
                gap: .55rem !important;
                align-items: center !important;
                border-radius: .55rem !important;
                white-space: normal;
                line-height: 1.2;
            }
            .page > aside.navbar-vertical .nav-link .nav-link-icon,
            .page > aside.navbar-vertical .nav-link .icon {
                width: 1.1rem;
                min-width: 1.1rem;
                margin-top: 0;
            }
            .page > aside.navbar-vertical .nav-link .nav-link-title {
                display: block;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
            .page > aside.navbar-vertical .badge {
                margin-left: auto !important;
                font-size: .68rem;
                line-height: 1.1;
            }
            .page > aside.navbar-vertical .dropdown-menu {
                margin-left: 0 !important;
                padding: .15rem 0 .2rem .45rem !important;
            }
            .page > aside.navbar-vertical .dropdown-item {
                border-radius: .45rem;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
                padding-top: .36rem;
                padding-bottom: .36rem;
            }
            .page > aside.navbar-vertical .text-uppercase {
                font-size: .68rem !important;
                letter-spacing: .05em;
            }
            .page-wrapper {
                min-width: 0;
                width: 100%;
            }
            body.sidebar-open .page > aside.navbar-vertical {
                transform: translateX(0);
            }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.36);
                z-index: 1040;
            }
            body.sidebar-open .sidebar-backdrop {
                display: block;
            }
            body.sidebar-open {
                overflow: hidden;
            }
        }
    </style>
</head>
<body class="bg-body">
    <div class="page">
        @include('shared.sidebar')
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

        <div class="page-wrapper">
            <header class="navbar navbar-expand-md">
                <div class="container-fluid">
                    <div class="d-flex align-items-center gap-2 d-lg-none">
                        <button type="button" class="btn btn-outline-secondary mobile-nav-toggle" id="mobile-nav-toggle" aria-label="Open menu">
                            <i class="ti ti-menu-2" aria-hidden="true"></i>
                        </button>
                    </div>
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

        // Mobile sidebar toggle
        const sidebarToggleBtn = document.getElementById('mobile-nav-toggle');
        const sidebarCloseBtn = document.getElementById('mobile-nav-close');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');
        const sidebarEl = document.querySelector('.page > aside.navbar-vertical');
        const closeSidebar = () => document.body.classList.remove('sidebar-open');
        const openSidebar = () => document.body.classList.add('sidebar-open');

        sidebarToggleBtn?.addEventListener('click', () => {
            if (document.body.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        sidebarCloseBtn?.addEventListener('click', closeSidebar);
        sidebarBackdrop?.addEventListener('click', closeSidebar);
        sidebarEl?.querySelectorAll('a.nav-link, a.dropdown-item').forEach((a) => {
            a.addEventListener('click', () => {
                if (window.matchMedia('(max-width: 991.98px)').matches) {
                    closeSidebar();
                }
            });
        });
        window.addEventListener('resize', () => {
            if (!window.matchMedia('(max-width: 991.98px)').matches) {
                closeSidebar();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
