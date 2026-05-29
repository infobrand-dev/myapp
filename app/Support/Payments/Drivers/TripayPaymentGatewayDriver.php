<?php

namespace App\Support\Payments\Drivers;

use App\Modules\Sales\Models\Sale;
use App\Modules\Tripay\Services\TripayService;
use App\Support\Payments\Contracts\PaymentGatewayDriver;

class TripayPaymentGatewayDriver implements PaymentGatewayDriver
{
    public function __construct(
        private readonly TripayService $service,
    ) {
    }

    public function provider(): string
    {
        return 'tripay';
    }

    public function label(): string
    {
        return 'Tripay';
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function requiredConfigFields(): array
    {
        return ['api_key', 'private_key', 'merchant_code', 'callback_signature_key'];
    }

    public function capabilities(): array
    {
        return [
            'public_checkout' => true,
            'supports_retry' => true,
            'supports_webhook' => true,
            'supports_token' => false,
        ];
    }

    public function settingsRoute(): ?string
    {
        return 'tripay.settings.edit';
    }

    public function transactionsRoute(): ?string
    {
        return 'tripay.transactions.index';
    }

    public function createCheckoutForSale(Sale $sale): array
    {
        $checkout = $this->service->createOrReuseCheckoutForSale($sale);

        return [
            'provider' => $this->provider(),
            'redirect_url' => (string) $checkout['redirect_url'],
            'reference' => (string) $checkout['reference'],
            'token' => null,
        ];
    }
}
