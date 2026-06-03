<?php

namespace App\Http\Controllers;

use App\Multitenancy\QueryContextGuard;
use App\Models\NotificationRecipient;
use App\Support\Notifications\NotificationQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private NotificationQueryService $queryService;
    private QueryContextGuard $guard;

    public function __construct(
        NotificationQueryService $queryService,
        QueryContextGuard $guard
    ) {
        $this->queryService = $queryService;
        $this->guard = $guard;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = [
            'module' => $request->string('module')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'severity' => $request->string('severity')->toString() ?: null,
            'unread' => $request->boolean('unread'),
        ];

        return view('notifications.index', [
            'notifications' => $this->queryService->inboxForUser($user->id, $filters),
            'previewNotifications' => $this->queryService->previewForUser($user->id, 6),
            'unreadCount' => $this->queryService->unreadCountForUser($user->id),
            'filters' => $filters,
            'severityOptions' => [
                'critical' => 'Critical',
                'warning' => 'Warning',
                'info' => 'Info',
                'success' => 'Success',
            ],
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->queryService->previewForUser($user->id, 6);

        return response()->json([
            'count' => $this->queryService->unreadCountForUser($user->id),
            'html' => view('shared.topbar-notification-items', [
                'topbarNotifications' => $notifications,
            ])->render(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->queryService->unreadCountForUser($request->user()->id),
        ]);
    }

    public function markRead(Request $request, int $recipient): JsonResponse|RedirectResponse
    {
        $row = $this->ownedRecipient($request, $recipient);
        $row->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this->responseForAction($request, 'Notifikasi ditandai sudah dibaca.');
    }

    public function markUnread(Request $request, int $recipient): JsonResponse|RedirectResponse
    {
        $row = $this->ownedRecipient($request, $recipient);
        $row->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return $this->responseForAction($request, 'Notifikasi ditandai belum dibaca.');
    }

    public function dismiss(Request $request, int $recipient): JsonResponse|RedirectResponse
    {
        $row = $this->ownedRecipient($request, $recipient);
        $row->update([
            'dismissed_at' => now(),
            'is_read' => true,
            'read_at' => $row->read_at ?: now(),
        ]);

        return $this->responseForAction($request, 'Notifikasi disembunyikan dari inbox.');
    }

    public function archive(Request $request, int $recipient): JsonResponse|RedirectResponse
    {
        $row = $this->ownedRecipient($request, $recipient);
        $row->update([
            'archived_at' => now(),
            'is_read' => true,
            'read_at' => $row->read_at ?: now(),
        ]);

        return $this->responseForAction($request, 'Notifikasi diarsipkan.');
    }

    private function ownedRecipient(Request $request, int $recipientId): NotificationRecipient
    {
        $tenantId = $this->guard->requireTenant('notification recipient lookup');

        return NotificationRecipient::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $request->user()->id)
            ->whereKey($recipientId)
            ->with('notification')
            ->firstOrFail();
    }

    private function responseForAction(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
