<?php

namespace App\Modules\Inventory\Actions;

use App\Support\AccountingJournalService;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreateOpeningStockAction
{
    private $mutationService;
    private $periodLockService;
    private $journalService;

    public function __construct(
        StockMutationService $mutationService,
        AccountingPeriodLockService $periodLockService,
        AccountingJournalService $journalService
    )
    {
        $this->mutationService = $mutationService;
        $this->periodLockService = $periodLockService;
        $this->journalService = $journalService;
    }

    public function execute(array $data, ?User $actor = null): StockOpening
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->periodLockService->ensureDateOpen($data['opening_date'] ?? now(), BranchContext::currentId(), 'opening stock');
            $actorId = $actor ? $actor->id : null;

            $opening = StockOpening::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'code' => 'OPN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'inventory_location_id' => $data['inventory_location_id'],
                'opening_date' => $data['opening_date'],
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ]);

            $movements = collect();

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

                $movements->push($movement);
            }

            $journalLines = $this->journalLines($movements);

            if (!empty($journalLines)) {
                $this->journalService->sync(
                    $opening,
                    'opening_stock',
                    $data['opening_date'] . ' 00:00:00',
                    $journalLines,
                    [
                        'inventory_location_id' => (int) $data['inventory_location_id'],
                        'movement_count' => $movements->count(),
                        'opening_code' => $opening->code,
                    ],
                    'Auto journal opening stock ' . $opening->code
                );
            }

            return $opening->load(['location', 'items.product', 'items.variant']);
        });
    }

    private function journalLines(Collection $movements): array
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
                'account_code' => 'OPENING_BAL_EQUITY',
                'account_name' => 'Opening Balance Equity',
                'debit' => 0,
                'credit' => $inventoryValue,
            ],
        ];
    }
}
