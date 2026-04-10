<?php

namespace App\Modules\Finance\Models;

use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FinanceCategory extends Model
{
    use NormalizesPgsqlBooleanAttributes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'transaction_type',
                'is_active',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('finance_category');
    }

    public const TYPE_CASH_IN = 'cash_in';
    public const TYPE_CASH_OUT = 'cash_out';
    public const TYPE_EXPENSE = 'expense';

    protected $table = 'finance_categories';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'slug',
        'transaction_type',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        $relation = $this->hasMany(FinanceTransaction::class, 'finance_category_id')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());

        return BranchContext::applyScope($relation);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->firstOrFail();
    }
}
