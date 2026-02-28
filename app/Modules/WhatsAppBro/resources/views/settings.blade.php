@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp Bro Settings</h2>
        <div class="text-muted small">Khusus konfigurasi bridge dan webhook token WhatsApp Bro.</div>
    </div>
    <a href="{{ route('whatsappbro.index') }}" class="btn btn-outline-secondary">Kembali ke Panel</a>
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

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('whatsappbro.settings.update') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bridge URL</label>
                    <input
                        type="url"
                        name="base_url"
                        class="form-control"
                        value="{{ old('base_url', $setting?->base_url ?? config('modules.whatsapp_bro.bridge_url')) }}"
                        placeholder="http://localhost:3020"
                        required
                    >
                    <div class="text-muted small">Endpoint Node bridge WhatsApp Bro.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Webhook Token</label>
                    <input
                        type="password"
                        name="verify_token"
                        class="form-control"
                        placeholder="Kosongkan jika tidak diubah"
                    >
                    <div class="text-muted small">Token disimpan terenkripsi dan dipakai untuk validasi inbound webhook.</div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
