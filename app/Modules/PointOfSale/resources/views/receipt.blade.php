<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isReprint ? 'POS Receipt Reprint' : 'POS Receipt' }} {{ $sale->sale_number }}</title>
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
        .reprint-banner { margin-top: .75rem; padding: .45rem .7rem; border: 1px solid #dc2626; border-radius: .75rem; color: #991b1b; background: #fef2f2; font-weight: 700; letter-spacing: .06em; }
        .reprint-meta { margin-top: .6rem; padding-top: .6rem; border-top: 1px dashed #fca5a5; }
        .reprint-form { max-width: 420px; margin: 0 auto 1.5rem; background: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, .08); border-radius: 1rem; padding: 1rem; }
        .reprint-form textarea { width: 100%; min-height: 90px; padding: .75rem; border-radius: .75rem; border: 1px solid #d1d5db; font: inherit; box-sizing: border-box; }
        .reprint-form button, .reprint-form a { display: inline-block; padding: .7rem 1rem; border-radius: .8rem; color: #fff; text-decoration: none; border: 0; cursor: pointer; }
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
            @if(!empty($documentSettings['document_header']))
                <div class="small" style="margin-top:.5rem;">{!! nl2br(e($documentSettings['document_header'])) !!}</div>
            @endif
            @if($isReprint && $reprintLog)
                <div class="reprint-banner">REPRINT / DUPLIKAT</div>
                <div class="reprint-meta small">
                    <div>Reprint #{{ $reprintLog->reprint_sequence }}</div>
                    <div>{{ $reprintLog->created_at->format('d M Y H:i') }} by {{ optional($reprintLog->requester)->name ?: '-' }}</div>
                </div>
            @endif
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
            @if(!empty($documentSettings['receipt_footer']))
                <div class="small" style="text-align:center;">{!! nl2br(e($documentSettings['receipt_footer'])) !!}</div>
            @else
                <div class="small" style="text-align:center;">Please keep this receipt for return or reprint</div>
            @endif
            @if($isReprint && $reprintLog)
                <div class="small strong" style="text-align:center; margin-top:.5rem; color:#991b1b;">Reprint reason: {{ $reprintLog->reason }}</div>
            @endif
        </div>
    </div>

    <div class="no-print" style="text-align:center; margin-bottom: 1.5rem;">
        @if($isReprint)
            <button type="button" onclick="window.print()" style="display:inline-block; padding:.7rem 1rem; border-radius:.8rem; background:#991b1b; color:#fff; text-decoration:none; border:0;">Print Reprint</button>
        @else
            <a href="{{ route('pos.receipts.print', $sale) }}" style="display:inline-block; padding:.7rem 1rem; border-radius:.8rem; background:#111827; color:#fff; text-decoration:none;">Print Original</a>
        @endif
    </div>

    @if(!$isReprint && auth()->check() && auth()->user()->can('pos.reprint-receipt') && Route::has('pos.receipts.reprint'))
        <div class="reprint-form no-print">
            <div class="strong" style="margin-bottom:.35rem;">Reprint Receipt</div>
            <div class="small" style="margin-bottom:.75rem;">Wajib isi alasan. Receipt hasil reprint akan ditandai sebagai duplikat.</div>
            <form method="POST" action="{{ route('pos.receipts.reprint', $sale) }}">
                @csrf
                <textarea name="reason" required minlength="10" maxlength="500" placeholder="Contoh: Customer kehilangan struk original dan meminta salinan untuk arsip.">{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="small" style="color:#b91c1c; margin-top:.5rem;">{{ $message }}</div>
                @enderror
                <div style="display:flex; gap:.5rem; margin-top:.75rem; justify-content:center;">
                    <button type="submit" style="background:#991b1b;">Generate Reprint</button>
                </div>
            </form>
        </div>
    @endif
</body>
</html>
