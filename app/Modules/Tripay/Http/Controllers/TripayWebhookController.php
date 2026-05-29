<?php

namespace App\Modules\Tripay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tripay\Models\TripayTransaction;
use App\Modules\Tripay\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripayWebhookController extends Controller
{
    public function __construct(
        private readonly TripayService $service,
    ) {
    }

    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();
        $reference = (string) ($payload['merchant_ref'] ?? '');

        if ($reference === '') {
            return response()->json(['message' => 'merchant_ref missing'], 400);
        }

        $transaction = TripayTransaction::query()
            ->where('merchant_reference', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('Tripay notification: unknown merchant_ref', ['merchant_ref' => $reference]);

            return response()->json(['message' => 'OK']);
        }

        try {
            $this->service->handleNotification($payload, (string) $request->header('x-callback-signature', ''));
        } catch (\RuntimeException $exception) {
            Log::warning('Tripay notification rejected', [
                'merchant_ref' => $reference,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 400);
        } catch (\Throwable $exception) {
            Log::error('Tripay notification processing error', [
                'merchant_ref' => $reference,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        return response()->json(['message' => 'OK']);
    }
}
