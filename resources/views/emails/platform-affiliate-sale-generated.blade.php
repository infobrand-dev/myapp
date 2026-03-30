@extends('emails.layout')

@section('subject', 'Ada penjualan baru dari link affiliate Anda')

@section('content')
    <h1>Selamat, ada penjualan baru</h1>
    <p>Halo {{ $affiliateName }},</p>
    <p>Link affiliate Anda menghasilkan penjualan baru di {{ config('app.name') }}.</p>

    <div class="info-box">
        <strong>Workspace / Tenant</strong>
        {{ $tenantName }}
    </div>

    <div class="info-box">
        <strong>Order</strong>
        {{ $orderNumber }} · {{ $planName }}
    </div>

    <div class="info-box">
        <strong>Nilai penjualan</strong>
        {{ app(\App\Support\MoneyFormatter::class)->format($orderAmount, $orderCurrency) }}
    </div>

    <div class="info-box">
        <strong>Estimasi komisi</strong>
        {{ app(\App\Support\MoneyFormatter::class)->format($commissionAmount, $orderCurrency) }}
    </div>

    <div class="btn-wrap">
        <a href="{{ $referralLink }}" class="btn">Lihat Link Referral</a>
    </div>
@endsection
