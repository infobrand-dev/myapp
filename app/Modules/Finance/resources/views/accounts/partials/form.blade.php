@php
    $account = $account ?? new \App\Modules\Finance\Models\FinanceAccount();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('account_type', $account->account_type ?: 'cash') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if(!empty($showSlug))
        <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $account->slug) }}">
            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
    <div class="col-md-{{ !empty($showSlug) ? '6' : '12' }}">
        <label class="form-label">Account Number / Reference</label>
        <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number', $account->account_number) }}">
        @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $account->exists ? $account->is_active : true))>
            <span class="form-check-label">Active</span>
        </label>
    </div>
    <div class="col-md-6">
        <label class="form-check">
            <input type="checkbox" class="form-check-input" name="is_default" value="1" @checked(old('is_default', $account->is_default))>
            <span class="form-check-label">Jadikan default account</span>
        </label>
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="{{ $notesRows ?? 4 }}">{{ old('notes', $account->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
