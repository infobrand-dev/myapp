@extends('layouts.admin')

@section('content')
@php $isEdit = $document->exists; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} Dokumen Knowledge</h2>
        <div class="text-muted small">Akun: {{ $account->name }}</div>
    </div>
    <a href="{{ route('chatbot.knowledge.index', $account) }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('chatbot.knowledge.update', [$account, $document]) : route('chatbot.knowledge.store', $account) }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $document->title) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Source</label>
                    <input type="text" name="source" class="form-control" value="{{ old('source', data_get($document->metadata, 'source')) }}" placeholder="manual / faq / sop">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', data_get($document->metadata, 'category')) }}" placeholder="produk / billing / operasional">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bahasa</label>
                    <input type="text" name="language" class="form-control" value="{{ old('language', data_get($document->metadata, 'language', 'id')) }}" placeholder="id / en">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', data_get($document->metadata, 'status', 'active')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prioritas</label>
                    <input type="number" min="1" max="10" name="priority" class="form-control" value="{{ old('priority', data_get($document->metadata, 'priority', 5)) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Chunk Size</label>
                    <input type="number" min="300" max="1200" name="chunk_size" class="form-control" value="{{ old('chunk_size', 600) }}">
                    <div class="form-hint">Karakter per chunk.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Konten</label>
                    <textarea name="content" rows="18" class="form-control" required>{{ old('content', $document->content) }}</textarea>
                    <div class="form-hint">Gunakan format rapi per paragraf agar retrieval lebih akurat.</div>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
