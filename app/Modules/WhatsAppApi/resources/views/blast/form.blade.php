@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Buat WA Blast Campaign</h2>
        <div class="text-muted small">Template variable mengikuti mapping di template. Untuk recipient manual/CSV, kolom <code>var1,var2,...</code> akan override mapping default bila diisi.</div>
    </div>
    <a href="{{ route('whatsapp-api.blast-campaigns.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('whatsapp-api.blast-campaigns.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Campaign</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Instance</label>
                            <select name="instance_id" id="instance_id" class="form-select" required>
                                <option value="">Pilih</option>
                                @foreach($instances as $inst)
                                    <option value="{{ $inst->id }}" data-namespace="{{ $inst->cloud_business_account_id }}" {{ (string) old('instance_id') === (string) $inst->id ? 'selected' : '' }}>
                                        {{ $inst->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('instance_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Template</label>
                            <select name="template_id" id="template_id" class="form-select" required>
                                <option value="">Pilih</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}" data-namespace="{{ $tpl->namespace }}" {{ (string) old('template_id') === (string) $tpl->id ? 'selected' : '' }}>
                                        {{ $tpl->name }} ({{ $tpl->language }})
                                    </option>
                                @endforeach
                            </select>
                            @error('template_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mt-2 small text-muted" id="template-variable-summary">Pilih template untuk melihat ringkasan variable.</div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Scheduled At (opsional)</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}">
                            @error('scheduled_at') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Delay per message (ms)</label>
                            <input type="number" min="0" max="5000" name="delay_ms" class="form-control" value="{{ old('delay_ms', 300) }}">
                            @error('delay_ms') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Sumber Recipients</label>
                        @php $recipientSource = old('recipient_source', 'manual'); @endphp
                        <select name="recipient_source" id="recipient_source" class="form-select" required>
                            <option value="manual" {{ $recipientSource === 'manual' ? 'selected' : '' }}>Input Blast</option>
                            <option value="csv" {{ $recipientSource === 'csv' ? 'selected' : '' }}>Upload CSV / TXT</option>
                            @if($contactsEnabled ?? false)
                                <option value="contacts" {{ $recipientSource === 'contacts' ? 'selected' : '' }}>Dari Contacts</option>
                            @endif
                        </select>
                    </div>

                    <div class="mt-3 recipient-source-panel" data-source-panel="manual" style="{{ $recipientSource === 'manual' ? '' : 'display:none;' }}">
                        <label class="form-label">Recipients Manual</label>
                        <textarea name="recipients_text" rows="14" class="form-control" placeholder="6281234567890,Andi,INV-001,100000&#10;6282233344455,Budi,INV-002,230000">{{ old('recipients_text') }}</textarea>
                        @error('recipients_text') <div class="text-danger small">{{ $message }}</div> @enderror
                        <div class="text-muted small mt-2">
                            <div>Kolom 1: nomor WA (E.164 tanpa simbol).</div>
                            <div>Kolom 2: nama kontak (opsional).</div>
                            <div>Kolom 3 dst: nilai placeholder template (otomatis map ke {{'{1}'}}, {{'{2}'}}, dst).</div>
                        </div>
                    </div>

                    <div class="mt-3 recipient-source-panel" data-source-panel="csv" style="{{ $recipientSource === 'csv' ? '' : 'display:none;' }}">
                        <label class="form-label">Upload File CSV / TXT</label>
                        <input type="file" name="recipients_file" class="form-control" accept=".csv,.txt">
                        @error('recipients_file') <div class="text-danger small">{{ $message }}</div> @enderror
                        <div class="text-muted small mt-2">
                            <div>Format per baris tetap sama: <code>nomor,nama,var1,var2</code>.</div>
                            <div>Bisa pakai pemisah <code>,</code>, <code>;</code>, atau <code>|</code>.</div>
                        </div>
                    </div>

                    @if($contactsEnabled ?? false)
                        <div class="mt-3 recipient-source-panel" data-source-panel="contacts" style="{{ $recipientSource === 'contacts' ? '' : 'display:none;' }}">
                            <label class="form-label">Filter Contacts</label>
                            <div id="filter-rows" class="mb-2">
                                @php $filterRows = old('filters', $filters ?? [['field' => 'name', 'operator' => 'contains', 'value' => '']]); @endphp
                                @foreach($filterRows as $idx => $filter)
                                    <div class="d-flex gap-2 align-items-center mb-2 filter-row">
                                        <select name="filters[{{$idx}}][field]" class="form-select form-select-sm filter-input" style="max-width:140px;">
                                            <option value="name" {{ ($filter['field'] ?? '') === 'name' ? 'selected' : '' }}>Name</option>
                                            <option value="company" {{ ($filter['field'] ?? '') === 'company' ? 'selected' : '' }}>Company</option>
                                            <option value="mobile" {{ ($filter['field'] ?? '') === 'mobile' ? 'selected' : '' }}>Mobile</option>
                                            <option value="phone" {{ ($filter['field'] ?? '') === 'phone' ? 'selected' : '' }}>Phone</option>
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
                                <span class="badge bg-azure-lt text-azure" id="matches-badge">Matches: {{ $matchCount ?? 0 }}</span>
                            </div>
                            @error('filters') <div class="text-danger small">{{ $message }}</div> @enderror
                            <div class="text-muted small mt-2">Contact aktif dengan nomor di field mobile atau phone. Rule kosong akan diabaikan.</div>
                        </div>
                    @endif

                    <div class="mt-4 d-flex gap-2 justify-content-end">
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">Simpan Draft</button>
                        <button type="submit" name="action" value="schedule" class="btn btn-outline-primary">Simpan & Schedule</button>
                        <button type="submit" name="action" value="send_now" class="btn btn-primary">Kirim Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const instanceSelect = document.getElementById('instance_id');
    const templateSelect = document.getElementById('template_id');
    const templateSummary = document.getElementById('template-variable-summary');
    const recipientSource = document.getElementById('recipient_source');
    const sourcePanels = Array.from(document.querySelectorAll('[data-source-panel]'));
    const filterWrap = document.getElementById('filter-rows');
    const addFilterBtn = document.getElementById('add-filter');
    const applyFilterBtn = document.getElementById('apply-filter');
    const matchesBadge = document.getElementById('matches-badge');
    const csrfToken = document.querySelector('input[name="_token"]')?.value;
    const templates = @json(
        $templates->map(fn ($template) => [
            'id' => $template->id,
            'placeholders' => \App\Modules\WhatsAppApi\Support\TemplateVariableResolver::placeholderIndexes(
                $template->body,
                \App\Modules\WhatsAppApi\Support\TemplateVariableResolver::headerText($template)
            ),
            'variable_mappings' => $template->variable_mappings ?? [],
        ])->values()
    );
    if (!instanceSelect || !templateSelect) return;

    const describeTemplate = () => {
        const current = templates.find((item) => String(item.id) === String(templateSelect.value || ''));
        if (!templateSummary) return;

        if (!current) {
            templateSummary.textContent = 'Pilih template untuk melihat ringkasan variable.';
            return;
        }

        if (!Array.isArray(current.placeholders) || current.placeholders.length === 0) {
            templateSummary.textContent = 'Template ini tidak punya placeholder.';
            return;
        }

        const labels = current.placeholders.map((idx) => {
            const config = current.variable_mappings?.[idx] || current.variable_mappings?.[String(idx)] || {};
            if ((config.source_type || 'text') === 'contact_field') {
                return `{{${idx}}} -> field:${config.contact_field || 'name'}${config.fallback_value ? ` (fallback: ${config.fallback_value})` : ''}`;
            }
            if ((config.source_type || 'text') === 'sender_field') {
                return `{{${idx}}} -> user:${config.sender_field || 'name'}${config.fallback_value ? ` (fallback: ${config.fallback_value})` : ''}`;
            }

            return `{{${idx}}} -> text${config.text_value ? `:${config.text_value}` : ''}${config.fallback_value ? ` (fallback: ${config.fallback_value})` : ''}`;
        });

        templateSummary.textContent = labels.join(' | ');
    };

    const syncTemplates = () => {
        const ns = instanceSelect.selectedOptions[0]?.dataset.namespace || '';
        Array.from(templateSelect.options).forEach((opt) => {
            if (!opt.value) return;
            const tplNs = opt.dataset.namespace || '';
            const allowed = !ns || !tplNs || tplNs === ns;
            opt.hidden = !allowed;
            if (!allowed && opt.selected) {
                opt.selected = false;
            }
        });
        describeTemplate();
    };

    instanceSelect.addEventListener('change', syncTemplates);
    templateSelect.addEventListener('change', describeTemplate);
    syncTemplates();
    describeTemplate();

    const syncRecipientPanels = () => {
        const activeSource = recipientSource?.value || 'manual';
        sourcePanels.forEach((panel) => {
            panel.style.display = panel.dataset.sourcePanel === activeSource ? '' : 'none';
        });
    };

    recipientSource?.addEventListener('change', syncRecipientPanels);
    syncRecipientPanels();

    addFilterBtn?.addEventListener('click', () => {
        const idx = filterWrap?.querySelectorAll('.filter-row').length ?? 0;
        const row = document.createElement('div');
        row.className = 'd-flex gap-2 align-items-center mb-2 filter-row';
        row.innerHTML = `
            <select name="filters[${idx}][field]" class="form-select form-select-sm filter-input" style="max-width:140px;">
                <option value="name">Name</option>
                <option value="company">Company</option>
                <option value="mobile">Mobile</option>
                <option value="phone">Phone</option>
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
        filterWrap?.appendChild(row);
    });

    filterWrap?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.remove-row');
        if (!removeBtn) return;
        removeBtn.parentElement?.remove();
    });

    applyFilterBtn?.addEventListener('click', () => {
        if (!csrfToken) return;

        const formData = new FormData();
        filterWrap?.querySelectorAll('.filter-row').forEach((row, idx) => {
            row.querySelectorAll('select, input').forEach((input) => {
                const normalizedName = input.name.replace(/\d+/, idx);
                formData.append(normalizedName, input.value);
            });
        });

        fetch("{{ route('whatsapp-api.blast-campaigns.matches') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (matchesBadge) {
                    matchesBadge.textContent = 'Matches: ' + (data.count ?? 0);
                }
            })
            .catch(() => {
                if (matchesBadge) {
                    matchesBadge.textContent = 'Matches: error';
                }
            });
    });
});
</script>
@endpush
