<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Events\PurchaseVoided;
use App\Modules\Purchases\Models\Purchase;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidPurchaseAction
{
    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        $purchase = DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items', 'receipts.items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (!in_array($purchase->status, [Purchase::STATUS_CONFIRMED, Purchase::STATUS_PARTIAL_RECEIVED, Purchase::STATUS_RECEIVED], true)) {
                throw ValidationException::withMessages([
                    'purchase' => 'Hanya purchase confirmed/received yang dapat di-void.',
                ]);
            }

            if ($purchase->receipts()->exists()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Purchase yang sudah memiliki receiving tidak boleh di-void langsung.',
                ]);
            }

            $fromStatus = $purchase->status;
            $purchase->update([
                'status' => Purchase::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
                'void_reason' => $data['reason'],
            ]);

            $purchase->voidLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'status_before' => $fromStatus,
                'reason' => $data['reason'],
                'snapshot' => [
                    'header' => $purchase->only(['purchase_number', 'status', 'purchase_date', 'grand_total', 'payment_status']),
                    'items' => $purchase->items->map->only(['id', 'product_id', 'product_variant_id', 'qty', 'qty_received', 'unit_cost', 'line_total'])->all(),
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'from_status' => $fromStatus,
                'to_status' => Purchase::STATUS_VOIDED,
                'event' => 'voided',
                'reason' => $data['reason'],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            return $purchase->refresh();
        });

        event(new PurchaseVoided($purchase));

        return $purchase;
    }
}
