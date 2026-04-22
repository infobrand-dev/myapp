@extends('layouts.admin')

@section('title', 'Payment Methods')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Payment Methods</h2>
            <p class="text-muted mb-0">Daftar metode pembayaran yang tersedia.</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Tambah Method</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('payments.methods.store') }}" class="row g-3">
                    @csrf
                    @include('payments::partials.method-form', ['method' => new \App\Modules\Payments\Models\PaymentMethod()])
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-device-floppy me-1"></i>Simpan Method
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Finance Account</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($methods as $method)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $method->name }}</div>
                                        <div class="text-muted small">Sort {{ $method->sort_order }}</div>
                                    </td>
                                    <td>{{ $method->code }}</td>
                                    <td>{{ $typeOptions[$method->type] ?? ucfirst(str_replace('_', ' ', $method->type)) }}</td>
                                    <td>{{ optional($method->financeAccount)->name ?: '-' }}</td>
                                    <td>
                                        @if($method->requires_reference)
                                            <span class="badge bg-azure-lt text-azure">Required</span>
                                        @else
                                            <span class="badge bg-secondary-lt text-secondary">Optional</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($method->is_active)
                                            <span class="badge bg-green-lt text-green">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end align-middle">
                                        <div class="table-actions">
                                            <a href="{{ route('payments.methods.edit', $method) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            @if(!$method->is_system)
                                                <form class="d-inline-block m-0" method="POST" action="{{ route('payments.methods.destroy', $method) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-icon btn-sm btn-outline-danger" type="submit" title="Hapus" data-confirm="Hapus metode '{{ $method->name }}'?">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="ti ti-credit-card text-muted d-block mb-2" style="font-size:2rem;"></i>
                                        <div class="text-muted mb-2">Belum ada metode pembayaran.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
