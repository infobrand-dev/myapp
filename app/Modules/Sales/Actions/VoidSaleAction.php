<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Events\SaleVoided;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidSaleAction
{
    public function execute(Sale $sale, array $data, ?User $actor = null): Sale
    {
        $sale = DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('items')
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (!$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya sale final yang dapat di-void.',
                ]);
            }

            $hasPostedPayments = $sale->paymentAllocations()
                ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
                ->exists();

            if ($hasPostedPayments) {
                throw ValidationException::withMessages([
                    'sale' => 'Sale yang masih memiliki payment posted tidak dapat di-void. Void/refund payment terlebih dahulu.',
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
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'status_before' => $statusBefore,
                'reason' => $reason,
                'snapshot' => $snapshot,
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
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

        event(new SaleVoided($sale));

        return $sale;
    }
}
