<?php

namespace App\Modules\PointOfSale\Services;

use App\Models\User;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\Sales\Actions\CreateDraftSaleAction;
use App\Modules\Sales\Actions\FinalizeSaleAction;
use App\Modules\Sales\Actions\UpdateDraftSaleAction;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCheckoutOrchestrator
{
    private $cartService;
    private $createDraftSale;
    private $updateDraftSale;
    private $finalizeSale;
    private $createPayment;

    public function __construct(
        PosCartService $cartService,
        CreateDraftSaleAction $createDraftSale,
        UpdateDraftSaleAction $updateDraftSale,
        FinalizeSaleAction $finalizeSale,
        CreatePaymentAction $createPayment
    ) {
        $this->cartService = $cartService;
        $this->createDraftSale = $createDraftSale;
        $this->updateDraftSale = $updateDraftSale;
        $this->finalizeSale = $finalizeSale;
        $this->createPayment = $createPayment;
    }

    public function execute(User $user, array $payload): array
    {
        $cart = DB::transaction(function () use ($user) {
            $cart = $this->cartService->activeCartFor($user);

            return PosCart::query()->with(['items', 'contact'])->lockForUpdate()->findOrFail($cart->id);
        });

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Cart kosong tidak bisa di-checkout.',
            ]);
        }

        if ($cart->status !== PosCart::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'cart' => 'Hanya cart aktif yang bisa di-checkout.',
            ]);
        }

        $paymentTotal = round(collect($payload['payments'])->sum(function ($payment) {
            return (float) ($payment['amount'] ?? 0);
        }), 2);

        if ($paymentTotal !== round((float) $cart->grand_total, 2)) {
            throw ValidationException::withMessages([
                'payments' => 'Total payment harus sama dengan grand total cart.',
            ]);
        }

        $salePayload = $this->buildSalePayload($cart, $payload);
        $sale = $this->createOrUpdateDraftSale($salePayload, $user);

        if ($sale->isDraft()) {
            $sale = $this->finalizeSale->execute($sale, [
                'payment_status' => Sale::PAYMENT_UNPAID,
                'reason' => 'Finalized from POS checkout',
            ], $user);
        }

        $payments = $this->createPayments($sale, $payload, $user);
        $sale = $sale->fresh(['items', 'paymentAllocations.payment.method']);

        $cashReceived = (float) ($payload['cash_received_amount'] ?? 0);
        $cashPaymentTotal = $this->cashPaymentTotal($payload['payments']);
        $changeAmount = $cashReceived > 0 && $cashPaymentTotal > 0
            ? round(max(0, $cashReceived - $cashPaymentTotal), 2)
            : 0.0;

        $cart->update([
            'status' => PosCart::STATUS_COMPLETED,
            'notes' => $payload['notes'] ?? $cart->notes,
            'completed_at' => now(),
            'completed_sale_id' => $sale->id,
            'meta' => array_merge($cart->meta ?? [], [
                'checkout_completed_at' => now()->toDateTimeString(),
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'change_amount' => $changeAmount,
            ]),
        ]);

        return [
            'cart' => $cart->fresh(['items', 'contact']),
            'sale' => $sale,
            'payments' => $payments,
            'change_amount' => $changeAmount,
        ];
    }

    private function createOrUpdateDraftSale(array $payload, User $user): Sale
    {
        $existing = Sale::query()
            ->where('source', Sale::SOURCE_POS)
            ->where('external_reference', $payload['external_reference'])
            ->first();

        if (!$existing) {
            return $this->createDraftSale->execute($payload, $user);
        }

        if ($existing->isFinalized()) {
            return $existing->load('items');
        }

        return $this->updateDraftSale->execute($existing, $payload, $user);
    }

    private function createPayments(Sale $sale, array $payload, User $user): array
    {
        $payments = [];

        foreach (array_values($payload['payments']) as $index => $paymentRow) {
            $method = $this->resolveMethod((string) $paymentRow['payment_method']);
            $amount = round((float) $paymentRow['amount'], 2);
            $externalReference = $sale->sale_number . '-POS-' . ($index + 1);

            $existing = Payment::query()
                ->with('method')
                ->where('source', Payment::SOURCE_POS)
                ->where('external_reference', $externalReference)
                ->first();

            if ($existing) {
                $payments[] = $existing;
                continue;
            }

            $payments[] = $this->createPayment->execute([
                'payment_method_id' => $method->id,
                'amount' => $amount,
                'currency_code' => $paymentRow['currency_code'] ?? $sale->currency_code,
                'paid_at' => $paymentRow['payment_date'] ?? now(),
                'source' => Payment::SOURCE_POS,
                'channel' => 'pos',
                'reference_number' => $paymentRow['reference_number'] ?? null,
                'external_reference' => $externalReference,
                'notes' => $paymentRow['notes'] ?? null,
                'received_by' => $user->id,
                'meta' => [
                    'sale_id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'source_module' => 'point-of-sale',
                ],
                'allocations' => [[
                    'payable_type' => 'sale',
                    'payable_id' => $sale->id,
                    'amount' => $amount,
                ]],
            ], $user);
        }

        return $payments;
    }

    private function buildSalePayload(PosCart $cart, array $payload): array
    {
        return [
            'external_reference' => $cart->uuid,
            'contact_id' => $cart->contact_id,
            'payment_status' => Sale::PAYMENT_UNPAID,
            'source' => Sale::SOURCE_POS,
            'transaction_date' => now(),
            'currency_code' => $cart->currency_code ?: 'IDR',
            'notes' => $payload['notes'] ?? $cart->notes,
            'source_context' => [
                'module' => 'point-of-sale',
                'pos_cart_id' => $cart->id,
                'pos_cart_uuid' => $cart->uuid,
            ],
            'items' => $cart->items->map(function ($item) {
                return [
                    'sellable_key' => $item->product_variant_id
                        ? 'variant:' . $item->product_variant_id
                        : 'product:' . $item->product_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'discount_total' => (float) $item->discount_total,
                    'tax_total' => (float) $item->tax_total,
                    'notes' => $item->notes,
                ];
            })->values()->all(),
        ];
    }

    private function resolveMethod(string $salesInput): PaymentMethod
    {
        $normalized = trim($salesInput);
        $directCodes = [
            PaymentMethod::CODE_CASH,
            PaymentMethod::CODE_BANK_TRANSFER,
            PaymentMethod::CODE_DEBIT_CARD,
            PaymentMethod::CODE_CREDIT_CARD,
            PaymentMethod::CODE_EWALLET,
            PaymentMethod::CODE_QRIS,
            PaymentMethod::CODE_MANUAL,
        ];
        $code = in_array($normalized, $directCodes, true)
            ? $normalized
            : PaymentMethod::fromSalesInput($normalized);

        $method = PaymentMethod::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$method) {
            throw ValidationException::withMessages([
                'payments' => 'Payment method POS tidak tersedia atau tidak aktif.',
            ]);
        }

        return $method;
    }

    private function containsCashPayment(array $payments): bool
    {
        foreach ($payments as $payment) {
            if (($payment['payment_method'] ?? null) === PaymentMethod::CODE_CASH) {
                return true;
            }
        }

        return false;
    }

    private function cashPaymentTotal(array $payments): float
    {
        return round((float) collect($payments)->sum(function ($payment) {
            return ($payment['payment_method'] ?? null) === PaymentMethod::CODE_CASH
                ? (float) ($payment['amount'] ?? 0)
                : 0;
        }), 2);
    }
}
