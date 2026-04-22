<?php

namespace App\Modules\Sales\Actions;

use App\Models\AccountingJournal;
use App\Models\User;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockMutationService;
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
use Illuminate\Support\Facades\Schema;
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

            $this->restoreInventory($sale, $reason, $actor);
            $this->reverseJournal($sale, 'sale_finalized', 'sale_void', $reason, 'Reversal journal sale void ');
            $this->reverseJournal($sale, 'sale_cogs', 'sale_cogs_void', $reason, 'Reversal journal COGS void ');

            return $sale->load('voidLogs', 'statusHistories');
        });

        event(new SaleVoided($sale));

        return $sale;
    }

    private function reverseJournal(Sale $sale, string $entryType, string $reversalEntryType, string $reason, string $descriptionPrefix): void
    {
        $originalJournal = AccountingJournal::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('entry_type', $entryType)
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->with('lines')
            ->first();

        if (!$originalJournal || $originalJournal->lines->isEmpty()) {
            return;
        }

        $this->journalService->sync(
            $sale,
            $reversalEntryType,
            now(),
            $originalJournal->lines->map(fn ($line) => [
                'account_code' => $line->account_code,
                'account_name' => $line->account_name,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
            ])->all(),
            ['reason' => $reason],
            $descriptionPrefix . $sale->sale_number
        );
    }

    private function restoreInventory(Sale $sale, string $reason, ?User $actor = null): void
    {
        if (!class_exists(StockMutationService::class)
            || !class_exists(StockMovement::class)
            || !Schema::hasTable('inventory_stock_movements')
        ) {
            return;
        }

        /** @var StockMutationService $stockMutation */
        $stockMutation = app(StockMutationService::class);

        $movements = StockMovement::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->where('movement_type', 'sale_finalized')
            ->where('direction', 'out')
            ->get();

        foreach ($movements as $movement) {
            $stockMutation->record([
                'product_id' => $movement->product_id,
                'product_variant_id' => $movement->product_variant_id,
                'inventory_location_id' => $movement->inventory_location_id,
                'movement_type' => 'sale_void_restore',
                'direction' => 'in',
                'quantity' => (float) $movement->quantity,
                'unit_cost' => (float) $movement->unit_cost,
                'reference_type' => $sale->getMorphClass(),
                'reference_id' => $sale->getKey(),
                'reason_code' => 'sale_void_restore',
                'reason_text' => $reason,
                'occurred_at' => now(),
                'performed_by' => $actor ? $actor->id : null,
                'approved_by' => $actor ? $actor->id : null,
                'meta' => [
                    'reversed_movement_id' => $movement->id,
                    'sale_number' => $sale->sale_number,
                ],
            ]);
        }
    }
}
