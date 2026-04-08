@extends('layouts.admin')

@section('title', 'Buat Transaksi')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Buat Transaksi</h2>
            <p class="text-muted mb-0">Pencatatan kas masuk dan keluar. Company: {{ $company?->name ?? '-' }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

@php
    $selectedBranchId = old('branch_id', old('outlet_id'));
    $defaultBranchLabel = optional($branches->first())->name;
    $usesAllBranchView = $branch === null;
    $categoryTypeLabels = [
        'cash_in' => 'Kas Masuk',
        'cash_out' => 'Kas Keluar',
        'expense' => 'Biaya Operasional',
    ];
    $groupedCategories = $categories->groupBy('transaction_type');
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('finance.transactions.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi Transaksi</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="transaction_type" id="finance-transaction-type" class="form-select @error('transaction_type') is-invalid @enderror" required>
                        @foreach($transactionTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('transaction_type', 'cash_out') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Pilih tipe dasar transaksi. `Expense` dipakai untuk biaya operasional, bukan sekadar uang keluar biasa.</div>
                    @error('transaction_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account <span class="text-danger">*</span></label>
                    <input type="search"
                           id="finance-account-search"
                           class="form-control mb-2"
                           placeholder="Cari account...">
                    <select name="finance_account_id" id="finance-account-select" class="form-select @error('finance_account_id') is-invalid @enderror" required size="6">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}"
                                    data-account-type="{{ $account->account_type }}"
                                    @selected((string) old('finance_account_id', optional($accounts->firstWhere('is_default', true))->id) === (string) $account->id)>{{ $account->name }} ({{ \App\Modules\Finance\Models\FinanceAccount::typeOptions()[$account->account_type] ?? $account->account_type }})</option>
                        @endforeach
                    </select>
                    <div class="form-hint">Gunakan `cash` untuk kas tunai, `bank` untuk rekening, dan `ewallet` untuk saldo digital.</div>
                    @error('finance_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <input type="search"
                           id="finance-category-search"
                           class="form-control mb-2"
                           placeholder="Cari category...">
                    <select name="finance_category_id" id="finance-category-select" class="form-select @error('finance_category_id') is-invalid @enderror" required size="8">
                        <option value="">Select category</option>
                        @foreach($groupedCategories as $transactionType => $categoryGroup)
                            <optgroup label="{{ $categoryTypeLabels[$transactionType] ?? ucfirst(str_replace('_', ' ', $transactionType)) }}">
                                @foreach($categoryGroup as $category)
                                    <option value="{{ $category->id }}"
                                            data-transaction-type="{{ $category->transaction_type }}"
                                            @selected((string) old('finance_category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-hint">Category otomatis difilter mengikuti type agar user tidak salah pilih.</div>
                    @error('finance_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">Gunakan branch default operasional</option>
                        @foreach($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected((string) $selectedBranchId === (string) $branchOption->id)>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint">
                        @if($usesAllBranchView)
                            Anda sedang melihat mode `Semua branch`. Jika dikosongkan, transaksi akan masuk ke branch default operasional{{ $defaultBranchLabel ? ': '.$defaultBranchLabel : '' }}.
                        @else
                            Jika dikosongkan, transaksi akan mengikuti branch operasional aktif/default.
                        @endif
                    </div>
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label d-inline-flex align-items-center gap-1">
                        Sesi Kasir
                        <span tabindex="0"
                              class="text-muted"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top"
                              title="Isi jika transaksi ini terkait sesi kasir POS tertentu. Jika tidak, biarkan kosong."
                              style="cursor:help; font-weight:700;">(?)</span>
                    </label>
                    <select name="pos_cash_session_id" class="form-select @error('pos_cash_session_id') is-invalid @enderror">
                        <option value="">Tidak terkait sesi kasir</option>
                        @if($shiftEnabled)
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected((string) old('pos_cash_session_id') === (string) $shift->id)>{{ $shift->code }}</option>
                            @endforeach
                        @endif
                    </select>
                    @if(!$shiftEnabled)
                        <div class="form-hint">POS shift belum tersedia. Field ini tetap opsional.</div>
                    @endif
                    @error('pos_cash_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        if (window.bootstrap && bootstrap.Tooltip) {
            bootstrap.Tooltip.getOrCreateInstance(element);
        }
    });

    const typeSelect = document.getElementById('finance-transaction-type');
    const accountSearch = document.getElementById('finance-account-search');
    const accountSelect = document.getElementById('finance-account-select');
    const categorySearch = document.getElementById('finance-category-search');
    const categorySelect = document.getElementById('finance-category-select');

    if (!typeSelect || !categorySelect || !accountSelect) {
        return;
    }

    const categoryOptions = Array.from(categorySelect.options);
    const categoryGroups = Array.from(categorySelect.querySelectorAll('optgroup'));
    const accountOptions = Array.from(accountSelect.options);

    function syncAccountOptions() {
        const searchTerm = (accountSearch?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        accountOptions.forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const visible = searchTerm === '' || option.text.toLowerCase().includes(searchTerm);
            option.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        if (selectedOption && selectedOption.value && selectedOption.hidden) {
            accountSelect.value = '';
        }

        accountSelect.size = Math.min(Math.max(visibleCount + 1, 4), 8);
    }

    function syncCategoryOptions() {
        const currentType = typeSelect.value;
        const searchTerm = (categorySearch?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        categoryOptions.forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionType = option.dataset.transactionType || '';
            const matchesType = optionType === currentType;
            const matchesSearch = searchTerm === '' || option.text.toLowerCase().includes(searchTerm);
            const visible = matchesType && matchesSearch;

            option.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        categoryGroups.forEach((group) => {
            const hasVisibleOption = Array.from(group.querySelectorAll('option')).some((option) => !option.hidden);
            group.hidden = !hasVisibleOption;
        });

        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        if (selectedOption && selectedOption.value && selectedOption.hidden) {
            categorySelect.value = '';
        }

        categorySelect.size = Math.min(Math.max(visibleCount + 1, 4), 8);
    }

    accountSearch?.addEventListener('input', syncAccountOptions);
    typeSelect.addEventListener('change', syncCategoryOptions);
    categorySearch?.addEventListener('input', syncCategoryOptions);
    syncAccountOptions();
    syncCategoryOptions();
});
</script>
@endpush

@endsection
