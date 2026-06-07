<?php

namespace App\Services;

use App\Contracts\BiteshipShippingGateway;

class NullBiteshipShippingGateway implements BiteshipShippingGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function quoteRates(array $payload): array
    {
        throw new \RuntimeException('Biteship belum tersedia.');
    }
}
