<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Purchases\Events\PurchaseFinalized;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Services\PurchaseSnapshotService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizePurchaseAction
{
    private $syncPaymentSummary;
    private $snapshotService;

    public function __construct(
        SyncPurchasePaymentSummaryAction $syncPaymentSummary,
        PurchaseSnapshotService $snapshotService
    )
    {
        $this->syncPaymentSummary = $syncPaymentSummary;
        $this->snapshotService = $snapshotService;
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        $purchase = DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if ($purchase->confirmed_at) {
                return $this->syncPaymentSummary->execute($purchase)->load('items');
            }

            if (!$purchase->isDraft()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Hanya draft purchase yang dapat di-finalize.',
                ]);
            }

            if ($purchase->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Purchase harus memiliki item sebelum di-finalize.',
                ]);
            }

            $supplier = $purchase->contact_id
                ? ContactScope::applyVisibilityScope(Contact::query()->with('parentContact'))->find($purchase->contact_id)
                : null;
            $supplierSnapshot = $this->snapshotService->supplierSnapshot($supplier);

            $fromStatus = $purchase->status;
            $purchase->update([
                'contact_id' => $supplier ? $supplier->id : null,
                'supplier_name_snapshot' => $supplierSnapshot['name'],
                'supplier_email_snapshot' => $supplierSnapshot['email'],
                'supplier_phone_snapshot' => $supplierSnapshot['phone'],
                'supplier_address_snapshot' => $supplierSnapshot['address'],
                'supplier_snapshot' => $supplierSnapshot['payload'],
                'status' => Purchase::STATUS_CONFIRMED,
                'purchase_date' => $data['purchase_date'] ?? $purchase->purchase_date ?? now(),
                'due_date' => array_key_exists('due_date', $data) ? ($data['due_date'] ?? null) : $purchase->due_date,
                'confirmed_at' => now(),
                'confirmed_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
                'notes' => $data['notes'] ?? $purchase->notes,
                'internal_notes' => $data['internal_notes'] ?? $purchase->internal_notes,
                'totals_snapshot' => array_merge($purchase->totals_snapshot ?? [], [
                    'finalized_at' => now()->toDateTimeString(),
                ]),
            ]);

            foreach ($purchase->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $productSnapshot = $this->snapshotService->productSnapshot($item->product, $item->variant);
                $item->update([
                    'product_name_snapshot' => $productSnapshot['product_name'],
                    'variant_name_snapshot' => $productSnapshot['variant_name'],
                    'sku_snapshot' => $productSnapshot['sku'],
                    'unit_snapshot' => $productSnapshot['unit'],
                    'product_snapshot' => $productSnapshot['payload'],
                ]);
            }

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'from_status' => $fromStatus,
                'to_status' => Purchase::STATUS_CONFIRMED,
                'event' => 'finalized',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor ? $actor->id : null,
                'meta' => ['purchase_number' => $purchase->purchase_number],
            ]);

            return $this->syncPaymentSummary->execute($purchase)->load('items');
        });

        event(new PurchaseFinalized($purchase));

        return $purchase;
    }
}
