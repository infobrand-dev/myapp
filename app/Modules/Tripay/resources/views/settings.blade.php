@extends('layouts.tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Tripay Settings</h2>
        <div class="text-muted small">Konfigurasi hosted checkout dan callback Tripay.</div>
    </div>
    <a href="{{ route('tripay.transactions.index') }}" class="btn btn-outline-secondary">Lihat Transaksi</a>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Kredensial API</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('tripay.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Environment</label>
                        <select name="environment" class="form-select">
                            <option value="sandbox" {{ ($setting->environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ ($setting->environment ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" name="api_key" class="form-control font-monospace"
                               placeholder="{{ $setting->api_key ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'Bearer API key Tripay' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Private Key</label>
                        <input type="password" name="private_key" class="form-control font-monospace"
                               placeholder="{{ $setting->private_key ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'Private key untuk signature request' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Merchant Code</label>
                        <input type="text" name="merchant_code" class="form-control font-monospace" value="{{ old('merchant_code', $setting->merchant_code) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Callback Signature Key</label>
                        <input type="password" name="callback_signature_key" class="form-control font-monospace"
                               placeholder="{{ $setting->callback_signature_key ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'Key verifikasi callback Tripay' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}>
                            <span class="form-check-label">Aktifkan Tripay</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Webhook URL</h3></div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="tripayWebhookUrl" class="form-control font-monospace small" readonly value="{{ route('tripay.webhook.notification') }}">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('tripayWebhookUrl').value)">Copy</button>
                </div>
                <div class="form-text mt-2">Daftarkan callback endpoint ini di dashboard Tripay.</div>
            </div>
        </div>
    </div>
</div>
@endsection

