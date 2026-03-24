@extends('emails.layout')

@section('subject', 'Selamat datang di ' . config('app.name') . '!')

@section('content')
    <h1>Halo, {{ $adminName }}!</h1>

    <p>
        Akun <strong>{{ $tenantName }}</strong> Anda telah berhasil dibuat di <strong>{{ config('app.name') }}</strong>.
        Mulai kelola bisnis Anda sekarang!
    </p>

    <div class="info-box">
        <strong>Detail Akun Anda</strong>
        Nama Bisnis: {{ $tenantName }}<br>
        Subdomain: <code>{{ $tenantSlug }}.{{ config('multitenancy.saas_domain') }}</code><br>
        Email Login: {{ $adminEmail }}
    </div>

    <div class="btn-wrap">
        <a href="{{ $loginUrl }}" class="btn">Masuk ke Dashboard &rarr;</a>
    </div>

    <hr class="divider">

    <p style="font-size:14px; color:#6b7280;">
        Jika Anda tidak mendaftar akun ini, abaikan email ini.
        Untuk bantuan, hubungi tim support kami.
    </p>
@endsection
