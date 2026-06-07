<?php

namespace App\Contracts;

interface MidtransCheckoutGateway
{
    public function isConfigured(): bool;

    /**
     * @return array{order_id:string,redirect_url:string,snap_token:string}
     */
    public function createCheckoutForTarget(object $checkoutTarget): array;
}
