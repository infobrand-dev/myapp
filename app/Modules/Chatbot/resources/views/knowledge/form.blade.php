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

