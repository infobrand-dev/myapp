<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankReconciliationItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'bank_reconciliation_id',
        'reconcilable_type',
        'reconcilable_id',
        'cleared_date',
        'cleared_amount',
        'status',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cleared_date' => 'date',
        'cleared_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function reconcilable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
