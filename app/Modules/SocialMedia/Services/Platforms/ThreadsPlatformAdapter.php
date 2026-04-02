<?php

namespace App\Modules\SocialMedia\Services\Platforms;

class ThreadsPlatformAdapter extends AbstractSocialPlatformAdapter
{
    public function key(): string
    {
        return 'threads';
    }

    public function label(): string
    {
        return 'Threads';
    }

    public function connectionMode(): string
    {
        return 'threads_api';
    }

    public function status(): string
    {
        return 'research';
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
        return [];
    }

    public function note(): ?string
    {
        return 'Scaffold internal. Jangan expose ke tenant sampai API auth, inbound, dan outbound benar-benar siap.';
    }
}
