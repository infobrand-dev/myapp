<?php

namespace App\Services;

use App\Models\TenantTransactionalMailSetting;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use RuntimeException;

class TenantTransactionalMailConfigResolver
{
    public function __construct(
        private readonly \App\Support\TenantPlanManager $planManager,
    ) {
    }

    public function current(?int $tenantId = null): ?TenantTransactionalMailSetting
    {
        $query = TenantTransactionalMailSetting::query();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    public function requireEnabled(?int $tenantId = null): TenantTransactionalMailSetting
    {
        $setting = $this->current($tenantId);

        if (!$setting || !$setting->is_enabled) {
            throw new RuntimeException('Transactional email tenant belum aktif.');
        }

        if ($this->usesManagedMail($setting)) {
            if (!$this->planManager->hasFeature(PlanFeature::TRANSACTIONAL_EMAIL_MANAGED, $tenantId)) {
                throw new RuntimeException('Plan tenant saat ini belum mendukung Email Terkelola.');
            }

            return $setting;
        }

        if (!$this->planManager->hasFeature(PlanFeature::TRANSACTIONAL_EMAIL_CUSTOM_SMTP, $tenantId)) {
            throw new RuntimeException('Plan tenant saat ini belum mendukung SMTP Sendiri.');
        }

        foreach (['smtp_host', 'smtp_username', 'smtp_password', 'from_email'] as $field) {
            if (trim((string) $setting->{$field}) === '') {
                throw new RuntimeException('Konfigurasi SMTP Sendiri belum lengkap.');
            }
        }

        return $setting;
    }

    public function assertCanDispatch(?int $tenantId = null): TenantTransactionalMailSetting
    {
        $setting = $this->requireEnabled($tenantId);

        if ($this->usesManagedMail($setting)) {
            $this->planManager->ensureWithinLimit(
                PlanLimit::TRANSACTIONAL_EMAILS_MONTHLY,
                1,
                'Kuota Email Terkelola bulanan plan Anda sudah habis.',
                $tenantId
            );
        }

        return $setting;
    }

    public function senderIdentity(TenantTransactionalMailSetting $setting): array
    {
        if ($this->usesManagedMail($setting)) {
            return [
                'from_email' => (string) config('mail.from.address'),
                'from_name' => (string) ($setting->from_name ?: config('mail.from.name')),
                'reply_to_email' => $setting->reply_to ?: null,
            ];
        }

        return [
            'from_email' => (string) $setting->from_email,
            'from_name' => (string) ($setting->from_name ?: $setting->from_email),
            'reply_to_email' => $setting->reply_to ?: null,
        ];
    }

    public function usesManagedMail(TenantTransactionalMailSetting $setting): bool
    {
        return $setting->deliveryMode() === TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED;
    }
}
