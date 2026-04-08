<?php

namespace App\Modules\Midtrans\Services;

use App\Modules\Midtrans\Models\MidtransSetting;
use App\Modules\Midtrans\Models\MidtransTransaction;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Support\BooleanQuery;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct(
        private readonly CreatePaymentAction $createPaymentAction,
    ) {}

    // ─── Settings ────────────────────────────────────────────────────────────

    public function getSettings(): ?MidtransSetting
    {
        return MidtransSetting::forCurrentTenant();
    }

    public function isConfigured(): bool
    {
        $s = $this->getSettings();
        return $s && $s->is_active && $s->server_key && $s->client_key;
    }

    // ─── Snap Token ──────────────────────────────────────────────────────────

    /**
     * Create a Snap payment token via Midtrans API.
     *
     * @param  array{
     *   order_id: string,
     *   gross_amount: float,
     *   customer_name?: string,
     *   customer_email?: string,
     *   customer_phone?: string,
     *   item_description?: string,
     * } $params
     * @return array{token: string, redirect_url: string}
     * @throws \RuntimeException on API failure
     */
    public function createSnapToken(array $params): array
    {
        $settings = $this->getSettings();

        if (!$settings || !$settings->is_active || !$settings->server_key) {
            throw new \RuntimeException('Midtrans belum dikonfigurasi atau tidak aktif.');
        }

        $payload = [
            'transaction_details' => [
                'order_id'     => $params['order_id'],
                'gross_amount' => (int) round($params['gross_amount']),
            ],
            'customer_details' => array_filter([
                'first_name' => $params['customer_name'] ?? null,
                'email'      => $params['customer_email'] ?? null,
                'phone'      => $params['customer_phone'] ?? null,
            ]),
            'item_details' => [
                [
                    'id'       => 'item-1',
                    'price'    => (int) round($params['gross_amount']),
                    'quantity' => 1,
                    'name'     => $params['item_description'] ?? 'Pembayaran',
                ],
            ],
        ];

        // Pass enabled payment methods to Snap (restrict to what's enabled in settings)
        $enabledPayments = $settings->enabled_payments ?? [];
        if (!empty($enabledPayments)) {
            $payload['enabled_payments'] = array_values($enabledPayments);
        }

        $url = $settings->getSnapBaseUrl() . '/transactions';

        $response = Http::withBasicAuth($settings->server_key, '')
            ->timeout(30)
            ->post($url, $payload);

        if (!$response->successful()) {
            $errorMessage = $response->json('error_messages.0') ?? $response->json('message') ?? $response->body();
            Log::error('Midtrans Snap token creation failed', [
                'status'  => $response->status(),
                'error'   => $errorMessage,
                'payload' => $this->sanitizePayload($payload),
            ]);
            throw new \RuntimeException('Gagal membuat Snap token: ' . $errorMessage);
        }

        return [
            'token'        => $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
        ];
    }

    // ─── Notification / Webhook ───────────────────────────────────────────────

    /**
     * Verify Midtrans notification signature.
     * Signature: SHA512(order_id + status_code + gross_amount + server_key)
     */
    public function verifySignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signatureFromMidtrans,
    ): bool {
        $settings = MidtransSetting::query()
            ->where('tenant_id', '>', 0); // find by order_id prefix later

        $settings = BooleanQuery::apply($settings, 'is_active')->get();

        foreach ($settings as $setting) {
            $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $setting->server_key);
            if (hash_equals($expected, $signatureFromMidtrans)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify signature for a specific setting.
     */
    public function verifySignatureForSetting(
        MidtransSetting $setting,
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signatureFromMidtrans,
    ): bool {
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $setting->server_key);
        return hash_equals($expected, $signatureFromMidtrans);
    }

    /**
     * Handle incoming Midtrans notification payload.
     * Returns the updated MidtransTransaction or null if not found.
     */
    public function handleNotification(array $payload): ?MidtransTransaction
    {
        $orderId           = (string) ($payload['order_id'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus       = (string) ($payload['fraud_status'] ?? '');
        $paymentType       = (string) ($payload['payment_type'] ?? '');
        $grossAmount       = (string) ($payload['gross_amount'] ?? '0');
        $statusCode        = (string) ($payload['status_code'] ?? '200');
        $transactionId     = (string) ($payload['transaction_id'] ?? '');
        $signatureKey      = (string) ($payload['signature_key'] ?? '');

        $transaction = MidtransTransaction::query()
            ->where('order_id', $orderId)
            ->first();

        if (!$transaction) {
            Log::warning('Midtrans notification for unknown order_id', ['order_id' => $orderId]);
            return null;
        }

        // Verify signature against this tenant's settings
        $settings = MidtransSetting::query()
            ->where('tenant_id', $transaction->tenant_id)
            ->first();

        if (!$settings) {
            Log::warning('Midtrans notification: no settings found for tenant', [
                'order_id'  => $orderId,
                'tenant_id' => $transaction->tenant_id,
            ]);
            throw new \RuntimeException('Midtrans settings not found for tenant.');
        }

        if ($signatureKey && !$this->verifySignatureForSetting(
            $settings,
            $orderId,
            $statusCode,
            $grossAmount,
            $signatureKey,
        )) {
            Log::warning('Midtrans notification signature mismatch — rejected', ['order_id' => $orderId]);
            throw new \RuntimeException('Signature verification failed.');
        }

        $transaction->update([
            'transaction_id'     => $transactionId ?: $transaction->transaction_id,
            'payment_type'       => $paymentType ?: $transaction->payment_type,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus ?: null,
            'raw_notification'   => $this->sanitizePayload($payload),
        ]);

        $transaction->refresh();

        // Create internal Payment on settlement
        if ($transaction->isSettled() && !$transaction->payment_id) {
            $this->createInternalPayment($transaction);
        }

        // Mark settled_at / expired_at
        if ($transaction->isSettled() && !$transaction->settled_at) {
            $transaction->update(['settled_at' => now()]);
        }
        if ($transaction->isFailed() && !$transaction->expired_at) {
            $transaction->update(['expired_at' => now()]);
        }

        return $transaction;
    }

    // ─── Internal Payment Creation ────────────────────────────────────────────

    private function createInternalPayment(MidtransTransaction $transaction): void
    {
        if (!$transaction->payable_type || !$transaction->payable_id) {
            Log::warning('Midtrans settled but no payable linked', ['order_id' => $transaction->order_id]);
            return;
        }

        try {
            DB::transaction(function () use ($transaction): void {
                // Re-fetch with a lock to prevent duplicate payment creation on concurrent webhooks
                $locked = MidtransTransaction::query()
                    ->where('id', $transaction->id)
                    ->lockForUpdate()
                    ->first();

                if (!$locked || $locked->payment_id) {
                    // Already processed by a concurrent request
                    return;
                }

                // Restore tenant + company context (not available in webhook scope)
                TenantContext::set($locked->tenant_id);
                if ($locked->company_id) {
                    CompanyContext::setCurrentId($locked->company_id);
                }

                // Ensure the Midtrans PaymentMethod exists for this tenant/company
                $method = $this->ensureMidtransPaymentMethod($locked->tenant_id);

                $payment = $this->createPaymentAction->execute([
                    'payment_method_id'  => $method->id,
                    'amount'             => $locked->gross_amount,
                    'paid_at'            => $locked->settled_at ?? now(),
                    'source'             => Payment::SOURCE_ONLINE,
                    'channel'            => $locked->payment_type,
                    'external_reference' => $locked->transaction_id,
                    'reference_number'   => $locked->order_id,
                    'notes'              => 'Midtrans - ' . $locked->paymentTypeLabel(),
                    'meta'               => [
                        'midtrans_order_id'       => $locked->order_id,
                        'midtrans_transaction_id' => $locked->transaction_id,
                        'midtrans_payment_type'   => $locked->payment_type,
                    ],
                    'allocations' => [
                        [
                            'payable_type' => $locked->payable_type,
                            'payable_id'   => $locked->payable_id,
                            'amount'       => $locked->gross_amount,
                        ],
                    ],
                ]);

                $locked->update(['payment_id' => $payment->id]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create internal payment from Midtrans settlement', [
                'order_id' => $transaction->order_id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find or create a PaymentMethod with code='midtrans' for this tenant.
     */
    private function ensureMidtransPaymentMethod(int $tenantId): PaymentMethod
    {
        return PaymentMethod::query()
            ->where('tenant_id', $tenantId)
            ->where('code', 'midtrans')
            ->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code'      => 'midtrans',
                ],
                [
                    'company_id'          => CompanyContext::currentId(),
                    'name'                => 'Midtrans (Online)',
                    'type'                => 'manual',
                    'requires_reference'  => false,
                    'is_active'           => true,
                    'is_system'           => false,
                    'sort_order'          => 99,
                ]
            );
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'signature_key',
            'approval_code',
            'token_id',
            'saved_token_id',
            'customer_email',
            'customer_phone',
            'customer_name',
            'email',
            'phone',
            'name',
            'masked_card',
        ];

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
                continue;
            }

            if (in_array(mb_strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
