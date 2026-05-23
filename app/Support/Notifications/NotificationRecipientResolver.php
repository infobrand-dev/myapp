<?php

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NotificationRecipientResolver
{
    public function resolve(NotificationMessage $message, array $definition): Collection
    {
        $tenantId = (int) ($message->tenantId ?: 1);
        $query = User::query()->where('tenant_id', $tenantId);

        $userIds = collect($message->recipientUserIds)->filter()->values();
        $roles = collect($definition['recipient_roles'] ?? [])->filter()->values();

        if ($userIds->isNotEmpty()) {
            $query->whereIn('id', $userIds->all());
        } elseif ($roles->isNotEmpty()) {
            $query->whereHas('roles', function ($roleQuery) use ($roles): void {
                $roleQuery->whereIn('name', $roles->all());
            });
        }

        if ($message->companyId && Schema::hasTable('user_companies')) {
            $query->where(function ($scope) use ($message): void {
                $scope->whereHas('companies', fn ($companyQuery) => $companyQuery->where('companies.id', $message->companyId))
                    ->orWhereDoesntHave('companies');
            });
        }

        if ($message->branchId && Schema::hasTable('user_branches')) {
            $query->where(function ($scope) use ($message): void {
                $scope->whereHas('branches', fn ($branchQuery) => $branchQuery->where('branches.id', $message->branchId))
                    ->orWhereDoesntHave('branches');
            });
        }

        return $query->orderBy('id')->get();
    }
}
