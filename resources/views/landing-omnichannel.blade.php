@extends('layouts.landing')

@section('head_title', config('app.name') . ' Omnichannel - Sedang Dipersiapkan')
@section('head_description', 'Jalur Omnichannel sedang dipersiapkan lebih lanjut. Untuk saat ini, jalur pendaftaran utama yang aktif adalah Accounting.')

@section('content')
<section class="landing-hero py-5 py-lg-6">
    <div class="container py-lg-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <div class="landing-badge mb-4">
                    <i class="ti ti-message-circle-2"></i> Omnichannel
                </div>
                <h1 class="landing-headline mb-4">
                    Jalur <span>Omnichannel</span> sedang kami siapkan lebih lanjut.
                </h1>
                <p class="landing-subtext mb-4">
                    Product line ini akan fokus ke shared inbox, live chat, CRM lite, WhatsApp, dan automation untuk tim sales dan support. Saat ini kami belum membukanya sebagai jalur pendaftaran utama.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('landing.accounting') }}" class="btn btn-lg btn-dark">Lihat Accounting</a>
                    <a href="{{ route('onboarding.create', ['product_line' => 'accounting', 'plan' => 'accounting_starter']) }}" class="btn btn-lg btn-outline-dark">Daftar Accounting</a>
                </div>
                <div class="small text-muted">
                    Jika fokus bisnis Anda saat ini adalah transaksi, pembayaran, finance, dan operasional, jalur yang aktif dan paling siap dipakai adalah Accounting.
                </div>
            </div>
            <div class="col-lg-5">
                <div class="landing-panel p-4 p-lg-5">
                    <div class="text-uppercase text-muted small fw-bold mb-3">Ruang Lingkup Omnichannel</div>
                    <div class="landing-checklist small">
                        <div><i class="ti ti-check text-success"></i> Shared inbox untuk tim</div>
                        <div><i class="ti ti-check text-success"></i> Social inbox dan live chat</div>
                        <div><i class="ti ti-check text-success"></i> CRM lite untuk follow up lead</div>
                        <div><i class="ti ti-check text-success"></i> WhatsApp dan automation</div>
                    </div>
                    <div class="alert alert-warning mt-4 mb-0 small">
                        Omnichannel tetap ada di roadmap produk, tetapi pendaftaran self-serve utamanya belum kami dorong dari halaman publik.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
