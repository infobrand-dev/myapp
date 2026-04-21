@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $taxRate->code) }}" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $taxRate->name) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if($isAdvancedMode)
        <div class="col-md-4">
            <label class="form-label">Tax Type</label>
            <select name="tax_type" class="form-select @error('tax_type') is-invalid @enderror">
                @foreach($taxTypeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('tax_type', $taxRate->tax_type ?: \App\Modules\Finance\Models\FinanceTaxRate::TYPE_SALES) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('tax_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <input type="hidden" name="tax_type" value="{{ old('tax_type', $taxRate->tax_type ?: \App\Modules\Finance\Models\FinanceTaxRate::TYPE_SALES) }}">
    @endif
    <div class="col-md-4">
        <label class="form-label">Rate %</label>
        <input type="number" min="0" max="100" step="0.0001" name="rate_percent" class="form-control @error('rate_percent') is-invalid @enderror" value="{{ old('rate_percent', $taxRate->rate_percent ?: 0) }}" required>
        @error('rate_percent') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="w-100">
            <label class="form-label d-block">Status</label>
            <label class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $taxRate->is_active ?? true) ? 'checked' : '' }}>
                <span class="form-check-label">Aktif</span>
            </label>
            @if($isAdvancedMode)
                <label class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="is_inclusive" value="1" {{ old('is_inclusive', $taxRate->is_inclusive ?? false) ? 'checked' : '' }}>
                    <span class="form-check-label">Tax Inclusive</span>
                </label>
            @else
                <input type="hidden" name="is_inclusive" value="{{ old('is_inclusive', $taxRate->is_inclusive ?? false) ? 1 : 0 }}">
            @endif
        </div>
    </div>
    @if($isAdvancedMode)
        <div class="col-md-6">
            <label class="form-label">Sales Tax Account</label>
            <select name="sales_account_code" class="form-select @error('sales_account_code') is-invalid @enderror">
                <option value="">None</option>
                @foreach($chartOfAccountOptions as $account)
                    <option value="{{ $account->code }}" @selected(old('sales_account_code', $taxRate->sales_account_code) === $account->code)>{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>
            @error('sales_account_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Purchase Tax Account</label>
            <select name="purchase_account_code" class="form-select @error('purchase_account_code') is-invalid @enderror">
                <option value="">None</option>
                @foreach($chartOfAccountOptions as $account)
                    <option value="{{ $account->code }}" @selected(old('purchase_account_code', $taxRate->purchase_account_code) === $account->code)>{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>
            @error('purchase_account_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $taxRate->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <input type="hidden" name="sales_account_code" value="{{ old('sales_account_code', $taxRate->sales_account_code) }}">
        <input type="hidden" name="purchase_account_code" value="{{ old('purchase_account_code', $taxRate->purchase_account_code) }}">
        <input type="hidden" name="description" value="{{ old('description', $taxRate->description) }}">
    @endif
</div>
