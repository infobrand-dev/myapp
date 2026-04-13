@extends('layouts.admin')

@section('content')
<div class="container-xl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title mb-1">Auto Journal</h2>
            <div class="text-muted">Ringkasan posting otomatis dari transaksi accounting.</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="entry_type" class="form-control" placeholder="Entry type" value="{{ $filters['entry_type'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($journals as $journal)
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <div class="fw-bold">{{ $journal->entry_type }} - {{ $journal->journal_number ?? 'Auto' }}</div>
                    <div class="text-muted small">{{ optional($journal->entry_date)->format('Y-m-d H:i') }} | {{ $journal->description }}</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journal->lines as $line)
                            <tr>
                                <td>{{ $line->account_code }} - {{ $line->account_name }}</td>
                                <td class="text-end">{{ number_format((float) $line->debit, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $line->credit, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{ $journals->links() }}
</div>
@endsection
