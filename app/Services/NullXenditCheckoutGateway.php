<?php

namespace App\Services;

use App\Contracts\XenditCheckoutGateway;

class NullXenditCheckoutGateway implements XenditCheckoutGateway
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function createCheckoutForTarget(object $checkoutTarget): array
    {
        throw new \RuntimeException('Xendit belum tersedia.');
    }
}
