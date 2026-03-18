<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Events\PurchaseFinalized;
use App\Modules\Purchases\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizePurchaseAction
{
    private $syncPaymentSummary;

    public function __construct(SyncPurchasePaymentSummaryAction $syncPaymentSummary)
    {
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        $purchase = DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);

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

            $fromStatus = $purchase->status;
            $purchase->update([
                'status' => Purchase::STATUS_CONFIRMED,
                'purchase_date' => $data['purchase_date'] ?? $purchase->purchase_date ?? now(),
                'confirmed_at' => now(),
                'confirmed_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
                'notes' => $data['notes'] ?? $purchase->notes,
                'internal_notes' => $data['internal_notes'] ?? $purchase->internal_notes,
                'totals_snapshot' => array_merge($purchase->totals_snapshot ?? [], [
                    'finalized_at' => now()->toDateTimeString(),
                ]),
            ]);

            $purchase->statusHistories()->create([
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
