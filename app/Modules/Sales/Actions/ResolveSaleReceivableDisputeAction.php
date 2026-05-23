<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReceivableAdjustment;
use App\Modules\Sales\Models\SaleReceivableDispute;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveSaleReceivableDisputeAction
{
    public function __construct(
        private readonly CreateSaleReceivableAdjustmentAction $createAdjustment,
    ) {
    }

    public function execute(Sale $sale, SaleReceivableDispute $dispute, array $data, ?User $actor = null): SaleReceivableDispute
    {
        return DB::transaction(function () use ($sale, $dispute, $data, $actor) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($sale->id);

            $dispute = SaleReceivableDispute::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('sale_id', $sale->id)
                ->lockForUpdate()
                ->findOrFail($dispute->id);

            if ($dispute->status !== SaleReceivableDispute::STATUS_OPEN) {
                throw ValidationException::withMessages([
                    'dispute' => 'Hanya dispute berstatus open yang bisa diselesaikan.',
                ]);
            }

            $outcomeType = (string) $data['outcome_type'];
            $status = match ($outcomeType) {
                SaleReceivableDispute::OUTCOME_CONTINUE_PAYMENT => SaleReceivableDispute::STATUS_RESOLVED,
                SaleReceivableDispute::OUTCOME_CLOSE_DISPUTE => SaleReceivableDispute::STATUS_CLOSED,
                SaleReceivableDispute::OUTCOME_CREDIT_MEMO => SaleReceivableDispute::STATUS_CREDIT_MEMO,
                SaleReceivableDispute::OUTCOME_WRITE_OFF => SaleReceivableDispute::STATUS_WRITE_OFF,
                default => throw ValidationException::withMessages([
                    'outcome_type' => 'Outcome dispute tidak valid.',
                ]),
            };

            $meta = $dispute->meta ?? [];

            if ($outcomeType === SaleReceivableDispute::OUTCOME_CREDIT_MEMO) {
                $adjustment = $this->createAdjustment->execute($sale, SaleReceivableAdjustment::TYPE_CREDIT_MEMO, [
                    'adjustment_date' => now()->toDateString(),
                    'amount' => round((float) $dispute->amount, 2),
                    'reason' => 'Dispute ' . $dispute->dispute_number . ': ' . $dispute->reason,
                    'notes' => $data['resolution_note'] ?? $dispute->notes,
                ], $actor);
                $meta['resolved_adjustment_number'] = $adjustment->adjustment_number;
            }

            if ($outcomeType === SaleReceivableDispute::OUTCOME_WRITE_OFF) {
                $adjustment = $this->createAdjustment->execute($sale, SaleReceivableAdjustment::TYPE_WRITE_OFF, [
                    'adjustment_date' => now()->toDateString(),
                    'amount' => round((float) $dispute->amount, 2),
                    'reason' => 'Dispute ' . $dispute->dispute_number . ': ' . $dispute->reason,
                    'notes' => $data['resolution_note'] ?? $dispute->notes,
                ], $actor);
                $meta['resolved_adjustment_number'] = $adjustment->adjustment_number;
            }

            $dispute->forceFill([
                'status' => $status,
                'outcome_type' => $outcomeType,
                'resolution_note' => $data['resolution_note'] ?? null,
                'resolved_at' => now(),
                'resolved_by' => $actor?->id,
                'updated_by' => $actor?->id,
                'meta' => $meta,
            ])->save();

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'sale_id' => $sale->id,
                'from_status' => SaleReceivableDispute::STATUS_OPEN,
                'to_status' => $status,
                'event' => 'receivable_dispute_resolved',
                'reason' => $data['resolution_note'] ?? $dispute->reason,
                'actor_id' => $actor?->id,
                'meta' => [
                    'dispute_id' => $dispute->id,
                    'dispute_number' => $dispute->dispute_number,
                    'outcome_type' => $outcomeType,
                    'resolved_adjustment_number' => $meta['resolved_adjustment_number'] ?? null,
                ],
            ]);

            return $dispute->fresh(['creator', 'resolver']);
        });
    }
}
