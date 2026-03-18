<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Validation\ValidationException;

class IntegrateRefundToPaymentsAction
{
    public function execute(SaleReturn $saleReturn, array $paymentData, ?User $actor = null): Payment
    {
        if (!$saleReturn->isFinalized()) {
            throw ValidationException::withMessages([
                'sale_return' => 'Refund hanya dapat diproses untuk sales return yang sudah finalized.',
            ]);
        }

        if (!$saleReturn->refund_required) {
            throw ValidationException::withMessages([
                'sale_return' => 'Sales return ini tidak membutuhkan refund.',
            ]);
        }

        $amount = round((float) ($paymentData['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal refund harus lebih besar dari nol.',
            ]);
        }

        if ($amount > (float) $saleReturn->refund_balance) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal refund melebihi sisa refund yang belum diproses.',
            ]);
        }

        /** @var CreatePaymentAction $createPayment */
        $createPayment = app(CreatePaymentAction::class);

        $payment = $createPayment->execute([
            'payment_method_id' => $paymentData['payment_method_id'],
            'amount' => $amount,
            'currency_code' => $paymentData['currency_code'] ?? $saleReturn->currency_code,
            'paid_at' => $paymentData['paid_at'] ?? now(),
            'source' => $paymentData['source'] ?? Payment::SOURCE_MANUAL,
            'reference_number' => $paymentData['reference_number'] ?? null,
            'external_reference' => $paymentData['external_reference'] ?? null,
            'notes' => $paymentData['notes'] ?? ('Refund for ' . $saleReturn->return_number),
            'received_by' => $paymentData['received_by'] ?? ($actor ? $actor->id : null),
            'allocations' => [[
                'payable_type' => 'sale_return',
                'payable_id' => $saleReturn->id,
                'amount' => $amount,
                'meta' => [
                    'kind' => 'refund',
                    'return_number' => $saleReturn->return_number,
                ],
            ]],
            'meta' => array_merge($paymentData['meta'] ?? [], [
                'kind' => 'refund',
                'sale_return_id' => $saleReturn->id,
                'sale_id' => $saleReturn->sale_id,
            ]),
        ], $actor);

        app(SyncSaleReturnRefundSummaryAction::class)->execute($saleReturn);

        return $payment;
    }
}
