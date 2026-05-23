<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReceivableDispute;
use App\Modules\Sales\Services\SaleReceivableDisputeNumberService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\Notifications\NotificationCenter;
use App\Support\Notifications\NotificationMessage;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSaleReceivableDisputeAction
{
    public function __construct(
        private readonly SaleReceivableDisputeNumberService $numberService,
        private readonly NotificationCenter $notificationCenter,
    ) {
    }

    public function execute(Sale $sale, array $data, ?User $actor = null): SaleReceivableDispute
    {
        return DB::transaction(function () use ($sale, $data, $actor) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (!$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale' => 'Dispute piutang hanya bisa dibuat untuk sale yang sudah finalized.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0 || $amount > round((float) $sale->balance_due, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal dispute harus lebih besar dari nol dan tidak boleh melebihi sisa piutang.',
                ]);
            }

            $disputeDate = Carbon::parse($data['dispute_date']);

            $dispute = $sale->receivableDisputes()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $sale->branch_id,
                'dispute_number' => $this->numberService->generate($disputeDate),
                'dispute_date' => $disputeDate->toDateString(),
                'amount' => $amount,
                'status' => SaleReceivableDispute::STATUS_OPEN,
                'reason' => (string) $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
                'meta' => [
                    'sale_number' => $sale->sale_number,
                    'payment_status_snapshot' => $sale->payment_status,
                    'balance_due_snapshot' => round((float) $sale->balance_due, 2),
                ],
            ]);

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'sale_id' => $sale->id,
                'from_status' => $sale->payment_status,
                'to_status' => SaleReceivableDispute::STATUS_OPEN,
                'event' => 'receivable_dispute_opened',
                'reason' => $dispute->reason,
                'actor_id' => $actor?->id,
                'meta' => [
                    'dispute_id' => $dispute->id,
                    'dispute_number' => $dispute->dispute_number,
                    'amount' => $amount,
                ],
            ]);

            $this->notificationCenter->publish(new NotificationMessage(
                module: 'sales',
                type: 'sales.receivable_anomaly',
                title: 'Dispute piutang baru',
                body: 'Sale ' . $sale->sale_number . ' punya dispute piutang ' . $dispute->dispute_number . '.',
                tenantId: (int) $sale->tenant_id,
                companyId: (int) $sale->company_id,
                branchId: $sale->branch_id ? (int) $sale->branch_id : null,
                resourceType: $sale->getMorphClass(),
                resourceId: (int) $sale->id,
                dedupeKey: 'sale-receivable-dispute:' . $sale->id,
                actions: [
                    [
                        'label' => 'Buka Sale',
                        'url' => route('sales.show', $sale),
                    ],
                ],
                meta: [
                    'dispute_id' => $dispute->id,
                    'dispute_number' => $dispute->dispute_number,
                    'amount' => $amount,
                ],
            ));

            return $dispute->fresh(['creator']);
        });
    }
}
