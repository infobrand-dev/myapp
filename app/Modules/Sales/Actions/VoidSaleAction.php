<?php

namespace App\Modules\Sales\Actions;

use App\Models\AccountingJournal;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Events\SaleVoided;
use App\Modules\Sales\Models\Sale;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidSaleAction
{
    public function __construct(
        private readonly SensitiveActionApprovalService $approvalService,
        private readonly AccountingPeriodLockService $periodLockService,
        private readonly AccountingJournalService $journalService
    ) {
    }

    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        $sale = DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (!$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya sale final yang dapat di-void.',
                ]);
            }

            $hasPostedPayments = $sale->paymentAllocations()
                ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
                ->exists();

            if ($hasPostedPayments) {
                throw ValidationException::withMessages([
                    'sale' => 'Sale yang masih memiliki payment posted tidak dapat di-void. Void/refund payment terlebih dahulu.',
                ]);
            }

            $reason = trim((string) ($data['reason'] ?? ''));
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Reason void wajib diisi.',
                ]);
            }

            $this->periodLockService->ensureDateOpen($sale->transaction_date ?: now(), $sale->branch_id, 'void sale');
            $this->approvalService->ensureApprovedOrCreatePending(
                'sales',
                'void_sale',
                $sale,
                ['reason' => $reason, 'status' => $sale->status],
                $actor,
                $reason
            );

            $statusBefore = $sale->status;
            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'void_reason' => $reason,
                'voided_at' => now(),
                'voided_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $snapshot = $sale->load('items')->toArray();

            $sale->voidLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'status_before' => $statusBefore,
                'reason' => $reason,
                'snapshot' => $snapshot,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'from_status' => $statusBefore,
                'to_status' => Sale::STATUS_VOIDED,
                'event' => 'voided',
                'reason' => $reason,
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'voided_at' => now()->toDateTimeString(),
                    'grand_total' => (float) $sale->grand_total,
                    'paid_total' => (float) $sale->paid_total,
                    'payment_status' => $sale->payment_status,
                ],
            ]);

            $originalJournal = AccountingJournal::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('entry_type', 'sale_finalized')
                ->where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->with('lines')
                ->first();

            if ($originalJournal && $originalJournal->lines->isNotEmpty()) {
                $this->journalService->sync(
                    $sale,
                    'sale_void',
                    now(),
                    $originalJournal->lines->map(fn ($line) => [
                        'account_code' => $line->account_code,
                        'account_name' => $line->account_name,
                        'debit' => (float) $line->credit,
                        'credit' => (float) $line->debit,
                    ])->all(),
                    ['reason' => $reason],
                    'Reversal journal sale void ' . $sale->sale_number
                );
            }

            return $sale->load('voidLogs', 'statusHistories');
        });

        event(new SaleVoided($sale));

        return $sale;
    }
}
