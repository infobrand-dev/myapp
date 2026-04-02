<?php

namespace App\Services;

use App\Models\PlatformInvoice;

class PlatformManualPaymentService
{
    public function isConfigured(): bool
    {
        $config = $this->config();

        return (bool) ($config['enabled'] ?? false)
            && $this->filled($config['bank_name'] ?? null)
            && $this->filled($config['account_name'] ?? null)
            && $this->filled($config['account_number'] ?? null);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     bank_name: string,
     *     account_name: string,
     *     account_number: string,
     *     review_sla_hours: int
     * }
     */
    public function config(): array
    {
        return [
            'enabled' => (bool) config('services.platform_manual_payment.enabled', false),
            'bank_name' => trim((string) config('services.platform_manual_payment.bank_name', '')),
            'account_name' => trim((string) config('services.platform_manual_payment.account_name', '')),
            'account_number' => trim((string) config('services.platform_manual_payment.account_number', '')),
            'review_sla_hours' => max(1, (int) config('services.platform_manual_payment.review_sla_hours', 24)),
        ];
    }

    /**
     * @return array{
     *     payment_method: string,
     *     unique_code: int,
     *     transfer_amount: int,
     *     bank_name: string,
     *     account_name: string,
     *     account_number: string,
     *     review_sla_hours: int
     * }
     */
    public function quoteForInvoice(PlatformInvoice $invoice): array
    {
        $stored = (array) data_get($invoice->meta ?? [], 'manual_transfer', []);
        $config = $this->config();

        return [
            'payment_method' => 'bank_transfer',
            'unique_code' => (int) ($stored['unique_code'] ?? $this->uniqueCodeForInvoice($invoice)),
            'transfer_amount' => (int) ($stored['transfer_amount'] ?? ($this->baseAmount($invoice) + $this->uniqueCodeForInvoice($invoice))),
            'bank_name' => (string) ($stored['bank_name'] ?? $config['bank_name']),
            'account_name' => (string) ($stored['account_name'] ?? $config['account_name']),
            'account_number' => (string) ($stored['account_number'] ?? $config['account_number']),
            'review_sla_hours' => (int) ($stored['review_sla_hours'] ?? $config['review_sla_hours']),
        ];
    }

    public function attachQuote(PlatformInvoice $invoice): PlatformInvoice
    {
        $quote = $this->quoteForInvoice($invoice);
        $meta = is_array($invoice->meta) ? $invoice->meta : [];
        $meta['manual_transfer'] = $quote;
        $meta['selected_payment_method'] = 'bank_transfer';

        $invoice->forceFill([
            'meta' => $meta,
        ])->save();

        if ($invoice->relationLoaded('order') && $invoice->order) {
            $orderMeta = is_array($invoice->order->meta) ? $invoice->order->meta : [];
            $orderMeta['manual_transfer'] = $quote;
            $orderMeta['selected_payment_method'] = 'bank_transfer';

            $invoice->order->forceFill([
                'payment_channel' => 'bank_transfer',
                'meta' => $orderMeta,
            ])->save();
        }

        return $invoice->fresh(['order']);
    }

    private function uniqueCodeForInvoice(PlatformInvoice $invoice): int
    {
        return 100 + (($invoice->id * 37) % 900);
    }

    private function baseAmount(PlatformInvoice $invoice): int
    {
        return (int) round((float) $invoice->amount);
    }

    private function filled(?string $value): bool
    {
        return trim((string) $value) !== '';
    }
}
