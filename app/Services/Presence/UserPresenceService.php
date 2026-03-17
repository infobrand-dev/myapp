<?php

namespace App\Services\Presence;

use App\Models\User;
use App\Models\UserPresence;

class UserPresenceService
{
    public function forUser(User $user): UserPresence
    {
        return UserPresence::query()->firstOrNew([
            'user_id' => $user->id,
        ]);
    }

    public function recordHeartbeat(User $user): UserPresence
    {
        $presence = $this->forUser($user);
        $presence->last_heartbeat_at = now();
        $presence->last_seen_at = now();
        $presence->save();

        return $presence;
    }

    public function setManualStatus(User $user, string $status): UserPresence
    {
        $presence = $this->forUser($user);
        $presence->manual_status = $status === UserPresence::STATUS_AUTO ? null : $status;
        $presence->last_seen_at = now();
        $presence->last_heartbeat_at = now();
        $presence->save();

        return $presence;
    }

    public function statusMapForUsers(iterable $userIds): array
    {
        return UserPresence::statusMapForUsers($userIds);
    }

    public function userIdsForStatuses(array $statuses): array
    {
        $statuses = collect($statuses)
            ->filter(fn ($status) => in_array($status, UserPresence::allowedManualStatuses(), true))
            ->values()
            ->all();

        if ($statuses === []) {
            return [];
        }

        return UserPresence::query()
            ->get()
            ->filter(fn (UserPresence $presence) => in_array($presence->effectiveStatus(), $statuses, true))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
