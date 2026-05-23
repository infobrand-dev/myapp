<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReceivableAdjustment;
use App\Modules\Sales\Services\SaleReceivableAdjustmentNumberService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\Notifications\NotificationCenter;
use App\Support\Notifications\NotificationMessage;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSaleReceivableAdjustmentAction
{
    public function __construct(
        private readonly SaleReceivableAdjustmentNumberService $numberService,
        private readonly SyncSalePaymentSummaryAction $syncPaymentSummary,
        private readonly AccountingJournalService $journalService,
        private readonly AccountingPeriodLockService $periodLockService,
        private readonly NotificationCenter $notificationCenter,
    ) {
    }

    public function execute(Sale $sale, string $type, array $data, ?User $actor = null): SaleReceivableAdjustment
    {
        return DB::transaction(function () use ($sale, $type, $data, $actor) {
            if (!in_array($type, [SaleReceivableAdjustment::TYPE_CREDIT_MEMO, SaleReceivableAdjustment::TYPE_WRITE_OFF], true)) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis penyesuaian piutang tidak didukung.',
                ]);
            }

            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (!$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale' => 'Penyesuaian piutang hanya bisa dibuat untuk sale yang sudah finalized.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            $currentBalanceDue = round((float) $sale->balance_due, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal penyesuaian harus lebih besar dari nol.',
                ]);
            }

            if ($amount > $currentBalanceDue) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal penyesuaian tidak boleh melebihi sisa piutang sale.',
                ]);
            }

            $adjustmentDate = !empty($data['adjustment_date']) ? Carbon::parse($data['adjustment_date']) : now();
            $this->periodLockService->ensureDateOpen($adjustmentDate, $sale->branch_id, 'sale receivable adjustment');

            $adjustment = $sale->receivableAdjustments()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $sale->branch_id,
                'adjustment_number' => $this->numberService->generate($type, $adjustmentDate),
                'adjustment_type' => $type,
                'adjustment_date' => $adjustmentDate,
                'amount' => $amount,
                'status' => 'posted',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'meta' => [
                    'sale_number' => $sale->sale_number,
                    'payment_status_before' => $sale->payment_status,
                    'balance_due_before' => $currentBalanceDue,
                ],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $journal = $this->journalService->sync(
                $adjustment,
                $type === SaleReceivableAdjustment::TYPE_WRITE_OFF ? 'sale_write_off' : 'sale_credit_memo',
                $adjustmentDate,
                $this->journalLines($type, $amount),
                [
                    'sale_id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'adjustment_type' => $type,
                ],
                ($type === SaleReceivableAdjustment::TYPE_WRITE_OFF ? 'Sale write-off ' : 'Sale credit memo ') . $adjustment->adjustment_number
            );

            $adjustment->forceFill(['journal_id' => $journal->id])->save();

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $sale->branch_id,
                'from_status' => $sale->status,
                'to_status' => $sale->status,
                'event' => 'receivable_adjustment_created',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor?->id,
                'meta' => [
                    'adjustment_number' => $adjustment->adjustment_number,
                    'adjustment_type' => $type,
                    'amount' => $amount,
                    'journal_number' => $journal->journal_number,
                ],
            ]);

            $this->syncPaymentSummary->execute($sale);

            if ($type === SaleReceivableAdjustment::TYPE_CREDIT_MEMO) {
                $this->notificationCenter->publish(new NotificationMessage(
                    module: 'sales',
                    type: 'sales.credit_memo_created',
                    title: 'Credit memo dibuat',
                    body: 'Credit memo ' . $adjustment->adjustment_number . ' untuk sale ' . $sale->sale_number . ' sudah diposting.',
                    tenantId: (int) $sale->tenant_id,
                    companyId: (int) $sale->company_id,
                    branchId: $sale->branch_id ? (int) $sale->branch_id : null,
                    resourceType: $adjustment->getMorphClass(),
                    resourceId: (int) $adjustment->id,
                    actions: [
                        [
                            'label' => 'Buka Sale',
                            'url' => route('sales.show', $sale),
                        ],
                    ],
                ));
            }

            return $adjustment->fresh(['creator', 'journal']);
        });
    }

    private function journalLines(string $type, float $amount): array
    {
        if ($type === SaleReceivableAdjustment::TYPE_WRITE_OFF) {
            return [
                ['account_code' => 'AR_WRITE_OFF', 'account_name' => 'Accounts Receivable Write-off', 'debit' => $amount, 'credit' => 0],
                ['account_code' => 'AR', 'account_name' => 'Accounts Receivable', 'debit' => 0, 'credit' => $amount],
            ];
        }

        return [
            ['account_code' => 'SALES_RETURNS', 'account_name' => 'Sales Return / Credit Memo', 'debit' => $amount, 'credit' => 0],
            ['account_code' => 'AR', 'account_name' => 'Accounts Receivable', 'debit' => 0, 'credit' => $amount],
        ];
    }
}
