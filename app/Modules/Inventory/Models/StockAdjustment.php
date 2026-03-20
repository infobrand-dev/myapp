<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    protected $table = 'inventory_stock_adjustments';

    protected $fillable = [
        'tenant_id',
        'code',
        'inventory_location_id',
        'adjustment_date',
        'status',
        'reason_code',
        'reason_text',
        'notes',
        'created_by',
        'finalized_by',
        'finalized_at',
        'meta',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'finalized_at' => 'datetime',
        'meta' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adjustment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
