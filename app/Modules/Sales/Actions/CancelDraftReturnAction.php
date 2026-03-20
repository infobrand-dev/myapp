<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDraftReturnAction
{
    public function execute(SaleReturn $saleReturn, ?string $reason = null, ?User $actor = null): SaleReturn
    {
        return DB::transaction(function () use ($saleReturn, $reason, $actor) {
            $saleReturn = SaleReturn::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->lockForUpdate()
                ->findOrFail($saleReturn->id);

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
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
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
