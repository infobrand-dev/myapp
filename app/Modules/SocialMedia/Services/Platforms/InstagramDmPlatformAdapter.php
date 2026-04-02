<?php

namespace App\Modules\SocialMedia\Services\Platforms;

class InstagramDmPlatformAdapter extends AbstractSocialPlatformAdapter
{
    public function key(): string
    {
        return 'instagram';
    }

    public function label(): string
    {
        return 'Instagram Business DM';
    }

    public function connectionMode(): string
    {
        return 'meta_oauth';
    }

    public function status(): string
    {
        return 'live';
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
        return ['connect', 'inbound', 'outbound_text', 'outbound_media', 'chatbot'];
    }

    public function note(): ?string
    {
        return null;
    }
}
