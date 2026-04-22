@extends('layouts.admin')

@section('title', 'Opening Stock')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Opening Stock</h2>
            <p class="text-muted mb-0">Inisialisasi stok awal per lokasi.</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a href="{{ route('inventory.openings.import-page') }}" class="btn btn-outline-secondary">
                <i class="ti ti-file-import me-1"></i>Import Opening
            </a>
            <a href="{{ route('inventory.openings.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Buat Opening
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Status</th><th>Journal</th><th>User</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @forelse($openings as $opening)
                        @php
                            $journal = ($journals ?? collect())->get($opening->id);
                        @endphp
                        <tr>
                            <td>{{ $opening->code }}</td>
                            <td>{{ $opening->opening_date?->format('d/m/Y') }}</td>
                            <td>{{ $opening->location?->name }}</td>
                            <td><span class="badge bg-green-lt text-green">{{ $opening->status }}</span></td>
                            <td>
                                @if($journal)
                                    <a href="{{ route('finance.journals.show', $journal->id) }}">{{ $journal->journal_number ?: ('Journal #' . $journal->id) }}</a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>{{ $opening->creator?->name ?? '-' }}</td>
                            <td class="text-end align-middle">
                                {{-- Opening stock adalah read-only setelah selesai --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-box text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada opening stock.</div>
                                <a href="{{ route('inventory.openings.create') }}" class="btn btn-sm btn-primary">Buat Opening</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $openings->links() }}</div>
</div>
@endsection
