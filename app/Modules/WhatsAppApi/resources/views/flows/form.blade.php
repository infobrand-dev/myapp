@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} WhatsApp Flow</h2>
        <div class="text-muted small">Buat Flow JSON di dashboard, lalu sync dan publish ke Meta.</div>
    </div>
    <a href="{{ route('whatsapp-api.flows.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
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
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $isEdit ? route('whatsapp-api.flows.update', $flow) : route('whatsapp-api.flows.store') }}">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Instance Cloud</label>
                            <select name="instance_id" class="form-select" required>
                                <option value="">- Pilih instance -</option>
                                @foreach($instances as $instance)
                                    <option value="{{ $instance->id }}" {{ (string) old('instance_id', $flow->instance_id) === (string) $instance->id ? 'selected' : '' }}>{{ $instance->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Flow</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $flow->name) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Kategori</label>
                            <div class="d-flex flex-wrap gap-2">
                                @php($selectedCategories = old('categories', $flow->categories ?? ['OTHER']))
                                @foreach($categories as $category)
                                    <label class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category }}" {{ in_array($category, $selectedCategories, true) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $category }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Endpoint URI (opsional)</label>
                            <input type="url" name="endpoint_uri" class="form-control" value="{{ old('endpoint_uri', $flow->endpoint_uri) }}" placeholder="https://your-app.test/wa-flows/endpoint">
                            <div class="text-muted small mt-1">Isi jika Flow memakai data channel / endpoint exchange.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between">
                                <span>Flow JSON</span>
                                <span class="text-muted small">Format resmi Meta</span>
                            </label>
                            <textarea name="flow_json" rows="24" class="form-control font-monospace">{{ old('flow_json', $flow->flow_json) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Simpan Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Meta Status</h3>
            </div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted">Meta Flow ID</span><div><code>{{ $flow->meta_flow_id ?: '-' }}</code></div></div>
                <div class="mb-2"><span class="text-muted">Status</span><div>{{ strtoupper($flow->status ?? 'draft') }}</div></div>
                <div class="mb-2"><span class="text-muted">JSON Version</span><div>{{ $flow->json_version ?: '-' }}</div></div>
                <div class="mb-2"><span class="text-muted">Data API Version</span><div>{{ $flow->data_api_version ?: '-' }}</div></div>
                @if($flow->preview_url)
                    <div class="mb-2">
                        <span class="text-muted">Preview</span>
                        <div><a href="{{ $flow->preview_url }}" target="_blank" rel="noopener noreferrer">Open Preview</a></div>
                        <div class="text-muted small">Expires: {{ optional($flow->preview_expires_at)->format('d M Y H:i') ?: '-' }}</div>
                    </div>
                @endif
                @if($flow->last_sync_error)
                    <div class="alert alert-danger mb-0">
                        {{ $flow->last_sync_error }}
                    </div>
                @endif
            </div>
            @if($isEdit)
                <div class="card-footer d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('whatsapp-api.flows.sync', $flow) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">Sync to Meta</button>
                    </form>
                    <form method="POST" action="{{ route('whatsapp-api.flows.refresh', $flow) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary" {{ !$flow->meta_flow_id ? 'disabled' : '' }}>Refresh</button>
                    </form>
                    <form method="POST" action="{{ route('whatsapp-api.flows.publish', $flow) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-success" {{ !$flow->meta_flow_id ? 'disabled' : '' }}>Publish</button>
                    </form>
                    <form method="POST" action="{{ route('whatsapp-api.flows.destroy', $flow) }}" onsubmit="return confirm('Hapus flow lokal ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Delete Local</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Validation Errors</h3>
            </div>
            <div class="card-body">
                @if(!empty($flow->validation_errors))
                    <div class="small">
                        @foreach($flow->validation_errors as $error)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold text-danger">{{ data_get($error, 'error') ?: 'ERROR' }}</div>
                                <div>{{ data_get($error, 'message') ?: '-' }}</div>
                                @if(data_get($error, 'line_start'))
                                    <div class="text-muted">Line {{ data_get($error, 'line_start') }} - {{ data_get($error, 'line_end') }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">Belum ada validation error.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
