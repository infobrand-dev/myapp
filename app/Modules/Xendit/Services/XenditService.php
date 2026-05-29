<?php

namespace App\Modules\Xendit\Services;

use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use App\Support\Commerce\CommerceOrderLifecycleService;
use App\Modules\Xendit\Models\XenditSetting;
use App\Modules\Xendit\Models\XenditTransaction;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    public function __construct(
        private readonly CreatePaymentAction $createPaymentAction,
        private readonly CommerceOrderLifecycleService $commerceOrders,
    ) {
    }

    public function getSettings(): ?XenditSetting
    {
        return XenditSetting::forCurrentTenant();
    }

    public function isConfigured(): bool
    {
        $setting = $this->getSettings();

        return $setting && $setting->is_active && $setting->secret_key && $setting->webhook_token;
    }

    /**
     * @return array{reference:string,redirect_url:string}
     */
    public function createOrReuseCheckoutForSale(Sale $sale): array
    {
        if (!$sale->isFinalized() && !$this->commerceOrders->isPayable($sale)) {
            throw new \RuntimeException('Checkout Xendit hanya bisa dibuat untuk sale yang sudah finalized.');
        }

        if ((float) $sale->balance_due <= 0) {
            throw new \RuntimeException('Sale ini sudah tidak memiliki tagihan.');
        }

        $setting = $this->getSettings();

        if (!$setting || !$setting->is_active || !$setting->secret_key) {
            throw new \RuntimeException('Xendit belum dikonfigurasi atau tidak aktif.');
        }

        $existing = XenditTransaction::query()
            ->where('tenant_id', (int) $sale->tenant_id)
            ->where('company_id', (int) $sale->company_id)
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', (int) $sale->id)
            ->where('status', XenditTransaction::STATUS_PENDING)
            ->whereNotNull('invoice_url')
            ->latest('id')
            ->first();

        if ($existing) {
            return [
                'reference' => (string) $existing->external_reference,
                'redirect_url' => (string) $existing->invoice_url,
            ];
        }

        $reference = XenditTransaction::generateReference((int) $sale->tenant_id);
        $result = $this->createInvoice([
            'external_id' => $reference,
            'amount' => (float) $sale->balance_due,
            'description' => 'Order ' . $sale->sale_number,
            'customer' => [
                'given_names' => $sale->customer_name_snapshot ?: 'Customer',
                'email' => $sale->customer_email_snapshot,
                'mobile_number' => $sale->customer_phone_snapshot,
            ],
        ]);

        XenditTransaction::query()->create([
            'tenant_id' => (int) $sale->tenant_id,
            'company_id' => (int) $sale->company_id,
            'external_reference' => $reference,
            'invoice_id' => (string) ($result['id'] ?? ''),
            'invoice_url' => (string) ($result['invoice_url'] ?? ''),
            'status' => (string) ($result['status'] ?? XenditTransaction::STATUS_PENDING),
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
            'reference' => $reference,
            'redirect_url' => (string) ($result['invoice_url'] ?? ''),
        ];
    }

    public function createInvoice(array $payload): array
    {
        $setting = $this->getSettings();

        if (!$setting || !$setting->is_active || !$setting->secret_key) {
            throw new \RuntimeException('Xendit belum dikonfigurasi atau tidak aktif.');
        }

        $response = Http::withBasicAuth($setting->secret_key, '')
            ->timeout(30)
            ->post($setting->getApiBaseUrl() . '/v2/invoices', array_filter([
                'external_id' => $payload['external_id'],
                'amount' => round((float) $payload['amount'], 2),
                'description' => $payload['description'] ?? 'Payment',
                'currency' => 'IDR',
                'customer' => array_filter($payload['customer'] ?? []),
                'invoice_duration' => 86400,
            ], fn ($value) => $value !== null));

        if (!$response->successful()) {
            $message = $response->json('message') ?? $response->body();

            Log::error('Xendit invoice creation failed', [
                'status' => $response->status(),
                'error' => $message,
                'payload' => $payload,
            ]);

            throw new \RuntimeException('Gagal membuat invoice Xendit: ' . $message);
        }

        return (array) $response->json();
    }

    public function handleNotification(array $payload, string $callbackToken): ?XenditTransaction
    {
        $reference = (string) ($payload['external_id'] ?? '');
        $transaction = XenditTransaction::query()
            ->where('external_reference', $reference)
            ->first();

        if (!$transaction) {
            return null;
        }

        $setting = XenditSetting::query()
            ->where('tenant_id', (int) $transaction->tenant_id)
            ->first();

        if (!$setting || !$setting->webhook_token) {
            throw new \RuntimeException('Xendit settings not found for tenant.');
        }

        if ($callbackToken === '' || !hash_equals((string) $setting->webhook_token, $callbackToken)) {
            throw new \RuntimeException('Signature verification failed.');
        }

        $transaction->update([
            'invoice_id' => (string) ($payload['id'] ?? $transaction->invoice_id),
            'invoice_url' => (string) ($payload['invoice_url'] ?? $transaction->invoice_url),
            'status' => (string) ($payload['status'] ?? $transaction->status),
            'payment_method' => (string) data_get($payload, 'payment_method', $transaction->payment_method),
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
                        XenditTransaction::STATUS_EXPIRED => $this->commerceOrders->markExpired($sale),
                        default => $this->commerceOrders->markPaymentFailed($sale, 'Pembayaran Xendit tidak berhasil.'),
                    };
                }
            }
        }

        return $transaction;
    }

    private function createInternalPayment(XenditTransaction $transaction): void
    {
        if (!$transaction->payable_type || !$transaction->payable_id) {
            Log::warning('Xendit settled but no payable linked', ['reference' => $transaction->external_reference]);
            return;
        }

        try {
            DB::transaction(function () use ($transaction): void {
                $locked = XenditTransaction::query()
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
                    'channel' => 'xendit',
                    'external_reference' => $locked->invoice_id,
                    'reference_number' => $locked->external_reference,
                    'notes' => 'Xendit Invoice',
                    'meta' => [
                        'xendit_reference' => $locked->external_reference,
                        'xendit_invoice_id' => $locked->invoice_id,
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
            Log::error('Failed to create internal payment from Xendit settlement', [
                'reference' => $transaction->external_reference,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensurePaymentMethod(int $tenantId): PaymentMethod
    {
        return PaymentMethod::query()
            ->where('tenant_id', $tenantId)
            ->where('code', 'xendit')
            ->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => 'xendit',
                ],
                [
                    'company_id' => CompanyContext::currentId(),
                    'name' => 'Xendit (Online)',
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
        $sensitiveKeys = ['payer_email', 'email', 'mobile_number', 'phone', 'secret_key', 'x-callback-token'];
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
