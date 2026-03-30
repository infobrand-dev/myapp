@extends('layouts.admin')

@section('title', 'Tenant Detail')

@section('content')
    @php
        $money = app(\App\Support\MoneyFormatter::class);
    @endphp
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">{{ $tenant->name }}</h1>
            <div class="text-muted small mt-1">{{ $tenant->slug }} · {{ optional($tenant->created_at)->format('d M Y H:i') }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Tenants
            </a>
            <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-clipboard-list me-1"></i>Katalog Plan
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Status Workspace</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-secondary text-uppercase small fw-bold">Plan Aktif</div>
                        <div class="fw-semibold mt-1">{{ optional(optional($tenant->activeSubscription)->plan)->display_name ?? optional(optional($tenant->activeSubscription)->plan)->name ?? 'Belum ada plan' }}</div>
                    </div>
                    <form method="POST" action="{{ route('platform.tenants.status', $tenant) }}" class="mb-3">
                        @csrf
                        <input type="hidden" name="is_active" value="{{ $tenant->is_active ? 0 : 1 }}">
                        <div class="mb-3">
                            <label class="form-label">Alasan Suspend</label>
                            <textarea class="form-control" name="suspend_reason" rows="3" placeholder="Alasan suspend / nonaktif">{{ old('suspend_reason', ($tenant->meta['suspend_reason'] ?? null)) }}</textarea>
                        </div>
                        <button type="submit"
                            class="btn {{ $tenant->is_active ? 'btn-outline-danger' : 'btn-success' }} w-100"
                            data-confirm="{{ $tenant->is_active ? 'Nonaktifkan tenant ' . $tenant->name . '? Akses workspace akan dihentikan.' : 'Aktifkan kembali tenant ' . $tenant->name . '?' }}"
                            data-loading="{{ $tenant->is_active ? 'Menonaktifkan...' : 'Mengaktifkan...' }}">
                            {{ $tenant->is_active ? 'Nonaktifkan Tenant' : 'Aktifkan Tenant' }}
                        </button>
                    </form>
                    <div class="text-muted small">Gunakan nonaktifkan untuk menghentikan akses workspace tanpa menghapus data.</div>

                    <hr>

                    <div>
                        <div class="text-secondary text-uppercase small fw-bold">AI Credits Bulan Ini</div>
                        <div class="fw-semibold mt-1">
                            {{ number_format($aiSummary['used'] ?? 0) }}
                            / {{ $aiSummary['available'] ?? 'Tidak terbatas' }}
                        </div>
                        <div class="text-muted small mt-1">
                            @if(!($aiSummary['ready'] ?? false))
                                AI usage table belum tersedia.
                            @elseif(($aiSummary['available'] ?? null) === null)
                                Tenant ini tidak dibatasi AI Credits bulanan.
                            @else
                                Termasuk {{ number_format($aiSummary['included'] ?? 0) }}
                                · Top up {{ number_format($aiSummary['top_up'] ?? 0) }}
                                · Sisa {{ number_format($aiSummary['remaining'] ?? 0) }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Catatan Platform</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.tenants.notes', $tenant) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Catatan Internal</label>
                            <textarea class="form-control" name="platform_notes" rows="6" placeholder="Catatan owner platform untuk tenant ini">{{ old('platform_notes', ($tenant->meta['platform_notes'] ?? null)) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary">Simpan Catatan</button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Pengguna Terbaru</h3></div>
                <div class="list-group list-group-flush">
                    @forelse($tenant->users as $user)
                        <div class="list-group-item">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">Belum ada pengguna.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Penggunaan & Batas Plan</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Metrik</th><th>Penggunaan</th><th>Batas</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($usageRows as $row)
                                @php
                                    $statusMap = [
                                        'ok' => ['label' => $row['limit'] === null ? 'Tidak terbatas' : 'OK', 'class' => $row['limit'] === null ? 'bg-azure-lt text-azure' : 'bg-success-lt text-success'],
                                        'near_limit' => ['label' => 'Near limit', 'class' => 'bg-warning-lt text-warning'],
                                        'at_limit' => ['label' => 'At limit', 'class' => 'bg-danger-lt text-danger'],
                                        'over_limit' => ['label' => 'Over limit', 'class' => 'bg-danger-lt text-danger'],
                                    ];
                                    $statusInfo = $statusMap[$row['status']] ?? $statusMap['ok'];
                                @endphp
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['usage'] }}</td>
                                    <td>{{ $row['limit'] ?? 'Tidak terbatas' }}</td>
                                    <td><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Tetapkan / Beli Plan</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.tenants.assign-plan', $tenant) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Plan</label>
                                <select class="form-select" name="subscription_plan_id" required>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected(optional(optional($tenant->activeSubscription)->plan)->id === $plan->id)>{{ $plan->display_name }} ({{ $plan->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="active">Aktif</option>
                                    <option value="trialing">Trial</option>
                                    <option value="past_due">Terlambat Bayar</option>
                                    <option value="cancelled">Dibatalkan</option>
                                    <option value="expired">Kedaluwarsa</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Billing Provider</label>
                                <input type="text" class="form-control" name="billing_provider" placeholder="manual / midtrans / xendit">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Billing Reference</label>
                                <input type="text" class="form-control" name="billing_reference" placeholder="INV-2026-0001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mulai</label>
                                <input type="datetime-local" class="form-control" name="starts_at">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Berakhir</label>
                                <input type="datetime-local" class="form-control" name="ends_at">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Trial Berakhir</label>
                                <input type="datetime-local" class="form-control" name="trial_ends_at">
                            </div>
                            <div class="col-12">
                                <label class="form-check">
                                    <input type="checkbox" class="form-check-input" name="auto_renews" value="1">
                                    <span class="form-check-label">Perpanjang otomatis</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Simpan Langganan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Buat Order Billing</h3></div>
                <div class="card-body">
                    @if(!$ordersReady)
                        <div class="alert alert-warning mb-3">
                            <i class="ti ti-alert-triangle me-2"></i>Billing order table belum tersedia. Jalankan migration terlebih dahulu.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('platform.tenants.orders.store', $tenant) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Plan</label>
                                <select class="form-select" name="subscription_plan_id" required>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->display_name }} ({{ $plan->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah</label>
                                <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mata Uang</label>
                                <input type="text" class="form-control" name="currency" value="IDR">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Periode Billing</label>
                                <input type="text" class="form-control" name="billing_period" value="monthly">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email Pembeli</label>
                                <input type="email" class="form-control" name="buyer_email" placeholder="owner@tenant.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Metode Pembayaran</label>
                                <input type="text" class="form-control" name="payment_channel" value="manual">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mulai</label>
                                <input type="datetime-local" class="form-control" name="starts_at">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Berakhir</label>
                                <input type="datetime-local" class="form-control" name="ends_at">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-outline-primary" @disabled(!$ordersReady)>Buat Order</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Top Up AI Credits</h3></div>
                <div class="card-body">
                    @if(!($aiSummary['transactions_ready'] ?? false))
                        <div class="alert alert-warning mb-3">
                            <i class="ti ti-alert-triangle me-2"></i>Table AI credit transactions belum tersedia. Jalankan migration terlebih dahulu.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('platform.tenants.ai-credits.store', $tenant) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Credits</label>
                                <input type="number" class="form-control" name="credits" min="1" step="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis</label>
                                <input type="text" class="form-control" name="kind" value="top_up">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sumber</label>
                                <input type="text" class="form-control" name="source" value="manual_sale">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referensi</label>
                                <input type="text" class="form-control" name="reference" placeholder="INV-AI-2026-0001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kedaluwarsa</label>
                                <input type="datetime-local" class="form-control" name="expires_at">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Catatan top up AI Credits"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-outline-primary" @disabled(!($aiSummary['transactions_ready'] ?? false))>Tambah AI Credits</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Riwayat Langganan</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Plan</th><th>Status</th><th>Provider</th><th>Referensi</th><th>Periode</th></tr></thead>
                        <tbody>
                            @forelse($tenant->subscriptions->sortByDesc('id') as $subscription)
                                @php
                                    $subStatusMap = [
                                        'active'    => ['label' => 'Aktif',         'class' => 'bg-success-lt text-success'],
                                        'trialing'  => ['label' => 'Trial',          'class' => 'bg-azure-lt text-azure'],
                                        'past_due'  => ['label' => 'Terlambat',      'class' => 'bg-warning-lt text-warning'],
                                        'cancelled' => ['label' => 'Dibatalkan',     'class' => 'bg-danger-lt text-danger'],
                                        'expired'   => ['label' => 'Kedaluwarsa',    'class' => 'bg-secondary-lt text-secondary'],
                                    ];
                                    $subInfo = $subStatusMap[$subscription->status] ?? ['label' => $subscription->status, 'class' => 'bg-secondary-lt text-secondary'];
                                @endphp
                                <tr>
                                    <td>{{ optional($subscription->plan)->display_name ?? optional($subscription->plan)->name ?? '-' }}</td>
                                    <td><span class="badge {{ $subInfo['class'] }}">{{ $subInfo['label'] }}</span></td>
                                    <td>{{ $subscription->billing_provider ?: '-' }}</td>
                                    <td>{{ $subscription->billing_reference ?: '-' }}</td>
                                    <td>{{ optional($subscription->starts_at)->format('d M Y') ?: '-' }} – {{ optional($subscription->ends_at)->format('d M Y') ?: '∞' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada riwayat langganan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Transaksi AI Credits</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Referensi</th><th>Jenis</th><th>Credits</th><th>Kedaluwarsa</th><th>Dibuat</th></tr></thead>
                        <tbody>
                            @forelse(($aiSummary['transactions_ready'] ? $tenant->aiCreditTransactions->sortByDesc('id') : collect()) as $transaction)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $transaction->reference ?: '-' }}</div>
                                        <div class="text-muted small">{{ $transaction->source ?: '-' }}</div>
                                    </td>
                                    <td>{{ $transaction->kind }}</td>
                                    <td>{{ number_format($transaction->credits) }}</td>
                                    <td>{{ optional($transaction->expires_at)->format('d M Y H:i') ?: 'Tidak pernah' }}</td>
                                    <td>{{ optional($transaction->created_at)->format('d M Y H:i') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada transaksi AI Credits.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Order Billing</h3>
                    <a href="{{ route('platform.orders.index') }}" class="btn btn-sm btn-outline-secondary">Semua order</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Order</th><th>Plan</th><th>Status</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse(($ordersReady ? $tenant->planOrders->sortByDesc('id') : collect()) as $order)
                                @php
                                    $orderStatusMap = [
                                        'paid'      => ['label' => 'Lunas',     'class' => 'bg-success-lt text-success'],
                                        'pending'   => ['label' => 'Menunggu',  'class' => 'bg-warning-lt text-warning'],
                                        'draft'     => ['label' => 'Draft',     'class' => 'bg-secondary-lt text-secondary'],
                                        'cancelled' => ['label' => 'Dibatalkan','class' => 'bg-danger-lt text-danger'],
                                        'expired'   => ['label' => 'Kedaluwarsa','class' => 'bg-danger-lt text-danger'],
                                    ];
                                    $orderInfo = $orderStatusMap[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary-lt text-secondary'];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $order->order_number }}</div>
                                        <div class="text-muted small">{{ optional($order->created_at)->format('d M Y H:i') }}</div>
                                    </td>
                                    <td>{{ optional($order->plan)->display_name ?? optional($order->plan)->name ?? '-' }}</td>
                                    <td><span class="badge {{ $orderInfo['class'] }}">{{ $orderInfo['label'] }}</span></td>
                                    <td>{{ $money->format((float) $order->amount, $order->currency) }}</td>
                                    <td class="text-nowrap">
                                        @if($ordersReady && $invoicesReady && $order->invoices->isEmpty())
                                            <form method="POST" action="{{ route('platform.orders.invoice', $order) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    data-confirm="Buat invoice untuk order {{ $order->order_number }}?"
                                                    data-loading="Membuat...">
                                                    Buat Invoice
                                                </button>
                                            </form>
                                        @endif
                                        @if($order->status !== 'paid')
                                            <form method="POST" action="{{ route('platform.orders.mark-paid', $order) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary"
                                                    data-confirm="Tandai order {{ $order->order_number }} sebagai lunas?"
                                                    data-loading="Menyimpan...">
                                                    Tandai Lunas
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($order->status, ['pending', 'draft'], true))
                                            <form method="POST" action="{{ route('platform.orders.cancel', $order) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Batalkan order {{ $order->order_number }}?"
                                                    data-loading="Membatalkan...">
                                                    Batalkan
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada order billing.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title mb-0">Invoice Platform</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Invoice</th><th>Plan</th><th>Status</th><th>Jumlah</th><th>Pembayaran</th></tr></thead>
                        <tbody>
                            @forelse(($invoicesReady ? $tenant->platformInvoices->sortByDesc('id') : collect()) as $invoice)
                                @php
                                    $invStatusMap = [
                                        'paid'    => ['label' => 'Lunas',   'class' => 'bg-success-lt text-success'],
                                        'unpaid'  => ['label' => 'Belum dibayar', 'class' => 'bg-warning-lt text-warning'],
                                        'overdue' => ['label' => 'Jatuh tempo', 'class' => 'bg-danger-lt text-danger'],
                                        'void'    => ['label' => 'Dibatalkan', 'class' => 'bg-secondary-lt text-secondary'],
                                    ];
                                    $invInfo = $invStatusMap[$invoice->status] ?? ['label' => $invoice->status, 'class' => 'bg-secondary-lt text-secondary'];
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('platform.invoices.show', $invoice) }}" class="fw-semibold text-reset">{{ $invoice->invoice_number }}</a>
                                        <div class="text-muted small">{{ optional($invoice->issued_at)->format('d M Y H:i') ?: '-' }}</div>
                                    </td>
                                    <td>{{ optional($invoice->plan)->display_name ?? optional($invoice->plan)->name ?? '-' }}</td>
                                    <td><span class="badge {{ $invInfo['class'] }}">{{ $invInfo['label'] }}</span></td>
                                    <td>{{ $money->format((float) $invoice->amount, $invoice->currency) }}</td>
                                    <td>
                                        @if(!$paymentsReady)
                                            <div class="text-muted small">Payment table belum tersedia.</div>
                                        @elseif($invoice->status !== 'paid')
                                            <form method="POST" action="{{ route('platform.invoices.payments.store', $invoice) }}" class="row g-2">
                                                @csrf
                                                <div class="col-md-4"><input type="number" name="amount" min="0" step="0.01" value="{{ (float) $invoice->amount }}" class="form-control form-control-sm" required></div>
                                                <div class="col-md-4"><input type="text" name="payment_channel" value="manual" class="form-control form-control-sm"></div>
                                                <div class="col-md-4"><button type="submit" class="btn btn-sm btn-primary w-100">Catat Pembayaran</button></div>
                                            </form>
                                        @else
                                            <div class="fw-semibold text-success">Lunas</div>
                                            <div class="text-muted small">{{ optional($invoice->paid_at)->format('d M Y H:i') }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada invoice platform.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
