@extends('layouts.tenant')

@section('title', 'Inventory Locations')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Inventori</div>
            <h2 class="page-title">Inventory Locations</h2>
            <p class="text-muted mb-0">Kelola gudang, staging, return area, dan lokasi stok lain dalam scope aktif.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventory.locations.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Buat Location
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Parent</th>
                        <th>Stocks</th>
                        <th>Children</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                        <tr>
                            <td>{{ $location->code }}</td>
                            <td>
                                <div class="fw-semibold">{{ $location->name }}</div>
                                @if($location->is_default)
                                    <div class="text-muted small">Default location</div>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::headline((string) $location->type) }}</td>
                            <td>{{ $location->parent?->name ?: '-' }}</td>
                            <td>{{ $location->stocks_count }}</td>
                            <td>{{ $location->children_count }}</td>
                            <td>
                                <span class="badge {{ $location->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('inventory.locations.edit', $location) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Edit">
                                    <i class="ti ti-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="ti ti-building-warehouse text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada inventory location.</div>
                                <a href="{{ route('inventory.locations.create') }}" class="btn btn-sm btn-primary">Buat Location</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $locations->links() }}</div>
</div>
@endsection

