@extends('layouts.admin')

@section('content')
@php
    $isEdit = $template->exists;
    $components = collect($template->components ?? []);
    $headerComp = $components->firstWhere('type', 'header');
    $footerComp = $components->firstWhere('type', 'footer');
    $headerType = strtolower(old('header_type', data_get($headerComp, 'format', 'none')));

    $buttonRows = [];
    $buttonsComp = $components->firstWhere('type', 'buttons');
    if (is_array($buttonsComp) && is_array(data_get($buttonsComp, 'buttons'))) {
        foreach ((array) data_get($buttonsComp, 'buttons', []) as $btn) {
            $typeMap = [
                'QUICK_REPLY' => 'quick_reply',
                'URL' => 'url',
                'PHONE_NUMBER' => 'phone_number',
                'COPY_CODE' => 'copy_code',
            ];
            $buttonRows[] = [
                'type' => $typeMap[strtoupper((string) data_get($btn, 'type'))] ?? 'quick_reply',
                'label' => (string) data_get($btn, 'text', ''),
                'url' => (string) data_get($btn, 'url', ''),
                'phone_number' => (string) data_get($btn, 'phone_number', ''),
                'example' => (string) data_get($btn, 'example', ''),
            ];
        }
    }
    if (old('buttons')) {
        $buttonRows = old('buttons');
    }
    if (empty($buttonRows)) {
        $buttonRows[] = ['type' => 'quick_reply', 'label' => '', 'url' => '', 'phone_number' => '', 'example' => ''];
    }
    $headerTextValue = old('header_text', data_get($headerComp, 'text') ?: data_get($headerComp, 'parameters.0.text', ''));
    $placeholderIndexes = \App\Modules\WhatsAppApi\Support\TemplateVariableResolver::placeholderIndexes(
        old('body', $template->body),
        $headerType === 'text' ? $headerTextValue : null
    );
    $variableMappings = old('variable_mappings', $template->variable_mappings ?? []);
@endphp

