<?php

namespace App\Modules\Conversations\Services;

use App\Models\StoredFile;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Services\StoredFileService;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InboundMediaCaptureService
{
    public function __construct(
        private readonly StoredFileService $storedFiles
    ) {
    }

    public function shouldCapture(string $type): bool
    {
        return in_array(strtolower(trim($type)), ['image', 'video', 'audio', 'document', 'sticker'], true);
    }

    public function captureProviderUrl(
        ConversationMessage $message,
        string $url,
        array $attributes = [],
        array $requestOptions = []
    ): ?StoredFile {
        $url = trim($url);
        if ($url === '' || !$this->shouldCapture((string) $message->type)) {
            return null;
        }

        $response = Http::timeout((int) ($requestOptions['timeout'] ?? 60))
            ->withHeaders((array) ($requestOptions['headers'] ?? []))
            ->get($url);

        if (!$response->successful()) {
            return null;
        }

        $mimeType = trim((string) ($attributes['mime_type'] ?? $response->header('Content-Type', '')));
        $filename = $this->resolveFilename(
            (string) ($attributes['filename'] ?? ''),
            $url,
            $mimeType,
            (string) ($attributes['provider_media_id'] ?? '')
        );

        $storedFile = $this->captureBinary(
            $message,
            (string) $response->body(),
            $filename,
            $mimeType,
            array_merge($attributes, [
                'provider_media_url' => $attributes['provider_media_url'] ?? $url,
            ])
        );

        if ($storedFile) {
            $this->markMessageAsCaptured($message, $storedFile, array_merge($attributes, [
                'provider_media_url' => $attributes['provider_media_url'] ?? $url,
            ]));
        }

        return $storedFile;
    }

    public function captureWhatsAppCloudMedia(
        ConversationMessage $message,
        WhatsAppInstance $instance,
        string $mediaId,
        ?string $mimeType = null,
        ?string $filename = null
    ): ?StoredFile {
        $mediaId = trim($mediaId);
        if ($mediaId === '' || !$this->shouldCapture((string) $message->type)) {
            return null;
        }

        $token = trim((string) $instance->cloud_token);
        if ($token === '') {
            return null;
        }

        $base = rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
        $metadataResponse = Http::withToken($token)
            ->timeout(30)
            ->get("{$base}/{$mediaId}");

        if (!$metadataResponse->successful()) {
            return null;
        }

        $downloadUrl = trim((string) $metadataResponse->json('url', ''));
        if ($downloadUrl === '') {
            return null;
        }

        return $this->captureProviderUrl($message, $downloadUrl, [
            'provider_origin' => 'whatsapp_cloud',
            'provider_media_id' => $mediaId,
            'provider_media_url' => $downloadUrl,
            'mime_type' => $mimeType ?: trim((string) $metadataResponse->json('mime_type', '')),
            'filename' => $filename ?: trim((string) $metadataResponse->json('sha256', '')),
        ], [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 60,
        ]);
    }

    public function captureBinary(
        ConversationMessage $message,
        string $contents,
        string $filename,
        ?string $mimeType = null,
        array $attributes = []
    ): ?StoredFile {
        if ($contents === '' || !$this->shouldCapture((string) $message->type)) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'inbound-media-');
        if ($tempPath === false) {
            return null;
        }

        file_put_contents($tempPath, $contents);

        $uploadedFile = new UploadedFile(
            $tempPath,
            $filename,
            $mimeType ?: 'application/octet-stream',
            null,
            true
        );

        try {
            TenantContext::setCurrentId((int) $message->tenant_id);

            return $this->storedFiles->storeUploadedFile($uploadedFile, 'channel_inbound_evidence', [
                'tenant_id' => $message->tenant_id,
                'source_module' => 'conversations',
                'source_context' => 'inbound_media_capture',
                'provider_origin' => $attributes['provider_origin'] ?? null,
                'provider_media_id' => $attributes['provider_media_id'] ?? null,
                'provider_media_url' => $attributes['provider_media_url'] ?? null,
                'meta' => [
                    'captured_from' => 'provider_media_fetch',
                    'conversation_message_id' => $message->id,
                    'channel' => optional($message->conversation)->channel,
                ],
            ]);
        } finally {
            @unlink($tempPath);
        }
    }

    private function markMessageAsCaptured(ConversationMessage $message, StoredFile $storedFile, array $attributes = []): void
    {
        $payload = is_array($message->payload) ? $message->payload : [];
        $payload = array_merge($payload, array_filter([
            'stored_file_id' => $storedFile->id,
            'storage_disk' => $storedFile->disk,
            'storage_path' => $storedFile->path,
            'content_hash' => $storedFile->content_hash,
            'provider_origin' => $attributes['provider_origin'] ?? $storedFile->provider_origin,
            'provider_media_id' => $attributes['provider_media_id'] ?? $storedFile->provider_media_id,
            'provider_media_url' => $attributes['provider_media_url'] ?? $storedFile->provider_media_url,
            'copied_locally' => true,
            'fetched_at' => now()->toIso8601String(),
            'capture_status' => 'captured',
        ], static fn ($value) => $value !== null && $value !== ''));

        $message->forceFill([
            'media_url' => URL::route('stored-files.preview', $storedFile),
            'media_mime' => $storedFile->mime_type ?: $message->media_mime,
            'payload' => $payload,
        ])->save();
    }

    private function resolveFilename(string $preferred, string $url, ?string $mimeType, string $fallbackSeed = ''): string
    {
        $preferred = trim($preferred);
        if ($preferred !== '') {
            return $this->ensureExtension($preferred, $mimeType);
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $basename = trim(basename($path));
        if ($basename !== '' && $basename !== '/' && $basename !== '.') {
            return $this->ensureExtension($basename, $mimeType);
        }

        $fallback = $fallbackSeed !== '' ? $fallbackSeed : Str::uuid()->toString();

        return $this->ensureExtension($fallback, $mimeType);
    }

    private function ensureExtension(string $filename, ?string $mimeType): string
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) !== '') {
            return $filename;
        }

        $extension = $this->extensionFromMime($mimeType);

        return $extension !== '' ? ($filename . '.' . $extension) : $filename;
    }

    private function extensionFromMime(?string $mimeType): string
    {
        $mimeType = strtolower(trim((string) $mimeType));

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'application/pdf' => 'pdf',
            default => '',
        };
    }
}
