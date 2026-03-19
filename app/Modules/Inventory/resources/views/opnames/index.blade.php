@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Stock Opname</h2>
        <div class="text-muted small">Audit stok fisik versus stok sistem per lokasi.</div>
    </div>
    <a href="{{ route('inventory.opnames.create') }}" class="btn btn-primary">Buat Sesi Opname</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Status</th><th>Adjustment</th><th></th></tr></thead>
            <tbody>
                @forelse($opnames as $opname)
                    <tr>
                        <td>{{ $opname->code }}</td>
                        <td>{{ $opname->opname_date ? $opname->opname_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $opname->location ? $opname->location->name : '-' }}</td>
                        <td>
                            <span class="badge {{ $opname->isFinalized() ? 'bg-success-lt text-success' : 'bg-yellow-lt text-yellow' }}">
                                {{ $opname->status }}
                            </span>
                        </td>
                        <td>{{ $opname->adjustment ? $opname->adjustment->code : '-' }}</td>
                        <td class="text-end"><a href="{{ route('inventory.opnames.show', $opname) }}" class="btn btn-outline-secondary btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada stock opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $opnames->links() }}</div>
</div>
@endsection
