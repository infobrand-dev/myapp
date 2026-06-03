<?php

namespace App\Support\Notifications;

use App\Multitenancy\QueryContextGuard;
use App\Models\NotificationRecipient;
use App\Support\BooleanQuery;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationQueryService
{
    private QueryContextGuard $guard;

    public function __construct(
        QueryContextGuard $guard
    ) {
        $this->guard = $guard;
    }

    public function inboxForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery($userId, $filters)
            ->with('notification')
            ->paginate(15)
            ->withQueryString();
    }

    public function previewForUser(int $userId, int $limit = 6): Collection
    {
        return $this->baseQuery($userId, ['unread' => true])
            ->with('notification')
            ->limit($limit)
            ->get();
    }

    public function unreadCountForUser(int $userId): int
    {
        return (int) $this->baseQuery($userId, ['unread' => true])->count();
    }

    public function attentionItemsForUser(int $userId, int $limit = 5): Collection
    {
        return $this->baseQuery($userId, ['severity' => ['critical', 'warning']])
            ->with('notification')
            ->limit($limit)
            ->get();
    }

    public function recentItemsForUser(int $userId, int $limit = 5): Collection
    {
        return $this->baseQuery($userId, [])
            ->with('notification')
            ->limit($limit)
            ->get();
    }

    private function baseQuery(int $userId, array $filters): Builder
    {
        $tenantId = $this->guard->requireTenant('notification inbox query');

        $query = NotificationRecipient::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('archived_at')
            ->whereHas('notification', function (Builder $notificationQuery) use ($filters): void {
                $notificationQuery
                    ->when(CompanyContext::currentId(), fn (Builder $query, int $companyId) => $query->where(function (Builder $scope) use ($companyId): void {
                        $scope->where('company_id', $companyId)->orWhereNull('company_id');
                    }))
                    ->when(BranchContext::currentId(), fn (Builder $query, int $branchId) => $query->where(function (Builder $scope) use ($branchId): void {
                        $scope->where('branch_id', $branchId)->orWhereNull('branch_id');
                    }))
                    ->when(!empty($filters['module']), fn (Builder $query) => $query->where('module', $filters['module']))
                    ->when(!empty($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
                    ->when(!empty($filters['severity']), function (Builder $query) use ($filters): void {
                        $severities = is_array($filters['severity']) ? $filters['severity'] : [$filters['severity']];
                        $query->whereIn('severity', $severities);
                    });
            })
            ->when(!empty($filters['unread']), fn (Builder $query) => BooleanQuery::apply($query, 'is_read', false)->whereNull('dismissed_at'))
            ->orderByDesc(
                \App\Models\CoreNotification::query()
                    ->select('last_seen_at')
                    ->whereColumn('notifications.id', 'notification_recipients.notification_id')
                    ->limit(1)
            );

        $query->whereNull('dismissed_at');

        return $query;
    }
}
