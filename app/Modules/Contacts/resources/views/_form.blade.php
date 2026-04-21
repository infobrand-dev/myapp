@php
    $selectedType = old('type', $contact->type ?? 'company');
    $selectedScope = old('scope', $contact->scope ?? \App\Modules\Contacts\Support\ContactScope::detectLevel($contact));
@endphp

{{-- ── Identity ──────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Identity</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Contact Type <span class="text-danger">*</span></label>
        <select name="type" id="contact-type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="company" {{ $selectedType === 'company' ? 'selected' : '' }}>Company</option>
            <option value="individual" {{ $selectedType === 'individual' ? 'selected' : '' }}>Individual</option>
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $contact->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Job Title</label>
        <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
            value="{{ old('job_title', $contact->job_title ?? '') }}">
        @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6" id="parent-contact-wrapper">
        <x-contact-select
            name="parent_contact_id"
            label="Parent Company"
            placeholder="— None —"
            :value="old('parent_contact_id', $contact->parent_contact_id ?? null)"
            :value-name="$contact->parentContact?->name ?? null"
            :value-type="$contact->parentContact?->type ?? null"
            hint="Fill if type is Individual and linked to a company."
        />
    </div>
    <div class="col-md-6">
        @include('shared.accounting.field-label', [
            'label' => 'Data Scope',
            'required' => true,
            'tooltip' => 'Tentukan siapa yang bisa melihat kontak ini. Tenant-wide berarti semua cabang dan pengguna bisa melihat. Company membatasi ke perusahaan aktif. Branch membatasi ke cabang aktif saja.',
        ])
        <select name="scope" id="contact-scope" class="form-select @error('scope') is-invalid @enderror" required>
            <option value="tenant" {{ $selectedScope === 'tenant' ? 'selected' : '' }}>Tenant-wide (all branches)</option>
            <option value="company" {{ $selectedScope === 'company' ? 'selected' : '' }}>Company only</option>
            @if(\App\Support\BranchContext::currentId() !== null)
                <option value="branch" {{ $selectedScope === 'branch' ? 'selected' : '' }}>Branch only</option>
            @endif
        </select>
        @error('scope') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="w-100">
            <label class="form-label d-block">Status</label>
            <label class="form-check form-switch mb-0 mt-1">
                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $contact->is_active ?? true) ? 'checked' : '' }}>
                <span class="form-check-label">Active</span>
            </label>
        </div>
    </div>
</div>

{{-- ── Contact Info ─────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Contact Info</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $contact->email ?? '') }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $contact->phone ?? '') }}" placeholder="+628123456789">
        <div class="form-hint">International format auto-applied.</div>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Mobile / WhatsApp</label>
        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
            value="{{ old('mobile', $contact->mobile ?? '') }}" placeholder="+628123456789">
        <div class="form-hint">08... auto-converted to 628...</div>
        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="url" name="website" class="form-control @error('website') is-invalid @enderror"
            value="{{ old('website', $contact->website ?? '') }}" placeholder="https://...">
        @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Business Info ────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Business Info</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Industry</label>
        <input type="text" name="industry" class="form-control @error('industry') is-invalid @enderror"
            value="{{ old('industry', $contact->industry ?? '') }}">
        @error('industry') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">VAT / Tax ID</label>
        <input type="text" name="vat" class="form-control @error('vat') is-invalid @enderror"
            value="{{ old('vat', $contact->vat ?? '') }}">
        @error('vat') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        @include('shared.accounting.field-label', [
            'label' => 'Tax Name',
            'tooltip' => 'Nama legal pada profil pajak partner, misalnya nama PKP atau nama di NPWP.',
        ])
        <input type="text" name="tax_name" class="form-control @error('tax_name') is-invalid @enderror"
            value="{{ old('tax_name', $contact->tax_name ?? '') }}">
        @error('tax_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        @include('shared.accounting.field-label', [
            'label' => 'Company Registry',
            'tooltip' => 'Nomor izin usaha resmi seperti NIB, SIUP, atau TDP. Dipakai untuk keperluan dokumen legal dan verifikasi vendor.',
        ])
        <input type="text" name="company_registry" class="form-control @error('company_registry') is-invalid @enderror"
            value="{{ old('company_registry', $contact->company_registry ?? '') }}">
        @error('company_registry') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        @include('shared.accounting.field-label', [
            'label' => 'Payment Term (Days)',
            'tooltip' => 'Jumlah hari tempo pembayaran default untuk kontak ini. Sistem akan mengisi due date otomatis saat membuat penjualan atau pembelian.',
        ])
        <input type="number" min="0" name="payment_term_days" class="form-control @error('payment_term_days') is-invalid @enderror"
            value="{{ old('payment_term_days', $contact->payment_term_days ?? '') }}">
        @error('payment_term_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        @include('shared.accounting.field-label', [
            'label' => 'Credit Limit',
            'tooltip' => 'Batas piutang maksimum yang diperbolehkan untuk kontak ini. Sistem akan memberi peringatan jika saldo piutang mendekati atau melebihi nilai ini.',
        ])
        <input type="number" min="0" step="0.01" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror"
            value="{{ old('credit_limit', $contact->credit_limit ?? '') }}">
        @error('credit_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        @include('shared.accounting.field-label', [
            'label' => 'Tags / Segment',
            'tooltip' => 'Label bebas untuk mengelompokkan kontak, misalnya "vip", "retail", atau "supplier-priority". Berguna untuk filter dan laporan per segmen.',
        ])
        <input type="text" name="tags_input" class="form-control @error('tags_input') is-invalid @enderror"
            value="{{ old('tags_input', collect($contact->tags ?? [])->implode(', ')) }}"
            placeholder="retail, vip, supplier-priority">
        <div class="form-hint">Separate with commas.</div>
        @error('tags_input') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="w-100">
            <label class="form-label d-block">Tax Profile</label>
            <label class="form-check form-switch mb-0 mt-1">
                <input class="form-check-input" type="checkbox" name="tax_is_pkp" value="1"
                    {{ old('tax_is_pkp', $contact->tax_is_pkp ?? false) ? 'checked' : '' }}>
                <span class="form-check-label">PKP / taxable entity</span>
            </label>
        </div>
    </div>
