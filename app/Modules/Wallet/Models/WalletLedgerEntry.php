<?php

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    protected $fillable = [
        'tenant_id',
        'wallet_account_id',
        'source_type',
        'source_id',
        'entry_type',
        'state',
        'direction',
        'amount',
        'currency_code',
        'notes',
        'meta',
        'recorded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }
}
