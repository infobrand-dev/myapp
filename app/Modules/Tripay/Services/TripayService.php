<?php

namespace App\Modules\Tripay\Services;

use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Modules\Tripay\Models\TripaySetting;
use App\Modules\Tripay\Models\TripayTransaction;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripayService
{
    public function __construct(
        private readonly CreatePaymentAction $createPaymentAction,
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
    }

    public function getSettings(): ?TripaySetting
    {
        return TripaySetting::forCurrentTenant();
    }

    public function isConfigured(): bool
    {
        $setting = $this->getSettings();

        return $setting
            && $setting->is_active
            && $setting->api_key
            && $setting->private_key
            && $setting->merchant_code;
    }

    /**
     * @return array{reference:string,redirect_url:string}
     */
    public function createOrReuseCheckoutForSale(Sale $sale): array
    {
        if (!$sale->isFinalized() && !$this->commerceOrders->isPayable($sale)) {
            throw new \RuntimeException('Checkout Tripay hanya bisa dibuat untuk sale yang sudah finalized.');
        }

        if ((float) $sale->balance_due <= 0) {
            throw new \RuntimeException('Sale ini sudah tidak memiliki tagihan.');
        }

        $setting = $this->getSettings();

        if (!$setting || !$setting->is_active || !$setting->api_key || !$setting->private_key || !$setting->merchant_code) {
            throw new \RuntimeException('Tripay belum dikonfigurasi atau tidak aktif.');
        }

        $existing = TripayTransaction::query()
            ->where('tenant_id', (int) $sale->tenant_id)
            ->where('company_id', (int) $sale->company_id)
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', (int) $sale->id)
            ->where('status', TripayTransaction::STATUS_UNPAID)
            ->whereNotNull('checkout_url')
            ->latest('id')
            ->first();

        if ($existing) {
            return [
                'reference' => (string) $existing->merchant_reference,
                'redirect_url' => (string) $existing->checkout_url,
            ];
        }

        $merchantReference = TripayTransaction::generateReference((int) $sale->tenant_id);
        $result = $this->createTransaction([
            'merchant_ref' => $merchantReference,
            'amount' => (float) $sale->balance_due,
            'customer_name' => $sale->customer_name_snapshot ?: 'Customer',
            'customer_email' => $sale->customer_email_snapshot,
            'customer_phone' => $sale->customer_phone_snapshot,
            'order_items' => [[
                'sku' => 'sale-' . $sale->id,
                'name' => 'Order ' . $sale->sale_number,
                'price' => (int) round((float) $sale->balance_due),
                'quantity' => 1,
            ]],
        ]);

        TripayTransaction::query()->create([
            'tenant_id' => (int) $sale->tenant_id,
            'company_id' => (int) $sale->company_id,
            'merchant_reference' => $merchantReference,
            'tripay_reference' => (string) data_get($result, 'data.reference', ''),
            'checkout_url' => (string) data_get($result, 'data.checkout_url', ''),
            'status' => (string) data_get($result, 'data.status', TripayTransaction::STATUS_UNPAID),
            'payment_method' => (string) data_get($result, 'data.payment_name', 'Tripay'),
            'gross_amount' => (float) $sale->balance_due,
            'currency_code' => (string) ($sale->currency_code ?: 'IDR'),
            'payable_type' => $sale->getMorphClass(),
            'payable_id' => (int) $sale->id,
            'customer_name' => $sale->customer_name_snapshot,
            'customer_email' => $sale->customer_email_snapshot,
            'customer_phone' => $sale->customer_phone_snapshot,
            'description' => 'Order ' . $sale->sale_number,
            'created_by' => $sale->created_by,
        ]);

        return [
            'reference' => $merchantReference,
            'redirect_url' => (string) data_get($result, 'data.checkout_url', ''),
        ];
    }

    public function createTransaction(array $payload): array
    {
        $setting = $this->getSettings();

        if (!$setting || !$setting->is_active || !$setting->api_key || !$setting->private_key || !$setting->merchant_code) {
            throw new \RuntimeException('Tripay belum dikonfigurasi atau tidak aktif.');
        }

        $merchantRef = (string) $payload['merchant_ref'];
        $amount = (int) round((float) $payload['amount']);
        $signature = hash_hmac('sha256', $setting->merchant_code . $merchantRef . $amount, $setting->private_key);

        $requestPayload = array_filter([
            'method' => 'QRIS',
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $payload['customer_name'] ?? 'Customer',
            'customer_email' => $payload['customer_email'] ?? null,
            'customer_phone' => $payload['customer_phone'] ?? null,
            'order_items' => $payload['order_items'] ?? [],
            'return_url' => route('storefront.public.index'),
            'expired_time' => now()->addDay()->timestamp,
            'signature' => $signature,
        ], fn ($value) => $value !== null);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $setting->api_key,
        ])->timeout(30)->post($setting->getApiBaseUrl() . '/transaction/create', $requestPayload);

        if (!$response->successful() || !data_get($response->json(), 'success', false)) {
            $message = data_get($response->json(), 'message') ?? $response->body();

            Log::error('Tripay transaction creation failed', [
                'status' => $response->status(),
                'error' => $message,
                'payload' => $requestPayload,
            ]);

            throw new \RuntimeException('Gagal membuat transaksi Tripay: ' . $message);
        }

        return (array) $response->json();
    }

    public function handleNotification(array $payload, string $signatureHeader): ?TripayTransaction
    {
        $reference = (string) ($payload['merchant_ref'] ?? '');
        $transaction = TripayTransaction::query()
            ->where('merchant_reference', $reference)
            ->first();

        if (!$transaction) {
            return null;
        }

        $setting = TripaySetting::query()
            ->where('tenant_id', (int) $transaction->tenant_id)
            ->first();

        if (!$setting || !$setting->callback_signature_key) {
            throw new \RuntimeException('Tripay settings not found for tenant.');
        }

        $rawBody = request()->getContent();
        $expected = hash_hmac('sha256', $rawBody, $setting->callback_signature_key);

        if ($signatureHeader === '' || !hash_equals($expected, $signatureHeader)) {
            throw new \RuntimeException('Signature verification failed.');
        }

        $transaction->update([
            'tripay_reference' => (string) ($payload['reference'] ?? $transaction->tripay_reference),
            'status' => (string) ($payload['status'] ?? $transaction->status),
            'payment_method' => (string) ($payload['payment_method'] ?? $transaction->payment_method),
            'raw_notification' => $this->sanitizePayload($payload),
        ]);

        $transaction->refresh();

        if ($transaction->isSettled() && !$transaction->payment_id) {
            $this->createInternalPayment($transaction);
        }

        if ($transaction->isSettled() && !$transaction->settled_at) {
            $transaction->update(['settled_at' => now()]);
        }

        if ($transaction->isFailed()) {
            if (!$transaction->expired_at) {
                $transaction->update(['expired_at' => now()]);
            }

            if ($transaction->payable_type === 'sale' && $transaction->payable_id) {
                $sale = Sale::query()->find($transaction->payable_id);
                if ($sale) {
                    match ($transaction->status) {
                        TripayTransaction::STATUS_EXPIRED => $this->commerceOrders->markExpired($sale),
                        default => $this->commerceOrders->markPaymentFailed($sale, 'Pembayaran Tripay tidak berhasil.'),
                    };
                }
            }
        }

        return $transaction;
    }

    private function createInternalPayment(TripayTransaction $transaction): void
    {
        if (!$transaction->payable_type || !$transaction->payable_id) {
            Log::warning('Tripay settled but no payable linked', ['reference' => $transaction->merchant_reference]);
            return;
        }

        try {
            DB::transaction(function () use ($transaction): void {
                $locked = TripayTransaction::query()
                    ->where('id', $transaction->id)
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->payment_id) {
                    return;
                }

                TenantContext::set($locked->tenant_id);

                if ($locked->company_id) {
                    CompanyContext::setCurrentId($locked->company_id);
                }

                $sale = Sale::query()->find($locked->payable_id);
                if ($sale) {
                    $this->commerceOrders->markPaid($sale);
                }

                $method = $this->ensurePaymentMethod($locked->tenant_id);

                $payment = $this->createPaymentAction->execute([
                    'payment_method_id' => $method->id,
                    'amount' => $locked->gross_amount,
                    'paid_at' => $locked->settled_at ?? now(),
                    'source' => Payment::SOURCE_ONLINE,
                    'channel' => 'tripay',
                    'external_reference' => $locked->tripay_reference,
                    'reference_number' => $locked->merchant_reference,
                    'notes' => 'Tripay Checkout',
                    'meta' => [
                        'tripay_reference' => $locked->tripay_reference,
                        'tripay_merchant_reference' => $locked->merchant_reference,
                    ],
                    'allocations' => [[
                        'payable_type' => $locked->payable_type,
                        'payable_id' => $locked->payable_id,
                        'amount' => $locked->gross_amount,
                    ]],
                ]);

                $locked->update(['payment_id' => $payment->id]);

                if ($sale) {
                    $this->commerceOrders->markReadyForFulfillment($sale);
                }
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to create internal payment from Tripay settlement', [
                'reference' => $transaction->merchant_reference,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensurePaymentMethod(int $tenantId): PaymentMethod
    {
        return PaymentMethod::query()
            ->where('tenant_id', $tenantId)
            ->where('code', 'tripay')
            ->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => 'tripay',
                ],
                [
                    'company_id' => CompanyContext::currentId(),
                    'name' => 'Tripay (Online)',
                    'type' => 'manual',
                    'requires_reference' => false,
                    'is_active' => true,
                    'is_system' => false,
                    'sort_order' => 99,
                ]
            );
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['customer_email', 'customer_phone', 'signature', 'x-callback-signature'];
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            $sanitized[$key] = in_array(mb_strtolower((string) $key), $sensitiveKeys, true)
                ? '[redacted]'
                : $value;
        }

        return $sanitized;
    }
}
