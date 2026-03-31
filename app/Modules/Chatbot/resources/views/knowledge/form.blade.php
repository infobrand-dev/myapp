@extends('layouts.admin')

@section('content')
@php $isEdit = $document->exists; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} Dokumen Referensi</h2>
        <div class="text-muted small">Chatbot: {{ $account->name }}</div>
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
                    <input type="text" name="title" class="form-control" value="{{ old('title', $document->title) }}" required placeholder="Contoh: FAQ Pengiriman, Katalog Produk A">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sumber</label>
                    <input type="text" name="source" class="form-control" value="{{ old('source', data_get($document->metadata, 'source')) }}" placeholder="manual / faq / sop">
                    <div class="form-hint">Opsional. Untuk memudahkan pengelompokan dokumen.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', data_get($document->metadata, 'category')) }}" placeholder="produk / billing / operasional">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bahasa</label>
                    <select name="language" class="form-select">
                        <option value="id" {{ old('language', data_get($document->metadata, 'language', 'id')) === 'id' ? 'selected' : '' }}>Indonesia</option>
                        <option value="en" {{ old('language', data_get($document->metadata, 'language', 'id')) === 'en' ? 'selected' : '' }}>English</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', data_get($document->metadata, 'status', 'active')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prioritas</label>
                    <input type="number" min="1" max="10" name="priority" class="form-control" value="{{ old('priority', data_get($document->metadata, 'priority', 5)) }}">
                    <div class="form-hint">1 = terendah, 10 = tertinggi. Dokumen prioritas tinggi lebih sering dipilih bot.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Konten</label>
                    <textarea name="content" rows="18" class="form-control" required placeholder="Tulis konten dokumen di sini. Gunakan paragraf terpisah untuk setiap topik agar bot lebih mudah menemukan informasi yang relevan.">{{ old('content', $document->content) }}</textarea>
                    <div class="form-hint">Tulis satu topik per paragraf. Semakin jelas dan terstruktur, semakin akurat jawaban bot.</div>
                </div>

                {{-- Advanced Settings (collapsible) --}}
                <div class="col-12">
                    <details class="border rounded p-3">
                        <summary class="fw-semibold" style="cursor:pointer;">
                            Pengaturan Lanjutan
                            <span class="text-muted small ms-2 fw-normal">ukuran potongan teks</span>
                        </summary>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label">Ukuran Potongan Teks</label>
                                <div class="input-group">
                                    <input type="number" min="300" max="1200" name="chunk_size" class="form-control" value="{{ old('chunk_size', 600) }}">
                                    <span class="input-group-text">karakter</span>
                                </div>
                                <div class="form-hint">Dokumen akan dipotong-potong sebelum disimpan. Nilai default 600 sudah optimal untuk sebagian besar kasus.</div>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('chatbot.knowledge.index', $account) }}" class="btn btn-outline-secondary">Batal</a>
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
