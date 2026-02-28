@extends('layouts.admin')

@section('content')
@php
    $isEdit = $template->exists;
    $components = collect($template->components ?? []);
    $headerComp = $components->firstWhere('type','header');
    $footerComp = $components->firstWhere('type','footer');
    $buttons = $components->where('type','button')->values();
    $firstSub = data_get($buttons->first(), 'sub_type');
    $detectMode = $firstSub === 'url' || $firstSub === 'phone_number' ? 'cta' : ($buttons->isNotEmpty() ? 'quick_reply' : 'none');
    $mode = old('button_mode', $detectMode);
    $headerType = strtolower(old('header_type', data_get($headerComp, 'format', 'none')));
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} WA Template</h2>
        <div class="text-muted small">Sesuai aturan Meta: header, body, dan tombol tervalidasi.</div>
    </div>
    <a href="{{ route('whatsapp-api.templates.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $isEdit ? route('whatsapp-api.templates.update', $template) : route('whatsapp-api.templates.store') }}">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    {{-- Template Info --}}
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Template Info</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input class="form-control" name="name" value="{{ old('name', $template->name) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Language</label>
                                @php
                                    $locales = [
                                        ['value' => 'id', 'label' => 'Indonesia (id)'],
                                        ['value' => 'en', 'label' => 'English (en)'],
                                    ];
                                    $langValue = old('language', $template->language);
                                @endphp
                                <select class="form-select" name="language" required>
                                    <option value="">Pilih locale</option>
                                    @foreach($locales as $loc)
                                        <option value="{{ $loc['value'] }}" {{ $langValue === $loc['value'] ? 'selected' : '' }}>
                                            {{ $loc['label'] }}
                                        </option>
                                    @endforeach
                                    @if($langValue && !collect($locales)->pluck('value')->contains($langValue))
                                        <option value="{{ $langValue }}" selected>{{ $langValue }} (custom)</option>
                                    @endif
                                </select>
                                <div class="text-muted small">Meta menerima kode bahasa (id/en).</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    @foreach(['utility','marketing','authentication'] as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $template->category) === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <input type="hidden" name="status" value="{{ old('status', $template->status ?: 'draft') }}">
                            <div class="col-md-3">
                                <label class="form-label">Account (WA Cloud)</label>
                                @php
                                    $nsValue = old('namespace', $template->namespace);
                                    $selectedInstance = old('instance_id');
                                @endphp
                                <select class="form-select mb-2" id="instance_select" name="instance_id" required>
                                    <option value="">Pilih account</option>
                                    @foreach(($instances ?? collect()) as $inst)
                                        @php $ns = $inst->cloud_business_account_id; @endphp
                                        <option value="{{ $inst->id }}" data-namespace="{{ $ns }}" {{ (string)$selectedInstance === (string)$inst->id || (!$selectedInstance && $nsValue === $ns) ? 'selected' : '' }}>
                                            {{ $inst->name }} ({{ $ns }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="namespace" id="namespace_input" value="{{ $nsValue }}">
                            </div>
                        </div>
                    </div>

                    <hr>
                    {{-- Header --}}
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Header</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Header Type</label>
                                <select name="header_type" id="header_type" class="form-select">
                                    @foreach(['none'=>'None','text'=>'Text (<=60)','image'=>'Image','document'=>'Document','video'=>'Video'] as $val=>$label)
                                        <option value="{{ $val }}" {{ $headerType === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 header-text-wrap" style="{{ $headerType === 'text' ? '' : 'display:none;' }}">
                                <label class="form-label">Header text</label>
                                <input class="form-control" name="header_text" id="header_text" maxlength="60" value="{{ old('header_text', data_get($headerComp, 'parameters.0.text', '')) }}">
                                <div class="d-flex justify-content-between text-muted small"><span>Limit 60</span><span id="header_count"></span></div>
                            </div>
                            <div class="col-md-8 header-media-wrap" style="{{ in_array($headerType, ['image','document','video']) ? '' : 'display:none;' }}">
                                <label class="form-label">Header media URL (isi saat kirim)</label>
                                <input class="form-control" name="header_media_url" placeholder="https://..." value="{{ old('header_media_url') }}">
                                <div class="text-muted small">Kosongkan header text jika media dipilih.</div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    {{-- Body --}}
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Body</div>
                        <label class="form-label">Body (gunakan &#123;&#123;1&#125;&#125;, &#123;&#123;2&#125;&#125;)</label>
                        <textarea class="form-control" rows="4" name="body" id="body_input" maxlength="1024" required>{{ old('body', $template->body) }}</textarea>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Maks 1024 karakter</span><span id="body_count"></span>
                        </div>
                    </div>

                    <hr>
                    {{-- Footer --}}
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Footer</div>
                        <label class="form-label">Footer text (opsional)</label>
                        <input class="form-control" name="footer_text" value="{{ old('footer_text', data_get($footerComp, 'text', '')) }}">
                    </div>

                    <hr>
                    {{-- Buttons --}}
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Buttons</div>
                        <label class="form-label d-flex align-items-center justify-content-between">
                            <span>Ikuti aturan Meta</span>
                            <span class="text-muted small">Quick Reply max 3, CTA: 1 URL + 1 Phone</span>
                        </label>
                        <div class="mb-2">
                            <select name="button_mode" id="button_mode" class="form-select">
                                @foreach(['none'=>'Tidak ada','quick_reply'=>'Quick Reply','cta'=>'CTA (URL + Phone)'] as $val=>$label)
                                    <option value="{{ $val }}" {{ $mode === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="qr-buttons" id="qr_buttons" style="{{ $mode === 'quick_reply' ? '' : 'display:none;' }}">
                            @for($i=0;$i<3;$i++)
                                @php $btn = ($mode === 'quick_reply') ? ($buttons[$i] ?? null) : null; @endphp
                                <div class="row g-2 mb-2">
                                    <div class="col-md-12">
                                        <input class="form-control" name="qr_label[]" placeholder="Quick reply {{ $i+1 }}" value="{{ old('qr_label.'.$i, data_get($btn, 'parameters.0.text', '')) }}" maxlength="25">
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="cta-buttons" id="cta_buttons" style="{{ $mode === 'cta' ? '' : 'display:none;' }}">
                            @php
                                $urlBtn = $buttons->firstWhere('sub_type','url');
                                $phoneBtn = $buttons->firstWhere('sub_type','phone_number');
                            @endphp
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <input class="form-control" name="cta_url_label" placeholder="URL label" value="{{ old('cta_url_label', data_get($urlBtn, 'parameters.0.text', '')) }}" maxlength="25">
                                </div>
                                <div class="col-md-6">
                                    <input class="form-control" name="cta_url_value" placeholder="https://..." value="{{ old('cta_url_value', data_get($urlBtn, 'url')) }}">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input class="form-control" name="cta_phone_label" placeholder="Call label" value="{{ old('cta_phone_label', data_get($phoneBtn, 'parameters.0.text', '')) }}" maxlength="25">
                                </div>
                                <div class="col-md-6">
                                    <input class="form-control" name="cta_phone_value" placeholder="+62..." value="{{ old('cta_phone_value', data_get($phoneBtn, 'phone_number')) }}">
                                    <div class="text-muted small">Format E.164, contoh +62812...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2"><button class="btn btn-secondary" type="submit" name="action" value="draft">Simpan Draft</button><button class="btn btn-primary" type="submit" name="action" value="submit">Submit Approval</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-0">
                <div class="bg-white border-bottom px-3 py-2 d-flex align-items-center rounded-top">
                    <div class="avatar avatar-sm bg-success text-white rounded-circle me-2">WA</div>
                    <div>
                        <div class="fw-bold">WhatsApp Business</div>
                        <div class="text-muted small">online</div>
                    </div>
                </div>
                <div class="p-3" style="background: #e5ddd5;">
                    <div class="d-flex mb-1">
                        <div class="bg-white rounded px-3 py-2 shadow-sm" id="preview-bubble" style="max-width: 85%;">
                            <div class="fw-bold mb-1 text-muted small" id="preview-header" style="display:none;"></div>
                            <div id="preview-body">(isi body)</div>
                            <div class="mt-1 text-muted small" id="preview-footer" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="preview-buttons" style="display:none; max-width: 85%;">
                        <!-- buttons -->
                    </div>
                </div>
                <div class="border-top px-3 py-2 bg-white rounded-bottom">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="form-control bg-light" disabled style="height: 42px;">Tulis pesan...</div>
                        </div>
                        <button type="button" class="btn btn-success ms-2" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 14l11 -11" />
                                <path d="M21 3l-6.5 18a0.55 .55 0 0 1 -1.05 0l-3.5 -7.5l-7.5 -3.5a0.55 .55 0 0 1 0 -1.05l18 -6.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bodyInput = document.getElementById('body_input');
    const headerTypeSelect = document.getElementById('header_type');
    const headerEl = document.getElementById('preview-header');
    const bodyEl = document.getElementById('preview-body');
    const footerEl = document.getElementById('preview-footer');
    const buttonsWrap = document.getElementById('preview-buttons');
    const headerInput = document.getElementById('header_text');
    const footerInput = document.querySelector('input[name="footer_text"]');
    const modeSelect = document.getElementById('button_mode');
    const qrLabels = document.querySelectorAll('input[name="qr_label[]"]');
    const ctaUrlLabel = document.querySelector('input[name="cta_url_label"]');
    const ctaUrlValue = document.querySelector('input[name="cta_url_value"]');
    const ctaPhoneLabel = document.querySelector('input[name="cta_phone_label"]');
    const ctaPhoneValue = document.querySelector('input[name="cta_phone_value"]');
    const qrWrap = document.getElementById('qr_buttons');
    const ctaWrap = document.getElementById('cta_buttons');
    const headerTextWrap = document.querySelector('.header-text-wrap');
    const headerMediaWrap = document.querySelector('.header-media-wrap');
    const bodyCount = document.getElementById('body_count');
    const headerCount = document.getElementById('header_count');
    const nsSelect = document.getElementById('instance_select');
    const nsInput = document.getElementById('namespace_input');

    const escapeHtml = (text) => text.replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m] || m));

    const formatInline = (text) => {
        let t = escapeHtml(text);
        t = t.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
        t = t.replace(/_(.*?)_/g, '<em>$1</em>');
        t = t.replace(/~(.*?)~/g, '<s>$1</s>');
        t = t.replace(/`(.*?)`/g, '<code>$1</code>');
        return t;
    };

    function toggleSections() {
        const mode = modeSelect.value;
        qrWrap.style.display = mode === 'quick_reply' ? '' : 'none';
        ctaWrap.style.display = mode === 'cta' ? '' : 'none';

        const hType = headerTypeSelect.value;
        headerTextWrap.style.display = hType === 'text' ? '' : 'none';
        headerMediaWrap.style.display = ['image','document','video'].includes(hType) ? '' : 'none';
        if (hType !== 'text') headerInput.value = headerInput.value; // keep but hidden
    }

    function syncNamespace() {
        if (!nsSelect || !nsInput) return;
        const ns = nsSelect.selectedOptions[0]?.dataset.namespace || '';
        nsInput.value = ns;
    }

    function render() {
        const body = bodyInput.value || '';
        bodyEl.innerHTML = formatInline(body.replace(/\{\{(\d+)\}\}/g, '____'));
        if (bodyCount) bodyCount.textContent = `${body.length}/1024`;

        const headerTxt = headerInput?.value || '';
        if (headerTypeSelect.value === 'text' && headerTxt.trim()) {
            headerEl.innerHTML = formatInline(headerTxt);
            headerEl.style.display = 'block';
        } else {
            headerEl.style.display = 'none';
        }
        if (headerCount) headerCount.textContent = `${headerTxt.length}/60`;

        const footerTxt = footerInput?.value || '';
        if (footerTxt.trim()) {
            footerEl.innerHTML = formatInline(footerTxt);
            footerEl.style.display = 'block';
        } else {
            footerEl.style.display = 'none';
        }

        buttonsWrap.innerHTML = '';
        let anyBtn = false;
        if (modeSelect.value === 'quick_reply') {
            qrLabels.forEach(inp => {
                const label = inp.value || '';
                if (!label.trim()) return;
                anyBtn = true;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-light border text-start py-2 px-3 rounded';
                btn.style.color = '#128C7E';
                btn.style.borderColor = '#d6d6d6';
                btn.style.whiteSpace = 'normal';
                btn.style.wordBreak = 'break-word';
                btn.style.flex = '1 1 45%';
                btn.textContent = label;
                buttonsWrap.appendChild(btn);
            });
        } else if (modeSelect.value === 'cta') {
            if ((ctaUrlLabel?.value || '').trim()) {
                anyBtn = true;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-light border text-start py-2 px-3 rounded';
                btn.style.color = '#128C7E';
                btn.style.borderColor = '#d6d6d6';
                btn.style.whiteSpace = 'normal';
                btn.style.wordBreak = 'break-word';
                btn.style.flex = '1 1 45%';
                btn.textContent = ctaUrlLabel.value;
                buttonsWrap.appendChild(btn);
            }
            if ((ctaPhoneLabel?.value || '').trim()) {
                anyBtn = true;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-light border text-start py-2 px-3 rounded';
                btn.style.color = '#128C7E';
                btn.style.borderColor = '#d6d6d6';
                btn.style.whiteSpace = 'normal';
                btn.style.wordBreak = 'break-word';
                btn.style.flex = '1 1 45%';
                btn.textContent = ctaPhoneLabel.value;
                buttonsWrap.appendChild(btn);
            }
        }
        buttonsWrap.style.display = anyBtn ? 'flex' : 'none';
    }

    bodyInput.addEventListener('input', render);
    headerInput.addEventListener('input', render);
    headerTypeSelect.addEventListener('change', () => { toggleSections(); render(); });
    footerInput.addEventListener('input', render);
    modeSelect.addEventListener('change', () => { toggleSections(); render(); });
    qrLabels.forEach(el => el.addEventListener('input', render));
    ctaUrlLabel?.addEventListener('input', render);
    ctaUrlValue?.addEventListener('input', render);
    ctaPhoneLabel?.addEventListener('input', render);
    ctaPhoneValue?.addEventListener('input', render);
    nsSelect?.addEventListener('change', syncNamespace);

    toggleSections();
    syncNamespace();
    render();
});
</script>
@endpush
@endsection

