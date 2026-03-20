<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockTransfer;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStockTransferAction
{
    public function execute(array $data, ?User $actor = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $actor) {
            $transfer = StockTransfer::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'code' => 'TRF-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'source_location_id' => $data['source_location_id'],
                'destination_location_id' => $data['destination_location_id'],
                'transfer_date' => $data['transfer_date'],
                'status' => 'draft',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
            ]);

            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'branch_id' => BranchContext::currentId(),
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'requested_quantity' => $item['requested_quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $transfer->load(['sourceLocation', 'destinationLocation', 'items.product', 'items.variant']);
        });
    }
}
