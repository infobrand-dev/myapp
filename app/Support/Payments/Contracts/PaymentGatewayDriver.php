<?php

namespace App\Support\Payments\Contracts;

use App\Modules\Sales\Models\Sale;

interface PaymentGatewayDriver
{
    public function provider(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /**
     * @return array<int, string>
     */
    public function requiredConfigFields(): array;

    /**
     * @return array<string, bool|int|string>
     */
    public function capabilities(): array;

    public function settingsRoute(): ?string;

    public function transactionsRoute(): ?string;

    /**
     * @return array{provider:string,redirect_url:string,reference:string,token:?string}
     */
    public function createCheckoutForSale(Sale $sale): array;
}
