@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Buat WA Blast Campaign</h2>
        <div class="text-muted small">Format recipient per baris: <code>nomor,nama,var1,var2,...</code> atau pakai pemisah <code>|</code>.</div>
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
                            <label class="form-label">Pilih Contacts</label>
                            <select name="contact_ids[]" class="form-select" multiple size="10">
                                @foreach(($contacts ?? collect()) as $contact)
                                    @php
                                        $selectedContacts = collect(old('contact_ids', []))->map(fn ($id) => (string) $id)->all();
                                        $contactPhone = $contact->mobile ?: $contact->phone;
                                    @endphp
                                    <option value="{{ $contact->id }}" {{ in_array((string) $contact->id, $selectedContacts, true) ? 'selected' : '' }}>
                                        {{ $contact->name }} - {{ $contactPhone }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_ids') <div class="text-danger small">{{ $message }}</div> @enderror
                            @error('contact_ids.*') <div class="text-danger small">{{ $message }}</div> @enderror
                            <div class="text-muted small mt-2">Contact aktif yang punya nomor di field mobile atau phone.</div>
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
    const recipientSource = document.getElementById('recipient_source');
    const sourcePanels = Array.from(document.querySelectorAll('[data-source-panel]'));
    if (!instanceSelect || !templateSelect) return;

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
    };

    instanceSelect.addEventListener('change', syncTemplates);
    syncTemplates();

    const syncRecipientPanels = () => {
        const activeSource = recipientSource?.value || 'manual';
        sourcePanels.forEach((panel) => {
            panel.style.display = panel.dataset.sourcePanel === activeSource ? '' : 'none';
        });
    };

    recipientSource?.addEventListener('change', syncRecipientPanels);
    syncRecipientPanels();
});
</script>
@endpush
