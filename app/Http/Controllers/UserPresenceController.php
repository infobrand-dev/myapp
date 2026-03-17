<?php

namespace App\Http\Controllers;

use App\Models\UserPresence;
use App\Services\Presence\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPresenceController extends Controller
{
    public function __construct(private readonly UserPresenceService $presenceService)
    {
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $presence = $this->presenceService->recordHeartbeat($request->user());

        return response()->json([
            'ok' => true,
            'status' => $presence->effectiveStatus(),
            'manual_status' => $presence->manualStatusOrAuto(),
        ]);
    }

    public function setStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', UserPresence::allowedManualStatuses())],
        ]);

        $presence = $this->presenceService->setManualStatus($request->user(), (string) $data['status']);

        return response()->json([
            'ok' => true,
            'status' => $presence->effectiveStatus(),
            'manual_status' => $presence->manualStatusOrAuto(),
        ]);
    }
}
