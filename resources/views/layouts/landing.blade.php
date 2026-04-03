<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('head_title', config('app.name'))</title>
    <meta name="description" content="@yield('head_description', 'Platform omnichannel untuk tim sales, support, dan marketing — semua percakapan pelanggan dalam satu workspace.')">
    @stack('head_meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    @stack('head')
</head>
<body class="landing-page">
<div class="landing-shell">

@section('topbar')
{{-- ══ TOPBAR ══════════════════════════════════════════════ --}}
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1">
                <a href="{{ route('landing') }}#solutions"  class="landing-nav-link">Fitur</a>
                <a href="{{ route('landing') }}#pricing"    class="landing-nav-link">Harga</a>
                <a href="{{ route('landing') }}#ai-credits" class="landing-nav-link">AI Credits</a>
                <a href="{{ route('landing') }}#faq"        class="landing-nav-link">FAQ</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login Workspace</a>
                <a href="{{ route('onboarding.create') }}" class="btn btn-dark btn-sm">Daftar Gratis</a>
            </div>
        </div>
    </div>
</header>
@show

<main>
@yield('content')
</main>

@section('footer')
{{-- ══ FOOTER ══════════════════════════════════════════════ --}}
<footer class="landing-footer">
    <div class="container">
        <div class="landing-footer-inner row g-5">
            {{-- Brand + contact --}}
            <div class="col-lg-4">
                <div class="mb-3">
                    <x-app-logo variant="default" :height="30" />
                </div>
                <p class="landing-footer-tagline mb-4">Satu workspace untuk jalankan bisnis Anda. Semua terhubung, semua terkendali.</p>
                <div class="landing-footer-contact">
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="landing-footer-contact-item">
                        <i class="ti ti-brand-whatsapp"></i>
                        <span>+62 812-222-9815</span>
                        <span class="landing-footer-contact-badge">Chat WhatsApp</span>
                    </a>
                    <a href="mailto:support@meetra.id" class="landing-footer-contact-item">
                        <i class="ti ti-mail"></i>
                        <span>support@meetra.id</span>
                    </a>
                    <div class="landing-footer-contact-item">
                        <i class="ti ti-headset"></i>
                        <span>Dukungan tersedia 24 jam</span>
                    </div>
                </div>
            </div>

            {{-- Produk --}}
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Produk</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('landing.meetra') }}">Meetra</a>
                    <a href="{{ route('landing.omnichannel') }}">Omnichannel</a>
                    <a href="{{ route('landing.accounting') }}">Accounting</a>
                    <a href="{{ route('landing') }}#solutions">Fitur</a>
                    <a href="{{ route('landing') }}#pricing">Harga</a>
                    <a href="{{ route('landing') }}#ai-credits">AI Credits</a>
                    <a href="{{ route('landing') }}#faq">FAQ</a>
                    <a href="{{ route('workspace.finder') }}">Login Workspace</a>
                    <a href="{{ route('onboarding.create') }}">Daftar Gratis</a>
                </nav>
            </div>

            {{-- Perusahaan --}}
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Perusahaan</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('affiliate.program') }}">Program Partner</a>
                    <a href="{{ route('security') }}">Keamanan Data</a>
                    <a href="{{ route('privacy') }}">Kebijakan Privasi</a>
                    <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
                </nav>
            </div>

            {{-- Trust --}}
            <div class="col-lg-4">
                <div class="landing-footer-heading">Infrastruktur &amp; Keamanan</div>
                <div class="landing-footer-trust-cards">
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-server-2"></i>
                        <div>
                            <div class="fw-semibold">Server di Indonesia</div>
                            <div>Data center cloud Tier-1 berlokasi di Indonesia.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div>
                            <div class="fw-semibold">Enkripsi &amp; Isolasi</div>
                            <div>Setiap workspace terisolasi. Koneksi dienkripsi TLS.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div>
                            <div class="fw-semibold">Database Kelas Enterprise</div>
                            <div>Backup otomatis harian, high-availability, zero data loss.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="landing-footer-bottom">
            <div class="landing-footer-copy">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Hak cipta dilindungi.
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="landing-footer-trust-pill"><i class="ti ti-shield-check"></i> Data Aman</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-brand-whatsapp"></i> WhatsApp API Resmi</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-credit-card"></i> Bayar Lokal</span>
            </div>
        </div>
    </div>
</footer>
@show

</div>{{-- .landing-shell --}}

@stack('scripts')
</body>
</html>
