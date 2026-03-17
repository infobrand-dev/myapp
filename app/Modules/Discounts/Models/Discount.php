<?php

namespace App\Modules\Discounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';
    public const TYPE_FREE_ITEM = 'free_item';
    public const TYPE_BUNDLE = 'bundle';

    public const SCOPE_INVOICE = 'invoice';
    public const SCOPE_ITEM = 'item';

    protected $fillable = [
        'internal_name',
        'public_label',
        'code',
        'description',
        'discount_type',
        'application_scope',
        'currency_code',
        'priority',
        'sequence',
        'is_active',
        'is_archived',
        'is_voucher_required',
        'is_manual_only',
        'is_override_allowed',
        'stack_mode',
        'combination_mode',
        'usage_limit',
        'usage_limit_per_customer',
        'max_discount_amount',
        'starts_at',
        'ends_at',
        'schedule_json',
        'rule_payload',
        'meta',
        'created_by',
        'updated_by',
        'archived_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'sequence' => 'integer',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'is_voucher_required' => 'boolean',
        'is_manual_only' => 'boolean',
        'is_override_allowed' => 'boolean',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'archived_at' => 'datetime',
        'schedule_json' => 'array',
        'rule_payload' => 'array',
        'meta' => 'array',
    ];

    protected $appends = ['status_view'];

    public function targets(): HasMany
    {
        return $this->hasMany(DiscountTarget::class)->orderBy('sort_order');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(DiscountCondition::class)->orderBy('sort_order');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(DiscountVoucher::class)->orderBy('code');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class)->latest('evaluated_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getStatusViewAttribute(): string
    {
        if ($this->is_archived) {
            return 'archived';
        }

        if (!$this->is_active) {
            return 'inactive';
        }

        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}
