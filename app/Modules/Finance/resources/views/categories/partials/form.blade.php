@php
    $category = $category ?? new \App\Modules\Finance\Models\FinanceCategory();
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        @include('shared.accounting.field-label', [
            'label' => 'Name',
            'required' => true,
            'tooltip' => 'Nama category untuk mengelompokkan transaksi finance, misalnya Biaya Operasional atau Pendapatan Lainnya.',
        ])
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        @include('shared.accounting.field-label', [
            'label' => 'Type',
            'required' => true,
            'tooltip' => 'Type category harus sesuai dengan arah transaksi. Ini mencegah cash in tercatat sebagai expense atau sebaliknya.',
        ])
        <select name="transaction_type" class="form-select @error('transaction_type') is-invalid @enderror" required>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('transaction_type', $category->transaction_type ?: 'expense') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true))>
            <span class="form-check-label">Active</span>
        </label>
    </div>
    @if($isAdvancedMode)
        <div class="col-12">
            @include('shared.accounting.field-label', [
                'label' => 'Notes',
                'tooltip' => 'Catatan tambahan untuk menjelaskan penggunaan category ini. Boleh dikosongkan jika category sudah cukup jelas.',
            ])
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="{{ $notesRows ?? 4 }}">{{ old('notes', $category->notes) }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
