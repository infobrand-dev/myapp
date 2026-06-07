<?php

namespace App\Services;

use App\Contracts\TripayCheckoutGateway;

class NullTripayCheckoutGateway implements TripayCheckoutGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function createCheckoutForTarget(object $checkoutTarget): array
    {
        throw new \RuntimeException('Tripay belum tersedia.');
    }
}
