<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Http\Requests\InboundWhatsAppWebhookRequest;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebConversationSyncService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function inbound(InboundWhatsAppWebhookRequest $request, WhatsAppWebConversationSyncService $sync): JsonResponse
    {
        $data = $request->validated();

        $expected = RuntimeSettings::waWebWebhookToken();
        if ($expected && $expected !== $data['token']) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        $wasExisting = null;
        if (!empty($data['external_message_id'])) {
            $wasExisting = \App\Modules\Conversations\Models\ConversationMessage::query()
                ->where('external_message_id', $data['external_message_id'])
                ->exists();
        }

        $sync->syncMessage($data);

        return response()->json([
            'stored' => true,
            'deduplicated' => (bool) $wasExisting,
        ]);
    }
}


