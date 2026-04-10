@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
    $selectedBranchId = old('branch_id', $transaction->branch_id ?? old('outlet_id'));
    $defaultBranchLabel = optional($branches->first())->name;
    $usesAllBranchView = $branch === null;
    $categoryTypeLabels = [
        'cash_in' => 'Cash In',
        'cash_out' => 'Cash Out',
        'expense' => 'Operating Expenses',
    ];
    $groupedCategories = $categories->groupBy('transaction_type');
    $accountDefaultId = optional($accounts->firstWhere('is_default', true))->id;
    $selectedAccountId = old('finance_account_id', $transaction->finance_account_id ?? $accountDefaultId);
    $selectedCategoryId = old('finance_category_id', $transaction->finance_category_id ?? null);
    $selectedType = old('transaction_type', $transaction->transaction_type ?? 'cash_out');
    $selectedDate = old('transaction_date', isset($transaction) && $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i'));
    $selectedAmount = old('amount', $transaction->amount ?? null);
    $selectedShiftId = old('pos_cash_session_id', $transaction->pos_cash_session_id ?? null);
    $selectedNotes = old('notes', $transaction->notes ?? null);
@endphp

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h3 class="card-title">{{ $cardTitle }}</h3>
            @include('shared.accounting.mode-badge')
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                @include('shared.accounting.field-label', [
                    'label' => 'Type',
                    'required' => true,
                    'tooltip' => 'Pilih jenis arus kas transaksi. Cash In untuk uang masuk, Cash Out untuk uang keluar non-beban, dan Expense untuk pengeluaran operasional.',
                ])
                <select name="transaction_type" id="finance-transaction-type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                    @foreach($transactionTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                @include('shared.accounting.field-label', [
                    'label' => 'Date',
                    'required' => true,
                    'tooltip' => 'Tanggal dan jam saat transaksi terjadi. Gunakan waktu yang paling mendekati kejadian sebenarnya agar laporan cash flow akurat.',
                ])
                <input type="datetime-local" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ $selectedDate }}" required>
                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                @include('shared.accounting.field-label', [
                    'label' => 'Amount',
                    'required' => true,
                    'tooltip' => 'Isi nominal transaksi yang benar-benar terjadi. Nilai ini akan mempengaruhi ringkasan cash flow.',
                ])
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ $selectedAmount }}" required>
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                @include('shared.accounting.field-label', [
                    'label' => 'Account',
                    'required' => true,
                    'tooltip' => 'Pilih akun kas, bank, atau e-wallet yang menerima atau mengeluarkan uang pada transaksi ini.',
                ])
                <select name="finance_account_id" class="form-select @error('finance_account_id') is-invalid @enderror" required>
                    <option value="">Select account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $selectedAccountId === (string) $account->id)>{{ $account->name }} ({{ \App\Modules\Finance\Models\FinanceAccount::typeOptions()[$account->account_type] ?? $account->account_type }})</option>
                    @endforeach
                </select>
                @error('finance_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                @include('shared.accounting.field-label', [
                    'label' => 'Category',
                    'required' => true,
                    'tooltip' => 'Category membantu pengelompokan arus kas. Pilih category yang sesuai agar laporan finance lebih rapi.',
                ])
                <select name="finance_category_id" id="finance-category-select" class="form-select @error('finance_category_id') is-invalid @enderror" required>
                    <option value="">Select category</option>
                    @foreach($groupedCategories as $transactionType => $categoryGroup)
                        <optgroup label="{{ $categoryTypeLabels[$transactionType] ?? ucfirst(str_replace('_', ' ', $transactionType)) }}">
                            @foreach($categoryGroup as $category)
                                <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('finance_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @if($isAdvancedMode)
                <div class="col-md-3">
                    @include('shared.accounting.field-label', [
                        'label' => 'Branch',
                        'tooltip' => 'Isi jika transaksi terkait branch tertentu. Jika dikosongkan, sistem memakai branch operasional default.',
                    ])
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">Default Branch</option>
                        @foreach($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected((string) $selectedBranchId === (string) $branchOption->id)>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
                    @if($usesAllBranchView || !empty($branchHint))
                        <div class="form-hint">
                            {{ $branchHint ?: 'You are currently in `All branches` mode. If left blank, this transaction will use the default operational branch'.($defaultBranchLabel ? ': '.$defaultBranchLabel : '').'.' }}
                        </div>
                    @endif
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    @include('shared.accounting.field-label', [
                        'label' => 'Cashier Session',
                        'tooltip' => 'Isi jika transaksi ini terkait sesi kasir POS tertentu. Jika tidak terkait POS, field ini boleh dikosongkan.',
                    ])
                    <select name="pos_cash_session_id" class="form-select @error('pos_cash_session_id') is-invalid @enderror">
                        <option value="">Not linked to a cashier session</option>
                        @if($shiftEnabled)
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected((string) $selectedShiftId === (string) $shift->id)>{{ $shift->code }}</option>
                            @endforeach
                        @endif
                    </select>
                    @if(!$shiftEnabled)
                        <div class="form-hint">POS shifts are not available yet. This field remains optional.</div>
                    @endif
                    @error('pos_cash_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif
            <div class="col-12">
                @include('shared.accounting.field-label', [
                    'label' => 'Notes',
                    'tooltip' => 'Catatan tambahan untuk menjelaskan konteks transaksi. Boleh dikosongkan jika transaksinya sudah jelas.',
                ])
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ $selectedNotes }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>Simpan
        </button>
    </div>
</div>
