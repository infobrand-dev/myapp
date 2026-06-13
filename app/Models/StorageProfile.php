<?php

namespace App\Models;

use App\Support\BooleanQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageProfile extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'code',
        'name',
        'driver',
        'visibility_scope',
        'is_active',
        'is_default',
        'weight',
        'priority',
        'failure_mode',
        'purposes',
        'bucket',
        'region',
        'endpoint',
        'url',
        'root_path',
        'access_key_id',
        'secret_access_key',
        'use_path_style_endpoint',
        'last_read_failed_at',
        'last_write_failed_at',
        'last_error_summary',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'purposes' => 'array',
        'use_path_style_endpoint' => 'boolean',
        'last_read_failed_at' => 'datetime',
        'last_write_failed_at' => 'datetime',
        'meta' => 'array',
        'access_key_id' => 'encrypted',
        'secret_access_key' => 'encrypted',
    ];

    public function storedFiles(): HasMany
    {
        return $this->hasMany(StoredFile::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        BooleanQuery::apply($query, 'is_active', true);

        return $query;
    }

    public function scopeDefault(Builder $query): Builder
    {
        BooleanQuery::apply($query, 'is_default', true);

        return $query;
    }

    public function supportsPurpose(?string $purpose): bool
    {
        $purposes = collect($this->purposes ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->values();

        if ($purposes->isEmpty() || $purpose === null || $purpose === '') {
            return true;
        }

        return $purposes->contains($purpose);
    }

    public function markWriteFailure(\Throwable $exception): void
    {
        $this->forceFill([
            'last_write_failed_at' => now(),
            'last_error_summary' => mb_substr($exception->getMessage(), 0, 1000),
        ])->save();
    }

    public function markReadFailure(\Throwable $exception): void
    {
        $this->forceFill([
            'last_read_failed_at' => now(),
            'last_error_summary' => mb_substr($exception->getMessage(), 0, 1000),
        ])->save();
    }

    /**
     * @param  Collection<int, self>  $profiles
     * @return Collection<int, self>
     */
    public static function ordered(Collection $profiles): Collection
    {
        return $profiles->sort(function (self $left, self $right): int {
            return [$left->priority, $left->id] <=> [$right->priority, $right->id];
        })->values();
    }
}
