<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingJournalLine extends Model
{
    protected $fillable = [
        'journal_id',
        'tenant_id',
        'company_id',
        'branch_id',
        'line_no',
        'account_code',
        'account_name',
        'debit',
        'credit',
        'meta',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'meta' => 'array',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(AccountingJournal::class, 'journal_id');
    }
}
