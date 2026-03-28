<?php

namespace App\Http\Controllers;

use App\Models\PlatformInvoice;
use App\Services\PlatformMidtransBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlatformBillingMidtransController extends Controller
{
    private $billing;

    public function __construct(PlatformMidtransBillingService $billing)
    {
        $this->billing = $billing;
    }

    public function checkout(PlatformInvoice $invoice): RedirectResponse
    {
        try {
            $checkout = $this->billing->createOrReuseCheckout($invoice);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($checkout['redirect_url']);
    }

    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = (string) ($payload['order_id'] ?? '');

        Log::info('Platform Midtrans notification received', [
            'order_id' => $orderId ?: null,
            'status' => $payload['transaction_status'] ?? null,
        ]);

        if ($orderId === '') {
            return response()->json(['message' => 'order_id missing'], 400);
        }

        try {
            $this->billing->handleNotification($payload);
        } catch (\RuntimeException $e) {
            Log::warning('Platform Midtrans notification rejected', [
                'order_id' => $orderId,
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Platform Midtrans notification processing error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        return response()->json(['message' => 'OK']);
    }
}
