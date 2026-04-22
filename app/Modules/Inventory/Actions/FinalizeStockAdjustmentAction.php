<?php

namespace App\Modules\Inventory\Actions;

use App\Models\AccountingJournal;
use App\Models\User;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Services\StockMutationService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use DomainException;
use Illuminate\Support\Facades\DB;

class FinalizeStockAdjustmentAction
{
    private $mutationService;
    private $journalService;
    private $periodLockService;

    public function __construct(
        StockMutationService $mutationService,
        AccountingJournalService $journalService,
        AccountingPeriodLockService $periodLockService
    )
    {
        $this->mutationService = $mutationService;
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
    }

    public function execute(StockAdjustment $adjustment, ?User $actor = null): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor) {
            $adjustment = StockAdjustment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['items.product', 'items.variant'])
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($adjustment->id);

            if (!$adjustment->isDraft()) {
                throw new DomainException('Stock adjustment yang sudah finalized tidak dapat diposting ulang.');
            }

            if ($adjustment->items->isEmpty()) {
                throw new DomainException('Stock adjustment tidak memiliki item untuk diposting.');
            }

            $this->periodLockService->ensureDateOpen(
                $adjustment->adjustment_date,
                $adjustment->branch_id,
                'stock adjustment'
            );

            $movements = collect();

            foreach ($adjustment->items as $item) {
                $movement = $this->mutationService->record([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_location_id' => $adjustment->inventory_location_id,
                    'movement_type' => 'stock_adjustment',
                    'direction' => $item->direction,
                    'quantity' => $item->quantity,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reason_code' => $adjustment->reason_code,
                    'reason_text' => $item->notes ?: $adjustment->reason_text,
                    'occurred_at' => $adjustment->adjustment_date->toDateString() . ' 00:00:00',
                    'performed_by' => $adjustment->created_by,
                    'approved_by' => $actor,
                    'meta' => [
                        'adjustment_item_id' => $item->id,
                        'adjustment_notes' => $adjustment->notes,
                    ],
                ]);

                $item->forceFill([
                    'movement_id' => $movement->id,
                ])->save();

                $movements->push($movement);
            }

            $adjustment->forceFill([
                'status' => StockAdjustment::STATUS_FINALIZED,
                'finalized_by' => $actor ? $actor->id : null,
                'finalized_at' => now(),
            ])->save();

            $journalLines = $this->journalLines($movements);

            if (!empty($journalLines)) {
                $this->journalService->sync(
                    $adjustment,
                    'stock_adjustment',
                    $adjustment->adjustment_date->toDateString() . ' 00:00:00',
                    $journalLines,
                    [
                        'reason_code' => $adjustment->reason_code,
                        'location_id' => $adjustment->inventory_location_id,
                        'movement_count' => $movements->count(),
                        'inventory_effect_total' => round((float) $movements->sum(function ($movement) {
                            return $movement->direction === 'in'
                                ? (float) $movement->movement_value
                                : -(float) $movement->movement_value;
                        }), 2),
                    ],
                    'Auto journal stock adjustment ' . $adjustment->code
                );
            }

            return $adjustment->load([
                'location',
                'creator',
                'finalizer',
                'items.product',
                'items.variant',
                'items.movement',
            ]);
        });
    }

    private function journalLines(Collection $movements): array
    {
        $increaseValue = round((float) $movements
            ->where('direction', 'in')
            ->sum('movement_value'), 2);
        $decreaseValue = round((float) $movements
            ->where('direction', 'out')
            ->sum('movement_value'), 2);
        $lines = [];

        if ($increaseValue > 0) {
            $lines[] = [
                'account_code' => 'INVENTORY',
                'account_name' => 'Inventory',
                'debit' => $increaseValue,
                'credit' => 0,
            ];
            $lines[] = [
                'account_code' => 'INV_ADJ_GAIN',
                'account_name' => 'Inventory Adjustment Gain',
                'debit' => 0,
                'credit' => $increaseValue,
            ];
        }

        if ($decreaseValue > 0) {
            $lines[] = [
                'account_code' => 'INV_ADJ_LOSS',
                'account_name' => 'Inventory Adjustment Loss',
                'debit' => $decreaseValue,
                'credit' => 0,
            ];
            $lines[] = [
                'account_code' => 'INVENTORY',
                'account_name' => 'Inventory',
                'debit' => 0,
                'credit' => $decreaseValue,
            ];
        }

        return $this->aggregateJournalLines($lines);
    }

    private function aggregateJournalLines(array $lines): array
    {
        return collect($lines)
            ->groupBy(function ($line) {
                return $line['account_code'] . '|' . $line['account_name'];
            })
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'account_code' => $first['account_code'],
                    'account_name' => $first['account_name'],
                    'debit' => round((float) $rows->sum('debit'), 2),
                    'credit' => round((float) $rows->sum('credit'), 2),
                ];
            })
            ->values()
            ->all();
    }
}
