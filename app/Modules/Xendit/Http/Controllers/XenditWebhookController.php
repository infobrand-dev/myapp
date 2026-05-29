<?php

namespace App\Modules\Xendit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Xendit\Models\XenditTransaction;
use App\Modules\Xendit\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function __construct(
        private readonly XenditService $service,
    ) {
    }

    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();
        $reference = (string) ($payload['external_id'] ?? '');

        if ($reference === '') {
            return response()->json(['message' => 'external_id missing'], 400);
        }

        $transaction = XenditTransaction::query()
            ->where('external_reference', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('Xendit notification: unknown external_id', ['external_id' => $reference]);

            return response()->json(['message' => 'OK']);
        }

        try {
            $this->service->handleNotification($payload, (string) $request->header('x-callback-token', ''));
        } catch (\RuntimeException $exception) {
            Log::warning('Xendit notification rejected', [
                'external_id' => $reference,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 400);
        } catch (\Throwable $exception) {
            Log::error('Xendit notification processing error', [
                'external_id' => $reference,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        return response()->json(['message' => 'OK']);
    }
}
