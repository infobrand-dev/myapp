<?php

namespace App\Modules\Sales\Models;

use App\Models\AccountingJournal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReceivableAdjustment extends Model
{
    public const TYPE_CREDIT_MEMO = 'credit_memo';
    public const TYPE_WRITE_OFF = 'write_off';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'sale_id',
        'journal_id',
        'adjustment_number',
        'adjustment_type',
        'adjustment_date',
        'amount',
        'status',
        'reason',
        'notes',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'adjustment_date' => 'datetime',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(AccountingJournal::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
