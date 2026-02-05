@extends('layouts.admin')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
        <div>
            <h2 class="page-title mb-1">{{ $pageTitle }}</h2>
            <div class="text-secondary">Kode lama tetap aktif; kode baru menjadi utama.</div>
        </div>
        <div class="btn-list">
            <a href="{{ route('shortlinks.index') }}" class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>
    </div>
</div>

<form action="{{ $formAction }}" method="POST">
    @csrf
    @if($formMethod === 'PUT')
        @method('PUT')
    @endif

    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Detail Shortlink</div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nama/Label (opsional)</label>
                        <input type="text" name="title" value="{{ old('title', $shortlink->title) }}" class="form-control" placeholder="Contoh: Landing Page Webinar">
                        @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL Tujuan</label>
                        <input type="url" name="destination_url" value="{{ old('destination_url', $shortlink->destination_url) }}" class="form-control" required placeholder="https://example.com/long-url">
                        @error('destination_url') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kode Utama</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ url('r') }}/</span>
                            <input type="text" name="code" value="{{ old('code', optional($primaryCode)->code ?? $generatedCode) }}" class="form-control" required placeholder="misal: launch-jan">
                        </div>
                        <div class="text-muted small">Kode lama tetap aktif, kode baru akan menjadi kode utama.</div>
                        @error('code') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">UTM Source (opsional)</label>
                                <input type="text" name="utm_source" value="{{ old('utm_source', $shortlink->utm_source) }}" class="form-control" placeholder="newsletter">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">UTM Medium (opsional)</label>
                                <input type="text" name="utm_medium" value="{{ old('utm_medium', $shortlink->utm_medium) }}" class="form-control" placeholder="email">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">UTM Campaign (opsional)</label>
                                <input type="text" name="utm_campaign" value="{{ old('utm_campaign', $shortlink->utm_campaign) }}" class="form-control" placeholder="launch-2026">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">UTM Term (opsional)</label>
                                <input type="text" name="utm_term" value="{{ old('utm_term', $shortlink->utm_term) }}" class="form-control" placeholder="keyword">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">UTM Content (opsional)</label>
                        <input type="text" name="utm_content" value="{{ old('utm_content', $shortlink->utm_content) }}" class="form-control" placeholder="banner-a">
                    </div>

                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', $shortlink->is_active ?? true) ? 'checked' : '' }}>
                        <span class="form-check-label">Aktifkan shortlink</span>
                    </label>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Histori Kode</div>
                </div>
                <div class="card-body">
                    @forelse($codes as $code)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="{{ $code->is_primary ? 'text-success fw-semibold' : '' }}">/r/{{ $code->code }}</div>
                                <div class="text-muted small">Dibuat: {{ $code->created_at }}</div>
                            </div>
                            @if($code->is_primary)
                                <span class="badge bg-success">Utama</span>
                            @endif
                        </div>
                        <hr class="my-2">
                    @empty
                        <div class="text-muted">Belum ada kode tersimpan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
