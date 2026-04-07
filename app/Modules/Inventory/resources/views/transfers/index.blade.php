@extends('layouts.admin')

@section('title', 'Stock Transfer')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Stock Transfer</h2>
            <p class="text-muted mb-0">Transfer stok antar outlet, gudang, atau lokasi.</p>
        </div>
        <div class="col-auto">
            @can('inventory.manage-stock-transfer')
                <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Buat Transfer
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Asal</th><th>Tujuan</th><th>Status</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->code }}</td>
                            <td>{{ $transfer->transfer_date?->format('d/m/Y') }}</td>
                            <td>{{ $transfer->sourceLocation?->name }}</td>
                            <td>{{ $transfer->destinationLocation?->name }}</td>
                            <td><span class="badge bg-blue-lt text-blue">{{ $transfer->status }}</span></td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-transfer text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada transfer.</div>
                                @can('inventory.manage-stock-transfer')
                                    <a href="{{ route('inventory.transfers.create') }}" class="btn btn-sm btn-primary">Buat Transfer</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $transfers->links() }}</div>
</div>
@endsection
