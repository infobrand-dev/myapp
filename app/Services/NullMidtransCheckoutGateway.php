<?php

namespace App\Services;

use App\Contracts\MidtransCheckoutGateway;

class NullMidtransCheckoutGateway implements MidtransCheckoutGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function createCheckoutForTarget(object $checkoutTarget): array
    {
        throw new \RuntimeException('Midtrans belum tersedia.');
    }
}
