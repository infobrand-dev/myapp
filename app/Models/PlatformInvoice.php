<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform_plan_order_id',
        'subscription_plan_id',
        'invoice_number',
        'status',
        'amount',
        'currency',
        'issued_at',
        'due_at',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PlatformPlanOrder::class, 'platform_plan_order_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlatformInvoiceItem::class)->orderBy('id');
    }

    public function syncAmountFromItems(): void
    {
        $total = (float) $this->items()->sum('total_price');

        $this->forceFill([
            'amount' => $total,
        ])->save();
    }
}
