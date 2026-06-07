<?php

namespace App\Contracts;

interface BiteshipShippingGateway
{
    public function isConfigured(): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function quoteRates(array $payload): array;
}
