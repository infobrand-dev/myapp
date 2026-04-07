@extends('layouts.admin')

@section('title', 'Stock Opname')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Stock Opname</h2>
            <p class="text-muted mb-0">Audit stok fisik versus stok sistem per lokasi.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.opnames.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Buat Sesi Opname
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Status</th><th>Adjustment</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @forelse($opnames as $opname)
                        <tr>
                            <td>{{ $opname->code }}</td>
                            <td>{{ $opname->opname_date ? $opname->opname_date->format('d/m/Y') : '-' }}</td>
                            <td>{{ $opname->location ? $opname->location->name : '-' }}</td>
                            <td>
                                <span class="badge {{ $opname->isFinalized() ? 'bg-green-lt text-green' : 'bg-orange-lt text-orange' }}">
                                    {{ $opname->status }}
                                </span>
                            </td>
                            <td>{{ $opname->adjustment ? $opname->adjustment->code : '-' }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('inventory.opnames.show', $opname) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-clipboard-list text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada stock opname.</div>
                                <a href="{{ route('inventory.opnames.create') }}" class="btn btn-sm btn-primary">Buat Sesi Opname</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $opnames->links() }}</div>
</div>
@endsection
