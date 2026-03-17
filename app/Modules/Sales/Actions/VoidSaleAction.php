<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidSaleAction
{
    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        return DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()->with('items')->lockForUpdate()->findOrFail($sale->id);

            if (!$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya sale final yang dapat di-void.',
                ]);
            }

            $reason = trim((string) ($data['reason'] ?? ''));
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Reason void wajib diisi.',
                ]);
            }

            $statusBefore = $sale->status;
            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'void_reason' => $reason,
                'voided_at' => now(),
                'voided_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
            ]);

            $snapshot = $sale->load('items')->toArray();

            $sale->voidLogs()->create([
                'status_before' => $statusBefore,
                'reason' => $reason,
                'snapshot' => $snapshot,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $sale->statusHistories()->create([
                'from_status' => $statusBefore,
                'to_status' => Sale::STATUS_VOIDED,
                'event' => 'voided',
                'reason' => $reason,
                'actor_id' => $actor ? $actor->id : null,
                'meta' => [
                    'voided_at' => now()->toDateTimeString(),
                ],
            ]);

            return $sale->load('voidLogs', 'statusHistories');
        });
    }
}
