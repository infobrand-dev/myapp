@extends('layouts.admin')

@section('content')
<div class="container-xl">
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Buat Period Lock</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.period-locks.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Tanggal mulai</label>
                            <input type="date" name="locked_from" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tanggal akhir</label>
                            <input type="date" name="locked_until" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">Semua branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100">Kunci periode</button>
                        </div>
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
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Dibuat</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($locks as $lock)
                                <tr>
                                    <td>{{ $lock->locked_from->format('Y-m-d') }} s/d {{ $lock->locked_until->format('Y-m-d') }}</td>
                                    <td>{{ ucfirst($lock->status) }}</td>
                                    <td>{{ $lock->notes ?: '-' }}</td>
                                    <td>{{ $lock->creator?->name ?? '-' }}</td>
                                    <td class="text-end">
                                        @if ($lock->status === 'active')
                                            <form method="POST" action="{{ route('finance.period-locks.destroy', $lock) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Release</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada period lock.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $locks->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
