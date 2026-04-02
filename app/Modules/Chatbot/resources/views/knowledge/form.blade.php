@extends('layouts.admin')

@section('title', ($document->exists ? 'Edit' : 'Tambah') . ' Dokumen Referensi')

@section('content')
@php $isEdit = $document->exists; @endphp

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Chatbot · {{ $account->name }}</div>
        <h2 class="page-title">{{ $isEdit ? 'Edit' : 'Tambah' }} Dokumen Referensi</h2>
        <div class="text-muted small mt-1">Dokumen ini akan digunakan bot sebagai referensi saat menjawab pertanyaan.</div>
    </div>
    <a href="{{ route('chatbot.knowledge.index', $account) }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Kembali
    </a>
</div>

<form method="POST"
      action="{{ $isEdit ? route('chatbot.knowledge.update', [$account, $document]) : route('chatbot.knowledge.store', $account) }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Dokumen</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label">Judul <span class="text-danger">*</span></label>
                    <input type="text" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $document->title) }}"
                           required
                           placeholder="Contoh: FAQ Pengiriman, Katalog Produk A">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sumber</label>
                    <input type="text" name="source"
                           class="form-control @error('source') is-invalid @enderror"
                           value="{{ old('source', data_get($document->metadata, 'source')) }}"
                           placeholder="manual / faq / sop">
                    @error('source')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Opsional. Untuk memudahkan pengelompokan dokumen.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="category"
                           class="form-control @error('category') is-invalid @enderror"
                           value="{{ old('category', data_get($document->metadata, 'category')) }}"
                           placeholder="produk / billing / operasional">
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Bahasa</label>
                    <select name="language" class="form-select @error('language') is-invalid @enderror">
                        <option value="id" {{ old('language', data_get($document->metadata, 'language', 'id')) === 'id' ? 'selected' : '' }}>Indonesia</option>
                        <option value="en" {{ old('language', data_get($document->metadata, 'language', 'id')) === 'en' ? 'selected' : '' }}>English</option>
                    </select>
                    @error('language')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach(['active' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', data_get($document->metadata, 'status', 'active')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Prioritas</label>
                    <input type="number" min="1" max="10" name="priority"
                           class="form-control @error('priority') is-invalid @enderror"
                           value="{{ old('priority', data_get($document->metadata, 'priority', 5)) }}">
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">1 = terendah, 10 = tertinggi. Dokumen prioritas tinggi lebih sering dipilih bot.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Konten <span class="text-danger">*</span></label>
                    <textarea name="content" rows="18"
                              class="form-control @error('content') is-invalid @enderror"
                              required
                              placeholder="Tulis konten dokumen di sini. Gunakan paragraf terpisah untuk setiap topik agar bot lebih mudah menemukan informasi yang relevan.">{{ old('content', $document->content) }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Tulis satu topik per paragraf. Semakin jelas dan terstruktur, semakin akurat jawaban bot.</div>
                </div>

                {{-- Pengaturan Lanjutan --}}
                <div class="col-12">
                    <a class="d-flex align-items-center gap-1 fw-semibold text-body text-decoration-none"
                       data-bs-toggle="collapse" href="#advanced-doc-settings" role="button" aria-expanded="false">
                        <i class="ti ti-chevron-right" style="transition:transform .2s;" id="doc-advanced-chevron"></i>
                        Pengaturan Lanjutan
                        <span class="text-muted small ms-1 fw-normal">ukuran potongan teks</span>
                    </a>
                    <div class="collapse mt-3" id="advanced-doc-settings">
                        <div class="border rounded p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Ukuran Potongan Teks</label>
                                    <div class="input-group">
                                        <input type="number" min="300" max="1200" name="chunk_size"
                                               class="form-control @error('chunk_size') is-invalid @enderror"
                                               value="{{ old('chunk_size', 600) }}">
                                        <span class="input-group-text">karakter</span>
                                    </div>
                                    @error('chunk_size')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint">Dokumen akan dipotong-potong sebelum disimpan. Nilai default 600 sudah optimal untuk sebagian besar kasus.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('chatbot.knowledge.index', $account) }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var collapseEl = document.getElementById('advanced-doc-settings');
    var chevron = document.getElementById('doc-advanced-chevron');
    if (collapseEl && chevron) {
        collapseEl.addEventListener('show.bs.collapse', function () { chevron.style.transform = 'rotate(90deg)'; });
        collapseEl.addEventListener('hide.bs.collapse', function () { chevron.style.transform = 'rotate(0deg)'; });
    }
})();
</script>
@endpush

@endsection
