<?php

namespace App\Modules\Purchases\Actions;

use App\Models\AccountingJournal;
use App\Models\User;
use App\Modules\Purchases\Events\PurchaseVoided;
use App\Modules\Purchases\Models\Purchase;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidPurchaseAction
{
    public function __construct(
        private readonly SensitiveActionApprovalService $approvalService,
        private readonly AccountingPeriodLockService $periodLockService,
        private readonly AccountingJournalService $journalService
    ) {
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        $purchase = DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items', 'receipts.items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (!in_array($purchase->status, [Purchase::STATUS_CONFIRMED, Purchase::STATUS_PARTIAL_RECEIVED, Purchase::STATUS_RECEIVED], true)) {
                throw ValidationException::withMessages([
                    'purchase' => 'Hanya purchase confirmed/received yang dapat di-void.',
                ]);
            }

            if ($purchase->receipts()->exists()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Purchase yang sudah memiliki receiving tidak boleh di-void langsung.',
                ]);
            }

            $this->periodLockService->ensureDateOpen($purchase->purchase_date ?: now(), $purchase->branch_id, 'void purchase');
            $this->approvalService->ensureApprovedOrCreatePending(
                'purchases',
                'void_purchase',
                $purchase,
                ['reason' => $data['reason'], 'status' => $purchase->status],
                $actor,
                $data['reason']
            );

            $fromStatus = $purchase->status;
            $purchase->update([
                'status' => Purchase::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
                'void_reason' => $data['reason'],
            ]);

            $purchase->voidLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'status_before' => $fromStatus,
                'reason' => $data['reason'],
                'snapshot' => [
                    'header' => $purchase->only(['purchase_number', 'status', 'purchase_date', 'grand_total', 'payment_status']),
                    'items' => $purchase->items->map->only(['id', 'product_id', 'product_variant_id', 'qty', 'qty_received', 'unit_cost', 'line_total'])->all(),
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'from_status' => $fromStatus,
                'to_status' => Purchase::STATUS_VOIDED,
                'event' => 'voided',
                'reason' => $data['reason'],
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'grand_total' => (float) $purchase->grand_total,
                    'payment_status' => $purchase->payment_status,
                    'received_total_qty' => (float) $purchase->received_total_qty,
                ],
            ]);

            $originalJournal = AccountingJournal::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('entry_type', 'purchase_finalized')
                ->where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->with('lines')
                ->first();

            if ($originalJournal && $originalJournal->lines->isNotEmpty()) {
                $this->journalService->sync(
                    $purchase,
                    'purchase_void',
                    now(),
                    $originalJournal->lines->map(fn ($line) => [
                        'account_code' => $line->account_code,
                        'account_name' => $line->account_name,
                        'debit' => (float) $line->credit,
                        'credit' => (float) $line->debit,
                    ])->all(),
                    ['reason' => $data['reason']],
                    'Reversal journal purchase void ' . $purchase->purchase_number
                );
            }

            return $purchase->refresh();
        });

        event(new PurchaseVoided($purchase));

        return $purchase;
    }
}
