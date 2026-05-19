<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
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

class SendStockTransferAction
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

        if (!in_array($transfer->status, ['approved', 'draft'], true)) {
            throw new DomainException('Transfer hanya bisa dikirim dari status draft atau approved.');
        }

        return DB::transaction(function () use ($transfer, $actor) {
            $transfer = StockTransfer::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            $this->periodLockService->ensureDateOpen(
                $transfer->transfer_date,
                $transfer->branch_id,
                'stock transfer send'
            );

            $movements = collect();

            foreach ($transfer->items as $item) {
                $movement = $this->mutationService->record([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_location_id' => $transfer->source_location_id,
                    'movement_type' => 'transfer_out',
                    'direction' => 'out',
                    'quantity' => $item->requested_quantity,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'reason_text' => $transfer->notes ?? 'Transfer out',
                    'occurred_at' => now(),
                    'performed_by' => $actor,
                ]);

                $item->update([
                    'sent_quantity' => $item->requested_quantity,
                    'transfer_out_movement_id' => $movement->id,
                ]);

                $movements->push($movement);
            }

            $transfer->update([
                'status' => 'sent',
                'sent_by' => $actor ? $actor->id : null,
                'sent_at' => now(),
            ]);

            $journalLines = $this->journalLines($movements);
            if (!empty($journalLines)) {
                $this->journalService->sync(
                    $transfer,
                    'inventory_transfer_out',
                    $transfer->transfer_date->toDateString() . ' 00:00:00',
                    $journalLines,
                    [
                        'source_location_id' => $transfer->source_location_id,
                        'destination_location_id' => $transfer->destination_location_id,
                        'movement_count' => $movements->count(),
                        'inventory_transfer_value' => round((float) $movements->sum('movement_value'), 2),
                    ],
                    'Auto journal inventory transfer out ' . $transfer->code
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
                'account_code' => 'INVENTORY_IN_TRANSIT',
                'account_name' => 'Inventory In Transit',
                'debit' => $transferValue,
                'credit' => 0,
            ],
            [
                'account_code' => 'INVENTORY',
                'account_name' => 'Inventory',
                'debit' => 0,
                'credit' => $transferValue,
            ],
        ];
    }
}
