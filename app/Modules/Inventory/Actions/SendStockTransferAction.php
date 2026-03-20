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

class SendStockTransferAction
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
            }

            $transfer->update([
                'status' => 'sent',
                'sent_by' => $actor?->id,
                'sent_at' => now(),
            ]);

            return $transfer->fresh(['sourceLocation', 'destinationLocation', 'items.product', 'items.variant']);
        });
    }
}
