<?php

namespace App\Support\Payments\Drivers;

use App\Modules\Midtrans\Services\MidtransService;
use App\Modules\Sales\Models\Sale;
use App\Support\Payments\Contracts\PaymentGatewayDriver;

class MidtransPaymentGatewayDriver implements PaymentGatewayDriver
{
    public function __construct(
        private readonly MidtransService $service,
    ) {
    }

    public function provider(): string
    {
        return 'midtrans';
    }

    public function label(): string
    {
        return 'Midtrans';
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function requiredConfigFields(): array
    {
        return ['server_key', 'client_key'];
    }

    public function capabilities(): array
    {
        return [
            'public_checkout' => true,
            'supports_retry' => true,
            'supports_webhook' => true,
            'supports_token' => true,
        ];
    }

    public function settingsRoute(): ?string
    {
        return 'midtrans.settings.edit';
    }

    public function transactionsRoute(): ?string
    {
        return 'midtrans.transactions.index';
    }

    public function createCheckoutForSale(Sale $sale): array
    {
        $checkout = $this->service->createOrReuseCheckoutForSale($sale);

        return [
            'provider' => $this->provider(),
            'redirect_url' => (string) $checkout['redirect_url'],
            'reference' => (string) $checkout['order_id'],
            'token' => (string) ($checkout['snap_token'] ?? ''),
        ];
    }
}
