@extends('layouts.admin')

@section('content')
<form method="POST" action="{{ $template->exists ? route('email-marketing.templates.update', $template) : route('email-marketing.templates.store') }}">
    @csrf
    @if($template->exists)
        @method('PUT')
    @endif
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">{{ $template->exists ? 'Edit Template' : 'Buat Template' }}</h2>
            <div class="text-muted small">Placeholder: {{ '{' }}{name}}, {{ '{' }}{email}}, {{ '{' }}{unsubscribe}}, {{ '{' }}{track_click}}</div>
        </div>
        <div class="btn-list">
            <a href="{{ route('email-marketing.templates.index') }}" class="btn btn-outline-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Filename</label>
                    <input type="text" name="filename" class="form-control" value="{{ old('filename', $template->filename) }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', $template->description) }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">HTML</label>
                    <textarea name="html" rows="12" class="form-control" required>{{ old('html', $template->html) }}</textarea>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
