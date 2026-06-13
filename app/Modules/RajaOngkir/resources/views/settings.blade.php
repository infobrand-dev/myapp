@extends('layouts.tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">RajaOngkir Settings</h2>
        <div class="text-muted small">Konfigurasi API key dan default area/courier untuk quote RajaOngkir.</div>
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
        <form method="POST" action="{{ route('rajaongkir.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Environment</label>
                    <select name="environment" class="form-select">
                        <option value="production" {{ ($setting->environment ?? 'production') === 'production' ? 'selected' : '' }}>Production</option>
                        <option value="sandbox" {{ ($setting->environment ?? '') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">API Key</label>
                    <input type="password" name="api_key" class="form-control font-monospace" placeholder="{{ $setting->api_key ? 'Tersimpan, kosongkan untuk tidak mengubah' : 'Shipping Cost API key' }}">
                    <div class="form-hint">RajaOngkir shipping cost API memakai header `key`.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Default Origin Area ID</label>
                    <input type="text" name="default_origin_area_id" class="form-control font-monospace" value="{{ old('default_origin_area_id', $setting->default_origin_area_id) }}" placeholder="Contoh: 501">
                    <div class="form-hint">Dipakai sebagai fallback origin untuk quote jika form shipping tidak mengisi origin area id.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Default Couriers</label>
                    <input type="text" name="default_couriers" class="form-control" value="{{ old('default_couriers', implode(',', (array) ($setting->default_couriers ?? []))) }}" placeholder="jne,sicepat,anteraja">
                </div>

                <div class="col-12">
                    <label class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $setting->is_active ? 'checked' : '' }}>
                        <span class="form-check-label">Aktifkan RajaOngkir</span>
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

