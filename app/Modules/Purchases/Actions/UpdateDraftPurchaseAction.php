<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDraftPurchaseAction
{
    private $recalculateTotals;
    private $snapshotService;
    private $syncPaymentSummary;

    public function __construct(
        RecalculatePurchaseTotalsAction $recalculateTotals,
        PurchaseSnapshotService $snapshotService,
        SyncPurchasePaymentSummaryAction $syncPaymentSummary
    ) {
        $this->recalculateTotals = $recalculateTotals;
        $this->snapshotService = $snapshotService;
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        if (!$purchase->isDraft()) {
            throw ValidationException::withMessages([
                'purchase' => 'Hanya draft purchase yang boleh diedit.',
            ]);
        }

        return DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()->where('tenant_id', TenantContext::currentId())->lockForUpdate()->findOrFail($purchase->id);
            $totals = $this->recalculateTotals->execute($data);
            $supplier = Contact::query()->with('company')->where('tenant_id', TenantContext::currentId())->find($data['contact_id']);
            $snapshot = $this->snapshotService->supplierSnapshot($supplier);

            $purchase->update([
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $snapshot['name'],
                'supplier_email_snapshot' => $snapshot['email'],
                'supplier_phone_snapshot' => $snapshot['phone'],
                'supplier_address_snapshot' => $snapshot['address'],
                'supplier_snapshot' => $snapshot['payload'],
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'supplier_notes' => $data['supplier_notes'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'balance_due' => $totals['grand_total'],
                'currency_code' => $data['currency_code'] ?? $purchase->currency_code,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'totals_snapshot' => $totals['totals_snapshot'],
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $purchase->items()->delete();
            $purchase->items()->createMany($this->withTenantId($totals['items']));

            return $this->syncPaymentSummary->execute($purchase);
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
