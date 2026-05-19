<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Services\StockMutationService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReceiveStockTransferAction
{
    private $mutationService;
    private $journalService;
    private $periodLockService;

    public function __construct(
        StockMutationService $mutationService,
        AccountingJournalService $journalService,
        AccountingPeriodLockService $periodLockService
    ) {
        $this->mutationService = $mutationService;
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
    }

    public function execute(StockTransfer $transfer, ?User $actor = null): StockTransfer
    {
        $transfer = StockTransfer::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->findOrFail($transfer->id);

        if ($transfer->status !== 'sent') {
            throw new DomainException('Transfer harus berstatus sent sebelum diterima.');
        }

        return DB::transaction(function () use ($transfer, $actor) {
            $transfer = StockTransfer::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            $receivedAt = now();
            $this->periodLockService->ensureDateOpen(
                $receivedAt,
                $transfer->branch_id,
                'stock transfer receive'
            );

            $movements = collect();

            foreach ($transfer->items as $item) {
                $received = $item->sent_quantity > 0 ? $item->sent_quantity : $item->requested_quantity;
                $transferOutMovement = $item->transfer_out_movement_id
                    ? StockMovement::query()->find($item->transfer_out_movement_id)
                    : null;

                $movement = $this->mutationService->record([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_location_id' => $transfer->destination_location_id,
                    'movement_type' => 'transfer_in',
                    'direction' => 'in',
                    'quantity' => $received,
                    'unit_cost' => $transferOutMovement ? (float) $transferOutMovement->unit_cost : null,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'reason_text' => $transfer->notes ?? 'Transfer in',
                    'occurred_at' => $receivedAt,
                    'performed_by' => $actor,
                ]);

                $item->update([
                    'received_quantity' => $received,
                    'transfer_in_movement_id' => $movement->id,
                ]);

                $movements->push($movement);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $actor ? $actor->id : null,
                'received_at' => $receivedAt,
            ]);

            $journalLines = $this->journalLines($movements);
            if (!empty($journalLines)) {
                $this->journalService->sync(
                    $transfer,
                    'inventory_transfer_in',
                    $receivedAt,
                    $journalLines,
                    [
                        'source_location_id' => $transfer->source_location_id,
                        'destination_location_id' => $transfer->destination_location_id,
                        'movement_count' => $movements->count(),
                        'inventory_transfer_value' => round((float) $movements->sum('movement_value'), 2),
                    ],
                    'Auto journal inventory transfer in ' . $transfer->code
                );
            }

            return $transfer->fresh(['sourceLocation', 'destinationLocation', 'items.product', 'items.variant']);
        });
    }

    private function journalLines(Collection $movements): array
    {
        $transferValue = round((float) $movements->sum('movement_value'), 2);

        if ($transferValue <= 0) {
            return [];
        }

        return [
            [
                'account_code' => 'INVENTORY',
                'account_name' => 'Inventory',
                'debit' => $transferValue,
                'credit' => 0,
            ],
            [
                'account_code' => 'INVENTORY_IN_TRANSIT',
                'account_name' => 'Inventory In Transit',
                'debit' => 0,
                'credit' => $transferValue,
            ],
        ];
    }
}
