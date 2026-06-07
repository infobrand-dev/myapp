<?php

namespace App\Services;

use App\Contracts\RajaOngkirShippingGateway;

class NullRajaOngkirShippingGateway implements RajaOngkirShippingGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function quoteRates(array $payload): array
    {
        throw new \RuntimeException('RajaOngkir belum tersedia.');
    }
}
