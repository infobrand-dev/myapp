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
                <div class="col-md-6">
                    <label class="form-label">Paper Size</label>
                    @php $paper = old('paper_size', $template->paper_size ?? 'A4'); @endphp
                    <select name="paper_size" class="form-select">
                        <option value="A4" {{ $paper === 'A4' ? 'selected' : '' }}>A4</option>
                        <option value="A4-landscape" {{ $paper === 'A4-landscape' ? 'selected' : '' }}>A4 Landscape</option>
                        <option value="Letter" {{ $paper === 'Letter' ? 'selected' : '' }}>Letter</option>
                        <option value="Letter-landscape" {{ $paper === 'Letter-landscape' ? 'selected' : '' }}>Letter Landscape</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', $template->description) }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">HTML (WYSIWYG)</label>
                    <div id="gjs" style="height: 700px; border:1px solid #e5e7eb;">{!! old('html', $template->html) !!}</div>
                    <input type="hidden" name="html" id="html_input" value="{{ old('html', $template->html) }}">
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://unpkg.com/grapesjs"></script>
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<script>
    (function() {
        const paperSelect = document.querySelector('select[name=\"paper_size\"]');
        const preset = {
            'A4': { w: 794, h: 1123 },
            'A4-landscape': { w: 1123, h: 794 },
            'Letter': { w: 816, h: 1056 },
            'Letter-landscape': { w: 1056, h: 816 },
        };
        const editor = grapesjs.init({
            container: '#gjs',
            height: '720px',
            width: preset[paperSelect.value].w + 'px',
            storageManager: false,
            fromElement: true,
            canvas: {
                styles: [
                    'https://unpkg.com/grapesjs/dist/css/grapes.min.css'
                ],
            },
            selectorManager: { appendTo: '' },
            styleManager: { appendTo: '' },
        });

        const applySize = () => {
            const size = preset[paperSelect.value] || preset['A4'];
            const canvas = editor.Canvas.getElement();
            canvas.style.maxWidth = size.w + 'px';
            canvas.style.minWidth = size.w + 'px';
            canvas.style.minHeight = size.h + 'px';
        };
        applySize();
        paperSelect.addEventListener('change', applySize);

        const form = document.querySelector('form');
        form.addEventListener('submit', () => {
            document.getElementById('html_input').value = editor.getHtml();
        });
    })();
</script>
@endpush
