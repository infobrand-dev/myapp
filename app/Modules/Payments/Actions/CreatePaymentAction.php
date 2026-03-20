<?php

namespace App\Modules\Payments\Actions;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentNumberService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePaymentAction
{

    private $numberService;
    private $validatePayableTransaction;
    private $recalculatePaymentSummary;

    public function __construct(
        PaymentNumberService $numberService,
        ValidatePayableTransactionAction $validatePayableTransaction,
        RecalculatePaymentSummaryAction $recalculatePaymentSummary
    ) {
        $this->numberService = $numberService;
        $this->validatePayableTransaction = $validatePayableTransaction;
        $this->recalculatePaymentSummary = $recalculatePaymentSummary;
    }

    public function execute(array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($data, $actor) {
            $paidAt = !empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();

            $method = PaymentMethod::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->whereKey($data['payment_method_id'])
                ->where('is_active', true)
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

            $payment = Payment::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'payment_number' => $this->numberService->nextNumber($paidAt),
                'payment_method_id' => $method->id,
                'amount' => $paymentAmount,
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'paid_at' => $paidAt,
                'status' => Payment::STATUS_POSTED,
                'source' => $data['source'] ?? Payment::SOURCE_BACKOFFICE,
                'channel' => $data['channel'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'branch_id' => $data['branch_id'] ?? BranchContext::currentId(),
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
                'received_by' => $data['received_by'] ?? ($actor ? $actor->id : null),
                'created_by' => $actor ? $actor->id : null,
                'updated_by' => $actor ? $actor->id : null,
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
                'meta' => ['allocation_count' => $allocations->count()],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            $this->recalculateSummaries($payables);

            return $payment->load(['method', 'receiver', 'allocations.payable']);
        });
    }

    private function recalculateSummaries(Collection $payables): void
    {
        $payables
            ->unique(fn ($payable) => get_class($payable) . ':' . $payable->getKey())
            ->each(fn ($payable) => $this->recalculatePaymentSummary->execute($payable));
    }
}
