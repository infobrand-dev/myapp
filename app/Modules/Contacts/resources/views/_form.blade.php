@php
    $selectedType = old('type', $contact->type ?? 'company');
    $selectedScope = old('scope', $contact->scope ?? \App\Modules\Contacts\Support\ContactScope::detectLevel($contact));
@endphp

{{-- ── Identitas ──────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Identitas</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tipe Contact <span class="text-danger">*</span></label>
        <select name="type" id="contact-type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="company" {{ $selectedType === 'company' ? 'selected' : '' }}>Company</option>
            <option value="individual" {{ $selectedType === 'individual' ? 'selected' : '' }}>Individual</option>
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $contact->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Jabatan</label>
        <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
            value="{{ old('job_title', $contact->job_title ?? '') }}">
        @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6" id="parent-contact-wrapper">
        <x-contact-select
            name="parent_contact_id"
            label="Relasi Company Contact"
            placeholder="— Tidak ada —"
            :value="old('parent_contact_id', $contact->parent_contact_id ?? null)"
            :value-name="$contact->parentContact?->name ?? null"
            :value-type="$contact->parentContact?->type ?? null"
            hint="Isi jika tipe Individual dan bekerja di perusahaan tertentu."
        />
    </div>
    <div class="col-md-6">
        <label class="form-label">Scope Data <span class="text-danger">*</span></label>
        <select name="scope" id="contact-scope" class="form-select @error('scope') is-invalid @enderror" required>
            <option value="tenant" {{ $selectedScope === 'tenant' ? 'selected' : '' }}>Tenant-wide (semua bisa melihat)</option>
            <option value="company" {{ $selectedScope === 'company' ? 'selected' : '' }}>Company aktif</option>
            @if(\App\Support\BranchContext::currentId() !== null)
                <option value="branch" {{ $selectedScope === 'branch' ? 'selected' : '' }}>Branch aktif</option>
            @endif
        </select>
        <div class="form-hint">Tentukan siapa yang bisa melihat kontak ini.</div>
        @error('scope') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="w-100">
            <label class="form-label d-block">Status</label>
            <label class="form-check form-switch mb-0 mt-1">
                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $contact->is_active ?? true) ? 'checked' : '' }}>
                <span class="form-check-label">Aktif</span>
            </label>
        </div>
    </div>
</div>

{{-- ── Kontak ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Kontak & Komunikasi</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $contact->email ?? '') }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Telepon</label>
        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $contact->phone ?? '') }}" placeholder="+628123456789">
        <div class="form-hint">Format internasional otomatis.</div>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Mobile / WhatsApp</label>
        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
            value="{{ old('mobile', $contact->mobile ?? '') }}" placeholder="+628123456789">
        <div class="form-hint">08... otomatis jadi 628...</div>
        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="url" name="website" class="form-control @error('website') is-invalid @enderror"
            value="{{ old('website', $contact->website ?? '') }}" placeholder="https://...">
        @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Bisnis ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Informasi Bisnis</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Industry</label>
        <input type="text" name="industry" class="form-control @error('industry') is-invalid @enderror"
            value="{{ old('industry', $contact->industry ?? '') }}">
        @error('industry') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">VAT / NPWP</label>
        <input type="text" name="vat" class="form-control @error('vat') is-invalid @enderror"
            value="{{ old('vat', $contact->vat ?? '') }}">
        @error('vat') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Company Registry</label>
        <input type="text" name="company_registry" class="form-control @error('company_registry') is-invalid @enderror"
            value="{{ old('company_registry', $contact->company_registry ?? '') }}">
        @error('company_registry') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Alamat ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="fw-semibold text-muted small text-uppercase mb-2" style="letter-spacing:.06em;">Alamat</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Alamat (Street)</label>
        <input type="text" name="street" class="form-control @error('street') is-invalid @enderror"
            value="{{ old('street', $contact->street ?? '') }}">
        @error('street') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Alamat 2</label>
        <input type="text" name="street2" class="form-control @error('street2') is-invalid @enderror"
            value="{{ old('street2', $contact->street2 ?? '') }}">
        @error('street2') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Kota</label>
        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
            value="{{ old('city', $contact->city ?? '') }}">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Provinsi / State</label>
        <input type="text" name="state" class="form-control @error('state') is-invalid @enderror"
            value="{{ old('state', $contact->state ?? '') }}">
        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Kode Pos</label>
        <input type="text" name="zip" class="form-control @error('zip') is-invalid @enderror"
            value="{{ old('zip', $contact->zip ?? '') }}">
        @error('zip') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Negara</label>
        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
            value="{{ old('country', $contact->country ?? '') }}">
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

{{-- ── Catatan ─────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Catatan Internal</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
            rows="3" placeholder="Catatan internal tentang kontak ini…">{{ old('notes', $contact->notes ?? '') }}</textarea>
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
