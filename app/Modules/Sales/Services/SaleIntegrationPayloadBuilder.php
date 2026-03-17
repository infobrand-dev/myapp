<?php

namespace App\Modules\Sales\Services;

use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Sales\Models\Sale;

class SaleIntegrationPayloadBuilder
{
    public function build(Sale $sale): array
    {
        $sale->loadMissing(['items', 'paymentAllocations.payment.method', 'statusHistories', 'voidLogs']);

        $payments = $this->paymentsPayload($sale);

        return [
            'id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'external_reference' => $sale->external_reference,
            'status' => $sale->status,
            'payment_status' => $sale->payment_status,
            'source' => $sale->source,
            'transaction_date' => optional($sale->transaction_date)->toDateTimeString(),
            'finalized_at' => optional($sale->finalized_at)->toDateTimeString(),
            'voided_at' => optional($sale->voided_at)->toDateTimeString(),
            'contact_id' => $sale->contact_id,
            'customer_snapshot' => $sale->customer_snapshot,
            'totals' => [
                'subtotal' => (float) $sale->subtotal,
                'discount_total' => (float) $sale->discount_total,
                'tax_total' => (float) $sale->tax_total,
                'grand_total' => (float) $sale->grand_total,
                'paid_total' => (float) $sale->paid_total,
                'balance_due' => (float) $sale->balance_due,
                'currency_code' => $sale->currency_code,
            ],
            'items' => $sale->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'line_no' => $item->line_no,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name_snapshot' => $item->product_name_snapshot,
                    'variant_name_snapshot' => $item->variant_name_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'discount_total' => (float) $item->discount_total,
                    'tax_total' => (float) $item->tax_total,
                    'line_total' => (float) $item->line_total,
                ];
            })->values()->all(),
            'payments' => $payments,
        ];
    }

    private function paymentsPayload(Sale $sale): array
    {
        return PaymentAllocation::query()
            ->with('payment.method')
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', $sale->getKey())
            ->get()
            ->map(function ($allocation) {
                $payment = $allocation->payment;

                return [
                    'id' => $payment ? $payment->id : null,
                    'payment_method' => $payment && $payment->method ? $payment->method->code : null,
                    'payment_method_name' => $payment && $payment->method ? $payment->method->name : null,
                    'amount' => (float) $allocation->amount,
                    'currency_code' => $payment ? $payment->currency_code : null,
                    'payment_date' => $payment ? optional($payment->paid_at)->toDateTimeString() : null,
                    'reference_number' => $payment ? $payment->reference_number : null,
                    'status' => $payment ? $payment->status : null,
                ];
            })
            ->values()
            ->all();
    }
}
