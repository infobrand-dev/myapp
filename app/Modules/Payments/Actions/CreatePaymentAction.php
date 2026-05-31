<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentNumberService;
use App\Support\AccountingJournalService;
use App\Support\AccountingPeriodLockService;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\HookManager;
use App\Support\Notifications\NotificationCenter;
use App\Support\Notifications\NotificationMessage;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePaymentAction
{

    private $numberService;
    private $validatePayableTransaction;
    private $recalculatePaymentSummary;
    private $journalService;
    private $periodLockService;
    private $notificationCenter;
    private $hooks;

    public function __construct(
        PaymentNumberService $numberService,
        ValidatePayableTransactionAction $validatePayableTransaction,
        RecalculatePaymentSummaryAction $recalculatePaymentSummary,
        AccountingJournalService $journalService,
        AccountingPeriodLockService $periodLockService,
        NotificationCenter $notificationCenter,
        HookManager $hooks
    ) {
        $this->numberService = $numberService;
        $this->validatePayableTransaction = $validatePayableTransaction;
        $this->recalculatePaymentSummary = $recalculatePaymentSummary;
        $this->journalService = $journalService;
        $this->periodLockService = $periodLockService;
        $this->notificationCenter = $notificationCenter;
        $this->hooks = $hooks;
    }

    public function execute(array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($data, $actor) {
            $paidAt = !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();
            $this->periodLockService->ensureDateOpen($paidAt, $data['branch_id'] ?? null, 'create payment');

            $method = BooleanQuery::apply(
                PaymentMethod::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->whereKey($data['payment_method_id']),
                'is_active'
            )
                ->first();

            if (!$method) {
                throw ValidationException::withMessages([
                    'payment_method_id' => 'Payment method tidak valid atau tidak aktif.',
                ]);
            }

            $allocations = collect($data['allocations'] ?? [])
                ->filter(fn ($allocation) => is_array($allocation))
                ->values();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Minimal satu alokasi pembayaran wajib diisi.',
                ]);
            }

            $allocationTotal = round((float) $allocations->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2);
            $paymentAmount = round((float) $data['amount'], 2);

            if ($paymentAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran harus lebih besar dari nol.',
                ]);
            }

            if ($paymentAmount !== $allocationTotal) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran harus sama dengan total alokasi.',
                ]);
            }

            if ($method->requires_reference && empty($data['reference_number']) && empty($data['external_reference'])) {
                throw ValidationException::withMessages([
                    'reference_number' => 'Payment method ini membutuhkan reference number atau external reference.',
                ]);
            }

            $resolvedBranchId = $this->resolveBranchId($data, $allocations, $actor);
            $proofFilePath = $this->storeProofFile($data['proof_file'] ?? null);
            $reconciliationStatus = $data['reconciliation_status'] ?? Payment::RECONCILIATION_UNRECONCILED;

            $payment = Payment::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'payment_number' => $this->numberService->nextNumber($paidAt),
                'payment_method_id' => $method->id,
                'amount' => $paymentAmount,
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'paid_at' => $paidAt,
                'status' => Payment::STATUS_POSTED,
                'reconciliation_status' => $reconciliationStatus,
                'source' => $data['source'] ?? Payment::SOURCE_BACKOFFICE,
                'channel' => $data['channel'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'proof_file_path' => $proofFilePath,
                'branch_id' => $resolvedBranchId,
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
                'received_by' => $data['received_by'] ?? ($actor ? $actor->id : null),
                'created_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
                'reconciled_by' => $reconciliationStatus === Payment::RECONCILIATION_RECONCILED && $actor ? $actor->id : null,
                'reconciled_at' => $reconciliationStatus === Payment::RECONCILIATION_RECONCILED ? now() : null,
            ]);

            $payables = $allocations->map(function (array $allocation, int $index) use ($payment) {
                $payable = $this->validatePayableTransaction->execute(
                    (string) $allocation['payable_type'],
                    (int) $allocation['payable_id']
                );

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
                'from_status' => null,
                'to_status' => Payment::STATUS_POSTED,
                'event' => 'created',
                'reason' => null,
                'meta' => [
                    'allocation_count' => $allocations->count(),
                    'allocation_total' => $allocationTotal,
                    'reconciliation_status' => $reconciliationStatus,
                    'payable_types' => $allocations->pluck('payable_type')->unique()->values()->all(),
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $this->recalculateSummaries($payables);

            $this->journalService->sync(
                $payment,
                'payment_posted',
                $paidAt,
                $this->journalLines($payment, $allocations),
                [
                    'amount' => (float) $payment->amount,
                    'kind' => data_get($payment->meta, 'kind'),
                ],
                'Auto journal payment ' . $payment->payment_number
            );

            $this->dispatchPostedHooks($payment, $payables, $allocations, $actor);
            $this->publishNotifications($payment, $payables);

            return $payment->load(['method', 'receiver', 'allocations.payable']);
        });
    }

    private function dispatchPostedHooks(Payment $payment, Collection $payables, Collection $allocations, ?User $actor = null): void
    {
        try {
            $this->hooks->dispatch('payments.posted', [
                'payment' => $payment,
                'payables' => $payables,
                'allocations' => $allocations,
                'actor' => $actor,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function recalculateSummaries(Collection $payables): void
    {
        $payables
            ->unique(fn ($payable) => get_class($payable) . ':' . $payable->getKey())
            ->each(fn ($payable) => $this->recalculatePaymentSummary->execute($payable));
    }

    private function resolveBranchId(array $data, Collection $allocations, ?User $actor = null): ?int
    {
        if (array_key_exists('branch_id', $data)) {
            return $data['branch_id'] ? (int) $data['branch_id'] : null;
        }

        if (BranchContext::currentId() !== null) {
            return BranchContext::currentId();
        }

        $branchIds = $allocations
            ->pluck('branch_id')
            ->filter(fn ($branchId) => $branchId !== null && $branchId !== '')
            ->map(fn ($branchId) => (int) $branchId)
            ->unique()
            ->values();

        if ($branchIds->count() === 1) {
            return $branchIds->first();
        }

        return BranchContext::currentOrDefaultId($actor ?? auth()->user(), CompanyContext::currentId());
    }

    private function storeProofFile($proofFile): ?string
    {
        if (!$proofFile instanceof UploadedFile) {
            return null;
        }

        return $proofFile->store('payments/proofs', 'public');
    }

    private function journalLines(Payment $payment, Collection $allocations): array
    {
        $methodName = $payment->method ? $payment->method->name : 'Payment';
        $cashAccountName = 'Cash/Bank - ' . $methodName;
        $lines = [];

        foreach ($allocations as $allocation) {
            $type = (string) ($allocation['payable_type'] ?? '');
            $amount = round((float) ($allocation['amount'] ?? 0), 2);
            $kind = (string) data_get($allocation, 'meta.kind', data_get($payment->meta, 'kind', ''));

            if ($amount <= 0) {
                continue;
            }

            if ($type === 'sale') {
                $lines[] = ['account_code' => 'CASH', 'account_name' => $cashAccountName, 'debit' => $amount, 'credit' => 0];
                $lines[] = ['account_code' => 'AR', 'account_name' => 'Accounts Receivable', 'debit' => 0, 'credit' => $amount];
            } elseif ($type === 'purchase') {
                $lines[] = ['account_code' => 'AP', 'account_name' => 'Accounts Payable', 'debit' => $amount, 'credit' => 0];
                $lines[] = ['account_code' => 'CASH', 'account_name' => $cashAccountName, 'debit' => 0, 'credit' => $amount];
            } elseif ($type === 'sale_return' || $kind === 'refund') {
                $lines[] = ['account_code' => 'AR', 'account_name' => 'Accounts Receivable', 'debit' => $amount, 'credit' => 0];
                $lines[] = ['account_code' => 'CASH', 'account_name' => $cashAccountName, 'debit' => 0, 'credit' => $amount];
            }
        }

        return $this->aggregateLines($lines);
    }

    private function aggregateLines(array $lines): array
    {
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

    private function publishNotifications(Payment $payment, Collection $payables): void
    {
        $this->notificationCenter->publish(new NotificationMessage(
            module: 'payments',
            type: 'payments.payment_posted',
            title: 'Payment posted',
            body: 'Payment ' . $payment->payment_number . ' sebesar ' . number_format((float) $payment->amount, 0, ',', '.') . ' sudah diposting.',
            tenantId: (int) $payment->tenant_id,
            companyId: $payment->company_id ? (int) $payment->company_id : null,
            branchId: $payment->branch_id ? (int) $payment->branch_id : null,
            resourceType: $payment->getMorphClass(),
            resourceId: (int) $payment->id,
            actions: [
                [
                    'label' => 'Buka Payment',
                    'url' => route('payments.show', $payment),
                ],
            ],
            meta: [
                'payment_number' => $payment->payment_number,
                'amount' => (float) $payment->amount,
            ],
        ));

        $overpaidPayable = $payables->first(function ($payable) {
            return (string) optional($payable->fresh())->payment_status === 'overpaid';
        });

        if ($overpaidPayable) {
            $this->notificationCenter->publish(new NotificationMessage(
                module: 'payments',
                type: 'payments.overpayment_detected',
                title: 'Overpayment terdeteksi',
                body: 'Payment ' . $payment->payment_number . ' membuat salah satu payable menjadi overpaid.',
                tenantId: (int) $payment->tenant_id,
                companyId: $payment->company_id ? (int) $payment->company_id : null,
                branchId: $payment->branch_id ? (int) $payment->branch_id : null,
                resourceType: $payment->getMorphClass(),
                resourceId: (int) $payment->id,
                dedupeKey: 'payment-overpaid:' . $payment->id,
                actions: [
                    [
                        'label' => 'Review Payment',
                        'url' => route('payments.show', $payment),
                    ],
                ],
            ));
        }
    }
}
