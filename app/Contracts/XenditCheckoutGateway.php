<?php

namespace App\Contracts;

interface XenditCheckoutGateway
{
    public function isConfigured(): bool;

    /**
     * @return array{reference:string,redirect_url:string}
     */
    public function createCheckoutForTarget(object $checkoutTarget): array;
}
