<?php

namespace App\Modules\SocialMedia\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TikTokDisplayApiClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.tiktok_api.client_key'))
            && filled(config('services.tiktok_api.client_secret'));
    }

    public function fetchUserProfile(string $accessToken): Response
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->get('/v2/user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name,username,profile_deep_link,bio_description,is_verified,follower_count,following_count,likes_count,video_count',
            ]);
    }

    public function fetchVideoList(string $accessToken, int $maxCount = 10): Response
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/v2/video/list/', [
                'max_count' => max(1, min($maxCount, 20)),
                'fields' => 'id,title,video_description,duration,cover_image_url,share_url,view_count,like_count,comment_count,create_time',
            ]);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.tiktok_api.base_url', 'https://open.tiktokapis.com'), '/');
    }
}
