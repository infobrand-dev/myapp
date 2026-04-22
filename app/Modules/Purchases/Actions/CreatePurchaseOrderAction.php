<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Models\PurchaseOrder;
use App\Modules\Purchases\Services\PurchaseOrderNumberService;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrderAction
{
    public function __construct(
        private readonly RecalculatePurchaseTotalsAction $recalculateTotals,
        private readonly PurchaseOrderNumberService $numberService,
        private readonly PurchaseSnapshotService $snapshotService,
    ) {
    }

    public function execute(array $data, ?User $actor = null): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor) {
            $resolvedBranchId = BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $supplier = ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);

            $order = PurchaseOrder::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $resolvedBranchId,
                'order_number' => $this->numberService->generate(
                    !empty($data['order_date']) ? new \DateTimeImmutable((string) $data['order_date']) : null
                ),
                'contact_id' => $supplier?->id,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'order_date' => $data['order_date'],
                'expected_receive_date' => $data['expected_receive_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'landed_cost_total' => $totals['landed_cost_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => array_filter([
                    'tax' => data_get($totals, 'tax_context'),
                ], fn ($value) => $value !== null),
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $order->items()->createMany($this->withTenantId($totals['items'], $resolvedBranchId));

            return $order->load('items');
        });
    }

    private function withTenantId(array $rows, ?int $branchId): array
    {
        return array_map(function (array $row) use ($branchId): array {
            $row['tenant_id'] = TenantContext::currentId();
            $row['company_id'] = CompanyContext::currentId();
            $row['branch_id'] = $branchId;

            return $row;
        }, $rows);
    }
}
