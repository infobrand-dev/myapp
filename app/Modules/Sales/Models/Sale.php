<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_REFUNDED = 'refunded';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_POS = 'pos';
    public const SOURCE_ONLINE = 'online';
    public const SOURCE_API = 'api';

    protected $fillable = [
        'sale_number',
        'external_reference',
        'contact_id',
        'customer_name_snapshot',
        'customer_email_snapshot',
        'customer_phone_snapshot',
        'customer_address_snapshot',
        'customer_snapshot',
        'status',
        'payment_status',
        'source',
        'transaction_date',
        'finalized_at',
        'voided_at',
        'cancelled_at',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'currency_code',
        'notes',
        'void_reason',
        'totals_snapshot',
        'meta',
        'created_by',
        'updated_by',
        'finalized_by',
        'voided_by',
        'cancelled_by',
    ];

    protected $casts = [
        'customer_snapshot' => 'array',
        'transaction_date' => 'datetime',
        'finalized_at' => 'datetime',
        'voided_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'totals_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class)->orderBy('line_no');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SaleStatusHistory::class)->latest();
    }

    public function voidLogs(): HasMany
    {
        return $this->hasMany(SaleVoidLog::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
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

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }
}
