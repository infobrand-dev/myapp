<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Services\StockMutationService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReceiveStockTransferAction
{
    public function __construct(private readonly StockMutationService $mutationService)
    {
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

            foreach ($transfer->items as $item) {
                $received = $item->sent_quantity > 0 ? $item->sent_quantity : $item->requested_quantity;

                $movement = $this->mutationService->record([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_location_id' => $transfer->destination_location_id,
                    'movement_type' => 'transfer_in',
                    'direction' => 'in',
                    'quantity' => $received,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'reason_text' => $transfer->notes ?? 'Transfer in',
                    'occurred_at' => now(),
                    'performed_by' => $actor,
                ]);

                $item->update([
                    'received_quantity' => $received,
                    'transfer_in_movement_id' => $movement->id,
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $actor?->id,
                'received_at' => now(),
            ]);

            return $transfer->fresh(['sourceLocation', 'destinationLocation', 'items.product', 'items.variant']);
        });
    }
}
