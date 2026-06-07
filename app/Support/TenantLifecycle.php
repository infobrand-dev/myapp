<?php

namespace App\Support;

class TenantLifecycle
{
    /**
     * @return array<int, string>
     */
    public function states(): array
    {
        return (array) config('platform-core.tenant_lifecycle.states', []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function transitions(): array
    {
        return (array) config('platform-core.tenant_lifecycle.allowed_transitions', []);
    }

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, $this->transitions()[$from] ?? [], true);
    }

    public function slugRetentionDays(): int
    {
        return max(0, (int) config('platform-core.tenant_lifecycle.slug_retention_days', 30));
    }

    public function domainRetentionDays(): int
    {
        return max(0, (int) config('platform-core.tenant_lifecycle.domain_retention_days', 30));
    }
}
