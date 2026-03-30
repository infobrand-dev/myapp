@extends('emails.layout')

@section('subject', 'Akun affiliate Anda di ' . config('app.name') . ' sudah aktif')

@section('content')
    <h1>Akun affiliate Anda sudah aktif</h1>
    <p>Halo {{ $affiliateName }},</p>
    <p>Anda sudah terdaftar sebagai affiliate platform {{ config('app.name') }}. Gunakan link referral di bawah ini untuk mengarahkan calon customer ke landing page kami.</p>

    <div class="info-box">
        <strong>Kode referral</strong>
        {{ $referralCode }}
    </div>

    <div class="info-box">
        <strong>Komisi</strong>
        @if($commissionType === 'flat')
            {{ number_format($commissionRate, 0, ',', '.') }} per penjualan
        @else
            {{ rtrim(rtrim(number_format($commissionRate, 2, '.', ''), '0'), '.') }}% per penjualan
        @endif
    </div>

    <div class="btn-wrap">
        <a href="{{ $referralLink }}" class="btn">Buka Link Referral</a>
    </div>

    <p>Link referral Anda:</p>
    <p><a href="{{ $referralLink }}">{{ $referralLink }}</a></p>
@endsection
