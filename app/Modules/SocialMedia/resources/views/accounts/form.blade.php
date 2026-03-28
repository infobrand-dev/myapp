@extends('layouts.admin')

@section('content')
@php
    $integrationAutoReply = old('auto_reply', data_get($integration, 'auto_reply', false));
    $integrationChatbotAccountId = old('chatbot_account_id', data_get($integration, 'chatbot_account_id'));
    $chatbotEnabled = $chatbotEnabled ?? false;
    $metaOAuthReady = $metaOAuthReady ?? false;
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Pengaturan Social Account</h2>
        <div class="text-muted small">Kredensial dihubungkan melalui Meta OAuth platform. Tenant hanya mengatur status dan AI auto-reply.</div>
    </div>
    <a href="{{ route('social-media.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

@if(!$metaOAuthReady)
    <div class="alert alert-warning">
        META OAuth belum siap di environment platform. Isi <code>META_APP_ID</code> dan <code>META_APP_SECRET</code>, lalu reconnect akun ini.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('social-media.accounts.update', $account) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Platform</label>
                    <input type="text" class="form-control" value="{{ ucfirst($account->platform) }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Page ID (FB)</label>
                    <input type="text" class="form-control" value="{{ $account->page_id }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">IG Business ID</label>
                    <input type="text" class="form-control" value="{{ $account->ig_business_id }}" readonly>
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        Token akses tidak diinput manual oleh tenant. Jika akses Meta berubah atau tenant ingin mengganti Page/Instagram yang terhubung, klik
                        <a href="{{ route('social-media.accounts.connect.meta') }}" class="alert-link">Hubungkan Meta</a>
                        untuk sinkron ulang akun dari platform OAuth.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active','inactive'] as $st)
                            <option value="{{ $st }}" {{ old('status', $account->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="auto_reply" value="1" id="auto_reply" {{ $integrationAutoReply ? 'checked' : '' }} {{ $chatbotEnabled ? '' : 'disabled' }}>
                        <label class="form-check-label" for="auto_reply">Auto-reply AI</label>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Chatbot Account</label>
                    <select name="chatbot_account_id" class="form-select" {{ $chatbotEnabled ? '' : 'disabled' }}>
                        <option value="">-- Pilih AI --</option>
                        @foreach(($chatbotAccounts ?? []) as $ai)
                            <option value="{{ $ai->id }}" {{ (string) $integrationChatbotAccountId === (string) $ai->id ? 'selected' : '' }}>{{ $ai->name }} ({{ $ai->model ?? 'default' }})</option>
                        @endforeach
                    </select>
                    <div class="text-muted small">
                        @if($chatbotEnabled)
                            Aktifkan auto-reply setelah akun sosial media berhasil terhubung melalui OAuth.
                        @else
                            Install dan aktifkan module Chatbot untuk menghubungkan auto-reply AI.
                        @endif
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
