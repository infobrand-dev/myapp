@extends('layouts.admin')

@section('content')
<form method="POST" action="{{ route('email-marketing.update', $campaign) }}" id="campaign-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="body_html" id="body_html">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Email Campaign</h2>
            <div class="text-muted small">Subject dan isi email dapat disesuaikan, kirim sekarang atau jadwalkan.</div>
        </div>
        <div class="btn-list">
            <button type="submit" name="action" value="send" class="btn btn-primary">Send Now</button>
            <button type="submit" name="action" value="schedule" class="btn btn-outline-primary">Schedule</button>
            <button type="submit" name="action" value="save" class="btn btn-outline-secondary">Save Draft</button>
            <a href="{{ route('email-marketing.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3 align-items-end mb-2">
                <div class="col-md-9">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $campaign->subject) }}">
                    @error('subject') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Schedule at</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ optional($campaign->scheduled_at)->format('Y-m-d\\TH:i') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Filter Recipients</label>
                <div id="filter-rows" class="mb-2">
                    @php $filterRows = $filters ?? collect([['field'=>'email','operator'=>'contains','value'=>'']]); @endphp
                    @foreach($filterRows as $idx => $filter)
                        <div class="d-flex gap-2 align-items-center mb-2 filter-row">
                            <select name="filters[{{$idx}}][field]" class="form-select form-select-sm filter-input" style="max-width:140px;">
                                <option value="email" {{ ($filter['field'] ?? '') === 'email' ? 'selected' : '' }}>Email</option>
                                <option value="name" {{ ($filter['field'] ?? '') === 'name' ? 'selected' : '' }}>Name</option>
                                <option value="company" {{ ($filter['field'] ?? '') === 'company' ? 'selected' : '' }}>Company</option>
                            </select>
                            <select name="filters[{{$idx}}][operator]" class="form-select form-select-sm filter-input" style="max-width:140px;">
                                <option value="contains" {{ ($filter['operator'] ?? '') === 'contains' ? 'selected' : '' }}>contains</option>
                                <option value="not_contains" {{ ($filter['operator'] ?? '') === 'not_contains' ? 'selected' : '' }}>not contains</option>
                                <option value="equals" {{ ($filter['operator'] ?? '') === 'equals' ? 'selected' : '' }}>equals</option>
                                <option value="starts_with" {{ ($filter['operator'] ?? '') === 'starts_with' ? 'selected' : '' }}>starts with</option>
                            </select>
                            <input type="text" name="filters[{{$idx}}][value]" class="form-control form-control-sm filter-input" placeholder="value" value="{{ $filter['value'] ?? '' }}">
                            <button class="btn btn-link text-danger btn-sm remove-row" type="button">Hapus</button>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="add-filter">Tambah Rule</button>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="apply-filter">Terapkan Filter</button>
                    <span class="badge bg-azure-lt text-azure">Matches: {{ $matchCount }}</span>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label">Body Mail</label>
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
        selectorManager: { appendTo: '' },
        styleManager: { appendTo: '' },
    });

    const bm = editor.BlockManager;
    bm.add('hero', { label: 'Hero', content: '<section style="padding:32px;background:#f4f6fb;text-align:center;"><h1>Judul Besar</h1><p>Paragraf singkat di sini.</p><a style="display:inline-block;padding:10px 18px;background:#206bc4;color:#fff;text-decoration:none;border-radius:6px;">Call To Action</a></section>' });
    bm.add('cta-btn', { label: 'Button', content: '<a style="display:inline-block;padding:10px 18px;background:#0f9f6e;color:#fff;text-decoration:none;border-radius:6px;">Tombol</a>' });
    bm.add('two-col', { label: '2 Kolom', content: '<table style="width:100%;"><tr><td style="width:50%;padding:10px;">Kolom kiri</td><td style="width:50%;padding:10px;">Kolom kanan</td></tr></table>' });
    bm.add('list', { label: 'List', content: '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>' });
    bm.add('image', { label: 'Image', content: '<img src=\"https://placehold.co/600x200\" alt=\"Image\" style=\"max-width:100%;height:auto;\">' });
    bm.add('spacer', { label: 'Spacer', content: '<div style=\"height:24px;\"></div>' });

    const form = document.getElementById('campaign-form');
    form?.addEventListener('submit', function () {
        document.getElementById('body_html').value = editor.getHtml();
    });

    editor.on('canvas:ready', () => {
        const css = "body{font-family:Arial, Helvetica, sans-serif;} a{color:#206bc4;} h1,h2,h3{font-family:Arial, Helvetica, sans-serif;}";
        const doc = editor.Canvas.getDocument();
        const styleEl = doc.createElement('style');
        styleEl.innerHTML = css;
        doc.head.appendChild(styleEl);
    });

    const filterWrap = document.getElementById('filter-rows');
    const addBtn = document.getElementById('add-filter');
    const applyBtn = document.getElementById('apply-filter');

    addBtn?.addEventListener('click', () => {
        const idx = filterWrap.querySelectorAll('.filter-row').length;
        const div = document.createElement('div');
        div.className = 'd-flex gap-2 align-items-center mb-2 filter-row';
        div.innerHTML = `
            <select name="filters[${idx}][field]" class="form-select form-select-sm filter-input" style="max-width:140px;">
                <option value="email">Email</option>
                <option value="name">Name</option>
                <option value="company">Company</option>
            </select>
            <select name="filters[${idx}][operator]" class="form-select form-select-sm filter-input" style="max-width:140px;">
                <option value="contains">contains</option>
                <option value="not_contains">not contains</option>
                <option value="equals">equals</option>
                <option value="starts_with">starts with</option>
            </select>
            <input type="text" name="filters[${idx}][value]" class="form-control form-control-sm filter-input" placeholder="value">
            <button class="btn btn-link text-danger btn-sm remove-row" type="button">Hapus</button>
        `;
        filterWrap.appendChild(div);
    });

    filterWrap?.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-row');
        if(!btn) return;
        btn.parentElement.remove();
    });

    function submitFiltersOnly() {
        const form = document.getElementById('campaign-form');
        const action = form.getAttribute('action');
        const params = new URLSearchParams(new FormData(form));
        // use GET to refresh matches without saving
        window.location = action + '?' + params.toString();
    }

    applyBtn?.addEventListener('click', submitFiltersOnly);

    filterWrap?.addEventListener('change', (e) => {
        if (e.target.classList.contains('filter-input')) {
            submitFiltersOnly();
        }
    });
</script>
@endpush
