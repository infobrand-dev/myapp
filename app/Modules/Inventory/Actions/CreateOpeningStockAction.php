<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockOpening;
use App\Modules\Inventory\Services\StockMutationService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOpeningStockAction
{
    public function __construct(private readonly StockMutationService $mutationService)
    {
    }

    public function execute(array $data, ?User $actor = null): StockOpening
    {
        return DB::transaction(function () use ($data, $actor) {
            $opening = StockOpening::query()->create([
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
                $existingMovement = StockMovement::query()
                    ->where('product_id', $item['product_id'])
                    ->where('inventory_location_id', $data['inventory_location_id'])
                    ->where('product_variant_id', $item['product_variant_id'] ?? null)
                    ->exists();

                if ($existingMovement) {
                    throw new DomainException('Opening stock hanya boleh dilakukan sebelum mutasi berjalan pada kombinasi product, variant, dan lokasi yang sama.');
                }

                $movement = $this->mutationService->record([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'inventory_location_id' => $data['inventory_location_id'],
                    'movement_type' => 'opening_stock',
                    'direction' => 'in',
                    'quantity' => $item['quantity'],
                    'minimum_quantity' => $item['minimum_quantity'] ?? 0,
                    'reorder_quantity' => $item['reorder_quantity'] ?? 0,
                    'reference_type' => StockOpening::class,
                    'reference_id' => $opening->id,
                    'reason_text' => $data['notes'] ?? 'Opening stock',
                    'occurred_at' => $data['opening_date'] . ' 00:00:00',
                    'performed_by' => $actor,
                ]);

                $opening->items()->create([
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
