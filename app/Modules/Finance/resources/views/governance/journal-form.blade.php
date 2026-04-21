@extends('layouts.admin')

@section('content')
@php
    $isAdvancedMode = ($accountingUiMode ?? 'standard') === 'advanced';
    $journalLines = old('lines', $journal->exists
        ? $journal->lines->map(fn ($line) => [
            'account_code' => $line->account_code,
            'account_name' => $line->account_name,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'notes' => data_get($line->meta, 'notes', ''),
        ])->all()
        : [
            ['account_code' => '', 'account_name' => '', 'debit' => 0, 'credit' => 0, 'notes' => ''],
            ['account_code' => '', 'account_name' => '', 'debit' => 0, 'credit' => 0, 'notes' => ''],
        ]);
@endphp

<div class="container-xl">
    <div class="mb-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h2 class="page-title mb-1">{{ $pageTitle }}</h2>
            <div class="text-muted">Manual journal untuk kebutuhan adjustment dan posting yang tidak berasal langsung dari modul transaksi.</div>
            <div class="text-muted small mt-1">
                Standard mode fokus ke entry inti. Advanced mode membuka kontrol governance seperti status posting, branch override, dan line notes.
            </div>
        </div>
        @include('shared.accounting.mode-badge')
    </div>

    @include('finance::partials.accounting-nav')

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $submitRoute }}">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        <datalist id="manual-journal-account-options">
            @foreach(($chartOfAccounts ?? collect()) as $chartOfAccount)
                <option value="{{ $chartOfAccount->code }}" label="{{ $chartOfAccount->name }}">{{ $chartOfAccount->code }} - {{ $chartOfAccount->name }}</option>
            @endforeach
        </datalist>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Entry Date</label>
                        <input type="datetime-local" name="entry_date" class="form-control @error('entry_date') is-invalid @enderror"
                            value="{{ old('entry_date', optional($journal->entry_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    @if($isAdvancedMode)
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="draft" @selected(old('status', $journal->status ?: 'draft') === 'draft')>Draft</option>
                                <option value="posted" @selected(old('status', $journal->status) === 'posted')>Posted</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch ID</label>
                            <input type="number" min="1" name="branch_id" class="form-control @error('branch_id') is-invalid @enderror"
                                value="{{ old('branch_id', $journal->branch_id) }}" placeholder="Optional">
                        </div>
                    @else
                        <input type="hidden" name="status" value="{{ old('status', $journal->status ?: 'draft') }}">
                        <input type="hidden" name="branch_id" value="{{ old('branch_id', $journal->branch_id) }}">
                    @endif
                    <div class="col-md-{{ $isAdvancedMode ? '12' : '9' }}">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                            value="{{ old('description', $journal->description) }}" placeholder="Contoh: Penyesuaian saldo awal kas kecil">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Journal Lines</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-journal-line>
                    <i class="ti ti-plus me-1"></i>Add Line
                </button>
            </div>
            <div class="card-body">
                <div data-journal-lines>
                    @foreach($journalLines as $index => $line)
                        <div class="border rounded p-3 mb-3 journal-line-row">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label">Account Code</label>
                                    <input type="text" name="lines[{{ $index }}][account_code]" class="form-control"
                                        list="manual-journal-account-options"
                                        data-account-code
                                        value="{{ $line['account_code'] }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Account Name</label>
                                    <input type="text" name="lines[{{ $index }}][account_name]" class="form-control"
                                        data-account-name
                                        value="{{ $line['account_name'] }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Debit</label>
                                    <input type="number" min="0" step="0.01" name="lines[{{ $index }}][debit]" class="form-control"
                                        value="{{ $line['debit'] }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Credit</label>
                                    <input type="number" min="0" step="0.01" name="lines[{{ $index }}][credit]" class="form-control"
                                        value="{{ $line['credit'] }}">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-icon btn-outline-danger" data-remove-journal-line>
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                                @if($isAdvancedMode)
                                    <div class="col-md-12">
                                        <label class="form-label">Notes</label>
                                        <input type="text" name="lines[{{ $index }}][notes]" class="form-control"
                                            value="{{ $line['notes'] }}" placeholder="Optional line note">
                                    </div>
                                @else
                                    <input type="hidden" name="lines[{{ $index }}][notes]" value="{{ $line['notes'] }}">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Journal</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('[data-journal-lines]');
    const addButton = document.querySelector('[data-add-journal-line]');
    const accountOptions = @json(collect($chartOfAccounts ?? [])->mapWithKeys(fn ($account) => [$account->code => $account->name]));
    const isAdvancedMode = @json($isAdvancedMode);

    if (!wrapper || !addButton) return;

    const lineHtml = (index) => `
        <div class="border rounded p-3 mb-3 journal-line-row">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Account Code</label>
                    <input type="text" name="lines[${index}][account_code]" class="form-control" list="manual-journal-account-options" data-account-code value="">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Name</label>
                    <input type="text" name="lines[${index}][account_name]" class="form-control" data-account-name value="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Debit</label>
                    <input type="number" min="0" step="0.01" name="lines[${index}][debit]" class="form-control" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Credit</label>
                    <input type="number" min="0" step="0.01" name="lines[${index}][credit]" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-icon btn-outline-danger" data-remove-journal-line>
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                ${isAdvancedMode ? `
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <input type="text" name="lines[${index}][notes]" class="form-control" value="" placeholder="Optional line note">
                </div>` : `<input type="hidden" name="lines[${index}][notes]" value="">`}
            </div>
        </div>`;

    const syncAccountName = (row) => {
        const codeInput = row.querySelector('[data-account-code]');
        const nameInput = row.querySelector('[data-account-name]');

        if (!codeInput || !nameInput || nameInput.value.trim() !== '') return;

        const accountName = accountOptions[codeInput.value.trim()] ?? null;
        if (accountName) {
            nameInput.value = accountName;
        }
    };

    addButton.addEventListener('click', () => {
        wrapper.insertAdjacentHTML('beforeend', lineHtml(wrapper.querySelectorAll('.journal-line-row').length));
    });

    wrapper.addEventListener('change', (event) => {
        if (event.target.matches('[data-account-code]')) {
            syncAccountName(event.target.closest('.journal-line-row'));
        }
    });

    wrapper.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-journal-line]');
        if (!button) return;

        const rows = wrapper.querySelectorAll('.journal-line-row');
        if (rows.length <= 2) return;

        button.closest('.journal-line-row')?.remove();
    });

    wrapper.querySelectorAll('.journal-line-row').forEach(syncAccountName);
});
</script>
@endpush
