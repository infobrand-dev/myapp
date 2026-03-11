<?php

namespace App\Modules\WhatsAppApi\Jobs;

use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubmitTemplateToMeta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $templateId;
    public int $instanceId;

    public function __construct(int $templateId, int $instanceId)
    {
        $this->templateId = $templateId;
        $this->instanceId = $instanceId;
    }

    public function handle(): void
    {
        $template = WATemplate::find($this->templateId);
        $instance = WhatsAppInstance::find($this->instanceId);

        if (!$template || !$instance || strtolower($instance->provider) !== 'cloud') {
            return;
        }

        $businessId = $instance->cloud_business_account_id;
        $token = $instance->cloud_token;
        $base = rtrim(config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
        $appId = trim((string) data_get($instance->settings, 'wa_cloud_app_id', config('services.wa_cloud.app_id', '')));

        if (!$businessId || !$token) {
            $template?->update(['status' => 'rejected', 'last_submit_error' => 'Missing business_id/token']);
            return;
        }

        try {
            $payload = $this->buildPayload($template, $token, $base, $appId);
            $response = Http::withToken($token)->post("{$base}/{$businessId}/message_templates", $payload);
            if ($response->successful()) {
                $template->update([
                    'meta_template_id' => $response->json('id'),
                    'status' => 'pending',
                    'last_submitted_at' => now(),
                    'last_submit_error' => null,
                ]);
            } else {
                $template->update([
                    'status' => 'rejected',
                    'last_submit_error' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Meta template submit failed', ['template_id' => $this->templateId, 'error' => $e->getMessage()]);
            $template?->update([
                'status' => 'rejected',
                'last_submit_error' => $e->getMessage(),
            ]);
        }
    }

    private function buildPayload(WATemplate $template, string $token, string $base, string $appId): array
    {
        $components = [];
        $storedComponents = (array) ($template->components ?? []);
        $tplComponents = collect($storedComponents);
        $variableMappings = (array) ($template->variable_mappings ?? []);

        // HEADER
        $header = $tplComponents->firstWhere('type', 'header');
        if ($header) {
            $comp = ['type' => 'HEADER', 'format' => data_get($header, 'format', 'TEXT')];
            if (strtoupper($comp['format']) === 'TEXT') {
                $headerText = data_get($header, 'text') ?: data_get($header, 'parameters.0.text');
                $comp['text'] = $headerText;
                $headerExamples = $this->placeholderExamples((string) $headerText, $variableMappings);
                if (!empty($headerExamples)) {
                    $comp['example'] = [
                        'header_text' => $headerExamples,
                    ];
                }
            } else {
                $sampleMedia = data_get($header, 'parameters.0.handle');
                if (!$sampleMedia) {
                    $sampleMedia = $this->uploadHeaderMediaAndStoreHandle($template, $header, $storedComponents, $token, $base, $appId);
                }
                if ($sampleMedia) {
                    if (!$this->looksLikeMetaHeaderHandle((string) $sampleMedia)) {
                        throw new RuntimeException('Template header media membutuhkan sample `example.header_handle` dari Resumable Upload API Meta. URL file biasa tidak valid untuk submit template.');
                    }
                    $comp['example'] = [
                        'header_handle' => [(string) $sampleMedia],
                    ];
                } else {
                    throw new RuntimeException('Template header media membutuhkan sample `example.header_handle`, tetapi handle media belum tersedia.');
                }
            }
            $components[] = $comp;
        }

        // BODY
        $components[] = [
            'type' => 'BODY',
            'text' => $template->body,
        ];
        $bodyExamples = $this->placeholderExamples((string) $template->body, $variableMappings);
        if (!empty($bodyExamples)) {
            $components[count($components) - 1]['example'] = [
                'body_text' => [$bodyExamples],
            ];
        }

        // FOOTER
        $footer = $tplComponents->firstWhere('type', 'footer');
        if ($footer) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => data_get($footer, 'text'),
            ];
        }

        // BUTTONS
        $buttonsComponent = $tplComponents->firstWhere('type', 'buttons');
        if (is_array($buttonsComponent) && is_array(data_get($buttonsComponent, 'buttons'))) {
            $btnArray = [];
            foreach ((array) data_get($buttonsComponent, 'buttons', []) as $btn) {
                $btnType = strtoupper((string) data_get($btn, 'type'));
                if ($btnType === 'QUICK_REPLY') {
                    $btnArray[] = [
                        'type' => 'QUICK_REPLY',
                        'text' => data_get($btn, 'text'),
                    ];
                    continue;
                }

                if ($btnType === 'URL') {
                    $item = [
                        'type' => 'URL',
                        'text' => data_get($btn, 'text'),
                        'url' => data_get($btn, 'url'),
                    ];
                    if (data_get($btn, 'example')) {
                        $item['example'] = [(string) data_get($btn, 'example')];
                    }
                    $btnArray[] = $item;
                    continue;
                }

                if ($btnType === 'PHONE_NUMBER') {
                    $btnArray[] = [
                        'type' => 'PHONE_NUMBER',
                        'text' => data_get($btn, 'text'),
                        'phone_number' => data_get($btn, 'phone_number'),
                    ];
                    continue;
                }

                if ($btnType === 'COPY_CODE') {
                    $item = [
                        'type' => 'COPY_CODE',
                        'text' => data_get($btn, 'text'),
                    ];
                    if (data_get($btn, 'example')) {
                        $item['example'] = data_get($btn, 'example');
                    }
                    $btnArray[] = $item;
                }
            }
            if ($btnArray) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => array_values($btnArray),
                ];
            }
        } else {
            // Backward compatibility for old per-button component format.
            $buttons = $tplComponents->where('type', 'button')->values();
            if ($buttons->isNotEmpty()) {
                $btnArray = [];
                foreach ($buttons as $btn) {
                    $sub = strtolower(data_get($btn, 'sub_type'));
                    if ($sub === 'quick_reply') {
                        $btnArray[] = [
                            'type' => 'QUICK_REPLY',
                            'text' => data_get($btn, 'parameters.0.text'),
                        ];
                    } elseif ($sub === 'url') {
                        $item = [
                            'type' => 'URL',
                            'text' => data_get($btn, 'parameters.0.text'),
                            'url' => data_get($btn, 'url'),
                        ];
                        if (data_get($btn, 'example')) {
                            $item['example'] = [(string) data_get($btn, 'example')];
                        }
                        $btnArray[] = $item;
                    } elseif ($sub === 'phone_number') {
                        $btnArray[] = [
                            'type' => 'PHONE_NUMBER',
                            'text' => data_get($btn, 'parameters.0.text'),
                            'phone_number' => data_get($btn, 'phone_number'),
                        ];
                    } elseif ($sub === 'copy_code') {
                        $item = [
                            'type' => 'COPY_CODE',
                            'text' => data_get($btn, 'parameters.0.text'),
                        ];
                        if (data_get($btn, 'example')) {
                            $item['example'] = (string) data_get($btn, 'example');
                        }
                        $btnArray[] = $item;
                    }
                }
                if ($btnArray) {
                    $components[] = [
                        'type' => 'BUTTONS',
                        'buttons' => array_values($btnArray),
                    ];
                }
            }
        }

        return [
            'name' => method_exists($template, 'metaTemplateName') ? $template->metaTemplateName() : ($template->meta_name ?: $template->name),
            'category' => strtoupper($template->category ?? 'UTILITY'),
            'language' => $this->mapLanguage($template->language),
            'components' => $components,
        ];
    }

    private function mapLanguage(string $lang): string
    {
        return match ($lang) {
            'id' => 'id',
            'en' => 'en_US',
            default => $lang,
        };
    }

    private function placeholderExamples(string $text, array $variableMappings): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        $indexes = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        sort($indexes);

        if (empty($indexes)) {
            return [];
        }

        $examples = [];
        foreach ($indexes as $index) {
            $mapping = $variableMappings[$index] ?? $variableMappings[(string) $index] ?? [];
            $examples[] = $this->exampleValueForMapping($index, is_array($mapping) ? $mapping : []);
        }

        return $examples;
    }

    private function exampleValueForMapping(int $index, array $mapping): string
    {
        $textValue = trim((string) ($mapping['text_value'] ?? ''));
        if ($textValue !== '') {
            return $textValue;
        }

        $fallbackValue = trim((string) ($mapping['fallback_value'] ?? ''));
        if ($fallbackValue !== '') {
            return $fallbackValue;
        }

        $sourceType = strtolower(trim((string) ($mapping['source_type'] ?? 'text')));
        if ($sourceType === 'contact_field') {
            return 'sample_' . trim((string) ($mapping['contact_field'] ?? 'value'));
        }

        if ($sourceType === 'sender_field') {
            return 'sample_' . trim((string) ($mapping['sender_field'] ?? 'value'));
        }

        return 'sample_' . $index;
    }

    private function looksLikeMetaHeaderHandle(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return preg_match('/^\d+:/', $value) === 1 || str_starts_with($value, 'upload:');
    }

    private function uploadHeaderMediaAndStoreHandle(
        WATemplate $template,
        array $header,
        array $storedComponents,
        string $token,
        string $base,
        string $appId
    ): string {
        if ($appId === '') {
            throw new RuntimeException('WA_CLOUD_APP_ID / META_APP_ID belum dikonfigurasi. Meta Resumable Upload API membutuhkan App ID untuk membuat header_handle.');
        }

        $media = $this->resolveHeaderMediaBinary($header);
        $uploadSessionId = $this->createMetaUploadSession(
            $base,
            $appId,
            $token,
            $media['filename'],
            $media['mime_type'],
            strlen($media['contents'])
        );
        $handle = $this->uploadMetaUploadSessionChunk($base, $uploadSessionId, $token, $media['contents']);

        foreach ($storedComponents as $componentIndex => $component) {
            if (!is_array($component) || strtolower((string) Arr::get($component, 'type')) !== 'header') {
                continue;
            }

            $parameters = (array) Arr::get($component, 'parameters', []);
            if (isset($parameters[0]) && is_array($parameters[0])) {
                $parameters[0]['handle'] = $handle;
                $storedComponents[$componentIndex]['parameters'] = $parameters;
                break;
            }
        }

        $template->forceFill(['components' => $storedComponents])->save();

        return $handle;
    }

    private function resolveHeaderMediaBinary(array $header): array
    {
        $param = (array) data_get($header, 'parameters.0', []);
        $disk = (string) ($param['storage_disk'] ?? 'public');
        $storagePath = trim((string) ($param['storage_path'] ?? ''));
        $filename = trim((string) ($param['original_name'] ?? ''));
        $mimeType = trim((string) ($param['mime_type'] ?? ''));

        if ($storagePath !== '' && Storage::disk($disk)->exists($storagePath)) {
            $contents = (string) Storage::disk($disk)->get($storagePath);

            return [
                'contents' => $contents,
                'filename' => $filename !== '' ? $filename : basename($storagePath),
                'mime_type' => $mimeType !== '' ? $mimeType : $this->guessMimeTypeFromFilename(basename($storagePath)),
            ];
        }

        $link = trim((string) ($param['link'] ?? ''));
        if ($link === '') {
            throw new RuntimeException('Header media tidak memiliki file lokal maupun URL sumber yang bisa diunggah ke Meta.');
        }

        $localPublicPath = $this->publicStoragePathFromUrl($link);
        if ($localPublicPath && is_file($localPublicPath)) {
            $contents = file_get_contents($localPublicPath);
            if ($contents === false) {
                throw new RuntimeException('Gagal membaca file header media lokal untuk sample template.');
            }

            return [
                'contents' => $contents,
                'filename' => $filename !== '' ? $filename : basename($localPublicPath),
                'mime_type' => $mimeType !== '' ? $mimeType : $this->guessMimeTypeFromFilename(basename($localPublicPath)),
            ];
        }

        $response = Http::timeout(60)->get($link);
        if (!$response->successful()) {
            throw new RuntimeException('Gagal mengunduh header media sample dari URL: ' . ($response->body() ?: $response->status()));
        }

        $resolvedFilename = $filename !== '' ? $filename : basename((string) (parse_url($link, PHP_URL_PATH) ?: 'header-sample'));
        $resolvedMime = $mimeType !== '' ? $mimeType : trim((string) $response->header('Content-Type'));
        if ($resolvedMime === '') {
            $resolvedMime = $this->guessMimeTypeFromFilename($resolvedFilename);
        }

        return [
            'contents' => (string) $response->body(),
            'filename' => $resolvedFilename,
            'mime_type' => $resolvedMime,
        ];
    }

    private function createMetaUploadSession(
        string $base,
        string $appId,
        string $token,
        string $filename,
        string $mimeType,
        int $byteLength
    ): string {
        $query = http_build_query([
            'file_name' => $filename,
            'file_length' => $byteLength,
            'file_type' => $mimeType,
        ]);

        $response = Http::withToken($token)->post("{$base}/{$appId}/uploads?{$query}");
        if (!$response->successful()) {
            throw new RuntimeException('Gagal membuat upload session Meta: ' . ($response->body() ?: $response->status()));
        }

        $sessionId = trim((string) $response->json('id'));
        if ($sessionId === '') {
            throw new RuntimeException('Upload session Meta tidak mengembalikan id session.');
        }

        return $sessionId;
    }

    private function uploadMetaUploadSessionChunk(string $base, string $sessionId, string $token, string $contents): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth ' . $token,
            'file_offset' => '0',
        ])->withBody($contents, 'application/octet-stream')->post("{$base}/{$sessionId}");

        if (!$response->successful()) {
            throw new RuntimeException('Gagal upload media sample ke Meta: ' . ($response->body() ?: $response->status()));
        }

        $handle = trim((string) ($response->json('h') ?? ''));
        if ($handle === '') {
            throw new RuntimeException('Meta upload berhasil tetapi tidak mengembalikan header_handle.');
        }

        return $handle;
    }

    private function publicStoragePathFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $marker = '/storage/';
        $position = strpos($path, $marker);
        if ($position === false) {
            return null;
        }

        $relative = ltrim(substr($path, $position + strlen($marker)), '/');

        return public_path('storage/' . $relative);
    }

    private function guessMimeTypeFromFilename(string $filename): string
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            default => 'application/octet-stream',
        };
    }
}
