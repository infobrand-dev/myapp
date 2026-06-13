<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" class="notranslate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    <meta name="theme-color" content="#2D47CC">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
    <script>
        (function() {
            const mode = localStorage.getItem('theme-mode') || 'light';
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', mode);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <style>
        .app-toast-stack {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1085;
            width: min(26rem, calc(100vw - 2rem));
            display: grid;
            gap: .75rem;
            pointer-events: none;
        }
        .app-toast {
            pointer-events: auto;
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, .08);
            background: #fff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, .12);
            overflow: hidden;
            transform: translateY(-8px);
            opacity: 0;
            transition: opacity .18s ease, transform .18s ease;
        }
        .app-toast.is-visible { opacity: 1; transform: translateY(0); }
        .app-toast.is-hiding  { opacity: 0; transform: translateY(-8px); }
        .app-toast__bar { width: 4px; flex: 0 0 4px; }
        .app-toast--success .app-toast__bar { background: #16a34a; }
        .app-toast--danger  .app-toast__bar { background: #dc2626; }
        .app-toast--warning .app-toast__bar { background: #d97706; }
        .app-toast--info    .app-toast__bar { background: #2563eb; }
        .app-toast__body {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            padding: .95rem 1rem;
        }
        .app-toast__icon { font-size: 1.1rem; line-height: 1; margin-top: .1rem; }
        .app-toast--success .app-toast__icon { color: #15803d; }
        .app-toast--danger  .app-toast__icon { color: #b91c1c; }
        .app-toast--warning .app-toast__icon { color: #b45309; }
        .app-toast--info    .app-toast__icon { color: #1d4ed8; }
        .app-toast__content {
            min-width: 0;
            flex: 1 1 auto;
            color: #0f172a;
            font-weight: 500;
        }
        .app-toast__close {
            border: 0;
            background: transparent;
            color: #94a3b8;
            padding: 0;
            line-height: 1;
            font-size: 1.05rem;
        }
        .app-toast__close:hover { color: #475569; }
        @media (max-width: 767.98px) {
            .app-toast-stack { top: .75rem; right: .75rem; left: .75rem; width: auto; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-body platform-layout">
    <a class="skip-link" href="#main-content">Skip to content</a>

    <div id="sidebar-backdrop" class="sidebar-backdrop" aria-hidden="true"></div>

    @php
        $flashToasts = collect([
            session('status') ?? session('success')
                ? ['tone' => 'success', 'icon' => 'ti ti-circle-check', 'message' => session('status') ?? session('success')]
                : null,
            session('error')
                ? ['tone' => 'danger', 'icon' => 'ti ti-alert-circle', 'message' => session('error')]
                : null,
            session('warning')
                ? ['tone' => 'warning', 'icon' => 'ti ti-alert-triangle', 'message' => session('warning')]
                : null,
            session('info')
                ? ['tone' => 'info', 'icon' => 'ti ti-info-circle', 'message' => session('info')]
                : null,
        ])->filter()->values();
    @endphp
    @if($flashToasts->isNotEmpty())
        <div class="app-toast-stack" id="app-toast-stack" aria-live="polite" aria-atomic="true">
            @foreach($flashToasts as $toast)
                <div class="app-toast app-toast--{{ $toast['tone'] }}" data-toast>
                    <div class="d-flex">
                        <div class="app-toast__bar"></div>
                        <div class="app-toast__body">
                            <i class="{{ $toast['icon'] }} app-toast__icon" aria-hidden="true"></i>
                            <div class="app-toast__content">{{ $toast['message'] }}</div>
                            <button type="button" class="app-toast__close" data-toast-close aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="page app-shell">
        @include('shared.sidebar-platform')

        <div class="page-wrapper">
            {{-- Desktop topbar --}}
            <header class="navbar desktop-topbar d-none d-lg-flex" role="banner">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-end w-100 gap-2">
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
                                    <div class="badge bg-purple-lt text-purple mt-1">Platform Owner</div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="ti ti-user me-2 opacity-60" aria-hidden="true"></i>Profil
                                </a>
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
                            id="mobile-nav-toggle"
                            aria-controls="sidebar-menu"
                            aria-label="Toggle menu"
                            aria-expanded="false"
                        >
                            <i class="ti ti-menu-2" aria-hidden="true"></i>
                        </button>

                        <a href="{{ route('platform.dashboard') }}" class="mobile-topbar-brand d-inline-flex align-items-center" aria-label="{{ config('app.name') }}">
                            <x-app-logo variant="default" :height="28" class="mobile-topbar-logo" />
                        </a>

                        <div class="ms-auto d-flex align-items-center gap-2">
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
                                        <i class="ti ti-user me-2 opacity-60" aria-hidden="true"></i>Profil
                                    </a>
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
        document.addEventListener('DOMContentLoaded', function () {
            var toasts = Array.prototype.slice.call(document.querySelectorAll('[data-toast]'));
            toasts.forEach(function (toast, index) {
                var hideTimer;
                var closeButton = toast.querySelector('[data-toast-close]');
                function hideToast() {
                    toast.classList.add('is-hiding');
                    window.setTimeout(function () { toast.remove(); }, 180);
                }
                window.setTimeout(function () { toast.classList.add('is-visible'); }, 20 + (index * 60));
                hideTimer = window.setTimeout(hideToast, 4200 + (index * 250));
                if (closeButton) {
                    closeButton.addEventListener('click', function () {
                        window.clearTimeout(hideTimer);
                        hideToast();
                    });
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            var sidebar    = document.querySelector('.page > .navbar-vertical');
            var backdrop   = document.getElementById('sidebar-backdrop');
            var mobileBtn  = document.getElementById('mobile-nav-toggle');
            var closeBtn   = document.getElementById('sidebar-close-btn');
            var collapseBtn = document.getElementById('sidebar-collapse-toggle');

            if (localStorage.getItem('sidebar-mini') === '1') {
                document.body.classList.add('sidebar-mini');
            }

            function openSidebar() {
                if (!sidebar) return;
                sidebar.classList.add('sidebar--open');
                if (backdrop) backdrop.classList.add('show');
                if (mobileBtn) mobileBtn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                if (!sidebar) return;
                sidebar.classList.remove('sidebar--open');
                if (backdrop) backdrop.classList.remove('show');
                if (mobileBtn) mobileBtn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }

            if (mobileBtn) mobileBtn.addEventListener('click', openSidebar);
            if (closeBtn)  closeBtn.addEventListener('click', closeSidebar);
            if (backdrop)  backdrop.addEventListener('click', closeSidebar);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSidebar(); });

            if (collapseBtn) {
                collapseBtn.addEventListener('click', function () {
                    var isMini = document.body.classList.toggle('sidebar-mini');
                    localStorage.setItem('sidebar-mini', isMini ? '1' : '0');
                    collapseBtn.setAttribute('aria-label', isMini ? 'Perluas sidebar' : 'Kecilkan sidebar');
                });
            }
        });

        (() => {
            let _pendingForm = null;
            const modalEl  = document.getElementById('confirm-modal');
            const modalMsg = document.getElementById('confirm-modal-message');
            const modalOk  = document.getElementById('confirm-modal-ok');
            const bsModal  = modalEl ? new bootstrap.Modal(modalEl, { keyboard: true }) : null;

            function showConfirm(message, onConfirm) {
                if (bsModal && modalMsg && modalOk) {
                    modalMsg.textContent = message;
                    _pendingForm = onConfirm;
                    bsModal.show();
                } else {
                    if (window.confirm(message)) onConfirm();
                }
            }

            if (modalOk) {
                modalOk.addEventListener('click', () => {
                    bsModal.hide();
                    if (typeof _pendingForm === 'function') { _pendingForm(); _pendingForm = null; }
                });
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-confirm]');
                if (!btn) return;
                const message = btn.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?';
                const form = btn.closest('form');
                if (form) {
                    e.preventDefault();
                    showConfirm(message, () => { btn.removeAttribute('data-confirm'); btn.click(); });
                    return;
                }
                if (btn.tagName === 'A') {
                    e.preventDefault();
                    showConfirm(message, () => { window.location.href = btn.getAttribute('href'); });
                }
            });
        })();
    </script>

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
