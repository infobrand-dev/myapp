<?php

namespace App\Modules\PointOfSale\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashSessionMovement extends Model
{
    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';

    protected $table = 'pos_cash_session_movements';

    protected $fillable = [
        'tenant_id',
        'cash_session_id',
        'movement_type',
        'amount',
        'notes',
        'occurred_at',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosCashSession::class, 'cash_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->movement_type === self::TYPE_CASH_OUT ? -1 * $amount : $amount;
    }
}
