<?php

namespace App\Modules\Finance\Models;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FinanceAccount extends Model
{
    use NormalizesPgsqlBooleanAttributes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'account_type',
                'account_number',
                'is_active',
                'is_default',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('finance_account');
    }

    public const TYPE_CASH = 'cash';
    public const TYPE_BANK = 'bank';
    public const TYPE_EWALLET = 'ewallet';

    protected $table = 'finance_accounts';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'slug',
        'account_type',
        'account_number',
        'is_active',
        'is_default',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $column = $query->qualifyColumn('is_active');

        if (DB::connection($this->getConnectionName())->getDriverName() === 'pgsql') {
            return $query->whereRaw($column . ' is true');
        }

        return $query->where('is_active', true);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'finance_account_id')
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId());
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

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CASH => 'Cash',
            self::TYPE_BANK => 'Bank',
            self::TYPE_EWALLET => 'E-Wallet',
        ];
    }
}
