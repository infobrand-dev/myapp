@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Payment Methods</h2>
        <div class="text-muted small">Daftar metode pembayaran yang tersedia.</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add Method</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('payments.methods.store') }}">
                    @csrf
                    @include('payments::partials.method-form', ['method' => new \App\Modules\Payments\Models\PaymentMethod()])
                    <button type="submit" class="btn btn-primary w-100">Save Method</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
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
                                <td>{{ $method->requires_reference ? 'Required' : 'Optional' }}</td>
                                <td>{{ $method->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <div class="table-actions">
                                        <a href="{{ route('payments.methods.edit', $method) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit" aria-label="Edit">
                                            <i class="ti ti-pencil icon" aria-hidden="true"></i>
                                        </a>
                                        @if(!$method->is_system)
                                            <form class="d-inline-block m-0" method="POST" action="{{ route('payments.methods.destroy', $method) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger btn-icon" type="submit" title="Delete" aria-label="Delete" data-confirm="Hapus metode '{{ $method->name }}'?">
                                                    <i class="ti ti-trash icon" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada metode pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
