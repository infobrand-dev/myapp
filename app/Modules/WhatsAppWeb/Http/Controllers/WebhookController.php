<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebConversationSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function inbound(Request $request, WhatsAppWebConversationSyncService $sync): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'contact_id' => ['required', 'string'],
            'contact_name' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:in,out'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:50'],
            'author' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ]);

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


