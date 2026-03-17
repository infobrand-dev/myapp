<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDraftSaleAction
{
    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        return DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (!$sale->isDraft()) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya draft sale yang dapat dibatalkan.',
                ]);
            }

            $reason = trim((string) ($data['reason'] ?? ''));
            $statusBefore = $sale->status;

            $sale->update([
                'status' => Sale::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'updated_by' => $actor ? $actor->id : null,
                'cancelled_by' => $actor ? $actor->id : null,
                'meta' => array_merge($sale->meta ?? [], [
                    'cancel_reason' => $reason ?: null,
                ]),
            ]);

            $sale->statusHistories()->create([
                'from_status' => $statusBefore,
                'to_status' => Sale::STATUS_CANCELLED,
                'event' => 'cancelled',
                'reason' => $reason ?: null,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            return $sale;
        });
    }
}
