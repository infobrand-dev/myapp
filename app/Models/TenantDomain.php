<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class TenantDomain extends Model
{
    public const PROVIDER_CLOUDFLARE_SAAS = 'cloudflare_saas';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PROVIDER = 'pending_provider';
    public const STATUS_PENDING_DNS = 'pending_dns';
    public const STATUS_PENDING_OWNERSHIP = 'pending_ownership';
    public const STATUS_PENDING_SSL = 'pending_ssl';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REMOVING = 'removing';
    public const STATUS_REMOVED = 'removed';

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'domain',
        'hostname',
        'hostname_type',
        'provider',
        'is_primary',
        'is_canonical',
        'status',
        'cloudflare_hostname_id',
        'cloudflare_ssl_status',
        'verification_method',
        'ownership_dns_name',
        'ownership_dns_value',
        'routing_record_type',
        'routing_record_name',
        'routing_record_value',
        'last_synced_at',
        'last_verified_at',
        'activation_checked_at',
        'last_error_code',
        'last_error_message',
        'meta',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_canonical' => 'boolean',
        'last_synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'activation_checked_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $domain): void {
            $hostname = $domain->normalizedHostname();
            $domain->hostname = $hostname;
            $domain->domain = $hostname;
            $domain->hostname_type = $domain->hostname_type ?: $domain->inferHostnameType();
            $domain->provider = $domain->provider ?: self::PROVIDER_CLOUDFLARE_SAAS;
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCanonical(Builder $query): Builder
    {
        return $query->where('is_canonical', true);
    }

    public function scopeHostname(Builder $query, string $hostname): Builder
    {
        $normalized = strtolower(trim($hostname));

        return $query->where(function (Builder $builder) use ($normalized): void {
            $builder->where('hostname', $normalized)
                ->orWhere('domain', $normalized);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TenantDomainEvent::class);
    }

    public function normalizedHostname(): string
    {
        return strtolower(trim((string) ($this->hostname ?: $this->domain)));
    }

    public function inferHostnameType(): string
    {
        return substr_count($this->normalizedHostname(), '.') <= 1 ? 'apex' : 'subdomain';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_BLOCKED, self::STATUS_FAILED, self::STATUS_REMOVED], true);
    }

    public function markFailure(string $code, string $message): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'last_error_code' => $code,
            'last_error_message' => mb_substr($message, 0, 4000),
            'last_synced_at' => now(),
        ])->save();
    }
}
