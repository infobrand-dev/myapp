@extends('layouts.tenant')

@section('title', 'Wallet Payout Queue')

@section('content')
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Platform Wallet</div>
                <h2 class="page-title">Payout Queue</h2>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Destination</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        <tr>
                            <td>#{{ $request->tenant_id }}</td>
                            <td>Rp{{ number_format((float) $request->amount, 0, ',', '.') }}</td>
                            <td>{{ $request->status }}</td>
                            <td>{{ data_get($request->destination_snapshot, 'bank_name') }} / {{ data_get($request->destination_snapshot, 'account_number') }}</td>
                            <td class="text-end">
                                @if($request->status === 'requested')
                                    <form method="POST" action="{{ route('platform.wallet.payouts.approve', $request) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Approve</button></form>
                                    <form method="POST" action="{{ route('platform.wallet.payouts.reject', $request) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                                @elseif($request->status === 'approved')
                                    <form method="POST" action="{{ route('platform.wallet.payouts.mark-paid', $request) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Mark Paid</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada payout queue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

