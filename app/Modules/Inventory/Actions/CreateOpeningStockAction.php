<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockOpening;
use App\Modules\Inventory\Services\StockMutationService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class CreateOpeningStockAction
{
    public function __construct(
        private readonly StockMutationService $mutationService,
        private readonly AccountingPeriodLockService $periodLockService
    )
    {
    }

    public function execute(array $data, ?User $actor = null): StockOpening
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->periodLockService->ensureDateOpen($data['opening_date'] ?? now(), BranchContext::currentId(), 'opening stock');

            $opening = StockOpening::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'code' => 'OPN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'inventory_location_id' => $data['inventory_location_id'],
                'opening_date' => $data['opening_date'],
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
                'posted_by' => $actor?->id,
                'posted_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $stockKey = $this->mutationService->stockKey(
                    (int) $item['product_id'],
                    isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    (int) $data['inventory_location_id']
                );

                $stock = StockBalance::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->tap(fn ($query) => BranchContext::applyScope($query))
                    ->where('stock_key', $stockKey)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    try {
                        $stock = StockBalance::query()->create([
                            'tenant_id' => TenantContext::currentId(),
                            'company_id' => CompanyContext::currentId(),
                            'branch_id' => BranchContext::currentId(),
                            'stock_key' => $stockKey,
                            'product_id' => $item['product_id'],
                            'product_variant_id' => $item['product_variant_id'] ?? null,
                            'inventory_location_id' => $data['inventory_location_id'],
                            'current_quantity' => 0,
                            'reserved_quantity' => 0,
                            'minimum_quantity' => $item['minimum_quantity'] ?? 0,
                            'reorder_quantity' => $item['reorder_quantity'] ?? 0,
                            'allow_negative_stock' => false,
                        ]);
                    } catch (QueryException $exception) {
                        $stock = null;
                    }

                    $stock = StockBalance::query()
                        ->where('tenant_id', TenantContext::currentId())
                        ->where('company_id', CompanyContext::currentId())
                        ->tap(fn ($query) => BranchContext::applyScope($query))
                        ->where('stock_key', $stockKey)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $existingMovement = StockMovement::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->tap(fn ($query) => BranchContext::applyScope($query))
                    ->where('stock_key', $stockKey)
                    ->lockForUpdate()
                    ->exists();

                if ($existingMovement || (float) $stock->current_quantity !== 0.0) {
                    throw new DomainException('Opening stock hanya boleh dilakukan sebelum mutasi berjalan pada kombinasi product, variant, dan lokasi yang sama.');
                }

                $movement = $this->mutationService->record([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'inventory_location_id' => $data['inventory_location_id'],
                    'movement_type' => 'opening_stock',
                    'direction' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? null,
                    'minimum_quantity' => $item['minimum_quantity'] ?? 0,
                    'reorder_quantity' => $item['reorder_quantity'] ?? 0,
                    'reference_type' => StockOpening::class,
                    'reference_id' => $opening->id,
                    'reason_text' => $data['notes'] ?? 'Opening stock',
                    'occurred_at' => $data['opening_date'] . ' 00:00:00',
                    'performed_by' => $actor,
                    'meta' => [
                        'unit_cost' => isset($item['unit_cost']) ? round((float) $item['unit_cost'], 2) : null,
                    ],
                ]);

                $opening->items()->create([ 
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'branch_id' => BranchContext::currentId(),
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'minimum_quantity' => $item['minimum_quantity'] ?? 0,
                    'reorder_quantity' => $item['reorder_quantity'] ?? 0,
                    'movement_id' => $movement->id,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $opening->load(['location', 'items.product', 'items.variant']);
        });
    }
}
