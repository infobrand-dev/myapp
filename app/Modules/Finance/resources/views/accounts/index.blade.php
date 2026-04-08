@extends('layouts.admin')

@section('title', 'Finance Accounts')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Keuangan</div>
            <h2 class="page-title">Finance Accounts</h2>
            <p class="text-muted mb-0">Pisahkan sumber dana operasional seperti cash, bank, dan e-wallet tanpa memisah domain transaksi finance.</p>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Account</h3>
            </div>
            <form method="POST" action="{{ route('finance.accounts.store') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                @foreach($typeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('account_type', 'cash') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Number / Reference</label>
                            <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}">
                            @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked(old('is_active', true))>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_default" value="1" @checked(old('is_default', false))>
                                <span class="form-check-label">Jadikan default account</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Simpan
                    </button>
                </div>
            </form>
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
                                <th>Type</th>
                                <th>Status</th>
                                <th>Transactions</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $account->name }}</div>
                                        @if($account->account_number)
                                            <div class="text-muted small">{{ $account->account_number }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-azure-lt text-azure">{{ $typeOptions[$account->account_type] ?? $account->account_type }}</span>
                                        @if($account->is_default)
                                            <span class="badge bg-primary-lt text-primary ms-1">Default</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->is_active)
                                            <span class="badge bg-green-lt text-green">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-lt text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>{{ $account->transactions_count }}</td>
                                    <td class="text-end align-middle">
                                        <div class="table-actions">
                                            <a href="{{ route('finance.accounts.edit', $account) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            @if($account->transactions_count === 0)
                                                <form class="d-inline-block m-0" method="POST" action="{{ route('finance.accounts.destroy', $account) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Hapus" data-confirm="Hapus account ini?">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="ti ti-building-bank text-muted d-block mb-2" style="font-size:2rem;"></i>
                                        <div class="text-muted mb-2">Belum ada finance account.</div>
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
