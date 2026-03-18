<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDraftPurchaseAction
{
    public function execute(Purchase $purchase, array $data, ?User $actor = null): Purchase
    {
        if (!$purchase->isDraft()) {
            throw ValidationException::withMessages([
                'purchase' => 'Hanya draft purchase yang dapat dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($purchase, $data, $actor) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $fromStatus = $purchase->status;

            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $purchase->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => Purchase::STATUS_CANCELLED,
                'event' => 'cancelled',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            return $purchase->refresh();
        });
    }
}
