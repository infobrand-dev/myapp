@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Opening Stock</h2>
        <div class="text-muted small">Inisialisasi stok awal per lokasi.</div>
    </div>
    <a href="{{ route('inventory.openings.create') }}" class="btn btn-primary">Buat Opening</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Status</th><th>User</th></tr></thead>
            <tbody>
                @forelse($openings as $opening)
                    <tr>
                        <td>{{ $opening->code }}</td>
                        <td>{{ $opening->opening_date?->format('d/m/Y') }}</td>
                        <td>{{ $opening->location?->name }}</td>
                        <td><span class="badge bg-success-lt text-success">{{ $opening->status }}</span></td>
                        <td>{{ $opening->creator?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada opening stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $openings->links() }}</div>
</div>
@endsection
