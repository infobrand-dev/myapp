@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Stock Transfer</h2>
        <div class="text-muted small">Transfer stok antar outlet, gudang, atau lokasi.</div>
    </div>
    <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">Buat Transfer</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Asal</th><th>Tujuan</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($transfers as $transfer)
                    <tr>
                        <td>{{ $transfer->code }}</td>
                        <td>{{ $transfer->transfer_date?->format('d/m/Y') }}</td>
                        <td>{{ $transfer->sourceLocation?->name }}</td>
                        <td>{{ $transfer->destinationLocation?->name }}</td>
                        <td><span class="badge bg-blue-lt text-blue">{{ $transfer->status }}</span></td>
                        <td class="text-end"><a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-outline-secondary btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada transfer.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $transfers->links() }}</div>
</div>
@endsection
