<?php

namespace App\Modules\SocialMedia\Services\Platforms;

use App\Modules\SocialMedia\Contracts\SocialPlatformAdapter;

abstract class AbstractSocialPlatformAdapter implements SocialPlatformAdapter
{
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'connection_mode' => $this->connectionMode(),
            'status' => $this->status(),
            'supports_oauth_connect' => $this->supportsOAuthConnect(),
            'supports_inbound_webhook' => $this->supportsInboundWebhook(),
            'supports_outbound_messages' => $this->supportsOutboundMessages(),
            'public_enabled' => $this->publicEnabled(),
            'capabilities' => $this->capabilities(),
            'note' => $this->note(),
        ];
    }
}
