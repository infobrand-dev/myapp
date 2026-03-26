<?php

namespace App\Modules\EmailMarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EmailWebhookController extends Controller
{
    /**
     * Mailtrap webhook receiver.
     * Expect JSON with at least:
     * - event (string) : delivered|bounce
     * - headers (array) containing 'X-Recipient-Token' or 'x-recipient-token'
     */
    public function mailtrap(Request $request)
    {
        if (!$this->isAuthorizedWebhook($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('mailtrap-webhook', $this->sanitizedWebhookLogContext($payload));

        $token = $this->extractToken($payload);
        if (!$token) {
            return response()->json(['status' => 'ignored', 'reason' => 'no token'], 200);
        }

        $recipient = EmailCampaignRecipient::where('tracking_token', $token)->first();
        if (!$recipient) {
            return response()->json(['status' => 'ignored', 'reason' => 'recipient not found'], 200);
        }

        TenantContext::setCurrentId((int) $recipient->tenant_id);

        $event = strtolower($payload['event'] ?? '');

        if ($event === 'delivered') {
            $recipient->update([
                'delivery_status' => 'delivered',
                'delivered_at' => $recipient->delivered_at ?: Carbon::now(),
            ]);
        } elseif (str_contains($event, 'bounce') || $event === 'bounced') {
            $recipient->update([
                'delivery_status' => 'bounced',
                'bounced_at' => Carbon::now(),
            ]);
        }

        $this->updateCampaignStatus($recipient->campaign);

        return response()->json(['status' => 'ok']);
    }

    protected function isAuthorizedWebhook(Request $request): bool
    {
        $secret = trim((string) config('services.mailtrap.webhook_secret', ''));
        if ($secret === '') {
            return true;
        }

        $providedSecret = trim((string) ($request->header('X-Webhook-Secret') ?: $request->query('secret', '')));

        return $providedSecret !== '' && hash_equals($secret, $providedSecret);
    }

    protected function extractToken(array $payload): ?string
    {
        // Mailtrap sends headers as array or as map
        $headers = $payload['message']['headers'] ?? $payload['headers'] ?? [];
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (is_string($key) && strtolower($key) === 'x-recipient-token') {
                    return is_array($value) ? ($value[0] ?? null) : $value;
                }
                if (is_array($value) && isset($value['name']) && strtolower($value['name']) === 'x-recipient-token') {
                    return $value['value'] ?? null;
                }
            }
        }
        return null;
    }

    protected function updateCampaignStatus($campaign): void
    {
        if (!$campaign) {
            return;
        }
        $pending = $campaign->recipients()
            ->whereIn('delivery_status', ['pending', 'outgoing'])
            ->count();
        if ($pending === 0 && $campaign->recipients()->count() > 0) {
            $campaign->update([
                'status' => 'done',
                'finished_at' => $campaign->finished_at ?: Carbon::now(),
            ]);
        }
    }

    private function sanitizedWebhookLogContext(array $payload): array
    {
        return [
            'event' => strtolower((string) ($payload['event'] ?? '')),
            'message_id' => (string) ($payload['message_id'] ?? data_get($payload, 'message.id', '')),
            'recipient_email' => $this->maskEmail((string) (
                $payload['email']
                ?? data_get($payload, 'message.to_email')
                ?? data_get($payload, 'message.to.0.email')
                ?? ''
            )),
            'token_present' => $this->extractToken($payload) !== null,
        ];
    }

    private function maskEmail(string $email): ?string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 2);

        return $visible . str_repeat('*', max(mb_strlen($local) - 2, 1)) . '@' . $domain;
    }
}
