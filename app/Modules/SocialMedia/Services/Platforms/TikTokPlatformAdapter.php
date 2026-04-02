<?php

namespace App\Modules\SocialMedia\Services\Platforms;

class TikTokPlatformAdapter extends AbstractSocialPlatformAdapter
{
    public function key(): string
    {
        return 'tiktok';
    }

    public function label(): string
    {
        return 'TikTok';
    }

    public function connectionMode(): string
    {
        return 'tiktok_developers';
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
        return 'Scaffold internal. Prioritas bisnis tinggi, tapi channel DM/business messaging belum dibuka ke tenant.';
    }
}
