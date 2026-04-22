<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankStatementLine extends Model
{
    public const MATCH_STATUS_UNMATCHED = 'unmatched';
    public const MATCH_STATUS_SUGGESTED = 'suggested';
    public const MATCH_STATUS_MATCHED = 'matched';
    public const MATCH_STATUS_EXCEPTION = 'exception';
    public const MATCH_STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'bank_reconciliation_id',
        'bank_statement_import_id',
        'transaction_date',
        'direction',
        'amount',
        'reference_number',
        'description',
        'external_key',
        'match_status',
        'resolution_reason',
        'resolution_note',
        'suggested_reconcilable_type',
        'suggested_reconcilable_id',
        'match_score',
        'matched_reconcilable_type',
        'matched_reconcilable_id',
        'matched_at',
        'matched_by',
        'resolved_at',
        'resolved_by',
        'meta',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'matched_at' => 'datetime',
        'resolved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function suggestedReconcilable(): MorphTo
    {
        return $this->morphTo();
    }

    public function matchedReconcilable(): MorphTo
    {
        return $this->morphTo();
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public static function statusOptions(): array
    {
        return [
            self::MATCH_STATUS_UNMATCHED => 'Unmatched',
            self::MATCH_STATUS_SUGGESTED => 'Suggested',
            self::MATCH_STATUS_MATCHED => 'Matched',
            self::MATCH_STATUS_EXCEPTION => 'Exception',
            self::MATCH_STATUS_IGNORED => 'Ignored',
        ];
    }
}
