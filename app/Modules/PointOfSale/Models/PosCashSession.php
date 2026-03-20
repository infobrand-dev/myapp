<?php

namespace App\Modules\PointOfSale\Models;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosCashSession extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $table = 'pos_cash_sessions';

    protected $fillable = [
        'tenant_id',
        'code',
        'cashier_user_id',
        'outlet_id',
        'status',
        'opening_cash_amount',
        'opening_note',
        'opened_at',
        'closing_cash_amount',
        'expected_cash_amount',
        'difference_amount',
        'closing_note',
        'closed_at',
        'closed_by',
        'meta',
    ];

    protected $casts = [
        'opening_cash_amount' => 'decimal:2',
        'closing_cash_amount' => 'decimal:2',
        'expected_cash_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function carts(): HasMany
    {
        return $this->hasMany(PosCart::class, 'pos_cash_session_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'pos_cash_session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'pos_cash_session_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashSessionMovement::class, 'cash_session_id')->latest('occurred_at');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
