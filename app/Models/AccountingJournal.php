<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingJournal extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'entry_type',
        'source_type',
        'source_id',
        'journal_number',
        'entry_date',
        'status',
        'description',
        'reversal_of_journal_id',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'meta' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingJournalLine::class, 'journal_id')->orderBy('line_no');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_journal_id');
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
