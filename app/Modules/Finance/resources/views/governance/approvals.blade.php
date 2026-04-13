@extends('layouts.admin')

@section('content')
<div class="container-xl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title mb-1">Approval Requests</h2>
            <div class="text-muted">Approval untuk void atau edit transaksi sensitif.</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach (['pending', 'approved', 'rejected', 'applied'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="module" class="form-control" placeholder="Module" value="{{ $filters['module'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Module</th>
                        <th>Aksi</th>
                        <th>Subjek</th>
                        <th>Status</th>
                        <th>Peminta</th>
                        <th>Approver</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($approvals as $approval)
                        <tr>
                            <td>#{{ $approval->id }}</td>
                            <td>{{ $approval->module }}</td>
                            <td>{{ $approval->action }}</td>
                            <td>
                                <div>{{ $approval->subject_label }}</div>
                                @if($approval->reason)
                                    <div class="small text-muted">{{ $approval->reason }}</div>
                                @endif
                            </td>
                            <td>{{ ucfirst($approval->status) }}</td>
                            <td>{{ $approval->requester?->name ?? '-' }}</td>
                            <td>{{ $approval->approver?->name ?? '-' }}</td>
                            <td class="text-end">
                                @if ($approval->status === 'pending')
                                    <div class="d-flex gap-2 justify-content-end">
                                        <form method="POST" action="{{ route('finance.approvals.approve', $approval) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-primary">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('finance.approvals.reject', $approval) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada approval request.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $approvals->links() }}</div>
    </div>
</div>
@endsection
