<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Sales\Services\SaleReturnNumberService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateSalesReturnAction
{
    private $validateReturnableItems;
    private $calculateReturnTotals;
    private $numberService;

    public function __construct(
        ValidateReturnableItemsAction $validateReturnableItems,
        CalculateReturnTotalsAction $calculateReturnTotals,
        SaleReturnNumberService $numberService
    ) {
        $this->validateReturnableItems = $validateReturnableItems;
        $this->calculateReturnTotals = $calculateReturnTotals;
        $this->numberService = $numberService;
    }

    public function execute(array $data, ?User $actor = null): SaleReturn
    {
        return DB::transaction(function () use ($data, $actor) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($data['sale_id']);

            $returnableMap = $this->validateReturnableItems->execute($sale, $data['items'] ?? []);
            $totals = $this->calculateReturnTotals->execute($sale, $data['items'] ?? [], $returnableMap);

            $saleReturn = SaleReturn::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'return_number' => $this->numberService->generate(),
                'sale_id' => $sale->id,
                'sale_number_snapshot' => $sale->sale_number,
                'contact_id' => $sale->contact_id,
                'customer_name_snapshot' => $sale->customer_name_snapshot,
                'customer_email_snapshot' => $sale->customer_email_snapshot,
                'customer_phone_snapshot' => $sale->customer_phone_snapshot,
                'customer_address_snapshot' => $sale->customer_address_snapshot,
                'customer_snapshot' => $sale->customer_snapshot,
                'status' => SaleReturn::STATUS_DRAFT,
                'inventory_status' => !empty($data['inventory_restock_required']) ? SaleReturn::INVENTORY_PENDING : SaleReturn::INVENTORY_SKIPPED,
                'refund_status' => !empty($data['refund_required']) ? SaleReturn::REFUND_PENDING : SaleReturn::REFUND_NOT_REQUIRED,
                'return_date' => $data['return_date'] ?? now(),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'refunded_total' => 0,
                'refund_balance' => !empty($data['refund_required']) ? $totals['grand_total'] : 0,
                'refund_required' => (bool) ($data['refund_required'] ?? false),
                'inventory_restock_required' => (bool) ($data['inventory_restock_required'] ?? false),
                'inventory_location_id' => $data['inventory_location_id'] ?? null,
                'currency_code' => $sale->currency_code,
                'totals_snapshot' => $totals['totals_snapshot'],
                'integration_snapshot' => [
                    'source_sale_id' => $sale->id,
                    'source_sale_number' => $sale->sale_number,
                ],
                'meta' => [
                    'requested_items_count' => count($totals['items']),
                    'return_scope' => count($totals['items']) === $sale->items->count() ? 'possible_full' : 'partial',
                ],
                'created_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $saleReturn->items()->createMany($this->withTenantId($totals['items']));
            $saleReturn->statusLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'from_status' => null,
                'to_status' => SaleReturn::STATUS_DRAFT,
                'event' => 'created',
                'reason' => $saleReturn->reason,
                'meta' => [
                    'sale_id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            return $saleReturn->load(['sale', 'items.saleItem']);
        });
    }

    private function withTenantId(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['tenant_id'] = TenantContext::currentId();

            return $row;
        }, $rows);
    }
}
