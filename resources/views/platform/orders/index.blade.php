@extends('layouts.admin')

@section('title', 'Platform Orders')

@section('content')
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <div class="page-pretitle">Platform Owner</div>
            <h1 class="page-title">Plan Orders</h1>
            <div class="text-muted small mt-1">Kelola order pembelian plan tenant dan tandai pembayaran secara manual.</div>
        </div>
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    @if(!$ordersReady)
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>Billing order table belum tersedia. Jalankan migration terlebih dahulu agar halaman ini aktif.
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Tenant</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Jumlah</th>
                        <th>Invoice</th>
                        <th>Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $statusMap = [
                                'paid'      => ['label' => 'Lunas',     'class' => 'bg-success-lt text-success'],
                                'pending'   => ['label' => 'Menunggu',  'class' => 'bg-warning-lt text-warning'],
                                'draft'     => ['label' => 'Draft',     'class' => 'bg-secondary-lt text-secondary'],
                                'cancelled' => ['label' => 'Dibatalkan','class' => 'bg-danger-lt text-danger'],
                                'expired'   => ['label' => 'Kedaluwarsa','class' => 'bg-danger-lt text-danger'],
                            ];
                            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'bg-secondary-lt text-secondary'];
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $order->order_number }}</div>
                                <div class="text-muted small">{{ optional($order->created_at)->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                <a href="{{ route('platform.tenants.show', $order->tenant) }}" class="text-reset fw-semibold">{{ optional($order->tenant)->name ?? '-' }}</a>
                                <div class="text-muted small">{{ optional($order->tenant)->slug }}</div>
                            </td>
                            <td>{{ optional($order->plan)->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                            </td>
                            <td>{{ number_format((float) $order->amount, 0, ',', '.') }} {{ $order->currency }}</td>
                            <td>
                                @php $invoice = $order->invoices->first(); @endphp
                                @if($invoice)
                                    <a href="{{ route('platform.invoices.show', $invoice) }}" class="fw-semibold text-reset">{{ $invoice->invoice_number }}</a>
                                    <div class="text-muted small">{{ $invoice->status }}</div>
                                @else
                                    <span class="text-muted small">Belum ada invoice</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $order->payment_channel ?: '-' }}</div>
                                <div class="text-muted small">{{ optional($order->paid_at)->format('d M Y H:i') ?: 'Belum dibayar' }}</div>
                            </td>
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
                                            data-confirm="Batalkan order {{ $order->order_number }}? Tindakan ini tidak dapat dibatalkan."
                                            data-loading="Membatalkan...">
                                            Batalkan
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="ti ti-receipt text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted">Belum ada order.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($orders, 'links'))
        <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
