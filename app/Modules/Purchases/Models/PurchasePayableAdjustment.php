<?php

namespace App\Modules\Purchases\Models;

use App\Models\AccountingJournal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayableAdjustment extends Model
{
    public const TYPE_DEBIT_NOTE = 'debit_note';
    public const TYPE_WRITE_OFF = 'write_off';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'purchase_id',
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

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(AccountingJournal::class, 'journal_id');
    }
}
