@extends('layouts.admin')

@section('title', 'Cash Session / Shift')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Point of Sale</div>
            <h2 class="page-title">Cash Session / Shift</h2>
            <p class="text-muted mb-0">Audit sesi kasir, opening cash, transaksi, pembayaran, dan closing cash.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('pos.shifts.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Open Shift
            </a>
        </div>
    </div>
</div>

@if($activeSession)
    <div class="alert alert-azure">
        Shift aktif: <a href="{{ route('pos.shifts.show', $activeSession) }}"><strong>{{ $activeSession->code }}</strong></a>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead><tr><th>Kode</th><th>Cashier</th><th>Branch</th><th>Opened</th><th>Status</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>{{ $session->code }}</td>
                            <td>{{ $session->cashier ? $session->cashier->name : '-' }}</td>
                            <td>{{ $session->branch_id ?: '-' }}</td>
                            <td>{{ $session->opened_at ? $session->opened_at->format('d/m/Y H:i') : '-' }}</td>
                            <td><span class="badge {{ $session->isActive() ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">{{ $session->status }}</span></td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('pos.shifts.show', $session) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-clock text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada shift.</div>
                                <a href="{{ route('pos.shifts.create') }}" class="btn btn-sm btn-primary">Open Shift</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $sessions->links() }}</div>
</div>
@endsection
