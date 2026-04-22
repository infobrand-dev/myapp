<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Inventory\Services\StockMutationService;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\AccountingJournalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class IntegrateReturnToInventoryAction
{
    private $journalService;

    public function __construct(AccountingJournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function execute(SaleReturn $saleReturn, ?User $actor = null): SaleReturn
    {
        if (!$saleReturn->inventory_restock_required) {
            $saleReturn->update([
                'inventory_status' => SaleReturn::INVENTORY_SKIPPED,
            ]);

            return $saleReturn->refresh();
        }

        if (!$saleReturn->inventory_location_id || !class_exists(StockMutationService::class)) {
            $saleReturn->update([
                'inventory_status' => SaleReturn::INVENTORY_FAILED,
                'integration_snapshot' => array_merge($saleReturn->integration_snapshot ?? [], [
                    'inventory' => [
                        'status' => 'failed',
                        'message' => 'Inventory location atau service inventory belum tersedia.',
                    ],
                ]),
            ]);

            return $saleReturn->refresh();
        }

        /** @var StockMutationService $stockMutation */
        $stockMutation = app(StockMutationService::class);

        try {
            DB::transaction(function () use ($saleReturn, $stockMutation, $actor) {
                $movements = collect();

                foreach ($saleReturn->items as $item) {
                    if (!$item->product_id) {
                        continue;
                    }

                    $movement = $stockMutation->record([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'inventory_location_id' => $saleReturn->inventory_location_id,
                        'movement_type' => 'sale_return',
                        'direction' => 'in',
                        'quantity' => $item->qty_returned,
                        'reference_type' => $saleReturn->getMorphClass(),
                        'reference_id' => $saleReturn->getKey(),
                        'reason_code' => 'sale_return',
                        'reason_text' => $saleReturn->reason,
                        'occurred_at' => $saleReturn->finalized_at ?: now(),
                        'performed_by' => $actor ? $actor->id : null,
                        'approved_by' => $actor ? $actor->id : null,
                        'meta' => [
                            'return_number' => $saleReturn->return_number,
                            'sale_number' => $saleReturn->sale_number_snapshot,
                            'sale_item_id' => $item->sale_item_id,
                        ],
                    ]);

                    $movements->push($movement);
                }

                $journalLines = $this->inventoryJournalLines($movements);
                if (!empty($journalLines)) {
                    $this->journalService->sync(
                        $saleReturn,
                        'sale_return_inventory',
                        $saleReturn->finalized_at ?: now(),
                        $journalLines,
                        [
                            'inventory_location_id' => $saleReturn->inventory_location_id,
                            'movement_count' => $movements->count(),
                            'return_number' => $saleReturn->return_number,
                        ],
                        'Auto journal inventory sales return ' . $saleReturn->return_number
                    );
                }
            });
        } catch (Throwable $exception) {
            $saleReturn->update([
                'inventory_status' => SaleReturn::INVENTORY_FAILED,
                'integration_snapshot' => array_merge($saleReturn->integration_snapshot ?? [], [
                    'inventory' => [
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                    ],
                ]),
            ]);

            throw $exception;
        }

        $saleReturn->update([
            'inventory_status' => SaleReturn::INVENTORY_COMPLETED,
            'integration_snapshot' => array_merge($saleReturn->integration_snapshot ?? [], [
                'inventory' => [
                    'status' => 'completed',
                    'location_id' => $saleReturn->inventory_location_id,
                    'processed_at' => now()->toDateTimeString(),
                ],
            ]),
        ]);

        return $saleReturn->refresh();
    }

    private function inventoryJournalLines(Collection $movements): array
    {
        $inventoryValue = round((float) $movements->sum('movement_value'), 2);

        if ($inventoryValue <= 0) {
            return [];
        }

        return [
            [
                'account_code' => 'INVENTORY',
                'account_name' => 'Inventory',
                'debit' => $inventoryValue,
                'credit' => 0,
            ],
            [
                'account_code' => 'COGS',
                'account_name' => 'Cost of Goods Sold',
                'debit' => 0,
                'credit' => $inventoryValue,
            ],
        ];
    }
}
