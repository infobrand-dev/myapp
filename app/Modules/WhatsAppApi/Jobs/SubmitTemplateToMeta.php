<?php

namespace App\Modules\WhatsAppApi\Jobs;

use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        if (!$businessId || !$token) {
            $template?->update(['status' => 'rejected', 'last_submit_error' => 'Missing business_id/token']);
            return;
        }

        $payload = $this->buildPayload($template);

        try {
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

    private function buildPayload(WATemplate $template): array
    {
        $components = [];
        $tplComponents = collect($template->components ?? []);

        // HEADER
        $header = $tplComponents->firstWhere('type', 'header');
        if ($header) {
            $comp = ['type' => 'HEADER', 'format' => data_get($header, 'format', 'TEXT')];
            if (strtoupper($comp['format']) === 'TEXT') {
                $comp['text'] = data_get($header, 'text') ?: data_get($header, 'parameters.0.text');
            } else {
                $sampleMedia = data_get($header, 'parameters.0.link');
                if ($sampleMedia) {
                    $comp['example'] = [
                        'header_handle' => [(string) $sampleMedia],
                    ];
                }
            }
            $components[] = $comp;
        }

        // BODY
        $components[] = [
            'type' => 'BODY',
            'text' => $template->body,
        ];

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
                        $item['example'] = data_get($btn, 'example');
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
                        $btnArray[] = [
                            'type' => 'URL',
                            'text' => data_get($btn, 'parameters.0.text'),
                            'url' => data_get($btn, 'url'),
                        ];
                    } elseif ($sub === 'phone_number') {
                        $btnArray[] = [
                            'type' => 'PHONE_NUMBER',
                            'text' => data_get($btn, 'parameters.0.text'),
                            'phone_number' => data_get($btn, 'phone_number'),
                        ];
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
}
