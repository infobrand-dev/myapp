@php
    $selectedRole = old('role', $currentRole ?? '');
    $selectedCompanyIds = collect(old('company_ids', $selectedCompanyIds ?? []))->map(fn ($id) => (int) $id)->all();
    $selectedBranchIds = collect(old('branch_ids', $selectedBranchIds ?? []))->map(fn ($id) => (int) $id)->all();
    $defaultCompanyId = old('default_company_id', $defaultCompanyId ?? null);
    $defaultBranchId = old('default_branch_id', $defaultBranchId ?? null);
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ isset($user) ? '(opsional)' : '' }}</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ isset($user) ? '' : 'required' }} placeholder="{{ isset($user) ? 'Biarkan kosong jika tidak diganti' : '' }}">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" {{ isset($user) ? '' : 'required' }}>
        @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">- Pilih role -</option>
            @foreach($roles as $role)
                @php $roleName = $role->name; @endphp
                <option value="{{ $roleName }}" {{ $selectedRole === $roleName ? 'selected' : '' }}>{{ $roleName }}</option>
            @endforeach
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="text-muted small mt-1">Hak akses user mengikuti role yang dipilih di sini. Role standar seperti Customer Service, Sales, Cashier, Inventory Staff, dan Finance Staff sudah disiapkan agar tim bisa langsung dipakai.</div>
        @if(!empty($roleDescriptions))
            <div class="mt-2 small text-muted">
                @foreach($roles as $role)
                    @if(!empty($roleDescriptions[$role->name]))
                        <div><span class="fw-semibold text-body">{{ $role->name }}:</span> {{ $roleDescriptions[$role->name] }}</div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
    <div class="col-12">
        <hr class="my-1">
        <div class="fw-semibold">Company & Branch Access</div>
        <div class="text-muted small">Batasi company dan branch yang boleh dipakai user ini. Jika branch tidak dipilih, user tetap bisa bekerja di level company sesuai akses company yang dipilih.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Company Access</label>
        <select name="company_ids[]" class="form-select @error('company_ids') is-invalid @enderror @error('company_ids.*') is-invalid @enderror" multiple size="6">
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected(in_array((int) $company->id, $selectedCompanyIds, true))>
                    {{ $company->name }}{{ $company->is_active ? '' : ' (inactive)' }}
                </option>
            @endforeach
        </select>
        @error('company_ids')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('company_ids.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Default Company</label>
        <select name="default_company_id" class="form-select @error('default_company_id') is-invalid @enderror">
            <option value="">Auto first allowed company</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected((string) $defaultCompanyId === (string) $company->id)>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
        @error('default_company_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Branch Access</label>
        <select name="branch_ids[]" class="form-select @error('branch_ids') is-invalid @enderror @error('branch_ids.*') is-invalid @enderror" multiple size="8">
            @foreach($branchesByCompany as $companyId => $branchGroup)
                <optgroup label="{{ optional($branchGroup->first()->company)->name ?? ('Company #' . $companyId) }}">
                    @foreach($branchGroup as $branch)
                        <option value="{{ $branch->id }}" @selected(in_array((int) $branch->id, $selectedBranchIds, true))>
                            {{ $branch->name }}{{ $branch->is_active ? '' : ' (inactive)' }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('branch_ids')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('branch_ids.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Default Branch</label>
        <select name="default_branch_id" class="form-select @error('default_branch_id') is-invalid @enderror">
            <option value="">No default branch</option>
            @foreach($branchesByCompany as $companyId => $branchGroup)
                <optgroup label="{{ optional($branchGroup->first()->company)->name ?? ('Company #' . $companyId) }}">
                    @foreach($branchGroup as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $defaultBranchId === (string) $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('default_branch_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
