<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\VoidPaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidSalePaymentAction
{
    private $syncPaymentSummary;

    public function __construct(SyncSalePaymentSummaryAction $syncPaymentSummary)
    {
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(SalePayment $payment, array $data, ?User $actor = null): SalePayment
    {
        return DB::transaction(function () use ($payment, $data, $actor) {
            $payment = SalePayment::query()->with('sale')->lockForUpdate()->findOrFail($payment->id);

            if (!$payment->isPosted()) {
                throw ValidationException::withMessages([
                    'payment' => 'Payment ini sudah void.',
                ]);
            }

            $payment->update([
                'status' => SalePayment::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $actor ? $actor->id : null,
                'meta' => array_merge($payment->meta ?? [], [
                    'void_reason' => $data['reason'] ?? null,
                ]),
            ]);

            $centralPaymentId = (int) (($payment->meta['payment_id'] ?? 0));
            if ($centralPaymentId > 0) {
                $centralPayment = Payment::query()->find($centralPaymentId);
                if ($centralPayment && $centralPayment->isPosted()) {
                    app(VoidPaymentAction::class)->execute($centralPayment, [
                        'reason' => $data['reason'] ?? null,
                    ], $actor);
                }
            }

            $this->syncPaymentSummary->execute($payment->sale);

            return $payment->refresh();
        });
    }
}
