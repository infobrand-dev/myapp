@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Email Campaign</h2>
        <div class="text-muted small">Subject dan isi email dapat disesuaikan, kirim sekarang atau jadwalkan.</div>
    </div>
    <a href="{{ route('email-marketing.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<form method="POST" action="{{ route('email-marketing.update', $campaign) }}" id="campaign-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="body_html" id="body_html">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" name="action" value="send" class="btn btn-primary">Send Now</button>
                <button type="submit" name="action" value="schedule" class="btn btn-outline-primary">Schedule</button>
                <button type="submit" name="action" value="save" class="btn btn-outline-secondary">Save Draft</button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 small text-muted">Schedule at</label>
                <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" value="{{ optional($campaign->scheduled_at)->format('Y-m-d\\TH:i') }}">
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Subject (sekaligus nama campaign)</label>
                <input type="text" name="subject" class="form-control" value="{{ old('subject', $campaign->subject) }}" required>
                @error('subject') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Recipients (ambil dari Contacts)</label>
                <select name="contact_ids[]" class="form-select" multiple size="8">
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ in_array($contact->id, $campaign->recipients->pluck('contact_id')->all()) ? 'selected' : '' }}>
                            {{ $contact->name }} — {{ $contact->email }}
                        </option>
                    @endforeach
                </select>
                <div class="text-muted small mt-1">Gunakan Ctrl/Cmd + klik untuk memilih banyak kontak. Kontak baru bisa ditambah lewat module Contacts.</div>
            </div>

            <div class="mb-2">
                <label class="form-label">Body Mail (GrapeJS)</label>
                <div id="gjs" style="height: 560px; border:1px solid #e5e7eb;">{!! $campaign->body_html !!}</div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://unpkg.com/grapesjs"></script>
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<script>
    const editor = grapesjs.init({
        container: '#gjs',
        height: '560px',
        fromElement: true,
        storageManager: false,
    });

    const bm = editor.BlockManager;
    bm.add('hero', { label: 'Hero', content: '<section style="padding:32px;background:#f4f6fb;text-align:center;"><h1>Judul Besar</h1><p>Paragraf singkat di sini.</p><a style="display:inline-block;padding:10px 18px;background:#206bc4;color:#fff;text-decoration:none;border-radius:6px;">Call To Action</a></section>' });
    bm.add('cta-btn', { label: 'Button', content: '<a style="display:inline-block;padding:10px 18px;background:#0f9f6e;color:#fff;text-decoration:none;border-radius:6px;">Tombol</a>' });
    bm.add('two-col', { label: '2 Kolom', content: '<table style="width:100%;"><tr><td style="width:50%;padding:10px;">Kolom kiri</td><td style="width:50%;padding:10px;">Kolom kanan</td></tr></table>' });
    bm.add('list', { label: 'List', content: '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>' });
    bm.add('image', { label: 'Image', content: '<img src="https://placehold.co/600x200" alt="Image" style="max-width:100%;height:auto;">' });
    bm.add('spacer', { label: 'Spacer', content: '<div style="height:24px;"></div>' });

    const form = document.getElementById('campaign-form');
    form?.addEventListener('submit', function () {
        document.getElementById('body_html').value = editor.getHtml();
    });
</script>
@endpush
