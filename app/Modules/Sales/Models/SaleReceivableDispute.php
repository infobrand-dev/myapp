<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReceivableDispute extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CREDIT_MEMO = 'converted_credit_memo';
    public const STATUS_WRITE_OFF = 'converted_write_off';

    public const OUTCOME_CONTINUE_PAYMENT = 'continue_payment';
    public const OUTCOME_CLOSE_DISPUTE = 'close_dispute';
    public const OUTCOME_CREDIT_MEMO = 'credit_memo';
    public const OUTCOME_WRITE_OFF = 'write_off';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'sale_id',
        'dispute_number',
        'dispute_date',
        'amount',
        'status',
        'reason',
        'outcome_type',
        'notes',
        'resolution_note',
        'resolved_at',
        'created_by',
        'updated_by',
        'resolved_by',
        'meta',
    ];

    protected $casts = [
        'dispute_date' => 'date',
        'amount' => 'decimal:2',
        'resolved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CREDIT_MEMO => 'Converted to Credit Memo',
            self::STATUS_WRITE_OFF => 'Converted to Write-off',
        ];
    }

    public static function outcomeOptions(): array
    {
        return [
            self::OUTCOME_CONTINUE_PAYMENT => 'Lanjut bayar',
            self::OUTCOME_CLOSE_DISPUTE => 'Tutup dispute',
            self::OUTCOME_CREDIT_MEMO => 'Credit memo',
            self::OUTCOME_WRITE_OFF => 'Write-off',
        ];
    }
};
