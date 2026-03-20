<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public const CODE_CASH = 'cash';
    public const CODE_BANK_TRANSFER = 'bank_transfer';
    public const CODE_DEBIT_CARD = 'debit_card';
    public const CODE_CREDIT_CARD = 'credit_card';
    public const CODE_EWALLET = 'ewallet';
    public const CODE_QRIS = 'qris';
    public const CODE_MANUAL = 'manual';

    public const TYPE_CASH = 'cash';
    public const TYPE_BANK_TRANSFER = 'bank_transfer';
    public const TYPE_DEBIT_CARD = 'debit_card';
    public const TYPE_CREDIT_CARD = 'credit_card';
    public const TYPE_EWALLET = 'ewallet';
    public const TYPE_QRIS = 'qris';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'tenant_id',
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

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }

    public static function salesInputOptions(): array
    {
        return [
            self::CODE_CASH,
            self::CODE_BANK_TRANSFER,
            'card',
            self::CODE_EWALLET,
            self::CODE_QRIS,
            'other',
        ];
    }

    public static function salesInputMap(): array
    {
        return [
            self::CODE_CASH => self::CODE_CASH,
            self::CODE_BANK_TRANSFER => self::CODE_BANK_TRANSFER,
            'card' => self::CODE_DEBIT_CARD,
            self::CODE_EWALLET => self::CODE_EWALLET,
            self::CODE_QRIS => self::CODE_QRIS,
            'other' => self::CODE_MANUAL,
        ];
    }

    public static function fromSalesInput(?string $value): string
    {
        $map = self::salesInputMap();

        return $map[$value ?: ''] ?? self::CODE_MANUAL;
    }
}
