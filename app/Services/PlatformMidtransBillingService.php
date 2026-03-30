<?php

namespace App\Services;

use App\Mail\PlatformPaymentReceivedMail;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Modules\Midtrans\Models\MidtransSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PlatformMidtransBillingService
{
    public function __construct(
        private readonly TenantOnboardingSalesService $onboardingSales,
        private readonly PlatformAffiliateService $affiliates,
    ) {
    }

    public function isConfigured(): bool
    {
        $settings = $this->platformSettings();

        return $settings && $settings->is_active && $settings->server_key;
    }

    /**
     * @return array{order_id: string, redirect_url: string, snap_token: string}
     */
    public function createOrReuseCheckout(PlatformInvoice $invoice): array
    {
        if ($invoice->status === 'paid') {
            throw new \RuntimeException('Invoice ini sudah dibayar.');
        }

        $settings = $this->platformSettings();
        if (!$settings || !$settings->is_active || !$settings->server_key) {
            throw new \RuntimeException('Midtrans platform belum dikonfigurasi atau tidak aktif.');
        }

        $meta = (array) ($invoice->meta ?? []);
        $midtransMeta = (array) ($meta['midtrans'] ?? []);

        if (
            !empty($midtransMeta['order_id']) &&
            !empty($midtransMeta['redirect_url']) &&
            in_array((string) ($midtransMeta['transaction_status'] ?? 'pending'), ['pending', 'capture'], true)
        ) {
            return [
                'order_id' => (string) $midtransMeta['order_id'],
                'redirect_url' => (string) $midtransMeta['redirect_url'],
                'snap_token' => (string) ($midtransMeta['snap_token'] ?? ''),
            ];
        }

        $orderId = !empty($midtransMeta['order_id'])
            ? (string) $midtransMeta['order_id']
            : $this->generateOrderId($invoice);

        $recipient = $this->billingRecipient($invoice->tenant, optional($invoice->order)->buyer_email);
        $invoice->loadMissing(['tenant', 'order', 'plan', 'items']);

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) round((float) $invoice->amount),
            ],
            'customer_details' => array_filter([
                'first_name' => optional($invoice->tenant)->name ?: 'Customer',
                'email' => $recipient,
            ]),
            'item_details' => $this->midtransItemDetails($invoice),
        ];

        $enabledPayments = $settings->enabled_payments ?? [];
        if (!empty($enabledPayments)) {
            $payload['enabled_payments'] = array_values($enabledPayments);
        }

        $response = Http::withBasicAuth($settings->server_key, '')
            ->timeout(30)
            ->post($settings->getSnapBaseUrl() . '/transactions', $payload);

        if (!$response->successful()) {
            $errorMessage = $response->json('error_messages.0') ?? $response->json('message') ?? $response->body();
            Log::error('Platform Midtrans checkout creation failed', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'error' => $errorMessage,
                'payload' => $this->sanitizePayload($payload),
            ]);

            throw new \RuntimeException('Gagal membuat Midtrans checkout: ' . $errorMessage);
        }

        $meta['midtrans'] = array_merge($midtransMeta, [
            'order_id' => $orderId,
            'snap_token' => (string) $response->json('token'),
            'redirect_url' => (string) $response->json('redirect_url'),
            'transaction_status' => 'pending',
            'gateway' => 'midtrans',
            'created_at' => now()->toIso8601String(),
        ]);

        $invoice->forceFill([
            'status' => in_array($invoice->status, ['issued', 'failed', 'cancelled', 'expired'], true) ? 'pending' : $invoice->status,
            'meta' => $meta,
        ])->save();

        return [
            'order_id' => $orderId,
            'redirect_url' => (string) $response->json('redirect_url'),
            'snap_token' => (string) $response->json('token'),
        ];
    }

    public function handleNotification(array $payload): ?PlatformInvoice
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '200');
        $grossAmount = (string) ($payload['gross_amount'] ?? '0');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionId = (string) ($payload['transaction_id'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        if ($orderId === '') {
            return null;
        }

        $invoice = PlatformInvoice::query()
            ->where('meta->midtrans->order_id', $orderId)
            ->first();

        if (!$invoice) {
            Log::warning('Platform Midtrans notification for unknown order_id', ['order_id' => $orderId]);
            return null;
        }

        $settings = $this->platformSettings();
        if (!$settings || !$settings->server_key) {
            throw new \RuntimeException('Midtrans platform setting tidak ditemukan.');
        }

        if ($signatureKey) {
            $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $settings->server_key);
            if (!hash_equals($expected, $signatureKey)) {
                Log::warning('Platform Midtrans signature mismatch', ['order_id' => $orderId]);
                throw new \RuntimeException('Signature verification failed.');
            }
        }

        $freshInvoice = null;
        $createdPayment = null;
        $welcomePayload = null;

        DB::transaction(function () use ($invoice, $payload, $transactionStatus, $transactionId, $paymentType, $fraudStatus, $orderId, &$freshInvoice, &$createdPayment, &$welcomePayload): void {
            $lockedInvoice = PlatformInvoice::query()
                ->with(['tenant', 'order', 'plan'])
                ->where('id', $invoice->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedInvoice) {
                return;
            }

            $meta = (array) ($lockedInvoice->meta ?? []);
            $midtransMeta = (array) ($meta['midtrans'] ?? []);
            $midtransMeta = array_merge($midtransMeta, [
                'order_id' => $orderId,
                'transaction_id' => $transactionId ?: ($midtransMeta['transaction_id'] ?? null),
                'payment_type' => $paymentType ?: ($midtransMeta['payment_type'] ?? null),
                'fraud_status' => $fraudStatus ?: ($midtransMeta['fraud_status'] ?? null),
                'transaction_status' => $transactionStatus,
                'last_notification_at' => now()->toIso8601String(),
                'raw_notification' => $this->sanitizePayload($payload),
            ]);
            $meta['midtrans'] = $midtransMeta;

            $invoiceStatus = $this->mapInvoiceStatus($transactionStatus, $fraudStatus, $lockedInvoice->status);
            $paidAt = $this->midtransSettled($transactionStatus, $fraudStatus) && !$lockedInvoice->paid_at
                ? now()
                : $lockedInvoice->paid_at;

            $lockedInvoice->forceFill([
                'status' => $invoiceStatus,
                'paid_at' => $paidAt,
                'meta' => $meta,
            ])->save();

            if ($this->midtransSettled($transactionStatus, $fraudStatus)) {
                $existingPayment = PlatformPayment::query()
                    ->where('platform_invoice_id', $lockedInvoice->id)
                    ->where('payment_channel', 'midtrans')
                    ->where('reference', $orderId)
                    ->first();

                if (!$existingPayment) {
                    $createdPayment = PlatformPayment::create([
                        'tenant_id' => $lockedInvoice->tenant_id,
                        'platform_invoice_id' => $lockedInvoice->id,
                        'amount' => $lockedInvoice->amount,
                        'currency' => $lockedInvoice->currency,
                        'status' => 'paid',
                        'payment_channel' => 'midtrans',
                        'reference' => $orderId,
                        'paid_at' => $paidAt ?: now(),
                        'meta' => [
                            'midtrans_order_id' => $orderId,
                            'midtrans_transaction_id' => $transactionId ?: null,
                            'midtrans_payment_type' => $paymentType ?: null,
                            'recorded_from' => 'midtrans_webhook',
                        ],
                    ]);
                }

                $order = $lockedInvoice->order;
                if ($order && $order->status !== 'paid') {
                    $subscription = $this->activateSubscriptionFromBilling(
                        $lockedInvoice->tenant_id,
                        $lockedInvoice->subscription_plan_id,
                        'midtrans',
                        $orderId,
                        $order->starts_at ?: now(),
                        $order->ends_at
                    );

                    $order->forceFill([
                        'status' => 'paid',
                        'paid_at' => $paidAt ?: now(),
                        'payment_channel' => 'midtrans',
                        'tenant_subscription_id' => $subscription->id,
                    ])->save();

                    $welcomePayload = $this->onboardingSales->completePaidOnboarding(
                        $order->fresh(['tenant']),
                        $paidAt ?: now()
                    );
                }
            } else {
                $order = $lockedInvoice->order;
                if ($order && $order->status !== 'paid') {
                    $mappedOrderStatus = $this->mapOrderStatus($transactionStatus, $order->status);
                    if ($mappedOrderStatus !== $order->status) {
                        $order->forceFill([
                            'status' => $mappedOrderStatus,
                            'payment_channel' => 'midtrans',
                        ])->save();
                    }
                }
            }

            $freshInvoice = $lockedInvoice->fresh(['tenant', 'order', 'plan', 'payments']);
        });

        if ($freshInvoice && $createdPayment) {
            $this->sendPlatformPaymentReceivedMail($freshInvoice, $createdPayment);
        }

        if ($freshInvoice?->order) {
            $this->affiliates->finalizeSale($freshInvoice->order->fresh(['affiliateReferral.affiliate', 'tenant', 'plan']), $freshInvoice->paid_at);
        }

        if ($welcomePayload) {
            $this->onboardingSales->queueWelcomeMail($welcomePayload);
        }

        return $freshInvoice;
    }

    private function platformSettings(): ?MidtransSetting
    {
        $setting = null;

        if (Schema::hasTable('midtrans_settings')) {
            $setting = MidtransSetting::query()
                ->where('tenant_id', 1)
                ->first();
        }

        if ($setting) {
            return $setting;
        }

        return MidtransSetting::platformOwnerFallback();
    }

    private function itemDescription(PlatformInvoice $invoice): string
    {
        $planName = optional($invoice->plan)->name ?: 'Plan SaaS';

        return $planName . ' - ' . $invoice->invoice_number;
    }

    private function midtransItemDetails(PlatformInvoice $invoice): array
    {
        if ($invoice->items->isNotEmpty()) {
            return $invoice->items
                ->map(function ($item) {
                    return [
                        'id' => $item->item_code ?: ('platform-item-' . $item->id),
                        'price' => (int) round((float) $item->unit_price),
                        'quantity' => max(1, (int) $item->quantity),
                        'name' => mb_substr($item->name, 0, 50),
                    ];
                })
                ->values()
                ->all();
        }

        return [[
            'id' => 'platform-invoice-' . $invoice->id,
            'price' => (int) round((float) $invoice->amount),
            'quantity' => 1,
            'name' => mb_substr($this->itemDescription($invoice), 0, 50),
        ]];
    }

    private function generateOrderId(PlatformInvoice $invoice): string
    {
        return 'PLATINV-' . $invoice->id . '-' . now()->format('YmdHis');
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

    private function mapInvoiceStatus(string $transactionStatus, string $fraudStatus, string $currentStatus): string
    {
        if ($this->midtransSettled($transactionStatus, $fraudStatus)) {
            return 'paid';
        }

        switch ($transactionStatus) {
            case 'pending':
                return 'pending';
            case 'cancel':
                return 'cancelled';
            case 'expire':
                return 'expired';
            case 'deny':
                return 'failed';
            default:
                return $currentStatus;
        }
    }

    private function mapOrderStatus(string $transactionStatus, string $currentStatus): string
    {
        switch ($transactionStatus) {
            case 'pending':
                return 'pending';
            case 'cancel':
                return 'cancelled';
            case 'expire':
                return 'expired';
            case 'deny':
                return 'failed';
            default:
                return $currentStatus;
        }
    }

    private function midtransSettled(string $transactionStatus, string $fraudStatus): bool
    {
        if ($transactionStatus === 'settlement') {
            return true;
        }

        if ($transactionStatus === 'capture' && ($fraudStatus === '' || $fraudStatus === 'accept')) {
            return true;
        }

        return false;
    }

    private function billingRecipient(Tenant $tenant, ?string $preferred = null): ?string
    {
        if ($preferred) {
            return $preferred;
        }

        return optional($tenant->users()->orderBy('id')->first())->email;
    }

    private function activateSubscriptionFromBilling(int $tenantId, int $planId, string $billingProvider, string $billingReference, $startsAt, $endsAt): TenantSubscription
    {
        TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'ends_at' => now(),
                'updated_at' => now(),
            ]);

        return TenantSubscription::create([
            'tenant_id' => $tenantId,
            'subscription_plan_id' => $planId,
            'status' => 'active',
            'billing_provider' => $billingProvider,
            'billing_reference' => $billingReference,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'auto_renews' => false,
            'meta' => [
                'assigned_from' => 'platform_billing_midtrans',
            ],
        ]);
    }

    private function sendPlatformPaymentReceivedMail(PlatformInvoice $invoice, PlatformPayment $payment): void
    {
        $recipient = $this->billingRecipient($invoice->tenant, optional($invoice->order)->buyer_email);
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->queue(
                new PlatformPaymentReceivedMail(
                    $invoice,
                    $payment,
                    URL::temporarySignedRoute(
                        'platform.invoices.public',
                        now()->addDays(30),
                        ['invoice' => $invoice->id]
                    )
                )
            );
        } catch (\Throwable $e) {
            Log::error('Platform payment email failed after Midtrans webhook', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
