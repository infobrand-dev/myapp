<?php

namespace App\Modules\SocialMedia\Services\Platforms;

class XPlatformAdapter extends AbstractSocialPlatformAdapter
{
    public function key(): string
    {
        return 'x';
    }

    public function label(): string
    {
        return 'X Direct Messages';
    }

    public function connectionMode(): string
    {
        return 'x_api';
    }

    public function status(): string
    {
        return 'scaffolded';
    }

    public function supportsOAuthConnect(): bool
    {
        return false;
    }

    public function supportsInboundWebhook(): bool
    {
        return false;
    }

    public function supportsOutboundMessages(): bool
    {
        return false;
    }

    public function publicEnabled(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return ['planned_connect', 'planned_inbound', 'planned_outbound'];
    }

    public function note(): ?string
    {
        return 'Scaffold internal. Jalur API ada, tapi connector tenant belum dibuka.';
    }
}
