<?php

namespace App\Modules\EmailMarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
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
        $payload = $request->all();
        Log::info('mailtrap-webhook', $payload);

        $token = $this->extractToken($payload);
        if (!$token) {
            return response()->json(['status' => 'ignored', 'reason' => 'no token'], 200);
        }

        $recipient = EmailCampaignRecipient::where('tracking_token', $token)->first();
        if (!$recipient) {
            return response()->json(['status' => 'ignored', 'reason' => 'recipient not found'], 200);
        }

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
}
