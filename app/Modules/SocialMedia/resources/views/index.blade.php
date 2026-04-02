@extends('layouts.admin')

@section('content')
@php
    $platformRegistry = app(\App\Modules\SocialMedia\Services\SocialPlatformRegistry::class);
    $livePlatforms = collect($platformRegistry->summary())->where('public_enabled', true)->pluck('label')->implode(', ');
@endphp
<div class="page-header mb-3">
    <div class="row align-items-center w-100">
        <div class="col">
            <h2 class="mb-0">Instagram / Facebook DM Inbox</h2>
            <div class="text-muted small">{{ $livePlatforms }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body text-muted">
        Integrasi belum dihubungkan. Tambahkan akun Instagram Business atau Facebook Page untuk menerima pesan.
    </div>
</div>
@endsection
