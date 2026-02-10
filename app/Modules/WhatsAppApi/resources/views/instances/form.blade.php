@extends('layouts.admin')

@section('content')
@php $isEdit = $instance->exists; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} WA Instance</h2>
        <div class="text-muted small">Isi token/webhook untuk koneksi WA API.</div>
    </div>
    <a href="{{ route('whatsapp-api.instances.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('whatsapp-api.instances.update', $instance) : route('whatsapp-api.instances.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $instance->name) }}" required>
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nomor WA</label>
                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $instance->phone_number) }}" placeholder="628xxxx">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Provider</label>
                    <input type="text" name="provider" class="form-control" value="{{ old('provider', $instance->provider ?? 'wwebjs') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">API Base URL</label>
                    <input type="url" name="api_base_url" class="form-control" value="{{ old('api_base_url', $instance->api_base_url) }}" placeholder="https://wa-gateway.test">
                </div>
                <div class="col-md-4">
                    <label class="form-label">API Token</label>
                    <input type="text" name="api_token" class="form-control" value="{{ old('api_token', $instance->api_token) }}" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url', $instance->webhook_url) }}" placeholder="https://myapp.test/webhooks/wa">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['disconnected','connecting','connected','error'] as $status)
                            <option value="{{ $status }}" {{ old('status', $instance->status) === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $instance->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan / Settings (JSON opsional)</label>
                    <textarea name="settings" class="form-control" rows="3" placeholder='{"webhook_secret":"..."}'>{{ old('settings', $instance->settings ? json_encode($instance->settings) : '') }}</textarea>
                    <div class="text-muted small">Optional: simpan konfigurasi tambahan.</div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
