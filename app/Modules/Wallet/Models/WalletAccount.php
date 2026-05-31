<?php

namespace App\Modules\Wallet\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'currency_code',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class)->latest('recorded_at');
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(WalletPayoutRequest::class)->latest('requested_at');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
