<?php

namespace App\Modules\SocialMedia\Services\Platforms;

class FacebookDmPlatformAdapter extends AbstractSocialPlatformAdapter
{
    public function key(): string
    {
        return 'facebook';
    }

    public function label(): string
    {
        return 'Facebook Messenger';
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
