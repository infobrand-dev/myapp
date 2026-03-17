@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Stock Adjustment</h2>
        <div class="text-muted small">Penambahan atau pengurangan stok manual yang dapat diaudit.</div>
    </div>
    <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary">Buat Adjustment</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Alasan</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($adjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->code }}</td>
                        <td>{{ $adjustment->adjustment_date?->format('d/m/Y') }}</td>
                        <td>{{ $adjustment->location?->name }}</td>
                        <td>{{ $adjustment->reason_code }}</td>
                        <td><span class="badge bg-success-lt text-success">{{ $adjustment->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada adjustment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $adjustments->links() }}</div>
</div>
@endsection
