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
        return 'active';
    }

    public function supportsOAuthConnect(): bool
    {
        return true;
    }

    public function supportsInboundWebhook(): bool
    {
        return true;
    }

    public function supportsOutboundMessages(): bool
    {
        return true;
    }

    public function publicEnabled(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return ['oauth_connect', 'inbound', 'outbound'];
    }

    public function note(): ?string
    {
        return 'Connector X memakai OAuth tenant dan sudah siap untuk inbound/outbound dasar.';
    }
}
