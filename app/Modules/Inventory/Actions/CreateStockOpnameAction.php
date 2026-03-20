<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStockOpnameAction
{
    private $stocks;

    public function __construct(StockRepository $stocks)
    {
        $this->stocks = $stocks;
    }

    public function execute(array $data, ?User $actor = null): StockOpname
    {
        return DB::transaction(function () use ($data, $actor) {
            $snapshotStocks = $this->stocks->snapshotByLocation((int) $data['inventory_location_id']);

            if ($snapshotStocks->isEmpty()) {
                throw new DomainException('Belum ada stok sistem pada lokasi ini untuk dibuat sesi stock opname.');
            }

            $opname = StockOpname::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'code' => 'OPN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'inventory_location_id' => $data['inventory_location_id'],
                'opname_date' => $data['opname_date'],
                'status' => StockOpname::STATUS_DRAFT,
                'notes' => isset($data['notes']) ? $data['notes'] : null,
                'created_by' => $actor ? $actor->id : null,
                'meta' => [
                    'snapshot_item_count' => $snapshotStocks->count(),
                ],
            ]);

            foreach ($snapshotStocks as $stock) {
                $opname->items()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'branch_id' => BranchContext::currentId(),
                    'inventory_stock_id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'product_variant_id' => $stock->product_variant_id,
                    'system_quantity' => $stock->current_quantity,
                    'physical_quantity' => null,
                    'difference_quantity' => null,
                    'notes' => null,
                ]);
            }

            return $opname->load(['location', 'items.product', 'items.variant']);
        });
    }
}
