<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Services\PurchaseNumberService;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateDraftPurchaseAction
{
    private $recalculateTotals;
    private $numberService;
    private $snapshotService;
    private $syncPaymentSummary;

    public function __construct(
        RecalculatePurchaseTotalsAction $recalculateTotals,
        PurchaseNumberService $numberService,
        PurchaseSnapshotService $snapshotService,
        SyncPurchasePaymentSummaryAction $syncPaymentSummary
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->numberService = $numberService;
        $this->snapshotService = $snapshotService;
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(array $data, ?User $actor = null): Purchase
    {
        return DB::transaction(function () use ($data, $actor) {
            $totals = $this->recalculateTotals->execute($data);
            $supplier = ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);

            $purchase = Purchase::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'purchase_number' => $this->numberService->generate(),
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'supplier_notes' => $data['supplier_notes'] ?? null,
                'status' => Purchase::STATUS_DRAFT,
                'payment_status' => Purchase::PAYMENT_UNPAID,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'received_total_qty' => 0,
                'paid_total' => 0,
                'balance_due' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'meta' => ['draft_created_from' => 'manual'],
                'created_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $purchase->items()->createMany($this->withTenantId($totals['items']));
            $purchase = $this->syncPaymentSummary->execute($purchase, Purchase::PAYMENT_UNPAID);

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'from_status' => null,
                'to_status' => Purchase::STATUS_DRAFT,
                'event' => 'created',
                'actor_id' => $actor ? $actor->id : null,
                'meta' => ['purchase_number' => $purchase->purchase_number],
            ]);

            return $purchase->load('items');
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