<style>
.wa-template-wrap .section-title { font-size: .85rem; letter-spacing: .03em; text-transform: uppercase; font-weight: 700; color: #54606c; margin-bottom: .65rem; }
.wa-template-wrap .section-card { border: 1px solid rgba(18, 29, 40, .08); }
.wa-template-wrap .tiny { font-size: .78rem; color: #667781; }
.wa-template-wrap .pill { font-size: .72rem; background: #f1f3f5; border-radius: 999px; padding: .15rem .5rem; color: #52606d; }
.wa-template-wrap .btn-row { border: 1px solid rgba(18, 29, 40, .08); border-radius: .6rem; padding: .6rem; background: #fcfcfd; }
.wa-template-wrap .preview-sticky { position: sticky; top: 1rem; }
.wa-template-wrap .wa-phone { border-radius: 1rem; overflow: hidden; border: 1px solid #d3d8dd; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
.wa-template-wrap .wa-head { background: #1f2c34; color: #e9edef; padding: .65rem .8rem; display: flex; align-items: center; gap: .55rem; }
.wa-template-wrap .wa-av { width: 1.9rem; height: 1.9rem; border-radius: 50%; background: #3a4a55; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; }
.wa-template-wrap .wa-body { min-height: 27rem; padding: 1rem; background: #efeae2; background-image: radial-gradient(rgba(17,27,33,.05) 1px, transparent 1px); background-size: 12px 12px; }
.wa-template-wrap .wa-bubble { background: #fff; border-radius: .85rem; border-top-left-radius: .35rem; padding: .7rem .75rem .55rem; box-shadow: 0 .15rem .45rem rgba(17,27,33,.08); }
.wa-template-wrap .wa-bubble-header { font-weight: 700; margin-bottom: .35rem; color: #1f2c34; }
.wa-template-wrap .wa-bubble-body { line-height: 1.38; color: #111b21; word-break: break-word; white-space: pre-wrap; font-weight: 400 !important; }
.wa-template-wrap .wa-bubble-body strong { font-weight: 700; }
.wa-template-wrap .wa-bubble-body em,
.wa-template-wrap .wa-bubble-body s,
.wa-template-wrap .wa-bubble-body code { font-weight: 400; }
.wa-template-wrap .wa-bubble-footer { margin-top: .45rem; font-size: .78rem; color: #667781; }
.wa-template-wrap .wa-media { border: 1px dashed #c8ccd0; border-radius: .5rem; font-size: .78rem; color: #5b6670; background: #f7f8f8; padding: .45rem .55rem; margin-bottom: .45rem; }
.wa-template-wrap .wa-btns { margin-top: .6rem; display: flex; flex-direction: column; gap: .35rem; }
.wa-template-wrap .wa-btn { border: 1px solid #d6dadd; background: #f5f6f6; border-radius: .55rem; padding: .4rem .55rem; font-size: .8rem; display: flex; justify-content: space-between; color: #0f6f5c; font-weight: 600; }
.wa-template-wrap .wa-btn small { color: #667781; margin-left: .45rem; font-weight: 500; }
.wa-template-wrap .var-row { border: 1px solid rgba(18, 29, 40, .08); border-radius: .6rem; padding: .75rem; background: #fcfcfd; }
.wa-template-wrap .editor-toolbar { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .55rem; }
.wa-template-wrap .editor-toolbar .btn { min-width: 2.4rem; }
</style>

<div class="wa-template-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} WA Template</h2>
            <div class="text-muted small">Pengaturan di kiri, preview WhatsApp realtime di kanan.</div>
        </div>
        <a href="{{ route('whatsapp-api.templates.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    @if($isEdit && $template->last_submit_error)
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Last Submit Error</div>
            <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $template->last_submit_error }}</pre>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('whatsapp-api.templates.update', $template) : route('whatsapp-api.templates.store') }}" id="wa-template-form" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <input type="hidden" name="status" value="{{ old('status', $template->status ?: 'draft') }}">

        <div class="row g-3">
            <div class="col-xl-7 col-lg-7">
                <div class="card section-card mb-3">
                    <div class="card-body">
                        <div class="section-title">Template Info</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Internal</label>
                                <input class="form-control" name="name" value="{{ old('name', $template->name) }}" required>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Meta Name</label>
                                <input class="form-control" name="meta_name" value="{{ old('meta_name', $template->meta_name) }}" placeholder="contoh: idpba_followup_1">
                                @error('meta_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Language</label>
                                @php
                                    $locales = [['value' => 'id', 'label' => 'Indonesia'], ['value' => 'en', 'label' => 'English']];
                                    $langValue = old('language', $template->language);
                                @endphp
                                <select class="form-select" name="language" required>
                                    <option value="">Pilih</option>
                                    @foreach($locales as $loc)
                                        <option value="{{ $loc['value'] }}" {{ $langValue === $loc['value'] ? 'selected' : '' }}>{{ $loc['label'] }} ({{ $loc['value'] }})</option>
                                    @endforeach
                                </select>
                                @error('language') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" required>
                                    @foreach(['utility', 'marketing', 'authentication'] as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $template->category) === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                    @endforeach
                                </select>
                                @error('category') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Account (WA Cloud)</label>
                                @php
                                    $nsValue = old('namespace', $template->namespace);
                                    $selectedInstance = old('instance_id');
                                @endphp
                                <select class="form-select" id="instance_select" name="instance_id" required>
                                    <option value="">Pilih account</option>
                                    @foreach(($instances ?? collect()) as $inst)
                                        @php $ns = $inst->cloud_business_account_id; @endphp
                                        <option value="{{ $inst->id }}" data-namespace="{{ $ns }}" {{ (string) $selectedInstance === (string) $inst->id || (!$selectedInstance && $nsValue === $ns) ? 'selected' : '' }}>
                                            {{ $inst->name }} ({{ $ns }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="namespace" id="namespace_input" value="{{ $nsValue }}">
                                @error('instance_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card section-card mb-3">
                    <div class="card-body">
                        <div class="section-title">Header</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Header Type</label>
                                <select name="header_type" id="header_type" class="form-select">
                                    @foreach(['none' => 'None', 'text' => 'Text', 'image' => 'Image', 'document' => 'Document', 'video' => 'Video'] as $val => $label)
                                        <option value="{{ $val }}" {{ $headerType === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 header-text-wrap" style="{{ $headerType === 'text' ? '' : 'display:none;' }}">
                                <label class="form-label d-flex justify-content-between"><span>Header text</span><span class="pill" id="header-count">0/60</span></label>
                                <div class="editor-toolbar" data-editor-toolbar data-target="header_text">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="*">B</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="_">I</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="~">S</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="`">Code</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-placeholder-target="header_text">+ Placeholder</button>
                                </div>
                                <input class="form-control" name="header_text" id="header_text" maxlength="60" value="{{ $headerTextValue }}">
                                <div class="tiny mt-1">Format teks: <code>*bold*</code>, <code>_italic_</code>, <code>~strike~</code>, <code>`code`</code>.</div>
                                @error('header_text') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8 header-media-wrap" style="{{ in_array($headerType, ['image', 'document', 'video']) ? '' : 'display:none;' }}">
                                @php $existingHeaderMedia = old('header_media_url', data_get($headerComp, 'parameters.0.link', '')); @endphp
                                <label class="form-label">Header media file</label>
                                <input type="file" class="form-control" name="header_media_file" id="header_media_file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.mp4,.mov,.avi,.mkv">
                                <input type="hidden" name="header_media_url" id="header_media_url" value="{{ $existingHeaderMedia }}">
                                @if($existingHeaderMedia)
                                    <div class="tiny mt-1">File saat ini: <a href="{{ $existingHeaderMedia }}" target="_blank" rel="noopener">lihat media</a></div>
                                @endif
                                @error('header_media_file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card section-card mb-3">
                    <div class="card-body">
                        <div class="section-title">Body & Footer</div>
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between"><span>Body</span><span class="pill" id="body-count">0/1024</span></label>
                            <div class="editor-toolbar" data-editor-toolbar data-target="body_input">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="*">B</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="_">I</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="~">S</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-wrap="`">Code</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-placeholder-target="body_input">+ Placeholder</button>
                            </div>
                            <textarea class="form-control" rows="5" name="body" id="body_input" maxlength="1024" required>{{ old('body', $template->body) }}</textarea>
                            <div class="tiny mt-1">Placeholder: <code>@{{1}}</code>, <code>@{{2}}</code>, dst.</div>
                            @error('body') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="form-label d-flex justify-content-between"><span>Footer (opsional)</span><span class="pill" id="footer-count">0/60</span></label>
                            <input class="form-control" name="footer_text" id="footer_input" maxlength="60" value="{{ old('footer_text', data_get($footerComp, 'text', '')) }}">
                            @error('footer_text') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card section-card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title mb-0">Variable Mapping</div>
                            <span class="pill" id="variable-count">{{ count($placeholderIndexes) }} placeholder</span>
                        </div>
                        <div class="tiny mb-2">Setiap placeholder bisa diisi dari free text, field contact, atau field user pengirim. Jika hasil field kosong, fallback default akan dipakai.</div>
                        <div id="variable-mappings-empty" class="text-muted small" style="{{ count($placeholderIndexes) ? 'display:none;' : '' }}">Belum ada placeholder di body/header.</div>
                        <div id="variable-mappings" class="d-grid gap-2"></div>
                        @error('variable_mappings') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="card section-card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-title mb-0">Buttons</div>
                            <button type="button" id="add-button-row" class="btn btn-sm btn-outline-primary">Tambah Button</button>
                        </div>
                        <div class="tiny mb-2">Maksimal total 10 button. Type: Quick Reply, URL, Phone Number, Copy Code.</div>

                        <div id="buttons-container" class="d-grid gap-2">
                            @foreach($buttonRows as $i => $btn)
                                <div class="btn-row" data-row>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label">Type</label>
                                            <select class="form-select form-select-sm btn-type" name="buttons[{{ $i }}][type]">
                                                <option value="quick_reply" {{ ($btn['type'] ?? '') === 'quick_reply' ? 'selected' : '' }}>Quick Reply</option>
                                                <option value="url" {{ ($btn['type'] ?? '') === 'url' ? 'selected' : '' }}>URL</option>
                                                <option value="phone_number" {{ ($btn['type'] ?? '') === 'phone_number' ? 'selected' : '' }}>Phone</option>
                                                <option value="copy_code" {{ ($btn['type'] ?? '') === 'copy_code' ? 'selected' : '' }}>Copy Code</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Label</label>
                                            <input class="form-control form-control-sm btn-label" name="buttons[{{ $i }}][label]" value="{{ $btn['label'] ?? '' }}" maxlength="25">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">URL</label>
                                            <input class="form-control form-control-sm btn-url" name="buttons[{{ $i }}][url]" value="{{ $btn['url'] ?? '' }}" placeholder="https://...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Phone</label>
                                            <input class="form-control form-control-sm btn-phone" name="buttons[{{ $i }}][phone_number]" value="{{ $btn['phone_number'] ?? '' }}" placeholder="+628...">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row">X</button>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Example (URL/Copy Code)</label>
                                            <input class="form-control form-control-sm btn-example" name="buttons[{{ $i }}][example]" value="{{ $btn['example'] ?? '' }}" placeholder="optional">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-secondary" type="submit" name="action" value="draft">Simpan Draft</button>
                    <button class="btn btn-primary" type="submit" name="action" value="submit">Submit Approval</button>
                </div>
            </div>

            <div class="col-xl-5 col-lg-5">
                <div class="preview-sticky">
                    <div class="card section-card">
                        <div class="card-body">
                            <div class="section-title">WhatsApp Preview</div>
                            <div class="wa-phone">
                                <div class="wa-head">
                                    <span class="wa-av">WA</span>
                                    <div>
                                        <div class="fw-semibold">WhatsApp Business</div>
                                        <div class="small opacity-75">Template Preview</div>
                                    </div>
                                </div>
                                <div class="wa-body">
                                    <div class="wa-bubble">
                                        <div class="wa-media" id="preview-media" style="display:none;"></div>
                                        <div class="wa-bubble-header" id="preview-header" style="display:none;"></div>
                                        <div class="wa-bubble-body" id="preview-body">(isi body)</div>
                                        <div class="wa-bubble-footer" id="preview-footer" style="display:none;"></div>
                                        <div class="wa-btns" id="preview-buttons" style="display:none;"></div>
                                        <div class="text-end mt-2 tiny">12:45</div>
                                    </div>
                                </div>
                            </div>
                            <div class="tiny mt-2" id="placeholder-hint"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="btn-row-template">
    <div class="btn-row" data-row>
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select form-select-sm btn-type" name="buttons[__IDX__][type]">
                    <option value="quick_reply">Quick Reply</option>
                    <option value="url">URL</option>
                    <option value="phone_number">Phone</option>
                    <option value="copy_code">Copy Code</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Label</label>
                <input class="form-control form-control-sm btn-label" name="buttons[__IDX__][label]" maxlength="25">
            </div>
            <div class="col-md-3">
                <label class="form-label">URL</label>
                <input class="form-control form-control-sm btn-url" name="buttons[__IDX__][url]" placeholder="https://...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Phone</label>
                <input class="form-control form-control-sm btn-phone" name="buttons[__IDX__][phone_number]" placeholder="+628...">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row">X</button>
            </div>
            <div class="col-md-6">
                <label class="form-label">Example (URL/Copy Code)</label>
                <input class="form-control form-control-sm btn-example" name="buttons[__IDX__][example]" placeholder="optional">
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const headerType = document.getElementById('header_type');
    const nameInput = document.querySelector('input[name="name"]');
    const metaNameInput = document.querySelector('input[name="meta_name"]');
    const headerInput = document.getElementById('header_text');
    const headerMediaFileInput = document.getElementById('header_media_file');
    const headerMediaInput = document.getElementById('header_media_url');
    const bodyInput = document.getElementById('body_input');
    const footerInput = document.getElementById('footer_input');
    const headerCount = document.getElementById('header-count');
    const bodyCount = document.getElementById('body-count');
    const footerCount = document.getElementById('footer-count');
    const headerTextWrap = document.querySelector('.header-text-wrap');
    const headerMediaWrap = document.querySelector('.header-media-wrap');
    const nsSelect = document.getElementById('instance_select');
    const nsInput = document.getElementById('namespace_input');

    const previewMedia = document.getElementById('preview-media');
    const previewHeader = document.getElementById('preview-header');
    const previewBody = document.getElementById('preview-body');
    const previewFooter = document.getElementById('preview-footer');
    const previewButtons = document.getElementById('preview-buttons');
    const placeholderHint = document.getElementById('placeholder-hint');
    const addBtn = document.getElementById('add-button-row');
    const container = document.getElementById('buttons-container');
    const template = document.getElementById('btn-row-template');
    const variableWrap = document.getElementById('variable-mappings');
    const variableEmpty = document.getElementById('variable-mappings-empty');
    const variableCount = document.getElementById('variable-count');
    const contactFieldOptions = @json($contactFieldOptions ?? []);
    const senderFieldOptions = @json($senderFieldOptions ?? []);
    const senderPreviewContext = @json($senderPreviewContext ?? []);
    const editorToolbars = Array.from(document.querySelectorAll('[data-editor-toolbar]'));
    let variableState = @json($variableMappings);
    let currentPlaceholderKey = null;

    const esc = (s) => (s || '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m] || m));
    const fmt = (s) => {
        let t = esc(s || '');
        // Strict WhatsApp-like formatting: opening/closing markers must hug non-space chars.
        t = t.replace(/`(\S(?:[^`]*\S)?)`/g, '<code>$1</code>');
        t = t.replace(/\*(\S(?:[^*]*\S)?)\*/g, '<strong>$1</strong>');
        t = t.replace(/_(\S(?:[^_]*\S)?)_/g, '<em>$1</em>');
        t = t.replace(/~(\S(?:[^~]*\S)?)~/g, '<s>$1</s>');
        t = t.replace(/\n/g, '<br>');
        return t;
    };
    const placeholders = (text) => [...new Set([...(text || '').matchAll(/\{\{(\d+)\}\}/g)].map((x) => Number(x[1])))].sort((a, b) => a - b);
    const contactFieldLabel = (key) => contactFieldOptions[key] || key;
    const senderFieldLabel = (key) => senderFieldOptions[key] || key;
    let metaNameTouched = Boolean(metaNameInput?.value);

    function syncNamespace() {
        if (!nsSelect || !nsInput) return;
        nsInput.value = nsSelect.selectedOptions[0]?.dataset.namespace || '';
    }

    function normalizeMetaName(value) {
        let normalized = String(value || '').toLowerCase();
        normalized = normalized.replace(/[^a-z]+/g, '_');
        normalized = normalized.replace(/^_+|_+$/g, '');
        normalized = normalized.replace(/_+/g, '_');
        return normalized.slice(0, 150);
    }

    function syncMetaName(force = false) {
        if (!metaNameInput) return;

        const current = metaNameInput.value || '';
        if (!force && metaNameTouched && current.trim() !== '') {
            metaNameInput.value = normalizeMetaName(current);
            return;
        }

        metaNameInput.value = normalizeMetaName(nameInput?.value || current);
    }

    function nextPlaceholderValue() {
        const indexes = [...new Set([
            ...placeholders(bodyInput?.value || ''),
            ...placeholders(headerInput?.value || ''),
        ])].sort((a, b) => a - b);

        return (indexes[indexes.length - 1] || 0) + 1;
    }

    function replaceSelection(input, before, after = before, fallback = '') {
        if (!input) return;

        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        const selected = input.value.slice(start, end);
        const inner = selected || fallback;
        const inserted = `${before}${inner}${after}`;

        input.setRangeText(inserted, start, end, 'end');

        const newStart = start + before.length;
        const newEnd = newStart + inner.length;

        input.focus();
        input.setSelectionRange(newStart, newEnd);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function insertPlaceholder(input) {
        if (!input) return;

        const placeholderNumber = nextPlaceholderValue();
        const placeholder = '{' + '{' + placeholderNumber + '}' + '}';
        replaceSelection(input, '', '', placeholder);
    }

    function bindEditorToolbar(toolbar) {
        const target = document.getElementById(toolbar.dataset.target || '');
        if (!target) return;

        toolbar.querySelectorAll('[data-wrap]').forEach((button) => {
            button.addEventListener('click', () => {
                const marker = button.dataset.wrap || '';
                replaceSelection(target, marker, marker, 'text');
            });
        });

        toolbar.querySelectorAll('[data-placeholder-target]').forEach((button) => {
            button.addEventListener('click', () => {
                insertPlaceholder(target);
            });
        });
    }

    function syncHeaderWrap() {
        const t = headerType?.value || 'none';
        headerTextWrap.style.display = t === 'text' ? '' : 'none';
        headerMediaWrap.style.display = ['image', 'document', 'video'].includes(t) ? '' : 'none';
    }

    function updateCounters() {
        if (headerCount) headerCount.textContent = `${(headerInput?.value || '').length}/60`;
        if (bodyCount) bodyCount.textContent = `${(bodyInput?.value || '').length}/1024`;
        if (footerCount) footerCount.textContent = `${(footerInput?.value || '').length}/60`;
    }

    function captureVariableState() {
        if (!variableWrap) return;

        variableWrap.querySelectorAll('[data-var-row]').forEach((row) => {
            const idx = row.dataset.varIndex;
            variableState[idx] = {
                source_type: row.querySelector('[data-role="source_type"]')?.value || 'text',
                text_value: row.querySelector('[data-role="text_value"]')?.value || '',
                contact_field: row.querySelector('[data-role="contact_field"]')?.value || 'name',
                sender_field: row.querySelector('[data-role="sender_field"]')?.value || 'name',
                fallback_value: row.querySelector('[data-role="fallback_value"]')?.value || '',
            };
        });
    }

    function syncVariableRow(row) {
        const sourceType = row.querySelector('[data-role="source_type"]')?.value || 'text';
        const textInput = row.querySelector('[data-role="text_value"]');
        const contactFieldSelect = row.querySelector('[data-role="contact_field"]');
        const senderFieldSelect = row.querySelector('[data-role="sender_field"]');
        const textWrap = row.querySelector('[data-role-wrap="text_value"]');
        const contactFieldWrap = row.querySelector('[data-role-wrap="contact_field"]');
        const senderFieldWrap = row.querySelector('[data-role-wrap="sender_field"]');

        if (textWrap) textWrap.style.display = sourceType === 'text' ? '' : 'none';
        if (contactFieldWrap) contactFieldWrap.style.display = sourceType === 'contact_field' ? '' : 'none';
        if (senderFieldWrap) senderFieldWrap.style.display = sourceType === 'sender_field' ? '' : 'none';
        if (textInput) textInput.disabled = sourceType !== 'text';
        if (contactFieldSelect) contactFieldSelect.disabled = sourceType !== 'contact_field';
        if (senderFieldSelect) senderFieldSelect.disabled = sourceType !== 'sender_field';
    }

    function renderVariableMappings(indexes) {
        if (!variableWrap || !variableEmpty) return;

        captureVariableState();
        variableWrap.innerHTML = '';
        variableCount.textContent = `${indexes.length} placeholder`;
        variableEmpty.style.display = indexes.length ? 'none' : 'block';

        indexes.forEach((idx) => {
            const state = variableState[idx] || {};
            const sourceType = ['contact_field', 'sender_field'].includes(state.source_type) ? state.source_type : 'text';
            const textValue = state.text_value || '';
            const contactField = state.contact_field || 'name';
            const senderField = state.sender_field || 'name';
            const fallbackValue = state.fallback_value || '';
            const contactFieldOptionsHtml = Object.entries(contactFieldOptions).map(([key, label]) => (
                `<option value="${esc(key)}" ${contactField === key ? 'selected' : ''}>${esc(label)}</option>`
            )).join('');
            const senderFieldOptionsHtml = Object.entries(senderFieldOptions).map(([key, label]) => (
                `<option value="${esc(key)}" ${senderField === key ? 'selected' : ''}>${esc(label)}</option>`
            )).join('');

            const row = document.createElement('div');
            row.className = 'var-row';
            row.dataset.varRow = '1';
            row.dataset.varIndex = String(idx);
            const placeholderLabel = '{' + '{' + idx + '}' + '}';
            row.innerHTML = `
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Placeholder</label>
                        <input class="form-control" value="${esc(placeholderLabel)}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sumber</label>
                        <select class="form-select" name="variable_mappings[${idx}][source_type]" data-role="source_type">
                            <option value="text" ${sourceType === 'text' ? 'selected' : ''}>Free text</option>
                            <option value="contact_field" ${sourceType === 'contact_field' ? 'selected' : ''}>Field Contact</option>
                            <option value="sender_field" ${sourceType === 'sender_field' ? 'selected' : ''}>Field User Pengirim</option>
                        </select>
                    </div>
                    <div class="col-md-4" data-role-wrap="text_value">
                        <label class="form-label">Nilai</label>
                        <input class="form-control" name="variable_mappings[${idx}][text_value]" data-role="text_value" value="${esc(textValue)}" placeholder="Isi text default">
                    </div>
                    <div class="col-md-4" data-role-wrap="contact_field">
                        <label class="form-label">Field Contact</label>
                        <select class="form-select" name="variable_mappings[${idx}][contact_field]" data-role="contact_field">
                            ${contactFieldOptionsHtml}
                        </select>
                    </div>
                    <div class="col-md-4" data-role-wrap="sender_field">
                        <label class="form-label">Field User Pengirim</label>
                        <select class="form-select" name="variable_mappings[${idx}][sender_field]" data-role="sender_field">
                            ${senderFieldOptionsHtml}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fallback Default</label>
                        <input class="form-control" name="variable_mappings[${idx}][fallback_value]" data-role="fallback_value" value="${esc(fallbackValue)}" placeholder="Dipakai jika kosong">
                    </div>
                </div>
            `;

            row.querySelectorAll('input, select').forEach((el) => {
                el.addEventListener('input', () => {
                    captureVariableState();
                    render();
                });
                el.addEventListener('change', () => {
                    syncVariableRow(row);
                    captureVariableState();
                    render();
                });
            });
            syncVariableRow(row);
            variableWrap.appendChild(row);
        });
    }

    function setTypeRules(row) {
        const type = row.querySelector('.btn-type')?.value || 'quick_reply';
        const url = row.querySelector('.btn-url');
        const phone = row.querySelector('.btn-phone');
        const ex = row.querySelector('.btn-example');
        const enUrl = type === 'url';
        const enPhone = type === 'phone_number';
        const enEx = type === 'url' || type === 'copy_code';
        url.disabled = !enUrl; if (!enUrl) url.value = '';
        phone.disabled = !enPhone; if (!enPhone) phone.value = '';
        ex.disabled = !enEx; if (!enEx) ex.value = '';
    }

    function reindex() {
        Array.from(container.querySelectorAll('[data-row]')).forEach((row, i) => {
            row.querySelectorAll('input, select').forEach((f) => f.name = f.name.replace(/buttons\\[\\d+\\]/, `buttons[${i}]`));
        });
    }

    function bindRow(row) {
        row.querySelectorAll('input, select').forEach((el) => {
            el.addEventListener('input', render);
            el.addEventListener('change', () => { setTypeRules(row); render(); });
        });
        row.querySelector('.remove-row')?.addEventListener('click', () => {
            if (container.querySelectorAll('[data-row]').length <= 1) {
                row.querySelectorAll('input').forEach((i) => i.value = '');
                row.querySelector('.btn-type').value = 'quick_reply';
                setTypeRules(row);
            } else {
                row.remove();
                reindex();
            }
            render();
        });
        setTypeRules(row);
    }

    function addRow() {
        const idx = container.querySelectorAll('[data-row]').length;
        const html = template.innerHTML.replaceAll('__IDX__', String(idx));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        container.appendChild(row);
        bindRow(row);
        reindex();
        render();
    }

    function renderButtons() {
        const labelType = (t) => t === 'url' ? 'URL' : (t === 'phone_number' ? 'Phone' : (t === 'copy_code' ? 'Copy' : 'Quick Reply'));
        const items = Array.from(container.querySelectorAll('[data-row]')).map((row) => ({
            type: row.querySelector('.btn-type')?.value || 'quick_reply',
            label: (row.querySelector('.btn-label')?.value || '').trim(),
            url: (row.querySelector('.btn-url')?.value || '').trim(),
            phone: (row.querySelector('.btn-phone')?.value || '').trim(),
            example: (row.querySelector('.btn-example')?.value || '').trim(),
        })).filter((x) => x.label !== '');

        previewButtons.innerHTML = '';
        if (!items.length) {
            previewButtons.style.display = 'none';
            return;
        }

        items.forEach((b) => {
            const suffix = b.type === 'url' ? b.url : (b.type === 'phone_number' ? b.phone : b.example);
            const el = document.createElement('div');
            el.className = 'wa-btn';
            el.innerHTML = `<span>${esc(b.label)}</span><small>${esc(labelType(b.type))}${suffix ? ' | ' + esc(suffix) : ''}</small>`;
            previewButtons.appendChild(el);
        });
        previewButtons.style.display = 'flex';
    }

    function render() {
        updateCounters();
        syncHeaderWrap();

        const hType = headerType?.value || 'none';
        const hText = headerInput?.value || '';
        const hMedia = headerMediaFileInput?.files?.[0]?.name || headerMediaInput?.value || '';
        const body = bodyInput?.value || '';
        const footer = footerInput?.value || '';

        previewHeader.style.display = 'none';
        previewMedia.style.display = 'none';

        if (hType === 'text' && hText.trim()) {
            previewHeader.innerHTML = fmt(hText);
            previewHeader.style.display = 'block';
        } else if (['image', 'document', 'video'].includes(hType)) {
            previewMedia.textContent = `${hType.toUpperCase()}${hMedia.trim() ? ': ' + hMedia.trim() : ' (url kosong)'}`;
            previewMedia.style.display = 'block';
        }

        previewBody.innerHTML = body.trim() ? fmt(body.replace(/\{\{(\d+)\}\}/g, '____')) : '(isi body)';

        if (footer.trim()) {
            previewFooter.innerHTML = fmt(footer);
            previewFooter.style.display = 'block';
        } else {
            previewFooter.style.display = 'none';
        }

        renderButtons();

        const allPh = [...new Set([...placeholders(body), ...placeholders(hText)])].sort((a, b) => a - b);
        const placeholderKey = JSON.stringify(allPh);
        if (placeholderKey !== currentPlaceholderKey) {
            renderVariableMappings(allPh);
            currentPlaceholderKey = placeholderKey;
        } else {
            captureVariableState();
        }
        const placeholderLabels = allPh.map((n) => '{' + '{' + n + '}' + '}').join(', ');
        placeholderHint.innerHTML = allPh.length ? `Placeholder: ${placeholderLabels}` : 'Tidak ada placeholder.';

        const previewValues = Object.fromEntries(allPh.map((idx) => {
            const cfg = variableState[idx] || {};
            let value = '';
            if ((cfg.source_type || 'text') === 'contact_field') {
                const fallback = cfg.fallback_value || '';
                value = fallback || `[${contactFieldLabel(cfg.contact_field || 'name')}]`;
            } else if ((cfg.source_type || 'text') === 'sender_field') {
                value = senderPreviewContext[cfg.sender_field || 'name'] || cfg.fallback_value || `[${senderFieldLabel(cfg.sender_field || 'name')}]`;
            } else {
                value = cfg.text_value || cfg.fallback_value || '';
            }

            return [idx, value];
        }));

        previewBody.innerHTML = body.trim()
            ? fmt(body.replace(/\{\{(\d+)\}\}/g, (_, idx) => previewValues[idx] || '____'))
            : '(isi body)';
        if (hType === 'text' && hText.trim()) {
            previewHeader.innerHTML = fmt(hText.replace(/\{\{(\d+)\}\}/g, (_, idx) => previewValues[idx] || '____'));
            previewHeader.style.display = 'block';
        }
    }

    Array.from(container.querySelectorAll('[data-row]')).forEach(bindRow);
    editorToolbars.forEach(bindEditorToolbar);
    addBtn?.addEventListener('click', addRow);
    nsSelect?.addEventListener('change', syncNamespace);
    nameInput?.addEventListener('input', () => syncMetaName(false));
    metaNameInput?.addEventListener('input', () => {
        metaNameTouched = true;
        syncMetaName(false);
    });
    headerMediaFileInput?.addEventListener('change', render);
    [headerType, headerInput, headerMediaInput, bodyInput, footerInput].forEach((el) => el?.addEventListener('input', render));
    syncNamespace();
    syncMetaName(true);
    render();
});
</script>
@endpush
@endsection
