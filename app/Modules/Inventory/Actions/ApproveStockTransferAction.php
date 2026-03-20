<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockTransfer;
use App\Support\TenantContext;
use DomainException;

class ApproveStockTransferAction
{
    public function execute(StockTransfer $transfer, ?User $actor = null): StockTransfer
    {
        $transfer = StockTransfer::query()
            ->where('tenant_id', TenantContext::currentId())
            ->findOrFail($transfer->id);

        if ($transfer->status !== 'draft') {
            throw new DomainException('Hanya transfer draft yang bisa di-approve.');
        }

        $transfer->update([
            'status' => 'approved',
            'approved_by' => $actor?->id,
            'approved_at' => now(),
        ]);

        return $transfer->fresh(['sourceLocation', 'destinationLocation', 'items.product', 'items.variant']);
    }
}
