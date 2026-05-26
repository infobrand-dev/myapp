<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\UtasWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UtasWebhookController extends Controller
{
    public function __invoke(Request $request, UtasWebhookService $service): JsonResponse
    {
        if (!$service->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $result = $service->handle($request);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Payload webhook UTAS tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('UTAS webhook processing failed', [
                'error' => $e->getMessage(),
                'state' => strtolower(trim((string) $request->input('state', ''))) ?: null,
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        if (!$result['handled']) {
            return response()->json([
                'message' => 'State webhook diabaikan.',
                'state' => $result['state'],
            ], 202);
        }

        return response()->json([
            'message' => 'OK',
            'state' => $result['state'],
            'notified' => $result['notified'],
        ]);
    }
}
