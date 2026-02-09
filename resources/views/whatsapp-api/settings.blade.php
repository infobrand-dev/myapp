@extends('layouts.admin')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle text-muted">Modules</div>
            <h2 class="page-title">WhatsApp API</h2>
            <div class="text-muted">Settings</div>
        </div>
        <div class="col-auto ms-auto d-flex align-items-center gap-2">
            @php
                $isActive = old('is_active', $setting?->is_active ?? true);
                $statusLabel = $isActive ? 'Active' : 'Inactive';
                $statusClass = $isActive ? 'bg-green-lt text-green' : 'bg-red-lt text-red';
                $lastStatus = $setting?->last_test_status ?? null;
                $lastStatusLabel = $lastStatus === 'success' ? 'Success' : ($lastStatus === 'failed' ? 'Failed' : null);
                $lastStatusClass = $lastStatus === 'success' ? 'bg-green-lt text-green' : 'bg-red-lt text-red';
            @endphp
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
            @if($lastStatusLabel)
                <span class="badge {{ $lastStatusClass }}">Last Test: {{ $lastStatusLabel }}</span>
            @endif
        </div>
    </div>
    <nav aria-label="breadcrumb" class="mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">Modules</li>
            <li class="breadcrumb-item active" aria-current="page">WhatsApp API</li>
        </ol>
    </nav>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if (session('test_message'))
    <div class="alert alert-{{ session('test_status', 'info') }}">{{ session('test_message') }}</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">WhatsApp API Settings</h3>
        @if($setting?->last_tested_at)
            <div class="text-muted small">Last tested: {{ $setting->last_tested_at->format('d M Y H:i') }}</div>
        @endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('whatsapp-api.settings.update') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Provider</label>
                    <select name="provider" class="form-select" required>
                        <option value="meta_cloud" {{ old('provider', $setting?->provider ?? 'meta_cloud') === 'meta_cloud' ? 'selected' : '' }}>Meta Cloud API</option>
                        <option value="third_party" {{ old('provider', $setting?->provider ?? '') === 'third_party' ? 'selected' : '' }}>Third Party</option>
                    </select>
                    <small class="text-muted">Pilih provider WhatsApp API yang digunakan.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Base URL</label>
                    <input type="text" name="base_url" class="form-control" value="{{ old('base_url', $setting?->base_url ?? '') }}" placeholder="https://api.example.com">
                    <small class="text-muted">Wajib untuk provider third party.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number ID</label>
                    <input type="text" name="phone_number_id" class="form-control" value="{{ old('phone_number_id', $setting?->phone_number_id ?? '') }}" placeholder="123456789">
                </div>
                <div class="col-md-6">
                    <label class="form-label">WABA ID (opsional)</label>
                    <input type="text" name="waba_id" class="form-control" value="{{ old('waba_id', $setting?->waba_id ?? '') }}" placeholder="WABA ID">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Access Token</label>
                    <input type="password" name="access_token" class="form-control" placeholder="Masukkan token baru jika ingin mengganti">
                    <small class="text-muted">Token disimpan terenkripsi. Kosongkan jika tidak ingin mengganti.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Verify Token (opsional)</label>
                    <input type="text" name="verify_token" class="form-control" value="{{ old('verify_token', $setting?->verify_token ?? '') }}" placeholder="verify-token">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Default Sender Name (opsional)</label>
                    <input type="text" name="default_sender_name" class="form-control" value="{{ old('default_sender_name', $setting?->default_sender_name ?? '') }}" placeholder="MyApp">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Timeout (seconds)</label>
                    <input type="number" name="timeout_seconds" min="5" max="120" class="form-control" value="{{ old('timeout_seconds', $setting?->timeout_seconds ?? 30) }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $setting?->is_active ?? true) ? 'checked' : '' }}>
                        <span class="form-check-label">Active</span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes (opsional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan internal">{{ old('notes', $setting?->notes ?? '') }}</textarea>
                </div>
            </div>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Save Settings</button>
                <button class="btn btn-outline-primary" type="submit" formaction="{{ route('whatsapp-api.test') }}">Test Connection</button>
            </div>
        </form>
    </div>
    <div class="text-muted small mt-2">Test Connection menggunakan settings yang sudah tersimpan. Simpan dulu untuk menggunakan data terbaru.</div>
</div>
@endsection
