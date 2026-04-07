@extends('layouts.admin')

@section('title', 'Stock Adjustment')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Stock Adjustment</h2>
            <p class="text-muted mb-0">Koreksi stok manual yang dapat diaudit.</p>
        </div>
        <div class="col-auto">
            @can('inventory.manage-stock-adjustment')
                <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Buat Adjustment
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Alasan</th><th>Status</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @forelse($adjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment->code }}</td>
                            <td>{{ $adjustment->adjustment_date?->format('d/m/Y') }}</td>
                            <td>{{ $adjustment->location?->name }}</td>
                            <td>{{ $adjustment->reason_code }}</td>
                            <td>
                                <span class="badge {{ $adjustment->isFinalized() ? 'bg-green-lt text-green' : 'bg-orange-lt text-orange' }}">
                                    {{ $adjustment->status }}
                                </span>
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('inventory.adjustments.show', $adjustment) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-adjustments text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada adjustment.</div>
                                @can('inventory.manage-stock-adjustment')
                                    <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-sm btn-primary">Buat Adjustment</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $adjustments->links() }}</div>
</div>
@endsection
