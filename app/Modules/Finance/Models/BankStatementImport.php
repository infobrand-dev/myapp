<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'bank_reconciliation_id',
        'original_name',
        'stored_path',
        'file_hash',
        'imported_rows',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class)->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
