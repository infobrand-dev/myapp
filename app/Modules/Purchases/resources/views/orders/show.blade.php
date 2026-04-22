@extends('layouts.admin')

@section('title', 'Purchase Order - ' . $order->order_number)

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">{{ $order->order_number }}</h2>
            <p class="text-muted mb-0">
                <span class="badge bg-secondary-lt text-secondary me-1">{{ ucfirst($order->status) }}</span>
                {{ optional($order->order_date)->format('d M Y, H:i') ?? '-' }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @if($order->isDraft())
                <a href="{{ route('purchases.orders.edit', $order) }}" class="btn btn-outline-primary">Edit Draft</a>
            @endif
            @if($order->canTransitionTo(\App\Modules\Purchases\Models\PurchaseOrder::STATUS_SENT))
                <form method="POST" action="{{ route('purchases.orders.status', [$order, 'sent']) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-primary">Mark Sent</button></form>
            @endif
            @if($order->canTransitionTo(\App\Modules\Purchases\Models\PurchaseOrder::STATUS_APPROVED))
                <form method="POST" action="{{ route('purchases.orders.status', [$order, 'approved']) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-outline-success">Approve</button></form>
            @endif
            @if($order->canTransitionTo(\App\Modules\Purchases\Models\PurchaseOrder::STATUS_REJECTED))
                <form method="POST" action="{{ route('purchases.orders.status', [$order, 'rejected']) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-outline-danger">Reject</button></form>
            @endif
            @if($order->canConvert($requiresApprovalBeforeConversion ?? true))
                <form method="POST" action="{{ route('purchases.orders.convert', $order) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-primary">Convert to Draft Purchase</button></form>
            @endif
            <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Items</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit Cost</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td><div class="fw-semibold">{{ $item->product_name_snapshot }}</div><div class="text-muted small">{{ $item->variant_name_snapshot ?: '-' }}</div></td>
                                    <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                    <td>{{ $money->format((float) $item->unit_cost, $order->currency_code) }}</td>
                                    <td>{{ $money->format((float) $item->discount_total, $order->currency_code) }}</td>
                                    <td>{{ $money->format((float) $item->tax_total, $order->currency_code) }}</td>
                                    <td>{{ $money->format((float) $item->line_total, $order->currency_code) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Summary</h3></div>
            <div class="card-body">
                <div class="mb-3"><div class="text-muted small">Supplier</div><div class="fw-medium">{{ $order->supplier_name_snapshot ?: ($order->supplier?->name ?? '-') }}</div></div>
                <div class="mb-3"><div class="text-muted small">Expected Receive Date</div><div>{{ optional($order->expected_receive_date)->format('d M Y') ?? '-' }}</div></div>
                <div class="mb-3"><div class="text-muted small">Lifecycle</div><div class="small">Draft -> Sent -> Approved -> Converted to Draft Purchase</div></div>
                <div class="mb-3"><div class="text-muted small">Approval Rule</div><div class="small">{{ ($requiresApprovalBeforeConversion ?? true) ? 'Approval wajib sebelum convert.' : 'Convert boleh tanpa approval formal.' }}</div></div>
                <div class="d-flex justify-content-between small mb-1"><span>Subtotal</span><span>{{ $money->format((float) $order->subtotal, $order->currency_code) }}</span></div>
                <div class="d-flex justify-content-between small mb-1"><span>Discount</span><span>{{ $money->format((float) $order->discount_total, $order->currency_code) }}</span></div>
                <div class="d-flex justify-content-between small mb-1"><span>Tax</span><span>{{ $money->format((float) $order->tax_total, $order->currency_code) }}</span></div>
                <div class="d-flex justify-content-between small mb-1"><span>Landed Cost</span><span>{{ $money->format((float) $order->landed_cost_total, $order->currency_code) }}</span></div>
                <div class="d-flex justify-content-between fw-semibold border-top pt-2"><span>Grand Total</span><span>{{ $money->format((float) $order->grand_total, $order->currency_code) }}</span></div>
                @if($order->convertedPurchase)
                    <hr>
                    <div class="text-muted small mb-1">Converted Purchase</div>
                    <a href="{{ route('purchases.show', $order->convertedPurchase) }}" class="fw-semibold text-decoration-none">{{ $order->convertedPurchase->purchase_number }}</a>
                @endif
                @if($order->notes)
                    <hr><div class="text-muted small mb-1">Notes</div><div class="small">{{ $order->notes }}</div>
                @endif
                @if($order->internal_notes)
                    <hr><div class="text-muted small mb-1">Internal Notes</div><div class="small">{{ $order->internal_notes }}</div>
                @endif
            </div>
        </div>

        @include('shared.accounting.activity-log', [
            'activities' => $activities,
            'fieldLabels' => [
                'contact_id' => 'Supplier',
                'status' => 'Status',
                'order_date' => 'Order date',
                'expected_receive_date' => 'Expected receive date',
                'subtotal' => 'Subtotal',
                'discount_total' => 'Discount',
                'tax_total' => 'Tax',
                'landed_cost_total' => 'Landed cost',
                'grand_total' => 'Grand total',
                'currency_code' => 'Currency',
                'notes' => 'Notes',
                'internal_notes' => 'Internal notes',
                'converted_purchase_id' => 'Converted purchase',
            ],
            'money' => $money,
            'currency' => $order->currency_code,
        ])
    </div>
</div>
@endsection
