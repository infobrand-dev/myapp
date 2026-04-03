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
        return 'active';
    }

    public function supportsOAuthConnect(): bool
    {
        return true;
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
        return true;
    }

    public function capabilities(): array
    {
        return ['oauth_connect', 'profile_sync', 'stats_sync', 'video_list'];
    }

    public function note(): ?string
    {
        return 'TikTok saat ini mendukung OAuth account connect, profile sync, stats, dan video list. Belum untuk inbox pesan.';
    }
}
