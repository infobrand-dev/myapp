<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Sales\Models\SaleOrder;
use App\Modules\Sales\Services\SaleSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSaleOrderAction
{
    public function __construct(
        private readonly RecalculateSaleTotalsAction $recalculateTotals,
        private readonly SaleSnapshotService $snapshotService,
    ) {
    }

    public function execute(SaleOrder $order, array $data, ?User $actor = null): SaleOrder
    {
        if (!$order->isDraft()) {
            throw ValidationException::withMessages([
                'sale_order' => 'Hanya draft sales order yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($order, $data, $actor) {
            $order = SaleOrder::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($order->id);

            $resolvedBranchId = $order->branch_id ?: BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $contact = !empty($data['contact_id'])
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id'])
                : null;
            $customer = $this->snapshotService->customerSnapshot($contact);
            $meta = $order->meta ?? [];
            $meta['tax'] = data_get($totals, 'tax_context');

            $order->update([
                'contact_id' => $contact?->id,
                'customer_name_snapshot' => $customer['name'],
                'customer_email_snapshot' => $customer['email'],
                'customer_phone_snapshot' => $customer['phone'],
                'customer_address_snapshot' => $customer['address'],
                'customer_snapshot' => $customer['payload'],
                'order_date' => $data['order_date'],
                'expected_delivery_date' => array_key_exists('expected_delivery_date', $data) ? ($data['expected_delivery_date'] ?? null) : $order->expected_delivery_date,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? $order->currency_code,
                'notes' => $data['notes'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => $meta,
                'updated_by' => $actor?->id,
            ]);

            $order->items()->delete();
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
