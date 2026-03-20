<?php

namespace App\Modules\Sales\Models;

use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SaleReturn extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_CANCELLED = 'cancelled';

    public const INVENTORY_PENDING = 'pending';
    public const INVENTORY_COMPLETED = 'completed';
    public const INVENTORY_SKIPPED = 'skipped';
    public const INVENTORY_FAILED = 'failed';

    public const REFUND_NOT_REQUIRED = 'not_required';
    public const REFUND_PENDING = 'pending';
    public const REFUND_PARTIAL = 'partial';
    public const REFUND_REFUNDED = 'refunded';
    public const REFUND_SKIPPED = 'skipped';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'return_number',
        'sale_id',
        'sale_number_snapshot',
        'contact_id',
        'customer_name_snapshot',
        'customer_email_snapshot',
        'customer_phone_snapshot',
        'customer_address_snapshot',
        'customer_snapshot',
        'status',
        'inventory_status',
        'refund_status',
        'return_date',
        'finalized_at',
        'cancelled_at',
        'reason',
        'notes',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'refunded_total',
        'refund_balance',
        'refund_required',
        'inventory_restock_required',
        'inventory_location_id',
        'currency_code',
        'totals_snapshot',
        'integration_snapshot',
        'meta',
        'created_by',
        'updated_by',
        'finalized_by',
        'cancelled_by',
    ];

    protected $casts = [
        'customer_snapshot' => 'array',
        'return_date' => 'datetime',
        'finalized_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'refunded_total' => 'decimal:2',
        'refund_balance' => 'decimal:2',
        'refund_required' => 'boolean',
        'inventory_restock_required' => 'boolean',
        'totals_snapshot' => 'array',
        'integration_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class)->orderBy('line_no');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SaleReturnStatusLog::class)->latest();
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'payable');
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

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->firstOrFail();
    }
}
