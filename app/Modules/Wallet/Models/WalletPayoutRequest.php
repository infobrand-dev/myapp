<?php

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPayoutRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'wallet_account_id',
        'amount',
        'currency_code',
        'status',
        'destination_snapshot',
        'notes',
        'meta',
        'requested_by',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'destination_snapshot' => 'array',
        'meta' => 'array',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }
}
