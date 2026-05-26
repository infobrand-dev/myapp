<?php

namespace App\Services;

use App\Models\TenantTransactionalMailSetting;

class TenantTransactionalMailerFactory
{
    public function configure(string $mailer, TenantTransactionalMailSetting $setting): string
    {
        if ($setting->deliveryMode() === TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED) {
            return (string) config('mail.default', 'smtp');
        }

        config([
            'mail.mailers.' . $mailer => [
                'transport' => 'smtp',
                'host' => $setting->smtp_host,
                'port' => $setting->smtp_port ?: 587,
                'encryption' => $setting->smtp_encryption ?: 'tls',
                'username' => $setting->smtp_username,
                'password' => $setting->smtp_password,
                'timeout' => 30,
            ],
        ]);

        return $mailer;
    }
}
