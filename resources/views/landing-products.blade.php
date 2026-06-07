@extends('layouts.landing')

@section('head_title', 'Business Suites - ' . config('app.name'))
@section('head_description', 'Business suites untuk customer, transaksi, workflow tim, dan operasional bisnis yang lebih tertata.')

@section('content')
@php
    $contactUrl = \Illuminate\Support\Facades\Route::has('contact') ? route('contact') : url('/contact-us');
@endphp
<style>
    .meetra-product-page-card { border:1px solid rgba(15,23,42,.08); border-radius:24px; background:#fff; padding:1.75rem; height:100%; box-shadow:0 18px 42px rgba(15,23,42,.05); }
    .meetra-product-page-icon { width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#eff6ff,#dbeafe); color:#1d4ed8; margin-bottom:1rem; }
    .meetra-product-page-icon i { font-size:1.45rem; }
</style>

<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,#f8fafc 0%,#eef1ff 100%);border-bottom:1px solid var(--landing-line);">
    <div class="container py-lg-3">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="landing-badge mb-4 mx-auto" style="display:inline-flex;">
                    <i class="ti ti-box"></i> Business Suites
                </div>
                <h1 class="landing-headline mb-4">Business suite untuk kebutuhan bisnis yang berbeda.</h1>
                <p class="landing-subtext mx-auto">Setiap product line dirancang untuk menjawab kebutuhan yang spesifik, namun tetap berada dalam ekosistem kerja yang selaras.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-report-money"></i></div>
                    <h2 class="h4 mb-2">Accounting</h2>
                    <p class="small text-muted mb-4">Untuk transaksi, pembayaran, pembelian, stok, kas, dan pelaporan operasional.</p>
                    <a href="{{ route('landing.accounting') }}" class="btn btn-outline-dark btn-sm">Lihat Detail</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-shopping-bag"></i></div>
                    <h2 class="h4 mb-2">Commerce</h2>
                    <p class="small text-muted mb-4">Untuk storefront, order online, payment status, shipping, fulfillment, affiliate, dan wallet dalam satu workflow yang lebih rapi.</p>
                    <a href="{{ route('landing.commerce') }}" class="btn btn-outline-dark btn-sm">Lihat Detail</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-message-circle-2"></i></div>
                    <h2 class="h4 mb-2">Omnichannel</h2>
                    <p class="small text-muted mb-4">Untuk pengelolaan percakapan customer, inbox terpadu, dan tindak lanjut lintas channel.</p>
                    <a href="{{ route('landing.omnichannel') }}" class="btn btn-outline-dark btn-sm">Lihat Detail</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-checklist"></i></div>
                    <h2 class="h4 mb-2">Productivity</h2>
                    <p class="small text-muted mb-4">Untuk task management, workflow internal, dan pemantauan progres tim.</p>
                    <a href="{{ route('landing.crm') }}" class="btn btn-outline-dark btn-sm">Lihat Detail</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-users"></i></div>
                    <h2 class="h4 mb-2">HR & Payroll</h2>
                    <p class="small text-muted mb-4">Untuk administrasi SDM, kehadiran, dan proses payroll yang lebih tertata.</p>
                    <a href="{{ $contactUrl }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-address-book"></i></div>
                    <h2 class="h4 mb-2">CRM</h2>
                    <p class="small text-muted mb-4">Untuk pengelolaan prospek, histori customer, dan pipeline hubungan bisnis.</p>
                    <a href="{{ $contactUrl }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="meetra-product-page-card">
                    <div class="meetra-product-page-icon"><i class="ti ti-speakerphone"></i></div>
                    <h2 class="h4 mb-2">Marketing Automation</h2>
                    <p class="small text-muted mb-4">Untuk campaign, segmentasi audiens, dan automation aktivitas pemasaran.</p>
                    <a href="{{ $contactUrl }}" class="btn btn-outline-dark btn-sm">Konsultasikan</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 py-lg-6" style="background:#f8fafc;border-top:1px solid var(--landing-line);">
    <div class="container">
        <div class="landing-panel p-4 p-lg-5 text-center">
            <div class="landing-eyebrow mb-2">Konsultasi</div>
            <h2 class="landing-section-title mb-3">Perlu bantuan memilih business suite yang tepat?</h2>
            <p class="landing-subtext mx-auto mb-4" style="max-width:720px;">Tim kami dapat membantu memetakan kebutuhan Anda dan mengarahkan business suite yang paling relevan untuk skenario bisnis yang sedang dijalankan.</p>
            <a href="{{ $contactUrl }}" class="btn btn-dark btn-lg">Konsultasikan Kebutuhan</a>
        </div>
    </div>
</section>
@endsection
