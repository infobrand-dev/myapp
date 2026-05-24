<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $fillable = [
        'tenant_id',
        'invited_by_user_id',
        'name',
        'email',
        'role_name',
        'company_ids',
        'branch_ids',
        'default_company_id',
        'default_branch_id',
        'token_hash',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'meta',
    ];

    protected $casts = [
        'company_ids' => 'array',
        'branch_ids' => 'array',
        'default_company_id' => 'integer',
        'default_branch_id' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