</div>

{{-- ── Address ──────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Address</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Street</label>
        <input type="text" name="street" class="form-control @error('street') is-invalid @enderror"
            value="{{ old('street', $contact->street ?? '') }}">
        @error('street') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Street 2</label>
        <input type="text" name="street2" class="form-control @error('street2') is-invalid @enderror"
            value="{{ old('street2', $contact->street2 ?? '') }}">
        @error('street2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
            value="{{ old('city', $contact->city ?? '') }}">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Province / State</label>
        <input type="text" name="state" class="form-control @error('state') is-invalid @enderror"
            value="{{ old('state', $contact->state ?? '') }}">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Postal Code</label>
        <input type="text" name="zip" class="form-control @error('zip') is-invalid @enderror"
            value="{{ old('zip', $contact->zip ?? '') }}">
        @error('zip') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
            value="{{ old('country', $contact->country ?? '') }}">
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Billing Address</label>
        <textarea name="billing_address" class="form-control @error('billing_address') is-invalid @enderror" rows="3"
            placeholder="If different from main address">{{ old('billing_address', $contact->billing_address ?? '') }}</textarea>
        @error('billing_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Shipping Address</label>
        <textarea name="shipping_address" class="form-control @error('shipping_address') is-invalid @enderror" rows="3"
            placeholder="If different from main address">{{ old('shipping_address', $contact->shipping_address ?? '') }}</textarea>
        @error('shipping_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        @include('shared.accounting.field-label', [
            'label' => 'Tax Address',
            'tooltip' => 'Alamat yang dipakai pada dokumen perpajakan. Boleh sama dengan billing address jika tidak ada perbedaan.',
        ])
        <textarea name="tax_address" class="form-control @error('tax_address') is-invalid @enderror" rows="3"
            placeholder="Tax / NPWP address">{{ old('tax_address', $contact->tax_address ?? '') }}</textarea>
        @error('tax_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Contact Person ───────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Contact Person</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Person Name</label>
        <input type="text" name="contact_person_name" class="form-control @error('contact_person_name') is-invalid @enderror"
            value="{{ old('contact_person_name', $contact->contact_person_name ?? '') }}">
        @error('contact_person_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Person Phone</label>
        <input type="text" name="contact_person_phone" class="form-control @error('contact_person_phone') is-invalid @enderror"
            value="{{ old('contact_person_phone', $contact->contact_person_phone ?? '') }}" placeholder="+628123456789">
        @error('contact_person_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Notes ───────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Internal Notes</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
            rows="3" placeholder="Internal notes about this contact…">{{ old('notes', $contact->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
<script>
(function () {
    const contactType = document.getElementById('contact-type');
    const parentWrapper = document.getElementById('parent-contact-wrapper');
    const parentSelect = document.getElementById('parent-contact-id');

    function toggleParentField() {
        const isIndividual = contactType.value === 'individual';
        parentWrapper.style.opacity = isIndividual ? '1' : '0.4';
        parentSelect.disabled = !isIndividual;
        if (!isIndividual) parentSelect.value = '';
    }

    if (contactType) {
        contactType.addEventListener('change', toggleParentField);
        toggleParentField();
    }
})();
</script>
@endpush
