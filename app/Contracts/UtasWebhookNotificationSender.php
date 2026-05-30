<?php

namespace App\Contracts;

interface UtasWebhookNotificationSender
{
    public function sendPaidNotification(string $to, array $payload): void;
}
