@php
    $account = $account ?? new \App\Modules\Finance\Models\FinanceAccount();
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        @include('shared.accounting.field-label', [
            'label' => 'Name',
            'required' => true,
            'tooltip' => 'Nama account yang akan muncul di form finance dan laporan kas, misalnya Kas Toko atau Bank BCA.',
        ])
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name) }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        @include('shared.accounting.field-label', [
            'label' => 'Type',
            'required' => true,
            'tooltip' => 'Pilih jenis account sesuai tempat uang disimpan, seperti cash, bank, atau e-wallet. Tipe ini membantu pengelompokan saldo.',
        ])
        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
            @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('account_type', $account->account_type ?: 'cash') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    @if($isAdvancedMode)
        <div class="col-md-6">
            @include('shared.accounting.field-label', [
                'label' => 'Account Number / Reference',
                'tooltip' => 'Nomor rekening, nomor virtual account, atau kode referensi internal. Boleh dikosongkan jika account tidak punya nomor khusus.',
            ])
            <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number', $account->account_number) }}">
            @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            @include('shared.accounting.field-label', [
                'label' => 'Opening Balance',
                'tooltip' => 'Saldo awal account saat mulai memakai modul finance. Nilai ini menjadi titik awal running balance.',
            ])
            <input type="number" step="0.01" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}">
            @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            @include('shared.accounting.field-label', [
                'label' => 'Opening Balance Date',
                'tooltip' => 'Tanggal efektif saldo awal account. Jika kosong, saldo awal dianggap baseline tanpa tanggal khusus.',
            ])
            <input type="date" name="opening_balance_date" class="form-control @error('opening_balance_date') is-invalid @enderror" value="{{ old('opening_balance_date', optional($account->opening_balance_date)->format('Y-m-d')) }}">
            @error('opening_balance_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
    <div class="col-md-6">
        <label class="form-check">
            <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', $account->exists ? $account->is_active : true))>
            <span class="form-check-label">Active</span>
        </label>
    </div>
    @if($isAdvancedMode)
        <div class="col-md-6">
            <label class="form-check">
                <input type="checkbox" class="form-check-input" name="is_default" value="1" @checked(old('is_default', $account->is_default))>
                <span class="form-check-label">Jadikan default account</span>
            </label>
            <div class="form-hint">Account default akan dipilih otomatis saat user membuat transaksi finance baru.</div>
        </div>
        <div class="col-12">
            @include('shared.accounting.field-label', [
                'label' => 'Notes',
                'tooltip' => 'Catatan internal untuk kebutuhan tim, misalnya batas penggunaan atau tujuan account. Boleh dikosongkan.',
            ])
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="{{ $notesRows ?? 4 }}">{{ old('notes', $account->notes) }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
