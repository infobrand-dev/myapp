<?php

namespace App\Modules\WhatsAppApi\Jobs;

use App\Modules\WhatsAppApi\Models\WABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastRecipient;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWABlastCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(): void
    {
        $campaign = WABlastCampaign::with(['instance', 'template'])->find($this->campaignId);
        if (!$campaign) {
            return;
        }

        if ($campaign->status === 'cancelled') {
            return;
        }

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            return;
        }

        $instance = $campaign->instance;
        $template = $campaign->template;
        if (!$instance || !$template) {
            $campaign->update([
                'status' => 'failed',
                'last_error' => 'Campaign missing instance/template',
                'finished_at' => now(),
            ]);
            return;
        }

        $delayMs = (int) data_get($campaign->settings, 'delay_ms', 300);
        $delayMs = max(0, min(5000, $delayMs));

        $campaign->update([
            'status' => 'running',
            'started_at' => $campaign->started_at ?: now(),
            'last_error' => null,
        ]);

        try {
            $recipients = WABlastRecipient::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->get();

            foreach ($recipients as $recipient) {
                $this->processRecipient($campaign, $instance, $template, $recipient);

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }

            $this->syncCounts($campaign);

            $campaign->refresh();
            $pendingLeft = WABlastRecipient::query()
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if (!$pendingLeft) {
                $campaign->update([
                    'status' => 'done',
                    'finished_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('WA blast campaign failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            $campaign->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }

    private function processRecipient(WABlastCampaign $campaign, WhatsAppInstance $instance, WATemplate $template, WABlastRecipient $recipient): void
    {
        $recipient->update([
            'status' => 'processing',
            'error_message' => null,
            'queued_at' => now(),
        ]);

        try {
            $payload = $this->buildTemplatePayload($template, $recipient->variables ?? []);
            foreach ($payload['placeholders'] as $idx) {
                if (!isset($payload['params'][$idx]) || trim((string) $payload['params'][$idx]) === '') {
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => "Placeholder {{$idx}} kosong.",
                    ]);
                    return;
                }
            }

            $response = $this->sendTemplate($instance, $recipient->phone_number, $template, $payload['components']);
            $success = $response->successful();
            $errorMessage = $success ? null : mb_substr("Cloud API {$response->status()}: " . $response->body(), 0, 65535);

            $recipient->update([
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => $success ? now() : null,
                'error_message' => $errorMessage,
            ]);
        } catch (Throwable $e) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 65535),
            ]);
        }
    }

    private function buildTemplatePayload(WATemplate $template, array $params): array
    {
        $componentsSource = collect($template->components ?? []);
        $header = $componentsSource->firstWhere('type', 'header');
        $headerText = null;

        if (is_array($header)) {
            $headerText = data_get($header, 'text');
            if (!$headerText) {
                $headerText = data_get($header, 'parameters.0.text');
            }
        }

        $bodyIndexes = $this->placeholderIndexes($template->body);
        $headerIndexes = $this->placeholderIndexes($headerText);
        $allIndexes = array_values(array_unique(array_merge($bodyIndexes, $headerIndexes)));
        sort($allIndexes);

        $bodyParams = [];
        foreach ($bodyIndexes as $idx) {
            $bodyParams[] = [
                'type' => 'text',
                'text' => (string) ($params[$idx] ?? ''),
            ];
        }

        $headerParams = [];
        if ($headerText) {
            foreach ($headerIndexes as $idx) {
                $headerParams[] = [
                    'type' => 'text',
                    'text' => (string) ($params[$idx] ?? ''),
                ];
            }
        } elseif ($header && data_get($header, 'parameters.0.link')) {
            $headerParams[] = [
                'type' => strtolower((string) data_get($header, 'parameters.0.type', 'image')),
                'link' => data_get($header, 'parameters.0.link'),
            ];
        }

        $components = [];
        if (!empty($headerParams)) {
            $components[] = ['type' => 'header', 'parameters' => $headerParams];
        }
        if (!empty($bodyParams)) {
            $components[] = ['type' => 'body', 'parameters' => $bodyParams];
        }

        return [
            'components' => $components,
            'placeholders' => $allIndexes,
            'params' => $params,
        ];
    }

    private function placeholderIndexes(?string $text): array
    {
        if (!$text) {
            return [];
        }

        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        $indexes = array_map('intval', $matches[1] ?? []);
        $indexes = array_values(array_unique($indexes));
        sort($indexes);

        return $indexes;
    }

    private function syncCounts(WABlastCampaign $campaign): void
    {
        $counts = WABlastRecipient::query()
            ->selectRaw("status, COUNT(*) as aggregate")
            ->where('campaign_id', $campaign->id)
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $campaign->update([
            'total_count' => (int) WABlastRecipient::where('campaign_id', $campaign->id)->count(),
            'queued_count' => (int) (($counts['queued'] ?? 0) + ($counts['processing'] ?? 0) + ($counts['pending'] ?? 0)),
            'sent_count' => (int) ($counts['sent'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
        ]);
    }

    private function sendTemplate(WhatsAppInstance $instance, string $to, WATemplate $template, array $components): Response
    {
        $base = rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
        $phoneId = trim((string) $instance->phone_number_id);
        $token = trim((string) $instance->cloud_token);

        if ($phoneId === '' || $token === '') {
            throw new \RuntimeException('Instance cloud credentials tidak lengkap.');
        }

        $url = "{$base}/{$phoneId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template->name,
                'language' => ['code' => $template->language],
                'components' => $components,
            ],
        ];

        return Http::withToken($token)
            ->timeout(20)
            ->post($url, $payload);
    }
}
