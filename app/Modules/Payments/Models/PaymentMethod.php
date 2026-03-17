<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public const TYPE_CASH = 'cash';
    public const TYPE_BANK_TRANSFER = 'bank_transfer';
    public const TYPE_DEBIT_CARD = 'debit_card';
    public const TYPE_CREDIT_CARD = 'credit_card';
    public const TYPE_EWALLET = 'ewallet';
    public const TYPE_QRIS = 'qris';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'code',
        'name',
        'type',
        'requires_reference',
        'is_active',
        'is_system',
        'sort_order',
        'config',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
