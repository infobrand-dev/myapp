<?php

namespace App\Modules\SocialMedia\Services;

use App\Modules\SocialMedia\Models\SocialAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class XTokenManager
{
    public function canRefresh(SocialAccount $account): bool
    {
        return filled($this->refreshToken($account))
            && filled(config('services.x_api.client_id'))
            && filled(config('services.x_api.client_secret'));
    }

    public function refreshAccessToken(SocialAccount $account): ?string
    {
        $refreshToken = $this->refreshToken($account);
        if ($refreshToken === '') {
            $account->updateOperationalMetadata([
                'last_token_refresh_attempt_at' => now()->toDateTimeString(),
                'last_token_refresh_status' => 'error',
                'last_token_refresh_message' => 'Refresh token X tidak tersedia.',
                'x_connector_status' => 'error',
            ]);
            return null;
        }

        $response = Http::asForm()
            ->timeout(20)
            ->withBasicAuth((string) config('services.x_api.client_id'), (string) config('services.x_api.client_secret'))
            ->post((string) config('services.x_api.token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if (!$response->successful()) {
            $account->updateOperationalMetadata([
                'last_token_refresh_attempt_at' => now()->toDateTimeString(),
                'last_token_refresh_status' => 'error',
                'last_token_refresh_message' => mb_substr((string) $response->body(), 0, 500),
                'x_connector_status' => 'error',
            ]);
            return null;
        }

        $newAccessToken = trim((string) $response->json('access_token', ''));
        if ($newAccessToken === '') {
            $account->updateOperationalMetadata([
                'last_token_refresh_attempt_at' => now()->toDateTimeString(),
                'last_token_refresh_status' => 'error',
                'last_token_refresh_message' => 'X tidak mengembalikan access token baru.',
                'x_connector_status' => 'error',
            ]);
            return null;
        }

        $metadata = is_array($account->metadata) ? $account->metadata : [];
        $newRefreshToken = trim((string) $response->json('refresh_token', ''));
        if ($newRefreshToken !== '') {
            $metadata['x_refresh_token_enc'] = Crypt::encryptString($newRefreshToken);
        }
        $metadata['oauth_refreshed_at'] = now()->toDateTimeString();
        $metadata['last_token_refresh_attempt_at'] = now()->toDateTimeString();
        $metadata['last_token_refresh_status'] = 'ok';
        $metadata['last_token_refresh_message'] = 'Refresh token X berhasil.';
        $metadata['x_connector_status'] = 'active';

        $account->fill([
            'access_token' => $newAccessToken,
            'metadata' => $metadata,
        ])->save();

        return $newAccessToken;
    }

    private function refreshToken(SocialAccount $account): string
    {
        $encrypted = trim((string) data_get($account->metadata, 'x_refresh_token_enc', ''));
        if ($encrypted === '') {
            return '';
        }

        try {
            return trim(Crypt::decryptString($encrypted));
        } catch (\Throwable) {
            return '';
        }
    }
}
