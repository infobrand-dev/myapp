<?php

namespace App\Modules\Payments\Actions;

use App\Models\AccountingJournal;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidPaymentAction
{

    private $recalculatePaymentSummary;

    public function __construct(
        RecalculatePaymentSummaryAction $recalculatePaymentSummary,
        private readonly SensitiveActionApprovalService $approvalService,
        private readonly AccountingPeriodLockService $periodLockService,
        private readonly AccountingJournalService $journalService
    )
    {
        $this->recalculatePaymentSummary = $recalculatePaymentSummary;
    }

    public function execute(Payment $payment, array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($payment, $data, $actor) {
            $payment = Payment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
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

            $this->periodLockService->ensureDateOpen($payment->paid_at ?: now(), $payment->branch_id, 'void payment');
            $this->approvalService->ensureApprovedOrCreatePending(
                'payments',
                'void_payment',
                $payment,
                ['reason' => $reason, 'amount' => (float) $payment->amount],
                $actor,
                $reason
            );

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
                'company_id' => CompanyContext::currentId(),
                'from_status' => $previousStatus,
                'to_status' => Payment::STATUS_VOIDED,
                'event' => 'voided',
                'reason' => $reason,
                'meta' => [
                    'amount' => (float) $payment->amount,
                    'allocation_count' => $payment->allocations->count(),
                    'reconciliation_status' => $payment->reconciliation_status,
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $payment->voidLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
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

            $originalJournal = AccountingJournal::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('entry_type', 'payment_posted')
                ->where('source_type', Payment::class)
                ->where('source_id', $payment->id)
                ->with('lines')
                ->first();

            if ($originalJournal && $originalJournal->lines->isNotEmpty()) {
                $this->journalService->sync(
                    $payment,
                    'payment_void',
                    now(),
                    $originalJournal->lines->map(fn ($line) => [
                        'account_code' => $line->account_code,
                        'account_name' => $line->account_name,
                        'debit' => (float) $line->credit,
                        'credit' => (float) $line->debit,
                    ])->all(),
                    ['reason' => $reason],
                    'Reversal journal payment void ' . $payment->payment_number
                );
            }

            return $payment->load(['method', 'receiver', 'allocations.payable', 'voider']);
        });
    }
}
