@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $transaction->transaction_number }}</h2>
        <div class="text-muted small">{{ $transaction->transaction_type }} | {{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '-' }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('finance.transactions.edit', $transaction) }}" class="btn btn-outline-primary">Edit</a>
        <form method="POST" action="{{ route('finance.transactions.destroy', $transaction) }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" data-confirm="Yakin ingin menghapus transaksi ini?">Hapus</button>
        </form>
        <a href="{{ route('finance.transactions.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Header</h3></div>
            <div class="card-body">
                <div class="mb-2"><div class="text-muted small">Type</div><div>{{ $transaction->transaction_type }}</div></div>
                <div class="mb-2"><div class="text-muted small">Amount</div><div>Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</div></div>
                <div class="mb-2"><div class="text-muted small">Category</div><div>{{ $transaction->category ? $transaction->category->name : '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">User</div><div>{{ $transaction->creator ? $transaction->creator->name : '-' }}</div></div>
                <div class="mb-2"><div class="text-muted small">Branch</div><div>{{ $transaction->branch_id ?: '-' }}</div></div>
                @if($shiftEnabled)
                    <div class="mb-2"><div class="text-muted small">Shift</div><div>{{ $transaction->shift ? $transaction->shift->code : '-' }}</div></div>
                @endif
                <div><div class="text-muted small">Notes</div><div>{{ $transaction->notes ?: '-' }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Audit Note</h3></div>
            <div class="card-body">
                <p class="mb-2">Transaction ini bersifat operasional dan immutable. Tidak ada debit, credit, journal, ledger, atau chart of accounts formal.</p>
                <p class="mb-0 text-muted">Future upgrade ke accounting sebaiknya memetakan category ini ke akun akuntansi dan mem-post jurnal otomatis tanpa mengubah histori transaksi finance yang sudah ada.</p>
            </div>
        </div>
    </div>
</div>
@endsection
