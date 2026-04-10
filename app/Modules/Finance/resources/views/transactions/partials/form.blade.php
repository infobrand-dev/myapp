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
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="transaction_type" id="finance-transaction-type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                    @foreach($transactionTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="datetime-local" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ $selectedDate }}" required>
                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ $selectedAmount }}" required>
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Account <span class="text-danger">*</span></label>
                <select name="finance_account_id" class="form-select @error('finance_account_id') is-invalid @enderror" required>
                    <option value="">Select account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $selectedAccountId === (string) $account->id)>{{ $account->name }} ({{ \App\Modules\Finance\Models\FinanceAccount::typeOptions()[$account->account_type] ?? $account->account_type }})</option>
                    @endforeach
                </select>
                @error('finance_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Category <span class="text-danger">*</span></label>
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
                    <label class="form-label">Branch</label>
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
                    <label class="form-label d-inline-flex align-items-center gap-1">
                        Cashier Session
                        <span tabindex="0" class="text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="Fill this only if the transaction is related to a specific POS cashier session. Otherwise, leave it blank." style="cursor:help; line-height:1;"><i class="ti ti-info-circle"></i></span>
                    </label>
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
                <label class="form-label">Notes</label>
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

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            if (window.bootstrap && bootstrap.Tooltip) {
                bootstrap.Tooltip.getOrCreateInstance(element);
            }
        });
    });
    </script>
    @endpush
@endonce
