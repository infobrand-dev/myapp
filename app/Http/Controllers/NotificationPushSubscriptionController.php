<?php

namespace App\Http\Controllers;

use App\Models\NotificationPushSubscription;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        $subscription = NotificationPushSubscription::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
            ],
            [
                'tenant_id' => TenantContext::currentId() ?: 1,
                'company_id' => CompanyContext::currentId(),
                'branch_id' => BranchContext::currentId(),
                'public_key' => data_get($data, 'keys.p256dh'),
                'auth_token' => data_get($data, 'keys.auth'),
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'user_agent' => (string) $request->userAgent(),
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Web push aktif.',
            'subscription_id' => $subscription->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        NotificationPushSubscription::query()
            ->where('tenant_id', TenantContext::currentId() ?: 1)
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->update([
                'is_active' => false,
            ]);

        return response()->json([
            'message' => 'Web push dinonaktifkan.',
        ]);
    }
}
