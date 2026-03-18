<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDraftReturnAction
{
    public function execute(SaleReturn $saleReturn, ?string $reason = null, ?User $actor = null): SaleReturn
    {
        return DB::transaction(function () use ($saleReturn, $reason, $actor) {
            $saleReturn = SaleReturn::query()->lockForUpdate()->findOrFail($saleReturn->id);

            if (!$saleReturn->isDraft()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Hanya draft sales return yang dapat dibatalkan.',
                ]);
            }

            $fromStatus = $saleReturn->status;
            $saleReturn->update([
                'status' => SaleReturn::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'updated_by' => $actor ? $actor->id : null,
                'cancelled_by' => $actor ? $actor->id : null,
            ]);

            $saleReturn->statusLogs()->create([
                'from_status' => $fromStatus,
                'to_status' => SaleReturn::STATUS_CANCELLED,
                'event' => 'cancelled',
                'reason' => $reason,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            return $saleReturn->refresh();
        });
    }
}
