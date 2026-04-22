@extends('layouts.admin')

@section('content')
<div class="container-xl">
    @php
        $money = app(\App\Support\MoneyFormatter::class);
        $currency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
        $debitTotal = (float) $journal->lines->sum('debit');
        $creditTotal = (float) $journal->lines->sum('credit');
        $badgeClass = $journal->status === 'posted' ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow';
        $canReverse = auth()->user() && auth()->user()->can('finance.manage-journal') && $journal->canBeReversed();
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-3 gap-3 flex-wrap">
        <div>
            <h2 class="page-title mb-1">Journal Detail</h2>
            <div class="text-muted">{{ $journal->entry_type }} - {{ $journal->journal_number ?: ('Journal #' . $journal->id) }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary">Back</a>
            @if($sourceReference['source_url'] ?? false)
                <a href="{{ $sourceReference['source_url'] }}" class="btn btn-primary">Open Source</a>
            @endif
        </div>
    </div>

    @include('finance::partials.accounting-nav')

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Entry Date</div>
                    <div class="fw-semibold">{{ optional($journal->entry_date)->format('d/m/Y H:i') ?: '-' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Status</div>
                    <div>
                        <span class="badge {{ $badgeClass }}">{{ strtoupper($journal->status) }}</span>
                        @if($journal->reversal_of_journal_id)
                            <span class="badge bg-red-lt text-red">REVERSAL</span>
                        @elseif(($journal->meta['reversed_by_journal_id'] ?? null) !== null)
                            <span class="badge bg-secondary-lt text-secondary">REVERSED</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Source</div>
                    <div class="fw-semibold">
                        @if($sourceReference['source_url'] ?? false)
                            <a href="{{ $sourceReference['source_url'] }}">{{ $sourceReference['source_label'] }}</a>
                        @else
                            {{ $sourceReference['source_label'] ?? '-' }}
                        @endif
                    </div>
                    <div class="text-muted small">{{ $sourceReference['source_type_label'] ?? 'Source Document' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Branch ID</div>
                    <div class="fw-semibold">{{ $journal->branch_id ?: '-' }}</div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Created By</div>
                    <div class="fw-semibold">{{ optional($journal->creator)->name ?: '-' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted small">Description</div>
                    <div>{{ $journal->description ?: '-' }}</div>
                </div>
                @if($journal->reversalOf)
                    <div class="col-md-6">
                        <div class="text-muted small">Reversal Of</div>
                        <div class="fw-semibold"><a href="{{ route('finance.journals.show', $journal->reversalOf->id) }}">{{ $journal->reversalOf->journal_number ?: ('Journal #' . $journal->reversalOf->id) }}</a></div>
                    </div>
                @endif
                @if(($journal->meta['reversed_by_journal_id'] ?? null) !== null)
                    <div class="col-md-6">
                        <div class="text-muted small">Reversed By</div>
                        <div class="fw-semibold"><a href="{{ route('finance.journals.show', $journal->meta['reversed_by_journal_id']) }}">Journal #{{ $journal->meta['reversed_by_journal_id'] }}</a></div>
                        <div class="text-muted small">{{ $journal->meta['reversal_reason'] ?? '' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($canReverse)
        <div class="card mb-3" id="journal-reverse-form">
            <div class="card-header"><h3 class="card-title mb-0">Reverse Journal</h3></div>
            <div class="card-body">
                <div class="text-muted mb-3">Reversal akan membuat journal posted baru dengan debit/kredit dibalik. Journal asli tetap disimpan untuk audit trail.</div>
                <form method="POST" action="{{ route('finance.journals.reverse', $journal->id) }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Entry Date</label>
                        <input type="datetime-local" name="entry_date" class="form-control" value="{{ old('entry_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" maxlength="500" value="{{ old('reason') }}" placeholder="Contoh: salah posting ke account yang tidak tepat">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100">Create Reversal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Journal Lines</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journal->lines as $line)
                        <tr>
                            <td>{{ $line->account_code }} - {{ $line->account_name }}</td>
                            <td class="text-muted">{{ data_get($line->meta, 'notes', '-') }}</td>
                            <td class="text-end">{{ $money->format((float) $line->debit, $currency) }}</td>
                            <td class="text-end">{{ $money->format((float) $line->credit, $currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-end">{{ $money->format($debitTotal, $currency) }}</td>
                        <td class="text-end">{{ $money->format($creditTotal, $currency) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
