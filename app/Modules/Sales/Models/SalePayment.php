<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'sale_id',
        'payment_method',
        'amount',
        'currency_code',
        'payment_date',
        'reference_number',
        'notes',
        'status',
        'meta',
        'created_by',
        'voided_by',
        'voided_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'voided_at' => 'datetime',
        'meta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }
}
