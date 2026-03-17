<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresence extends Model
{
    public const STATUS_AUTO = 'auto';
    public const STATUS_ONLINE = 'online';
    public const STATUS_AWAY = 'away';
    public const STATUS_BUSY = 'busy';
    public const STATUS_OFFLINE = 'offline';

    protected $table = 'user_presences';

    protected $fillable = [
        'user_id',
        'manual_status',
        'last_heartbeat_at',
        'last_seen_at',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function effectiveStatus(): string
    {
        $manual = strtolower(trim((string) $this->manual_status));
        if (in_array($manual, [self::STATUS_AWAY, self::STATUS_BUSY, self::STATUS_OFFLINE], true)) {
            return $manual;
        }

        if (!$this->last_heartbeat_at) {
            return $manual === self::STATUS_ONLINE ? self::STATUS_ONLINE : self::STATUS_OFFLINE;
        }

        $seconds = $this->last_heartbeat_at->diffInSeconds(now());
        if ($seconds <= 90) {
            return self::STATUS_ONLINE;
        }

        if ($seconds <= 600) {
            return self::STATUS_AWAY;
        }

        return self::STATUS_OFFLINE;
    }

    public function manualStatusOrAuto(): string
    {
        return $this->manual_status ?: self::STATUS_AUTO;
    }

    public static function allowedManualStatuses(): array
    {
        return [
            self::STATUS_AUTO,
            self::STATUS_ONLINE,
            self::STATUS_AWAY,
            self::STATUS_BUSY,
            self::STATUS_OFFLINE,
        ];
    }

    public static function availabilityStatuses(): array
    {
        return [
            self::STATUS_ONLINE,
            self::STATUS_AWAY,
        ];
    }

    public static function statusMapForUsers(iterable $userIds): array
    {
        $ids = collect($userIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return static::query()
            ->whereIn('user_id', $ids)
            ->get()
            ->mapWithKeys(fn (self $presence) => [$presence->user_id => $presence->effectiveStatus()])
            ->all();
    }
}
