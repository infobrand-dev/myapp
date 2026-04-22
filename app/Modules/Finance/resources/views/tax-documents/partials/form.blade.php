<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Document Type</label>
        <select name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
            @foreach($documentTypeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('document_type', $taxDocument->document_type ?: \App\Modules\Finance\Models\FinanceTaxDocument::TYPE_OUTPUT_VAT) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('document_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="document_status" class="form-select @error('document_status') is-invalid @enderror" required>
            @foreach($documentStatusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('document_status', $taxDocument->document_status ?: \App\Modules\Finance\Models\FinanceTaxDocument::STATUS_DRAFT) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('document_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Tax Master</label>
        <select name="finance_tax_rate_id" class="form-select @error('finance_tax_rate_id') is-invalid @enderror">
            <option value="">Manual / Tanpa Master</option>
            @foreach($taxRateOptions as $taxRate)
                <option value="{{ $taxRate->id }}" @selected((string) old('finance_tax_rate_id', $taxDocument->finance_tax_rate_id) === (string) $taxRate->id)>
                    {{ $taxRate->code }} - {{ $taxRate->name }}
                </option>
            @endforeach
        </select>
        @error('finance_tax_rate_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Source Document</label>
        @php
            $sourceReferenceValue = old('source_reference');
            if ($sourceReferenceValue === null && $taxDocument->source_document_type && $taxDocument->source_document_id) {
                $sourceReferenceValue = class_basename($taxDocument->source_document_type) === 'Sale'
                    ? 'sale:' . $taxDocument->source_document_id
                    : (class_basename($taxDocument->source_document_type) === 'Purchase' ? 'purchase:' . $taxDocument->source_document_id : '');
            }
            $hasCurrentSourceOption = collect($sourceOptions)->contains(function ($sourceOption) use ($sourceReferenceValue) {
                return (string) $sourceOption['value'] === (string) $sourceReferenceValue;
            });
        @endphp
        <select name="source_reference" class="form-select @error('source_reference') is-invalid @enderror">
            <option value="">Manual / Tidak terhubung</option>
            @if($sourceReferenceValue && !$hasCurrentSourceOption && $taxDocument->sourceDocument)
                <option value="{{ $sourceReferenceValue }}" selected>
                    Existing: {{ class_basename($taxDocument->source_document_type) }} #{{ $taxDocument->source_document_id }}
                </option>
            @endif
            @foreach($sourceOptions as $sourceOption)
                <option value="{{ $sourceOption['value'] }}" @selected((string) $sourceReferenceValue === (string) $sourceOption['value'])>{{ $sourceOption['label'] }}</option>
            @endforeach
        </select>
        <div class="form-hint">Pilih sale atau purchase agar nilai dasar pajak dan snapshot partner bisa terisi dari dokumen sumber.</div>
        @error('source_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Document Number</label>
        <input type="text" name="document_number" class="form-control @error('document_number') is-invalid @enderror" value="{{ old('document_number', $taxDocument->document_number) }}">
        @error('document_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">External Number</label>
        <input type="text" name="external_document_number" class="form-control @error('external_document_number') is-invalid @enderror" value="{{ old('external_document_number', $taxDocument->external_document_number) }}">
        @error('external_document_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Document Date</label>
        <input type="date" name="document_date" class="form-control @error('document_date') is-invalid @enderror" value="{{ old('document_date', optional($taxDocument->document_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}" required>
        @error('document_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Transaction Date</label>
        <input type="date" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date', optional($taxDocument->transaction_date)->format('Y-m-d')) }}">
        @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Tax Period Month</label>
        <input type="number" min="1" max="12" name="tax_period_month" class="form-control @error('tax_period_month') is-invalid @enderror" value="{{ old('tax_period_month', $taxDocument->tax_period_month ?: $defaultPeriodMonth) }}" required>
        @error('tax_period_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Tax Period Year</label>
        <input type="number" min="2000" max="2999" name="tax_period_year" class="form-control @error('tax_period_year') is-invalid @enderror" value="{{ old('tax_period_year', $taxDocument->tax_period_year ?: $defaultPeriodYear) }}" required>
        @error('tax_period_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Taxable Base</label>
        <input type="number" min="0" step="0.01" name="taxable_base" class="form-control @error('taxable_base') is-invalid @enderror" value="{{ old('taxable_base', $taxDocument->taxable_base ?: 0) }}" required>
        @error('taxable_base') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Tax Amount</label>
        <input type="number" min="0" step="0.01" name="tax_amount" class="form-control @error('tax_amount') is-invalid @enderror" value="{{ old('tax_amount', $taxDocument->tax_amount ?: 0) }}" required>
        @error('tax_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Withheld Amount</label>
        <input type="number" min="0" step="0.01" name="withheld_amount" class="form-control @error('withheld_amount') is-invalid @enderror" value="{{ old('withheld_amount', $taxDocument->withheld_amount ?: 0) }}">
        @error('withheld_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Currency</label>
        <input type="text" name="currency_code" class="form-control @error('currency_code') is-invalid @enderror" value="{{ old('currency_code', $taxDocument->currency_code ?: 'IDR') }}">
        @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Counterparty Name</label>
        <input type="text" name="counterparty_name_snapshot" class="form-control @error('counterparty_name_snapshot') is-invalid @enderror" value="{{ old('counterparty_name_snapshot', $taxDocument->counterparty_name_snapshot) }}">
        @error('counterparty_name_snapshot') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Counterparty Tax ID</label>
        <input type="text" name="counterparty_tax_id_snapshot" class="form-control @error('counterparty_tax_id_snapshot') is-invalid @enderror" value="{{ old('counterparty_tax_id_snapshot', $taxDocument->counterparty_tax_id_snapshot) }}">
        @error('counterparty_tax_id_snapshot') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Counterparty Tax Name</label>
        <input type="text" name="counterparty_tax_name_snapshot" class="form-control @error('counterparty_tax_name_snapshot') is-invalid @enderror" value="{{ old('counterparty_tax_name_snapshot', $taxDocument->counterparty_tax_name_snapshot) }}">
        @error('counterparty_tax_name_snapshot') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Counterparty Tax Address</label>
        <textarea name="counterparty_tax_address_snapshot" class="form-control @error('counterparty_tax_address_snapshot') is-invalid @enderror" rows="2">{{ old('counterparty_tax_address_snapshot', $taxDocument->counterparty_tax_address_snapshot) }}</textarea>
        @error('counterparty_tax_address_snapshot') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Reference Note</label>
        <textarea name="reference_note" class="form-control @error('reference_note') is-invalid @enderror" rows="3">{{ old('reference_note', $taxDocument->reference_note) }}</textarea>
        @error('reference_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
