<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSalesReturnAction
{
    private $validateReturnableItems;
    private $integrateReturnToInventory;
    private $syncRefundSummary;
    private $journalService;
    private $periodLockService;

    public function __construct(
        ValidateReturnableItemsAction $validateReturnableItems,
        IntegrateReturnToInventoryAction $integrateReturnToInventory,
        SyncSaleReturnRefundSummaryAction $syncRefundSummary,
        AccountingJournalService $journalService,
        AccountingPeriodLockService $periodLockService
    ) {
        $this->validateReturnableItems = $validateReturnableItems;
        $this->integrateReturnToInventory = $integrateReturnToInventory;
        $this->syncRefundSummary = $syncRefundSummary;
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
    }

    public function execute(SaleReturn $saleReturn, ?User $actor = null): SaleReturn
    {
        $saleReturn = DB::transaction(function () use ($saleReturn, $actor) {
            $saleReturn = SaleReturn::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['items', 'sale.items'])
                ->lockForUpdate()
                ->findOrFail($saleReturn->id);

            if (!$saleReturn->isDraft()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Hanya draft sales return yang dapat di-finalize.',
                ]);
            }

            /** @var Sale $sale */
            $sale = $saleReturn->sale;
            if (!$sale || !$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Sale asal harus tetap finalized untuk memproses return.',
                ]);
            }

            $this->periodLockService->ensureDateOpen(
                $saleReturn->return_date ?: now(),
                $sale->branch_id,
                'finalize sales return'
            );

            $requestedItems = $saleReturn->items->map(fn ($item) => [
                'sale_item_id' => $item->sale_item_id,
                'qty_returned' => $item->qty_returned,
            ])->all();

            $this->validateReturnableItems->execute($sale, $requestedItems, $saleReturn->id);

            $fromStatus = $saleReturn->status;
            $saleReturn->update([
                'status' => SaleReturn::STATUS_FINALIZED,
                'finalized_at' => now(),
                'updated_by' => $actor ? $actor->id : null,
                'finalized_by' => $actor ? $actor->id : null,
            ]);

            $saleReturn->statusLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'from_status' => $fromStatus,
                'to_status' => SaleReturn::STATUS_FINALIZED,
                'event' => 'finalized',
                'reason' => $saleReturn->reason,
                'meta' => [
                    'refund_required' => $saleReturn->refund_required,
                    'inventory_restock_required' => $saleReturn->inventory_restock_required,
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $this->journalService->sync(
                $saleReturn,
                'sale_return_finalized',
                $saleReturn->return_date ?: now(),
                $this->journalLines($saleReturn),
                [
                    'sale_id' => $saleReturn->sale_id,
                    'sale_number' => $saleReturn->sale_number_snapshot,
                    'refund_required' => (bool) $saleReturn->refund_required,
                    'inventory_restock_required' => (bool) $saleReturn->inventory_restock_required,
                ],
                'Auto journal sales return ' . $saleReturn->return_number
            );

            return $saleReturn->refresh()->load(['items', 'sale']);
        });

        $saleReturn = $this->integrateReturnToInventory->execute($saleReturn->loadMissing('items'), $actor);

        return $this->syncRefundSummary->execute($saleReturn);
    }

    private function journalLines(SaleReturn $saleReturn): array
    {
        $lines = [
            [
                'account_code' => 'SALES_REFUND',
                'account_name' => 'Sales Refund',
                'debit' => (float) $saleReturn->subtotal,
                'credit' => 0,
            ],
            [
                'account_code' => 'AR',
                'account_name' => 'Accounts Receivable',
                'debit' => 0,
                'credit' => (float) $saleReturn->grand_total,
            ],
        ];

        if ((float) $saleReturn->discount_total > 0) {
            $lines[] = [
                'account_code' => 'SALES_DISC',
                'account_name' => 'Sales Discount',
                'debit' => 0,
                'credit' => (float) $saleReturn->discount_total,
            ];
        }

        if ((float) $saleReturn->tax_total > 0) {
            $lines[] = [
                'account_code' => 'SALES_TAX',
                'account_name' => 'Sales Tax Payable',
                'debit' => (float) $saleReturn->tax_total,
                'credit' => 0,
            ];
        }

        return $lines;
    }
}
