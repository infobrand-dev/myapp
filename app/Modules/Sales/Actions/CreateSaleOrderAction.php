<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Sales\Models\SaleOrder;
use App\Modules\Sales\Services\SaleOrderNumberService;
use App\Modules\Sales\Services\SaleSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateSaleOrderAction
{
    public function __construct(
        private readonly RecalculateSaleTotalsAction $recalculateTotals,
        private readonly SaleOrderNumberService $numberService,
        private readonly SaleSnapshotService $snapshotService,
    ) {
    }

    public function execute(array $data, ?User $actor = null): SaleOrder
    {
        return DB::transaction(function () use ($data, $actor) {
            $resolvedBranchId = BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);

            $order = SaleOrder::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $resolvedBranchId,
                'order_number' => $this->numberService->generate(
                    !empty($data['order_date']) ? new \DateTimeImmutable((string) $data['order_date']) : null
                ),
                'contact_id' => $contact?->id,
                'customer_name_snapshot' => $customer['name'],
                'customer_email_snapshot' => $customer['email'],
                'customer_phone_snapshot' => $customer['phone'],
                'customer_address_snapshot' => $customer['address'],
                'customer_snapshot' => $customer['payload'],
                'status' => SaleOrder::STATUS_DRAFT,
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'notes' => $data['notes'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
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
