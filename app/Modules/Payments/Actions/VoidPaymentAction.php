<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidPaymentAction
{

    private $recalculatePaymentSummary;

    public function __construct(RecalculatePaymentSummaryAction $recalculatePaymentSummary)
    {
        $this->recalculatePaymentSummary = $recalculatePaymentSummary;
    }

    public function execute(Payment $payment, array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($payment, $data, $actor) {
            $payment = Payment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with('allocations.payable')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (!$payment->isPosted()) {
                throw ValidationException::withMessages([
                    'payment' => 'Payment ini tidak bisa di-void lagi.',
                ]);
            }

            $reason = trim((string) ($data['reason'] ?? ''));
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Reason void wajib diisi.',
                ]);
            }

            $previousStatus = $payment->status;

            $payment->update([
                'status' => Payment::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $actor ? $actor->id : null,
                'void_reason' => $reason,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $payment->statusLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'from_status' => $previousStatus,
                'to_status' => Payment::STATUS_VOIDED,
                'event' => 'voided',
                'reason' => $reason,
                'meta' => null,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $payment->voidLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'status_before' => $previousStatus,
                'reason' => $reason,
                'snapshot' => [
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) $payment->amount,
                    'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                    'reference_number' => $payment->reference_number,
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $payment->allocations
                ->pluck('payable')
                ->filter()
                ->unique(fn ($payable) => get_class($payable) . ':' . $payable->getKey())
                ->each(fn ($payable) => $this->recalculatePaymentSummary->execute($payable));

            return $payment->load(['method', 'receiver', 'allocations.payable', 'voider']);
        });
    }
}
