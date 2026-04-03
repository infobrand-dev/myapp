<?php

namespace App\Modules\SocialMedia\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Str;

class XDirectMessageClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.x_api.client_id')) && filled(config('services.x_api.client_secret'));
    }

    public function sendTextToParticipant(string $userAccessToken, string $participantId, string $text): Response
    {
        return $this->sendToParticipant($userAccessToken, $participantId, $this->buildMessageBody($text));
    }

    public function fetchAuthenticatedUser(string $userAccessToken): Response
    {
        return Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->get('/2/users/me', [
                'user.fields' => 'username,name,profile_image_url,verified',
            ]);
    }

    /**
     * @param  array<int, string>  $mediaIds
     */
    public function sendToParticipantWithMedia(string $userAccessToken, string $participantId, ?string $text, array $mediaIds): Response
    {
        return $this->sendToParticipant($userAccessToken, $participantId, $this->buildMessageBody($text, $mediaIds));
    }

    public function sendToConversation(string $userAccessToken, string $conversationId, ?string $text, array $mediaIds = []): Response
    {
        return Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/2/dm_conversations/' . trim($conversationId) . '/messages', $this->buildMessageBody($text, $mediaIds));
    }

    public function uploadMedia(string $userAccessToken, string $absolutePath, string $mimeType = 'image/jpeg'): Response
    {
        if (!is_file($absolutePath)) {
            throw new RuntimeException('X media file tidak ditemukan di storage lokal.');
        }

        if ($this->shouldUseChunkedUpload($mimeType, $absolutePath)) {
            return $this->chunkedUpload($userAccessToken, $absolutePath, $mimeType);
        }

        return Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->attach('media', fopen($absolutePath, 'r'), basename($absolutePath), ['Content-Type' => $mimeType])
            ->post('/2/media/upload', [
                'media_category' => 'dm_image',
                'media_type' => $mimeType,
                'shared' => false,
            ]);
    }

    /**
     * @param  array<int, string>  $mediaIds
     * @return array<string, mixed>
     */
    public function buildMessageBody(?string $text, array $mediaIds = []): array
    {
        $text = trim((string) $text);
        $mediaIds = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $mediaIds)));

        if ($text === '' && $mediaIds === []) {
            throw new InvalidArgumentException('X DM requires text or at least one media attachment.');
        }

        if (count($mediaIds) > 1) {
            throw new InvalidArgumentException('X DM supports only one media attachment per message.');
        }

        $payload = [];

        if ($text !== '') {
            $payload['text'] = $text;
        }

        if ($mediaIds !== []) {
            $payload['attachments'] = [
                ['media_id' => $mediaIds[0]],
            ];
        }

        return $payload;
    }

    private function sendToParticipant(string $userAccessToken, string $participantId, array $payload): Response
    {
        return Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/2/dm_conversations/with/' . trim($participantId) . '/messages', $payload);
    }

    private function chunkedUpload(string $userAccessToken, string $absolutePath, string $mimeType): Response
    {
        $fileSize = filesize($absolutePath) ?: 0;
        $category = $this->mediaCategoryForMime($mimeType);

        $init = Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->asForm()
            ->post('/2/media/upload', [
                'command' => 'INIT',
                'media_type' => $mimeType,
                'media_category' => $category,
                'total_bytes' => $fileSize,
                'shared' => 'false',
            ]);

        if (!$init->successful()) {
            return $init;
        }

        $mediaId = trim((string) (data_get($init->json(), 'data.id')
            ?? data_get($init->json(), 'media_id')
            ?? data_get($init->json(), 'data.media_id')));

        if ($mediaId === '') {
            throw new RuntimeException('X media upload INIT tidak mengembalikan media_id.');
        }

        $append = Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->attach('media', fopen($absolutePath, 'r'), basename($absolutePath), ['Content-Type' => $mimeType])
            ->post('/2/media/upload/' . $mediaId . '/append', [
                'segment_index' => 0,
            ]);

        if (!$append->successful()) {
            return $append;
        }

        $finalize = Http::withToken($userAccessToken)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/2/media/upload/' . $mediaId . '/finalize');

        if (!$finalize->successful()) {
            return $finalize;
        }

        $state = Str::lower((string) data_get($finalize->json(), 'data.processing_info.state', ''));
        if ($state === '' || $state === 'succeeded') {
            return $finalize;
        }

        if ($state === 'failed') {
            return $finalize;
        }

        $attempts = 0;
        do {
            usleep(300000);
            $status = Http::withToken($userAccessToken)
                ->acceptJson()
                ->baseUrl($this->baseUrl())
                ->get('/2/media/upload', [
                    'command' => 'STATUS',
                    'media_id' => $mediaId,
                ]);

            if (!$status->successful()) {
                return $status;
            }

            $state = Str::lower((string) data_get($status->json(), 'data.processing_info.state', ''));
            if ($state === '' || $state === 'succeeded') {
                return $status;
            }

            if ($state === 'failed') {
                return $status;
            }

            $attempts++;
        } while ($attempts < 5);

        return $finalize;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.x_api.base_url', 'https://api.x.com'), '/');
    }

    private function shouldUseChunkedUpload(string $mimeType, string $absolutePath): bool
    {
        $mimeType = Str::lower(trim($mimeType));
        $fileSize = filesize($absolutePath) ?: 0;

        return $mimeType === 'image/gif'
            || Str::startsWith($mimeType, 'video/')
            || $fileSize > 5 * 1024 * 1024;
    }

    private function mediaCategoryForMime(string $mimeType): string
    {
        $mimeType = Str::lower(trim($mimeType));

        if ($mimeType === 'image/gif') {
            return 'dm_gif';
        }

        if (Str::startsWith($mimeType, 'video/')) {
            return 'dm_video';
        }

        return 'dm_image';
    }
}
