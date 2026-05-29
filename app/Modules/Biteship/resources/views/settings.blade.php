@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Biteship Settings</h2>
        <div class="text-muted small">Konfigurasi API key dan courier default untuk quote ongkir Biteship.</div>
    </div>
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('biteship.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Environment</label>
                    <select name="environment" class="form-select">
                        <option value="sandbox" {{ ($setting->environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="production" {{ ($setting->environment ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">API Key</label>
                    <input type="password" name="api_key" class="form-control font-monospace" placeholder="{{ $setting->api_key ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'biteship_test_... / biteship_live_...' }}">
                    <div class="form-hint">Biteship menggunakan API key di header `authorization`.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Default Couriers</label>
                    <input type="text" name="default_couriers" class="form-control" value="{{ old('default_couriers', implode(',', (array) ($setting->default_couriers ?? []))) }}" placeholder="jne,sicepat,anteraja">
                    <div class="form-hint">Pisahkan dengan koma. Dipakai sebagai fallback saat quote form tidak mengisi courier.</div>
                </div>

                <div class="col-12">
                    <label class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}>
                        <span class="form-check-label">Aktifkan Biteship</span>
                    </label>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
