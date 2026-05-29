<?php

namespace App\Modules\Xendit\Models;

use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class XenditTransaction extends Model
{
    protected $table = 'xendit_transactions';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_SETTLED = 'SETTLED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'external_reference',
        'invoice_id',
        'invoice_url',
        'status',
        'payment_method',
        'gross_amount',
        'currency_code',
        'payable_type',
        'payable_id',
        'payment_id',
        'raw_notification',
        'customer_name',
        'customer_email',
        'customer_phone',
        'description',
        'settled_at',
        'expired_at',
        'created_by',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'raw_notification' => 'array',
        'settled_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public static function generateReference(int $tenantId): string
    {
        return 'XENDIT-' . $tenantId . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_SETTLED], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_EXPIRED, self::STATUS_FAILED], true);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID, self::STATUS_SETTLED => 'text-bg-green',
            self::STATUS_PENDING => 'text-bg-yellow',
            self::STATUS_EXPIRED, self::STATUS_FAILED => 'text-bg-red',
            default => 'text-bg-secondary',
        };
    }

    public function maskedInvoiceId(): ?string
    {
        $value = (string) ($this->invoice_id ?? '');

        if ($value === '') {
            return null;
        }

        return Str::mask($value, '*', 6, max(mb_strlen($value) - 10, 0));
    }
}
