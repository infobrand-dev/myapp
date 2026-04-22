@extends('layouts.admin')

@section('content')
<div class="container-xl">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
            <h2 class="page-title mb-1">Journals</h2>
            <div class="text-muted">Auto journal dan manual journal untuk governance accounting.</div>
        </div>
        @can('finance.manage-journal')
            <a href="{{ route('finance.journals.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Manual Journal
            </a>
        @endcan
    </div>

    @include('finance::partials.accounting-nav')

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="entry_type" class="form-control" placeholder="Entry type" value="{{ $filters['entry_type'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All status</option>
                        <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                        <option value="posted" @selected(($filters['status'] ?? '') === 'posted')>Posted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Filter</button>
                    <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @foreach ($journals as $journal)
        @php
            $badgeClass = $journal->status === 'posted' ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow';
            $debitTotal = (float) $journal->lines->sum('debit');
            $creditTotal = (float) $journal->lines->sum('credit');
            $sourceReference = $sourceReferences[$journal->id] ?? [];
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="fw-bold">
                        <a href="{{ route('finance.journals.show', $journal->id) }}">{{ $journal->entry_type }} - {{ $journal->journal_number ?? 'Auto' }}</a>
                    </div>
                    <div class="text-muted small">{{ optional($journal->entry_date)->format('Y-m-d H:i') }} | {{ $journal->description }}</div>
                    <div class="text-muted small mt-1">
                        Source:
                        @if($sourceReference['source_url'] ?? false)
                            <a href="{{ $sourceReference['source_url'] }}">{{ $sourceReference['source_label'] }}</a>
                        @else
                            {{ $sourceReference['source_label'] ?? '-' }}
                        @endif
                    </div>
                    <div class="mt-2">
                        <span class="badge {{ $badgeClass }}">{{ strtoupper($journal->status) }}</span>
                        @if(($journal->meta['manual'] ?? false) === true)
                            <span class="badge bg-blue-lt text-blue">MANUAL</span>
                        @endif
                        @if($journal->reversal_of_journal_id)
                            <span class="badge bg-red-lt text-red">REVERSAL</span>
                        @elseif(!$journal->canBeReversed())
                            <span class="badge bg-secondary-lt text-secondary">REVERSED</span>
                        @endif
                    </div>
                </div>
                @can('finance.manage-journal')
                    @if($journal->entry_type === 'manual')
                        <div class="d-flex gap-2">
                            <a href="{{ route('finance.journals.show', $journal->id) }}" class="btn btn-outline-secondary btn-sm">View</a>
                            @if($journal->status !== 'posted')
                                <a href="{{ route('finance.journals.edit', $journal->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('finance.journals.post', $journal->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Post</button>
                                </form>
                            @elseif($journal->canBeReversed())
                                <a href="{{ route('finance.journals.show', $journal->id) }}#journal-reverse-form" class="btn btn-outline-danger btn-sm">Reverse</a>
                            @endif
                        </div>
                    @else
                        <div class="d-flex gap-2">
                            <a href="{{ route('finance.journals.show', $journal->id) }}" class="btn btn-outline-secondary btn-sm">View</a>
                            @if($journal->canBeReversed())
                                <a href="{{ route('finance.journals.show', $journal->id) }}#journal-reverse-form" class="btn btn-outline-danger btn-sm">Reverse</a>
                            @endif
                        </div>
                    @endif
                @endcan
            </div>
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
                        @foreach ($journal->lines as $line)
                            <tr>
                                <td>{{ $line->account_code }} - {{ $line->account_name }}</td>
                                <td class="text-muted">{{ data_get($line->meta, 'notes', '-') }}</td>
                                <td class="text-end">{{ number_format((float) $line->debit, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $line->credit, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="2" class="text-end">Total</td>
                            <td class="text-end">{{ number_format($debitTotal, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($creditTotal, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    {{ $journals->links() }}
</div>
@endsection
