@extends('layouts.admin')

@section('content')
<div class="container-xl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title mb-1">Approval Requests</h2>
            <div class="text-muted">Approval lintas modul untuk aksi sensitif, termasuk matrix rule berdasarkan module, action, branch, dan threshold nominal.</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Tambah Approval Matrix Rule</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.approvals.rules.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">Company level / semua branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Module</label>
                            <select name="module" class="form-select" id="approval-matrix-module">
                                @foreach ($moduleActionOptions as $module => $actions)
                                    <option value="{{ $module }}" @selected(old('module') === $module)>{{ $module }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select">
                                @foreach ($moduleActionOptions as $module => $actions)
                                    @foreach ($actions as $action)
                                        <option value="{{ $action }}" data-module="{{ $module }}" @selected(old('action') === $action)>{{ $action }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min Amount</label>
                            <input type="number" step="0.01" min="0" name="min_amount" class="form-control" value="{{ old('min_amount', 0) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Approver</label>
                            <input type="number" min="1" max="9" name="required_approvals" class="form-control" value="{{ old('required_approvals', 1) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Backdate Days</label>
                            <input type="number" min="0" max="3650" name="max_backdate_days" class="form-control" value="{{ old('max_backdate_days') }}" placeholder="Kosong = tidak dibatasi">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="maker_checker_required" value="1" @checked(old('maker_checker_required'))>
                                <span class="form-check-label">Maker-checker wajib</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', true))>
                                <span class="form-check-label">Rule aktif</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100">Tambah Rule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Approval Matrix Rules</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Scope</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th class="text-end">Min Amount</th>
                                <th class="text-center">Min Approver</th>
                                <th class="text-center">Maker-Checker</th>
                                <th class="text-center">Backdate</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rules as $rule)
                                <tr>
                                    <td>{{ $rule->branch_id ? ('Branch #' . $rule->branch_id) : 'Company' }}</td>
                                    <td>{{ $rule->module }}</td>
                                    <td>{{ $rule->action }}</td>
                                    <td class="text-end">{{ number_format((float) $rule->min_amount, 2, ',', '.') }}</td>
                                    <td class="text-center">{{ $rule->required_approvals }}</td>
                                    <td class="text-center">{{ $rule->maker_checker_required ? 'Ya' : 'Tidak' }}</td>
                                    <td class="text-center">{{ $rule->max_backdate_days !== null ? ($rule->max_backdate_days . ' hari') : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $rule->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                            {{ $rule->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('finance.approvals.rules.destroy', $rule) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Belum ada approval matrix rule.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
                        <th>Approval</th>
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
                            <td>
                                <div>{{ (int) $approval->current_approvals }} / {{ max(1, (int) $approval->required_approvals) }}</div>
                                @if($approval->decisions->isNotEmpty())
                                    <div class="small text-muted">
                                        {{ $approval->decisions->map(function ($decision) {
                                            return ($decision->approver ? $decision->approver->name : 'Approver') . ' (' . $decision->decision . ')';
                                        })->implode(', ') }}
                                    </div>
                                @endif
                            </td>
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
                            <td colspan="9" class="text-center text-muted py-4">Belum ada approval request.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $approvals->links() }}</div>
    </div>
</div>
@endsection
