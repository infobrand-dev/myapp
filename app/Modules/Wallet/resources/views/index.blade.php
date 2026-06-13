@extends('layouts.tenant')

@section('title', 'Wallet')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Commerce</div>
                <h2 class="page-title">Wallet</h2>
                <p class="text-muted mb-0">Ledger wallet immutable untuk gross sale, fee platform, komisi affiliate, refund, dan payout manual.</p>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Pending</div><div class="h3 mb-0">Rp{{ number_format((float) $balances['pending'], 0, ',', '.') }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Available</div><div class="h3 mb-0">Rp{{ number_format((float) $balances['available'], 0, ',', '.') }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Locked</div><div class="h3 mb-0">Rp{{ number_format((float) $balances['locked'], 0, ',', '.') }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Paid Out</div><div class="h3 mb-0">Rp{{ number_format((float) $balances['paid_out'], 0, ',', '.') }}</div></div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><h3 class="card-title mb-0">Request Payout</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('wallet.payout-requests.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Amount</label>
                            <input type="number" min="1" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank</label>
                            <input type="text" name="bank_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="account_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-primary">Kirim Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Ledger Entries</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>State</th>
                                <th>Direction</th>
                                <th>Amount</th>
                                <th>Recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr>
                                    <td>{{ $entry->entry_type }}</td>
                                    <td>{{ $entry->state }}</td>
                                    <td>{{ $entry->direction }}</td>
                                    <td>Rp{{ number_format((float) $entry->amount, 0, ',', '.') }}</td>
                                    <td>{{ optional($entry->recorded_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada ledger entry.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header"><h3 class="card-title mb-0">Payout Requests</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Requested</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Destination</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payoutRequests as $request)
                                <tr>
                                    <td>{{ optional($request->requested_at)->format('d M Y H:i') }}</td>
                                    <td>Rp{{ number_format((float) $request->amount, 0, ',', '.') }}</td>
                                    <td>{{ $request->status }}</td>
                                    <td>{{ data_get($request->destination_snapshot, 'bank_name') }} / {{ data_get($request->destination_snapshot, 'account_number') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada payout request.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

