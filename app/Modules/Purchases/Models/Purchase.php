<?php

namespace App\Modules\Purchases\Models;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PARTIAL_RECEIVED = 'partial_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_VOIDED = 'voided';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_OVERPAID = 'overpaid';

    protected $fillable = [
        'tenant_id',
        'purchase_number',
        'contact_id',
        'supplier_name_snapshot',
        'supplier_email_snapshot',
        'supplier_phone_snapshot',
        'supplier_address_snapshot',
        'supplier_snapshot',
        'supplier_reference',
        'supplier_invoice_number',
        'supplier_notes',
        'status',
        'payment_status',
        'purchase_date',
        'confirmed_at',
        'cancelled_at',
        'voided_at',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'received_total_qty',
        'paid_total',
        'balance_due',
        'currency_code',
        'notes',
        'internal_notes',
        'void_reason',
        'totals_snapshot',
        'integration_snapshot',
        'meta',
        'created_by',
        'updated_by',
        'confirmed_by',
        'voided_by',
        'cancelled_by',
    ];

    protected $casts = [
        'supplier_snapshot' => 'array',
        'purchase_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'received_total_qty' => 'decimal:4',
        'paid_total' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'totals_snapshot' => 'array',
        'integration_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class)->orderBy('line_no');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class)->latest('receipt_date');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PurchaseStatusHistory::class)->latest();
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'payable');
    }

    public function voidLogs(): HasMany
    {
        return $this->hasMany(PurchaseVoidLog::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmedLike(): bool
    {
        return in_array($this->status, [
            self::STATUS_CONFIRMED,
            self::STATUS_PARTIAL_RECEIVED,
            self::STATUS_RECEIVED,
        ], true);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
