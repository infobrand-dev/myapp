<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdatePaymentAction
{
    public function __construct(
        private readonly ValidatePayableTransactionAction $validatePayableTransaction,
        private readonly RecalculatePaymentSummaryAction $recalculatePaymentSummary,
        private readonly SensitiveActionApprovalService $approvalService,
        private readonly AccountingPeriodLockService $periodLockService,
        private readonly AccountingJournalService $journalService
    ) {
    }

    public function execute(Payment $payment, array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($payment, $data, $actor) {
            $payment = Payment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->with('allocations.payable')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_POSTED) {
                throw ValidationException::withMessages([
                    'payment' => 'Hanya payment posted yang bisa diperbarui.',
                ]);
            }

            $targetPaidAt = !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : $payment->paid_at;
            $this->periodLockService->ensureDateOpen($targetPaidAt, $payment->branch_id, 'update payment');
            $this->approvalService->ensureApprovedOrCreatePending(
                'payments',
                'update_payment',
                $payment,
                [
                    'amount' => round((float) ($data['amount'] ?? $payment->amount), 2),
                    'allocations' => $data['allocations'] ?? [],
                ],
                $actor,
                'Edit payment posted'
            );

            $method = BooleanQuery::apply(
                PaymentMethod::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->whereKey($data['payment_method_id']),
                'is_active'
            )->first();

            if (!$method) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'Payment method tidak valid atau tidak aktif.',
                ]);
            }

            $allocations = collect($data['allocations'] ?? [])->filter(fn ($allocation) => is_array($allocation))->values();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Minimal satu alokasi pembayaran wajib diisi.',
                ]);
            }

            $allocationTotal = round((float) $allocations->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2);
            $paymentAmount = round((float) $data['amount'], 2);

            if ($paymentAmount <= 0 || $paymentAmount !== $allocationTotal) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran harus sama dengan total alokasi dan lebih besar dari nol.',
                ]);
            }

            $oldPayables = $payment->allocations->pluck('payable');
            $oldAmount = (float) $payment->amount;
            $oldProof = $payment->proof_file_path;
            $newProof = $this->storeProofFile($data['proof_file'] ?? null);

            $payment->update([
                'payment_method_id' => $method->id,
                'amount' => $paymentAmount,
                'currency_code' => $data['currency_code'] ?? $payment->currency_code,
                'paid_at' => !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : $payment->paid_at,
                'reconciliation_status' => $data['reconciliation_status'] ?? $payment->reconciliation_status,
                'source' => $data['source'] ?? $payment->source,
                'channel' => $data['channel'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'proof_file_path' => $newProof ?: $payment->proof_file_path,
                'branch_id' => array_key_exists('branch_id', $data) ? ($data['branch_id'] ? (int) $data['branch_id'] : null) : $payment->branch_id,
                'notes' => $data['notes'] ?? null,
                'received_by' => $data['received_by'] ?? ($actor ? $actor->id : $payment->received_by),
                'updated_by' => $actor ? $actor->id : $payment->updated_by,
                'reconciled_by' => ($data['reconciliation_status'] ?? $payment->reconciliation_status) === Payment::RECONCILIATION_RECONCILED ? ($actor?->id ?? $payment->reconciled_by) : null,
                'reconciled_at' => ($data['reconciliation_status'] ?? $payment->reconciliation_status) === Payment::RECONCILIATION_RECONCILED ? now() : null,
            ]);

            $payment->allocations()->delete();

            $payables = $allocations->map(function (array $allocation, int $index) use ($payment) {
                $payable = $this->validatePayableTransaction->execute((string) $allocation['payable_type'], (int) $allocation['payable_id']);

                $payment->allocations()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'company_id' => CompanyContext::currentId(),
                    'payable_type' => $payable->getMorphClass(),
                    'payable_id' => $payable->getKey(),
                    'allocation_order' => $index + 1,
                    'amount' => round((float) $allocation['amount'], 2),
                    'meta' => $allocation['meta'] ?? null,
                ]);

                return $payable;
            });

            $payment->statusLogs()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'from_status' => $payment->status,
                'to_status' => $payment->status,
                'event' => 'updated',
                'actor_id' => $actor?->id,
                'meta' => [
                    'allocation_count' => $allocations->count(),
                    'old_amount' => $oldAmount,
                    'new_amount' => $paymentAmount,
                    'reconciliation_status' => $payment->reconciliation_status,
                    'payable_types' => $allocations->pluck('payable_type')->unique()->values()->all(),
                ],
            ]);

            $this->recalculateSummaries($oldPayables->merge($payables));

            $this->journalService->sync(
                $payment,
                'payment_posted',
                $targetPaidAt,
                $this->journalLines($payment, $allocations),
                [
                    'amount' => (float) $payment->amount,
                    'updated' => true,
                ],
                'Auto journal payment update ' . $payment->payment_number
            );

            if ($newProof && $oldProof && $oldProof !== $newProof) {
                Storage::disk('public')->delete($oldProof);
            }

            return $payment->fresh(['method', 'receiver', 'allocations.payable', 'statusLogs.actor']);
        });
    }

    private function recalculateSummaries(Collection $payables): void
    {
        $payables
            ->filter()
            ->unique(fn ($payable) => get_class($payable) . ':' . $payable->getKey())
            ->each(fn ($payable) => $this->recalculatePaymentSummary->execute($payable));
    }

    private function storeProofFile(mixed $proofFile): ?string
    {
        if (!$proofFile instanceof UploadedFile) {
            return null;
        }

        return $proofFile->store('payments/proofs', 'public');
    }

    private function journalLines(Payment $payment, Collection $allocations): array
    {
        $cashAccountName = 'Cash/Bank - ' . ($payment->method?->name ?? 'Payment');
        $lines = [];

        foreach ($allocations as $allocation) {
            $type = (string) ($allocation['payable_type'] ?? '');
            $amount = round((float) ($allocation['amount'] ?? 0), 2);
            $kind = (string) data_get($allocation, 'meta.kind', data_get($payment->meta, 'kind', ''));

            if ($type === 'sale') {
                $lines[] = ['account_code' => 'CASH', 'account_name' => $cashAccountName, 'debit' => $amount, 'credit' => 0];
                $lines[] = ['account_code' => 'AR', 'account_name' => 'Accounts Receivable', 'debit' => 0, 'credit' => $amount];
            } elseif ($type === 'purchase') {
                $lines[] = ['account_code' => 'AP', 'account_name' => 'Accounts Payable', 'debit' => $amount, 'credit' => 0];
                $lines[] = ['account_code' => 'CASH', 'account_name' => $cashAccountName, 'debit' => 0, 'credit' => $amount];
            } elseif ($type === 'sale_return' || $kind === 'refund') {
                $lines[] = ['account_code' => 'SALES_REFUND', 'account_name' => 'Sales Refund', 'debit' => $amount, 'credit' => 0];
                $lines[] = ['account_code' => 'CASH', 'account_name' => $cashAccountName, 'debit' => 0, 'credit' => $amount];
            }
        }

        return collect($lines)
            ->groupBy(fn (array $line) => $line['account_code'] . '|' . $line['account_name'])
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'account_code' => $first['account_code'],
                    'account_name' => $first['account_name'],
                    'debit' => round((float) $rows->sum('debit'), 2),
                    'credit' => round((float) $rows->sum('credit'), 2),
                ];
            })
            ->values()
            ->all();
    }
}
