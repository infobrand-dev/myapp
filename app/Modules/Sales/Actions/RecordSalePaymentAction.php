<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Payments\Actions\CreatePaymentAction as CreateCentralPaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSalePaymentAction
{
    private $syncPaymentSummary;

    public function __construct(SyncSalePaymentSummaryAction $syncPaymentSummary)
    {
        $this->syncPaymentSummary = $syncPaymentSummary;
    }

    public function execute(Sale $sale, array $data, ?User $actor = null): Payment
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
                    'sale' => 'Pembayaran hanya bisa dicatat pada sale final.',
                ]);
            }

            $centralPayment = $this->recordCentralPayment($sale, $data, $actor);

            $this->syncPaymentSummary->execute($sale);

            return $centralPayment;
        });
    }

    private function resolveCentralPaymentMethodId(?string $legacyMethod): ?int
    {
        $code = PaymentMethod::fromSalesInput($legacyMethod);

        $methodId = DB::table('payment_methods')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('code', $code)
            ->value('id');

        return $methodId ? (int) $methodId : null;
    }

    private function recordCentralPayment(Sale $sale, array $data, ?User $actor): Payment
    {
        $paymentMethodId = $this->resolveCentralPaymentMethodId($data['payment_method'] ?? null);

        if (!$paymentMethodId) {
            throw ValidationException::withMessages([
                'payment_method' => 'Payment method belum tersedia di module Payments.',
            ]);
        }

        /** @var CreateCentralPaymentAction $centralAction */
        $centralAction = app(CreateCentralPaymentAction::class);

        return $centralAction->execute([
            'payment_method_id' => $paymentMethodId,
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'] ?? $sale->currency_code,
            'paid_at' => $data['payment_date'] ?? now(),
            'source' => Payment::SOURCE_MANUAL,
            'reference_number' => $data['reference_number'] ?? null,
            'branch_id' => $sale->branch_id,
            'notes' => $data['notes'] ?? null,
            'received_by' => $actor ? $actor->id : null,
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => $data['amount'],
            ]],
        ], $actor);
    }
}
