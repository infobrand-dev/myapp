<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2D47CC">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/pwa-icon-192.svg">
    <link id="dynamic-favicon" rel="icon" type="image/svg+xml" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='%232D47CC'/></svg>">
    <script>
        // Early apply saved theme to avoid FOUC
        (function() {
            const mode = localStorage.getItem('theme-mode') || 'light';
            const color = localStorage.getItem('theme-color');
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', mode);
            if (color) {
                root.style.setProperty('--tblr-primary', color);
                const rgb = color.match(/[0-9a-f]{2}/gi)?.map(h => parseInt(h, 16)) ?? [45,71,204];
                root.style.setProperty('--tblr-primary-rgb', rgb.join(','));
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    @stack('styles')
</head>
<body class="bg-body">
    <a class="skip-link" href="#main-content">Skip to content</a>

    <div class="page app-shell">
        @include('shared.sidebar')

        <div class="page-wrapper">
            {{-- Desktop topbar --}}
            <header class="navbar desktop-topbar d-none d-lg-flex" role="banner">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between w-100 gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="desktop-topbar-title">{{ config('app.name') }}</span>
                            @include('shared.topbar-context-switcher', ['selectorId' => 'desktop-topbar'])
                        </div>

                        {{-- User dropdown --}}
                        <div class="dropdown">
                            <button
                                type="button"
                                class="topbar-user-toggle"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="User menu"
                            >
                                <span class="topbar-user-avatar">
                                    {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 2)) }}
                                </span>
                                <span class="topbar-user-name d-none d-xl-block">{{ auth()->user()->name ?? '' }}</span>
                                <i class="ti ti-chevron-down" style="font-size: 0.75rem; opacity: 0.6;" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 12rem;">
                                <div class="dropdown-header px-3 py-2">
                                    <div class="fw-semibold text-truncate" style="max-width: 11rem;">{{ auth()->user()->name ?? '' }}</div>
                                    <div class="small text-secondary text-truncate" style="max-width: 11rem;">{{ auth()->user()->email ?? '' }}</div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="ti ti-user me-2 opacity-60" aria-hidden="true"></i>Profile
                                </a>
                                @can('settings.view')
                                <a class="dropdown-item" href="{{ route('settings.general') }}">
                                    <i class="ti ti-settings me-2 opacity-60" aria-hidden="true"></i>Settings
                                </a>
                                @endcan
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="ti ti-logout me-2 opacity-70" aria-hidden="true"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Mobile topbar --}}
            <header class="navbar navbar-expand-md mobile-topbar d-lg-none" role="banner">
                <div class="container-fluid">
                    <div class="d-flex align-items-center gap-2 w-100">
                        <button
                            type="button"
                            class="btn btn-outline-secondary mobile-nav-toggle"
                            data-bs-toggle="collapse"
                            data-bs-target="#sidebar-menu"
                            aria-controls="sidebar-menu"
                            aria-label="Toggle menu"
                            aria-expanded="false"
                        >
                            <i class="ti ti-menu-2" aria-hidden="true"></i>
                        </button>

                        <a href="{{ route('dashboard') }}" class="mobile-topbar-brand">
                            {{ config('app.name') }}
                        </a>

                        <div class="d-flex">
                            @include('shared.topbar-context-switcher', ['selectorId' => 'mobile-topbar'])
                        </div>

                        <div class="ms-auto">
                            <div class="dropdown">
                                <button
                                    type="button"
                                    class="topbar-user-toggle"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    aria-label="User menu"
                                >
                                    <span class="topbar-user-avatar">
                                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 2)) }}
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 12rem;">
                                    <div class="dropdown-header px-3 py-2">
                                        <div class="fw-semibold text-truncate" style="max-width: 11rem;">{{ auth()->user()->name ?? '' }}</div>
                                        <div class="small text-secondary text-truncate" style="max-width: 11rem;">{{ auth()->user()->email ?? '' }}</div>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        <i class="ti ti-user me-2 opacity-60" aria-hidden="true"></i>Profile
                                    </a>
                                    @can('settings.view')
                                    <a class="dropdown-item" href="{{ route('settings.general') }}">
                                        <i class="ti ti-settings me-2 opacity-60" aria-hidden="true"></i>Settings
                                    </a>
                                    @endcan
                                    <div class="dropdown-divider"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="ti ti-logout me-2 opacity-70" aria-hidden="true"></i>Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page-body" id="main-content" tabindex="-1">
                <div class="container-xl">
                    {{-- Flash messages --}}
                    @if(session('status') || session('success'))
                        <div class="alert alert-success alert-dismissible mb-3" role="alert">
                            <i class="ti ti-circle-check me-2" aria-hidden="true"></i>
                            {{ session('status') ?? session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                            <i class="ti ti-alert-circle me-2" aria-hidden="true"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible mb-3" role="alert">
                            <i class="ti ti-alert-triangle me-2" aria-hidden="true"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible mb-3" role="alert">
                            <i class="ti ti-info-circle me-2" aria-hidden="true"></i>
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                            <i class="ti ti-alert-circle me-2" aria-hidden="true"></i>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script src="{{ mix('js/app.js') }}" defer></script>
    <script>
        // ── MyApp Push Notifications ─────────────────────────────
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

                const opts = {
                    body: (body || '').toString().slice(0, 180),
                    tag,
                    data: { url: url || window.location.href },
                    icon: '/pwa-icon-192.svg',
                    badge: '/pwa-icon-192.svg',
                };

                const reg = await registerServiceWorker();
                if (reg && typeof reg.showNotification === 'function') {
                    reg.showNotification((title || '{{ config('app.name') }}').toString(), opts);
                    return true;
                }
                try {
                    const fallback = new Notification((title || '{{ config('app.name') }}').toString(), opts);
                    fallback.onclick = () => { window.focus(); if (url) window.location.href = url; };
                    return true;
                } catch (_) {
                    return false;
                }
            };

            registerServiceWorker();
            return { registerServiceWorker, supportsNotifications, supportsServiceWorker, permission, ensurePermission, show };
        })();

        // ── Dynamic favicon: green → red after 30 min idle ───────
        const faviconEl = document.getElementById('dynamic-favicon');
        const faviconGreen = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='%2314b8a6'/></svg>";
        const faviconRed   = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><circle cx='32' cy='32' r='30' fill='%23ef4444'/></svg>";
        let hideTimer = null;
        const IDLE_THRESHOLD = 30 * 60 * 1000;

        function setFavicon(uri) { if (faviconEl) faviconEl.setAttribute('href', uri); }
        function handleVisibility() {
            if (document.hidden) {
                hideTimer = setTimeout(() => setFavicon(faviconRed), IDLE_THRESHOLD);
            } else {
                if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
                setFavicon(faviconGreen);
            }
        }
        document.addEventListener('visibilitychange', handleVisibility);
        handleVisibility();

        // ── Auto-close mobile sidebar on nav click ────────────────
        // Uses getOrCreateInstance (not getInstance) so it works even if
        // the user has not yet clicked the hamburger on this page load.
        const sidebarEl = document.getElementById('sidebar-menu');
        sidebarEl?.querySelectorAll('a.nav-link, a.dropdown-item').forEach(a => {
            a.addEventListener('click', () => {
                if (!window.matchMedia('(max-width: 991.98px)').matches) return;
                if (window.bootstrap?.Collapse) {
                    window.bootstrap.Collapse.getOrCreateInstance(sidebarEl).hide();
                } else {
                    // Fallback: manually remove Bootstrap show class
                    sidebarEl.classList.remove('show');
                }
            });
        });

        // ── data-confirm: confirmation dialog before form submit ──
        // Usage: add data-confirm="Yakin?" to a submit button or a form.
        // Works for both buttons inside forms and standalone <a> tags with data-method.
        (() => {
            let _pendingForm = null;

            const modalEl = document.getElementById('confirm-modal');
            const modalMsg = document.getElementById('confirm-modal-message');
            const modalOk  = document.getElementById('confirm-modal-ok');

            const bsModal = modalEl
                ? new bootstrap.Modal(modalEl, { keyboard: true })
                : null;

            function showConfirm(message, onConfirm) {
                if (bsModal && modalMsg && modalOk) {
                    modalMsg.textContent = message;
                    _pendingForm = onConfirm;
                    bsModal.show();
                } else {
                    // Fallback if modal element missing
                    if (window.confirm(message)) onConfirm();
                }
            }

            if (modalOk) {
                modalOk.addEventListener('click', () => {
                    bsModal.hide();
                    if (typeof _pendingForm === 'function') {
                        _pendingForm();
                        _pendingForm = null;
                    }
                });
            }

            // Listen on document so it works for dynamically added elements too
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-confirm]');
                if (!btn) return;

                const message = btn.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?';

                // Button inside a form
                const form = btn.closest('form');
                if (form) {
                    e.preventDefault();
                    showConfirm(message, () => {
                        // Remove data-confirm so the next submit goes through
                        btn.removeAttribute('data-confirm');
                        btn.click();
                    });
                    return;
                }

                // Anchor link with data-confirm
                if (btn.tagName === 'A') {
                    e.preventDefault();
                    const href = btn.getAttribute('href');
                    showConfirm(message, () => { window.location.href = href; });
                }
            });
        })();
    </script>

    {{-- Confirmation modal (Bootstrap 5 / Tabler) --}}
    <div class="modal modal-blur fade" id="confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-title">Konfirmasi</div>
                    <div id="confirm-modal-message" class="text-muted mt-1">Yakin ingin melanjutkan?</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirm-modal-ok">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
