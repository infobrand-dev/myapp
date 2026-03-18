<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Receipt {{ $sale->sale_number }}</title>
    <style>
        body { margin: 0; background: #f3f4f6; font-family: "Courier New", monospace; color: #111827; }
        .receipt-page { max-width: 420px; margin: 1rem auto; background: #fff; box-shadow: 0 20px 40px rgba(0, 0, 0, .12); border-radius: 1rem; overflow: hidden; }
        .receipt-header { padding: 1.25rem 1rem 1rem; text-align: center; background: linear-gradient(180deg, #f8fafc, #ffffff); border-bottom: 1px dashed #d1d5db; }
        .receipt-body { padding: 1rem; }
        .receipt-row, .receipt-line { display: flex; justify-content: space-between; gap: .75rem; }
        .receipt-row { font-size: .87rem; margin-bottom: .35rem; }
        .receipt-line { font-size: .88rem; padding: .5rem 0; border-bottom: 1px dashed #e5e7eb; }
        .receipt-total { border-top: 1px dashed #9ca3af; margin-top: .9rem; padding-top: .9rem; }
        .small { font-size: .78rem; color: #6b7280; }
        .strong { font-weight: 700; }
        @media print {
            body { background: #fff; }
            .receipt-page { margin: 0; max-width: none; box-shadow: none; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body @if($printMode) onload="window.print()" @endif>
    <div class="receipt-page">
        <div class="receipt-header">
            <div class="strong" style="font-size:1.1rem;">MYAPP STORE</div>
            <div class="small">Point Of Sale Receipt</div>
            <div class="small">Invoice {{ $sale->sale_number }}</div>
        </div>

        <div class="receipt-body">
            <div class="receipt-row"><span>Date</span><span>{{ optional($sale->transaction_date)->format('d M Y H:i') }}</span></div>
            <div class="receipt-row"><span>Cashier</span><span>{{ optional($sale->finalizer)->name ?: optional($sale->creator)->name ?: '-' }}</span></div>
            <div class="receipt-row"><span>Customer</span><span>{{ $sale->customer_name_snapshot ?: 'Walk-in Customer' }}</span></div>
            <div class="receipt-row"><span>Channel</span><span>{{ strtoupper($sale->source) }}</span></div>

            <div style="border-top:1px dashed #9ca3af; margin: .9rem 0;"></div>

            @foreach($sale->items as $item)
                <div class="receipt-line">
                    <div>
                        <div class="strong">{{ $item->product_name_snapshot }}</div>
                        <div class="small">{{ $item->variant_name_snapshot ?: $item->sku_snapshot ?: '-' }}</div>
                        <div class="small">{{ number_format((float) $item->qty, 0, ',', '.') }} x {{ number_format((float) $item->unit_price, 0, ',', '.') }}</div>
                    </div>
                    <div class="strong">{{ number_format((float) $item->line_total, 0, ',', '.') }}</div>
                </div>
            @endforeach

            <div class="receipt-total">
                <div class="receipt-row"><span>Subtotal</span><span>{{ number_format((float) $sale->subtotal, 0, ',', '.') }}</span></div>
                <div class="receipt-row"><span>Discount</span><span>{{ number_format((float) $sale->discount_total, 0, ',', '.') }}</span></div>
                <div class="receipt-row"><span>Tax</span><span>{{ number_format((float) $sale->tax_total, 0, ',', '.') }}</span></div>
                <div class="receipt-row strong"><span>Grand Total</span><span>{{ number_format((float) $sale->grand_total, 0, ',', '.') }}</span></div>

                <div style="border-top:1px dashed #9ca3af; margin: .9rem 0;"></div>

                @foreach($sale->paymentAllocations as $allocation)
                    @if($allocation->payment && $allocation->payment->method)
                        <div class="receipt-row">
                            <span>{{ $allocation->payment->method->name }}</span>
                            <span>{{ number_format((float) $allocation->amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                @endforeach
                <div class="receipt-row"><span>Paid</span><span>{{ number_format((float) $sale->paid_total, 0, ',', '.') }}</span></div>
                <div class="receipt-row"><span>Change</span><span>{{ number_format((float) $changeAmount, 0, ',', '.') }}</span></div>
            </div>

            <div style="border-top:1px dashed #9ca3af; margin: .9rem 0;"></div>
            <div class="small" style="text-align:center;">Thank you for shopping</div>
            <div class="small" style="text-align:center;">Please keep this receipt for return or reprint</div>
        </div>
    </div>

    <div class="no-print" style="text-align:center; margin-bottom: 1.5rem;">
        <a href="{{ route('pos.receipts.print', $sale) }}" style="display:inline-block; padding:.7rem 1rem; border-radius:.8rem; background:#111827; color:#fff; text-decoration:none;">Print</a>
    </div>
</body>
</html>
