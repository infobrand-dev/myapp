<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Models\PurchaseOrder;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseOrderAction
{
    public function __construct(
        private readonly RecalculatePurchaseTotalsAction $recalculateTotals,
        private readonly PurchaseSnapshotService $snapshotService,
    ) {
    }

    public function execute(PurchaseOrder $order, array $data, ?User $actor = null): PurchaseOrder
    {
        if (!$order->isDraft()) {
            throw ValidationException::withMessages([
                'purchase_order' => 'Hanya draft purchase order yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($order, $data, $actor) {
            $order = PurchaseOrder::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($order->id);

            $resolvedBranchId = $order->branch_id ?: BranchContext::currentOrDefaultId($actor, CompanyContext::currentId());
            $totals = $this->recalculateTotals->execute($data);
            $supplier = ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);
            $meta = $order->meta ?? [];
            $meta['tax'] = data_get($totals, 'tax_context');

            $order->update([
                'contact_id' => $supplier?->id,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'order_date' => $data['order_date'],
                'expected_receive_date' => array_key_exists('expected_receive_date', $data) ? ($data['expected_receive_date'] ?? null) : $order->expected_receive_date,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'landed_cost_total' => $totals['landed_cost_total'],
                'grand_total' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? $order->currency_code,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => array_key_exists('internal_notes', $data) ? ($data['internal_notes'] ?? null) : $order->internal_notes,
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
