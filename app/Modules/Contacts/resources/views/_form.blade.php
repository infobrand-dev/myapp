@php
    $selectedType = old('type', $contact->type ?? 'company');
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tipe Contact</label>
        <select name="type" id="contact-type" class="form-select" required>
            <option value="company" {{ $selectedType === 'company' ? 'selected' : '' }}>Company</option>
            <option value="individual" {{ $selectedType === 'individual' ? 'selected' : '' }}>Individual</option>
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $contact->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Bekerja di (Company)</label>
        <select name="company_id" id="company-id" class="form-select">
            <option value="">-</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ (string) old('company_id', $contact->company_id ?? '') === (string) $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
        <div class="text-muted small">Isi hanya jika tipe Individual.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Jabatan</label>
        <input type="text" name="job_title" class="form-control" value="{{ old('job_title', $contact->job_title ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $contact->email ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Telepon</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $contact->phone ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Mobile</label>
        <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $contact->mobile ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="url" name="website" class="form-control" value="{{ old('website', $contact->website ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">VAT/NPWP</label>
        <input type="text" name="vat" class="form-control" value="{{ old('vat', $contact->vat ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Company Registry</label>
        <input type="text" name="company_registry" class="form-control" value="{{ old('company_registry', $contact->company_registry ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Industry</label>
        <input type="text" name="industry" class="form-control" value="{{ old('industry', $contact->industry ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Street</label>
        <input type="text" name="street" class="form-control" value="{{ old('street', $contact->street ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Street 2</label>
        <input type="text" name="street2" class="form-control" value="{{ old('street2', $contact->street2 ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $contact->city ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">State</label>
        <input type="text" name="state" class="form-control" value="{{ old('state', $contact->state ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Zip</label>
        <input type="text" name="zip" class="form-control" value="{{ old('zip', $contact->zip ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" value="{{ old('country', $contact->country ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $contact->notes ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $contact->is_active ?? true) ? 'checked' : '' }}>
            <span class="form-check-label">Aktif</span>
        </label>
    </div>
</div>

@push('scripts')
<script>
    const contactType = document.getElementById('contact-type');
    const companySelect = document.getElementById('company-id');

    function toggleCompany() {
        const isIndividual = contactType.value === 'individual';
        companySelect.disabled = !isIndividual;
        if (!isIndividual) {
            companySelect.value = '';
        }
    }

    if (contactType && companySelect) {
        contactType.addEventListener('change', toggleCompany);
        toggleCompany();
    }
</script>
@endpush
