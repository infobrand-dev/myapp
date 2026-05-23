<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\SaleReturn;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSaleReturnRefundStatusAction
{
    public function execute(SaleReturn $saleReturn, string $status, ?string $reason = null, ?User $actor = null): SaleReturn
    {
        return DB::transaction(function () use ($saleReturn, $status, $reason, $actor) {
            $saleReturn = SaleReturn::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->lockForUpdate()
                ->findOrFail($saleReturn->id);

            if (!$saleReturn->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Status refund hanya bisa diubah untuk sales return yang sudah finalized.',
                ]);
            }

            if (!$saleReturn->refund_required) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Sales return ini tidak membutuhkan refund.',
                ]);
            }

            $status = trim(strtolower($status));
            if (!in_array($status, [SaleReturn::REFUND_PENDING, SaleReturn::REFUND_SKIPPED], true)) {
                throw ValidationException::withMessages([
                    'refund_status' => 'Status refund tidak didukung.',
                ]);
            }

            $currentStatus = (string) $saleReturn->refund_status;
            if ($currentStatus === $status) {
                throw ValidationException::withMessages([
                    'refund_status' => 'Status refund sudah sama dengan pilihan yang diminta.',
                ]);
            }

            if ($status === SaleReturn::REFUND_SKIPPED && (float) $saleReturn->refunded_total > 0) {
                throw ValidationException::withMessages([
                    'refund_status' => 'Refund yang sudah berjalan tidak bisa langsung diubah ke skipped.',
                ]);
            }

            if (trim((string) $reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Alasan perubahan status refund wajib diisi.',
                ]);
            }

            $saleReturn->update([
                'refund_status' => $status,
                'updated_by' => $actor?->id,
                'meta' => array_merge($saleReturn->meta ?? [], [
                    'refund_status_override' => $status === SaleReturn::REFUND_SKIPPED ? SaleReturn::REFUND_SKIPPED : null,
                ]),
            ]);

            $saleReturn->statusLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'from_status' => $currentStatus,
                'to_status' => $status,
                'event' => 'refund_status_updated',
                'reason' => $reason,
                'meta' => [
                    'refunded_total' => (float) $saleReturn->refunded_total,
                    'refund_balance' => (float) $saleReturn->refund_balance,
                ],
                'actor_id' => $actor?->id,
            ]);

            return $saleReturn->refresh();
        });
    }
}
