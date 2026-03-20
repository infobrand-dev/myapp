<?php

namespace App\Modules\Inventory\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpening extends Model
{
    protected $table = 'inventory_stock_openings';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'code',
        'inventory_location_id',
        'opening_date',
        'status',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
        'meta',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpeningItem::class, 'opening_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
