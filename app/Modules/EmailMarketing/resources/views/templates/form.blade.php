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
                    <div id="gjs" style="border:1px solid #e5e7eb;">{!! old('html', $template->html) !!}</div>
                    <input type="hidden" name="html" id="html_input" value="{{ old('html', $template->html) }}">
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://unpkg.com/grapesjs@0.21.10/dist/grapes.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/grapesjs@0.21.10/dist/css/grapes.min.css">
<script>
    (function() {
        const paperSelect = document.querySelector('select[name=\"paper_size\"]');
        const preset = {
            'A4': { w: 794, h: 1123, name: 'A4' },
            'A4-landscape': { w: 1123, h: 794, name: 'A4-landscape' },
            'Letter': { w: 816, h: 1056, name: 'Letter' },
            'Letter-landscape': { w: 1056, h: 816, name: 'Letter-landscape' },
        };
        const editor = grapesjs.init({
            container: '#gjs',
            height: '820px',
            width: '100%',
            storageManager: false,
            fromElement: true,
            canvas: {
                styles: [
                    'https://unpkg.com/grapesjs@0.21.10/dist/css/grapes.min.css',
                    `data:text/css,body{background:#f2f4f7;margin:0;padding:24px;font-family:Arial,sans-serif;} .page{background:#fff;margin:0 auto;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:32px 36px;border-radius:10px;}`
                ],
                scripts: [],
            },
            selectorManager: { appendTo: '' },
            styleManager: { appendTo: '' },
        });

        const applySize = () => {
            const size = preset[paperSelect.value] || preset['A4'];
            const canvas = editor.Canvas.getElement();
            const frame = editor.Canvas.getFrameEl();
            canvas.style.minHeight = size.h + 'px';
            if (frame) {
                frame.style.width = size.w + 'px';
                frame.style.minHeight = size.h + 'px';
                const holder = frame.parentElement;
                if (holder) {
                    holder.style.display = 'flex';
                    holder.style.justifyContent = 'center';
                    holder.style.background = '#f8fafc';
                    holder.style.padding = '12px';
                    holder.style.overflow = 'auto';
                }
            }
            // set editor height a bit larger than paper to avoid cut-off but keep card tidy
            const targetHeight = Math.min(size.h + 120, 900);
            editor.setHeight(targetHeight + 'px');
            if (frame && frame.parentElement) {
                frame.parentElement.style.maxHeight = targetHeight + 'px';
            }
            const canvasDoc = editor.Canvas.getDocument();
            if (canvasDoc && canvasDoc.documentElement) {
                canvasDoc.documentElement.style.setProperty('--paper-width', size.w + 'px');
                canvasDoc.documentElement.style.setProperty('--paper-height', size.h + 'px');
            }
            // wrap content in .page to enforce padding/width
            const comps = editor.getComponents();
            if (comps.length === 0) {
                editor.setComponents(`<div class=\"page\" style=\"min-height:${size.h}px;\">Klik blok di sebelah kiri untuk mulai mendesain.</div>`);
            }
        };
        applySize();
        paperSelect.addEventListener('change', applySize);

        // Blocks library
        const bm = editor.BlockManager;
        bm.add('title', { label: 'Title', content: '<h1 style=\"margin:0 0 12px;\">Judul Besar</h1>' });
        bm.add('subtitle', { label: 'Subtitle', content: '<h3 style=\"margin:0 0 8px;color:#475569;\">Subjudul ringkas</h3>' });
        bm.add('paragraph', { label: 'Paragraph', content: '<p style=\"margin:0 0 12px;line-height:1.6;color:#334155;\">Tulis paragraf di sini.</p>' });
        bm.add('button', { label: 'Button', content: '<a style=\"display:inline-block;padding:12px 18px;background:#206bc4;color:#fff;text-decoration:none;border-radius:8px;\">Call To Action</a>' });
        bm.add('two-col', { label: '2 Columns', content: '<div style=\"display:flex;gap:16px;\"><div style=\"flex:1;\">Kolom kiri</div><div style=\"flex:1;\">Kolom kanan</div></div>' });
        bm.add('list', { label: 'List', content: '<ul style=\"padding-left:20px;margin:0 0 12px;\"><li>Item 1</li><li>Item 2</li></ul>' });
        bm.add('image', { label: 'Image', content: '<img src=\"https://placehold.co/600x200\" alt=\"Image\" style=\"max-width:100%;height:auto;\">' });
        bm.add('spacer', { label: 'Spacer', content: '<div style=\"height:24px;\"></div>' });
        bm.add('table', { label: 'Table', content: '<table style=\"width:100%;border-collapse:collapse;\"><tr><th style=\"border:1px solid #e2e8f0;padding:8px;\">Kolom 1</th><th style=\"border:1px solid #e2e8f0;padding:8px;\">Kolom 2</th></tr><tr><td style=\"border:1px solid #e2e8f0;padding:8px;\">Isi</td><td style=\"border:1px solid #e2e8f0;padding:8px;\">Isi</td></tr></table>' });
        bm.add('info', { label: 'Info Box', content: '<div style=\"background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;\">Masukkan highlight informasi di sini.</div>' });

        const form = document.querySelector('form');
        form.addEventListener('submit', () => {
            document.getElementById('html_input').value = editor.getHtml();
        });
    })();
</script>
@endpush
