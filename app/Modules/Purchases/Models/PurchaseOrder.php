<?php

namespace App\Modules\Purchases\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use LogsActivity;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'contact_id',
                'status',
                'order_date',
                'expected_receive_date',
                'subtotal',
                'discount_total',
                'tax_total',
                'landed_cost_total',
                'grand_total',
                'currency_code',
                'notes',
                'internal_notes',
                'converted_purchase_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('purchase_order');
    }

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'order_number',
        'contact_id',
        'supplier_name_snapshot',
        'supplier_email_snapshot',
        'supplier_phone_snapshot',
        'supplier_address_snapshot',
        'supplier_snapshot',
        'status',
        'order_date',
        'expected_receive_date',
        'sent_at',
        'approved_at',
        'rejected_at',
        'converted_at',
        'subtotal',
        'discount_total',
        'tax_total',
        'landed_cost_total',
        'grand_total',
        'currency_code',
        'notes',
        'internal_notes',
        'totals_snapshot',
        'meta',
        'converted_purchase_id',
        'created_by',
        'updated_by',
        'approved_by',
        'converted_by',
    ];

    protected $casts = [
        'supplier_snapshot' => 'array',
        'order_date' => 'datetime',
        'expected_receive_date' => 'date',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'landed_cost_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'totals_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('line_no');
    }

    public function convertedPurchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'converted_purchase_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function canTransitionTo(string $status): bool
    {
        $transitions = [
            self::STATUS_DRAFT => [self::STATUS_SENT, self::STATUS_REJECTED],
            self::STATUS_SENT => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [],
            self::STATUS_REJECTED => [],
            self::STATUS_CONVERTED => [],
        ];

        return in_array($status, $transitions[$this->status] ?? [], true);
    }

    public function canConvert(bool $requiresApproval = true): bool
    {
        if ($this->isConverted()) {
            return false;
        }

        if ($requiresApproval) {
            return $this->status === self::STATUS_APPROVED;
        }

        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_APPROVED], true);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return BranchContext::applyScope(
            $this->where($field ?? $this->getRouteKeyName(), $value)
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
        )->firstOrFail();
    }
}
