<?php

namespace App\Modules\Payments\Models;

use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'payment_method_id',
                'amount',
                'currency_code',
                'paid_at',
                'status',
                'reconciliation_status',
                'source',
                'channel',
                'reference_number',
                'external_reference',
                'proof_file_path',
                'branch_id',
                'notes',
                'received_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payment');
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const RECONCILIATION_UNRECONCILED = 'unreconciled';
    public const RECONCILIATION_IN_REVIEW = 'in_review';
    public const RECONCILIATION_RECONCILED = 'reconciled';

    public const SOURCE_BACKOFFICE = 'backoffice';
    public const SOURCE_POS = 'pos';
    public const SOURCE_API = 'api';
    public const SOURCE_ONLINE = 'online';
    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'payment_number',
        'payment_method_id',
        'amount',
        'currency_code',
        'paid_at',
        'status',
        'reconciliation_status',
        'source',
        'channel',
        'reference_number',
        'external_reference',
        'proof_file_path',
        'branch_id',
        'pos_cash_session_id',
        'notes',
        'meta',
        'received_by',
        'created_by',
        'updated_by',
        'voided_by',
        'reconciled_by',
        'voided_at',
        'reconciled_at',
        'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'meta' => 'array',
        'voided_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class)->orderBy('allocation_order');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PaymentStatusLog::class)->latest();
    }

    public function voidLogs(): HasMany
    {
        return $this->hasMany(PaymentVoidLog::class)->latest();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(PosCashSession::class, 'pos_cash_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function hasProof(): bool
    {
        return !empty($this->proof_file_path);
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
