<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('head_title', config('app.name'))</title>
    <meta name="description" content="@yield('head_description', 'Meetra — platform bisnis untuk operasional, penjualan, customer, dan workflow tim dalam satu workspace.')">
    @stack('head_meta')
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.34.1/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    @stack('head')
</head>
<body class="landing-page">
<div class="landing-shell">
@php
    $productsUrl = \Illuminate\Support\Facades\Route::has('products') ? route('products') : url('/products');
    $contactUrl = \Illuminate\Support\Facades\Route::has('contact') ? route('contact') : url('/contact-us');
    $onboardingUrl = \Illuminate\Support\Facades\Route::has('onboarding.create') ? route('onboarding.create') : url('/onboarding');
@endphp

@section('topbar')
{{-- ══ TOPBAR ══════════════════════════════════════════════ --}}
<header class="landing-topbar sticky-top">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                <x-app-logo variant="default" :height="36" />
            </a>
            <nav class="d-none d-lg-flex align-items-center gap-1 landing-nav-shell">
                <a href="{{ route('landing') }}" class="landing-nav-link d-inline-flex align-items-center gap-2">
                    <i class="ti ti-home-2"></i>
                    <span>Home</span>
                </a>
                <a href="{{ $productsUrl }}" class="landing-nav-link d-inline-flex align-items-center gap-2">
                    <i class="ti ti-box"></i>
                    <span>Products</span>
                </a>
                <a href="{{ route('about') }}" class="landing-nav-link d-inline-flex align-items-center gap-2">
                    <i class="ti ti-building"></i>
                    <span>About Us</span>
                </a>
                <a href="{{ $contactUrl }}" class="landing-nav-link d-inline-flex align-items-center gap-2">
                    <i class="ti ti-phone-call"></i>
                    <span>Contact Us</span>
                </a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $contactUrl }}" class="landing-topbar-action d-none d-md-inline-flex">Talk to Sales</a>
                <a href="{{ route('workspace.finder') }}" class="btn btn-outline-dark btn-sm d-none d-md-inline-flex">Login</a>
                <a href="{{ $onboardingUrl }}" class="btn btn-dark btn-sm">Sign Up</a>
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
                <p class="landing-footer-tagline mb-4">One workspace to run your business. Everything connected, everything under control.</p>
                <div class="landing-footer-contact">
                    <a href="https://wa.me/6281222229815" target="_blank" rel="noopener" class="landing-footer-contact-item">
                        <i class="ti ti-brand-whatsapp"></i>
                        <span>+62 812-222-9815</span>
                        <span class="landing-footer-contact-badge">WhatsApp Chat</span>
                    </a>
                    <a href="mailto:support@meetra.id" class="landing-footer-contact-item">
                        <i class="ti ti-mail"></i>
                        <span>support@meetra.id</span>
                    </a>
                    <div class="landing-footer-contact-item">
                        <i class="ti ti-headset"></i>
                        <span>Support available 24 hours</span>
                    </div>
                </div>
            </div>

            {{-- Produk --}}
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Products</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('landing') }}">Meetra</a>
                    <a href="{{ $productsUrl }}">Business Suites</a>
                    <a href="{{ route('landing.accounting') }}">Accounting</a>
                    <a href="{{ route('landing.commerce') }}">Commerce</a>
                    <a href="{{ $onboardingUrl }}">Sign Up</a>
                    <a href="{{ $contactUrl }}">Talk to Sales</a>
                    <a href="{{ route('about') }}">About Meetra</a>
                    <a href="{{ route('workspace.finder') }}">Login</a>
                </nav>
            </div>

            {{-- Perusahaan --}}
            <div class="col-6 col-lg-2">
                <div class="landing-footer-heading">Company</div>
                <nav class="landing-footer-nav">
                    <a href="{{ route('about') }}">About Us</a>
                    <a href="{{ $contactUrl }}">Contact Us</a>
                    <a href="{{ route('affiliate.program') }}">Partner Program</a>
                    <a href="{{ route('security') }}">Data Security</a>
                    <a href="{{ route('privacy') }}">Privacy Policy</a>
                    <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
                </nav>
            </div>

            {{-- Trust --}}
            <div class="col-lg-4">
                <div class="landing-footer-heading">Infrastructure &amp; Security</div>
                <div class="landing-footer-trust-cards">
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-server-2"></i>
                        <div>
                            <div class="fw-semibold">Servers in Indonesia</div>
                            <div>Tier-1 cloud data center located in Indonesia.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-lock"></i>
                        <div>
                            <div class="fw-semibold">Encryption &amp; Isolation</div>
                            <div>Each workspace is isolated. Connections are secured with TLS.</div>
                        </div>
                    </div>
                    <div class="landing-footer-trust-card">
                        <i class="ti ti-database-heart"></i>
                        <div>
                            <div class="fw-semibold">Enterprise-Grade Database</div>
                            <div>Daily automated backups, high availability, zero data loss.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="landing-footer-bottom">
            <div class="landing-footer-copy">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                <span style="opacity:.45; margin-left:.5rem;">PT Meetra Digital Teknologi</span>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="landing-footer-trust-pill"><i class="ti ti-shield-check"></i> Secure Data</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-server-2"></i> Indonesia Servers</span>
                <span class="landing-footer-trust-pill"><i class="ti ti-credit-card"></i> Local Payments</span>
            </div>
        </div>
    </div>
</footer>
@show

</div>{{-- .landing-shell --}}

@stack('scripts')
</body>
</html>
