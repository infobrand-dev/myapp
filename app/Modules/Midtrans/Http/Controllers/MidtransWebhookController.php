<?php

namespace App\Modules\Midtrans\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Midtrans\Models\MidtransTransaction;
use App\Modules\Midtrans\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(private readonly MidtransService $midtrans) {}

    /**
     * Receive payment notification from Midtrans.
     * Endpoint: POST /midtrans/webhook/notification
     * — No auth, no CSRF (uses signature verification instead)
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans notification received', [
            'order_id' => $payload['order_id'] ?? null,
            'status'   => $payload['transaction_status'] ?? null,
        ]);

        $orderId = (string) ($payload['order_id'] ?? '');

        if (empty($orderId)) {
            return response()->json(['message' => 'order_id missing'], 400);
        }

        // Find the transaction to determine tenant context
        $transaction = MidtransTransaction::query()
            ->where('order_id', $orderId)
            ->first();

        if (!$transaction) {
            // Midtrans may retry — return 200 to stop retries for unknown orders
            Log::warning('Midtrans notification: unknown order_id', ['order_id' => $orderId]);
            return response()->json(['message' => 'OK']);
        }

        try {
            $this->midtrans->handleNotification($payload);
        } catch (\RuntimeException $e) {
            // Signature mismatch or missing settings — reject with 400 so Midtrans does NOT retry
            Log::warning('Midtrans notification rejected', [
                'order_id' => $orderId,
                'reason'   => $e->getMessage(),
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Midtrans notification processing error', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            // Return 200 to prevent Midtrans retrying on transient errors — investigate manually
            return response()->json(['message' => 'OK']);
        }

        return response()->json(['message' => 'OK']);
    }
}
