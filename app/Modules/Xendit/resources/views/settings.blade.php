@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Xendit Settings</h2>
        <div class="text-muted small">Konfigurasi checkout link dan webhook Xendit.</div>
    </div>
    <a href="{{ route('xendit.transactions.index') }}" class="btn btn-outline-secondary">Lihat Transaksi</a>
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
                <form method="POST" action="{{ route('xendit.settings.update') }}">
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
                        <label class="form-label">Secret Key</label>
                        <input type="password" name="secret_key" class="form-control font-monospace"
                               placeholder="{{ $setting->secret_key ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'xnd_development_... / xnd_production_...' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Webhook Token</label>
                        <input type="password" name="webhook_token" class="form-control font-monospace"
                               placeholder="{{ $setting->webhook_token ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'Token verifikasi callback Xendit' }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}>
                            <span class="form-check-label">Aktifkan Xendit</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Webhook URL</h3></div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="xenditWebhookUrl" class="form-control font-monospace small" readonly value="{{ route('xendit.webhook.notification') }}">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('xenditWebhookUrl').value)">Copy</button>
                </div>
                <div class="form-text mt-2">Masukkan URL ini ke dashboard Xendit sebagai invoice callback endpoint.</div>
            </div>
        </div>
    </div>
</div>
@endsection
