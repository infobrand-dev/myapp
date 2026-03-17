@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $transfer->code }}</h2>
        <div class="text-muted small">{{ $transfer->sourceLocation?->name }} -> {{ $transfer->destinationLocation?->name }}</div>
    </div>
    <div class="d-flex gap-2">
        @if($transfer->status === 'draft')
            <form method="POST" action="{{ route('inventory.transfers.approve', $transfer) }}">@csrf<button class="btn btn-outline-primary">Approve</button></form>
        @endif
        @if(in_array($transfer->status, ['draft', 'approved'], true))
            <form method="POST" action="{{ route('inventory.transfers.send', $transfer) }}">@csrf<button class="btn btn-primary">Send</button></form>
        @endif
        @if($transfer->status === 'sent')
            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}">@csrf<button class="btn btn-success">Receive</button></form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-2"><div class="text-muted small">Tanggal</div><div>{{ $transfer->transfer_date?->format('d/m/Y') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Status</div><div><span class="badge bg-blue-lt text-blue">{{ $transfer->status }}</span></div></div>
                <div class="mb-2"><div class="text-muted small">Asal</div><div>{{ $transfer->sourceLocation?->name }}</div></div>
                <div class="mb-2"><div class="text-muted small">Tujuan</div><div>{{ $transfer->destinationLocation?->name }}</div></div>
                <div><div class="text-muted small">Catatan</div><div>{{ $transfer->notes ?: '-' }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Items</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead><tr><th>Produk</th><th>Requested</th><th>Sent</th><th>Received</th></tr></thead>
                    <tbody>
                        @foreach($transfer->items as $item)
                            <tr>
                                <td>{{ $item->product?->name }} @if($item->variant)<div class="text-muted small">{{ $item->variant->name }}</div>@endif</td>
                                <td>{{ number_format((float) $item->requested_quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->sent_quantity, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->received_quantity, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
