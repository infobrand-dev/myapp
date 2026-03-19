@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Cash Session / Shift</h2>
        <div class="text-muted small">Audit sesi kasir, opening cash, transaksi, pembayaran, dan closing cash.</div>
    </div>
    <a href="{{ route('pos.shifts.create') }}" class="btn btn-primary">Open Shift</a>
</div>

@if($activeSession)
    <div class="alert alert-info">
        Shift aktif: <a href="{{ route('pos.shifts.show', $activeSession) }}">{{ $activeSession->code }}</a>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>Kode</th><th>Cashier</th><th>Outlet</th><th>Opened</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->code }}</td>
                        <td>{{ $session->cashier ? $session->cashier->name : '-' }}</td>
                        <td>{{ $session->outlet_id ?: '-' }}</td>
                        <td>{{ $session->opened_at ? $session->opened_at->format('d/m/Y H:i') : '-' }}</td>
                        <td><span class="badge {{ $session->isActive() ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' }}">{{ $session->status }}</span></td>
                        <td class="text-end"><a href="{{ route('pos.shifts.show', $session) }}" class="btn btn-outline-secondary btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada shift.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $sessions->links() }}</div>
</div>
@endsection
