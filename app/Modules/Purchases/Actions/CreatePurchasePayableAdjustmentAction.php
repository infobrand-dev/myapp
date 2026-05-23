<?php

namespace App\Modules\Purchases\Actions;

use App\Models\User;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Models\PurchasePayableAdjustment;
use App\Modules\Purchases\Services\PurchasePayableAdjustmentNumberService;
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

class CreatePurchasePayableAdjustmentAction
{
    public function __construct(
        private readonly PurchasePayableAdjustmentNumberService $numberService,
        private readonly SyncPurchasePaymentSummaryAction $syncPaymentSummary,
        private readonly AccountingJournalService $journalService,
        private readonly AccountingPeriodLockService $periodLockService,
        private readonly NotificationCenter $notificationCenter,
    ) {
    }

    public function execute(Purchase $purchase, string $type, array $data, ?User $actor = null): PurchasePayableAdjustment
    {
        return DB::transaction(function () use ($purchase, $type, $data, $actor) {
            if (!in_array($type, [PurchasePayableAdjustment::TYPE_DEBIT_NOTE, PurchasePayableAdjustment::TYPE_WRITE_OFF], true)) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis penyesuaian hutang tidak didukung.',
                ]);
            }

            $purchase = Purchase::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (!$purchase->isConfirmedLike()) {
                throw ValidationException::withMessages([
                    'purchase' => 'Penyesuaian hutang hanya bisa dibuat untuk purchase yang sudah aktif.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal penyesuaian harus lebih besar dari nol.',
                ]);
            }

            $currentBalanceDue = round((float) $purchase->balance_due, 2);
            if ($amount > $currentBalanceDue) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal penyesuaian tidak boleh melebihi sisa hutang purchase.',
                ]);
            }

            $adjustmentDate = !empty($data['adjustment_date']) ? Carbon::parse($data['adjustment_date']) : now();
            $this->periodLockService->ensureDateOpen($adjustmentDate, $purchase->branch_id, 'purchase payable adjustment');

            $adjustment = $purchase->payableAdjustments()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'adjustment_number' => $this->numberService->generate($type, $adjustmentDate),
                'adjustment_type' => $type,
                'adjustment_date' => $adjustmentDate,
                'amount' => $amount,
                'status' => 'posted',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'meta' => [
                    'purchase_number' => $purchase->purchase_number,
                    'payment_status_before' => $purchase->payment_status,
                    'balance_due_before' => $currentBalanceDue,
                ],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $journal = $this->journalService->sync(
                $adjustment,
                $type === PurchasePayableAdjustment::TYPE_WRITE_OFF ? 'purchase_write_off' : 'purchase_debit_note',
                $adjustmentDate,
                $this->journalLines($type, $amount),
                [
                    'purchase_id' => $purchase->id,
                    'purchase_number' => $purchase->purchase_number,
                    'adjustment_type' => $type,
                ],
                ($type === PurchasePayableAdjustment::TYPE_WRITE_OFF ? 'Purchase write-off ' : 'Purchase debit note ') . $adjustment->adjustment_number
            );

            $adjustment->forceFill(['journal_id' => $journal->id])->save();

            $purchase->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $purchase->branch_id,
                'from_status' => $purchase->status,
                'to_status' => $purchase->status,
                'event' => 'payable_adjustment_created',
                'reason' => $data['reason'] ?? null,
                'actor_id' => $actor?->id,
                'meta' => [
                    'adjustment_number' => $adjustment->adjustment_number,
                    'adjustment_type' => $type,
                    'amount' => $amount,
                    'journal_number' => $journal->journal_number,
                ],
            ]);

            $this->syncPaymentSummary->execute($purchase);

            if ($type === PurchasePayableAdjustment::TYPE_DEBIT_NOTE) {
                $this->notificationCenter->publish(new NotificationMessage(
                    module: 'purchases',
                    type: 'purchases.debit_note_created',
                    title: 'Debit note supplier dibuat',
                    body: 'Debit note ' . $adjustment->adjustment_number . ' untuk purchase ' . $purchase->purchase_number . ' sudah diposting.',
                    tenantId: (int) $purchase->tenant_id,
                    companyId: (int) $purchase->company_id,
                    branchId: $purchase->branch_id ? (int) $purchase->branch_id : null,
                    resourceType: $adjustment->getMorphClass(),
                    resourceId: (int) $adjustment->id,
                    actions: [
                        [
                            'label' => 'Buka Purchase',
                            'url' => route('purchases.show', $purchase),
                        ],
                    ],
                ));
            }

            return $adjustment->fresh(['creator']);
        });
    }

    private function journalLines(string $type, float $amount): array
    {
        if ($type === PurchasePayableAdjustment::TYPE_WRITE_OFF) {
            return [
                [
                    'account_code' => 'AP',
                    'account_name' => 'Accounts Payable',
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => 'AP_WRITE_OFF',
                    'account_name' => 'Accounts Payable Write-off',
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ];
        }

        return [
            [
                'account_code' => 'AP',
                'account_name' => 'Accounts Payable',
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'account_code' => 'PURCHASES',
                'account_name' => 'Purchases / Inventory',
                'debit' => 0,
                'credit' => $amount,
            ],
        ];
    }
}
