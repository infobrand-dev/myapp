<?php

namespace App\Modules\Tripay\Models;

use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TripayTransaction extends Model
{
    protected $table = 'tripay_transactions';

    public const STATUS_UNPAID = 'UNPAID';
    public const STATUS_PAID = 'PAID';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'merchant_reference',
        'tripay_reference',
        'checkout_url',
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
        return 'TRIPAY-' . $tenantId . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_EXPIRED, self::STATUS_FAILED], true);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'text-bg-green',
            self::STATUS_UNPAID => 'text-bg-yellow',
            self::STATUS_EXPIRED, self::STATUS_FAILED => 'text-bg-red',
            default => 'text-bg-secondary',
        };
    }
}
