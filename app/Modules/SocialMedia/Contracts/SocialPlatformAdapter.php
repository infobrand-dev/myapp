<?php

namespace App\Modules\SocialMedia\Contracts;

interface SocialPlatformAdapter
{
    public function key(): string;

    public function label(): string;

    public function connectionMode(): string;

    public function status(): string;

    public function supportsOAuthConnect(): bool;

    public function supportsInboundWebhook(): bool;

    public function supportsOutboundMessages(): bool;

    public function publicEnabled(): bool;

    /**
     * @return array<int, string>
     */
    public function capabilities(): array;

    public function note(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
