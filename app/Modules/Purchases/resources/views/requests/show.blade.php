@extends('layouts.admin')

@section('title', 'Purchase Request - ' . $requestModel->request_number)

@section('content')
@php $money = app(\App\Support\MoneyFormatter::class); @endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">{{ $requestModel->request_number }}</h2>
            <p class="text-muted mb-0">
                <span class="badge bg-secondary-lt text-secondary me-1">{{ ucfirst($requestModel->status) }}</span>
                {{ optional($requestModel->request_date)->format('d M Y, H:i') ?? '-' }}
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            @if($requestModel->isDraft())
                <a href="{{ route('purchases.requests.edit', $requestModel) }}" class="btn btn-outline-primary">Edit Draft</a>
            @endif
            @if($requestModel->canTransitionTo(\App\Modules\Purchases\Models\PurchaseRequest::STATUS_SUBMITTED))
                <form method="POST" action="{{ route('purchases.requests.status', [$requestModel, 'submitted']) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-primary">Submit</button></form>
            @endif
            @if($requestModel->canTransitionTo(\App\Modules\Purchases\Models\PurchaseRequest::STATUS_APPROVED))
                <form method="POST" action="{{ route('purchases.requests.status', [$requestModel, 'approved']) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-outline-success">Approve</button></form>
            @endif
            @if($requestModel->canTransitionTo(\App\Modules\Purchases\Models\PurchaseRequest::STATUS_REJECTED))
                <form method="POST" action="{{ route('purchases.requests.status', [$requestModel, 'rejected']) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-outline-danger">Reject</button></form>
            @endif
            @if($requestModel->canConvert($requiresApprovalBeforeConversion ?? true))
                <form method="POST" action="{{ route('purchases.requests.convert', $requestModel) }}" class="d-inline-block m-0">@csrf<button type="submit" class="btn btn-primary">Convert to Purchase Order</button></form>
            @endif
            <a href="{{ route('purchases.requests.index') }}" class="btn btn-outline-secondary">Back</a>
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
                            @foreach($requestModel->items as $item)
                                <tr>
                                    <td><div class="fw-semibold">{{ $item->product_name_snapshot }}</div><div class="text-muted small">{{ $item->variant_name_snapshot ?: '-' }}</div></td>
                                    <td>{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                                    <td>{{ $money->format((float) $item->unit_cost, $requestModel->currency_code) }}</td>
                                    <td>{{ $money->format((float) $item->discount_total, $requestModel->currency_code) }}</td>
                                    <td>{{ $money->format((float) $item->tax_total, $requestModel->currency_code) }}</td>
                                    <td>{{ $money->format((float) $item->line_total, $requestModel->currency_code) }}</td>
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
                <div class="mb-3"><div class="text-muted small">Supplier</div><div class="fw-medium">{{ $requestModel->supplier_name_snapshot ?: ($requestModel->supplier ? $requestModel->supplier->name : '-') }}</div></div>
                <div class="mb-3"><div class="text-muted small">Needed By Date</div><div>{{ optional($requestModel->needed_by_date)->format('d M Y') ?? '-' }}</div></div>
                <div class="mb-3"><div class="text-muted small">Lifecycle</div><div class="small">Draft -> Submitted -> Approved -> Converted to Purchase Order</div></div>
                <div class="mb-3"><div class="text-muted small">Approval Rule</div><div class="small">{{ ($requiresApprovalBeforeConversion ?? true) ? 'Approval wajib sebelum convert.' : 'Convert boleh tanpa approval formal.' }}</div></div>
                <div class="d-flex justify-content-between small mb-1"><span>Subtotal</span><span>{{ $money->format((float) $requestModel->subtotal, $requestModel->currency_code) }}</span></div>
                <div class="d-flex justify-content-between small mb-1"><span>Discount</span><span>{{ $money->format((float) $requestModel->discount_total, $requestModel->currency_code) }}</span></div>
                <div class="d-flex justify-content-between small mb-1"><span>Tax</span><span>{{ $money->format((float) $requestModel->tax_total, $requestModel->currency_code) }}</span></div>
                <div class="d-flex justify-content-between small mb-1"><span>Landed Cost</span><span>{{ $money->format((float) $requestModel->landed_cost_total, $requestModel->currency_code) }}</span></div>
                <div class="d-flex justify-content-between fw-semibold border-top pt-2"><span>Grand Total</span><span>{{ $money->format((float) $requestModel->grand_total, $requestModel->currency_code) }}</span></div>
                @if($requestModel->convertedPurchaseOrder)
                    <hr>
                    <div class="text-muted small mb-1">Converted Purchase Order</div>
                    <a href="{{ route('purchases.orders.show', $requestModel->convertedPurchaseOrder) }}" class="fw-semibold text-decoration-none">{{ $requestModel->convertedPurchaseOrder->order_number }}</a>
                @endif
                @if($requestModel->notes)
                    <hr><div class="text-muted small mb-1">Notes</div><div class="small">{{ $requestModel->notes }}</div>
                @endif
                @if($requestModel->internal_notes)
                    <hr><div class="text-muted small mb-1">Internal Notes</div><div class="small">{{ $requestModel->internal_notes }}</div>
                @endif
            </div>
        </div>

        @include('shared.accounting.activity-log', [
            'activities' => $activities,
            'fieldLabels' => [
                'contact_id' => 'Supplier',
                'status' => 'Status',
                'request_date' => 'Request date',
                'needed_by_date' => 'Needed by date',
                'subtotal' => 'Subtotal',
                'discount_total' => 'Discount',
                'tax_total' => 'Tax',
                'landed_cost_total' => 'Landed cost',
                'grand_total' => 'Grand total',
                'currency_code' => 'Currency',
                'notes' => 'Notes',
                'internal_notes' => 'Internal notes',
                'converted_purchase_order_id' => 'Converted purchase order',
            ],
            'money' => $money,
            'currency' => $requestModel->currency_code,
        ])
    </div>
</div>
@endsection
