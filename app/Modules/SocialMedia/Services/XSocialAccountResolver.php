<?php

namespace App\Modules\SocialMedia\Services;

use App\Modules\SocialMedia\Models\SocialAccount;

class XSocialAccountResolver
{
    public function resolveByForUserId(string $forUserId): ?SocialAccount
    {
        $forUserId = trim($forUserId);
        if ($forUserId === '') {
            return null;
        }

        return SocialAccount::query()
            ->where('status', 'active')
            ->where('platform', 'x')
            ->get()
            ->first(function (SocialAccount $account) use ($forUserId): bool {
                $metadata = is_array($account->metadata) ? $account->metadata : [];

                return trim((string) ($metadata['x_user_id'] ?? '')) === $forUserId;
            });
    }
}
