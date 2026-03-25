<?php

namespace App\Modules\Midtrans\Models;

use App\Modules\Payments\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MidtransTransaction extends Model
{
    protected $table = 'midtrans_transactions';

    /**
     * Whitelist of model classes that may be linked as payable.
     * Prevents arbitrary class injection via the snap-token endpoint.
     */
    public const ALLOWED_PAYABLE_TYPES = [
        \App\Modules\Sales\Models\Sale::class,
        \App\Modules\PointOfSale\Models\PosOrder::class,
        \App\Modules\Invoices\Models\Invoice::class,
    ];

    // Transaction statuses
    public const STATUS_PENDING    = 'pending';
    public const STATUS_CAPTURE    = 'capture';
    public const STATUS_SETTLEMENT = 'settlement';
    public const STATUS_DENY       = 'deny';
    public const STATUS_CANCEL     = 'cancel';
    public const STATUS_EXPIRE     = 'expire';
    public const STATUS_REFUND     = 'refund';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'order_id',
        'snap_token',
        'snap_redirect_url',
        'transaction_id',
        'payment_type',
        'gross_amount',
        'currency_code',
        'transaction_status',
        'fraud_status',
        'payable_type',
        'payable_id',
        'payment_id',
        'raw_notification',
        'customer_name',
        'customer_email',
        'customer_phone',
        'item_description',
        'settled_at',
        'expired_at',
        'created_by',
    ];

    protected $casts = [
        'gross_amount'     => 'decimal:2',
        'raw_notification' => 'array',
        'settled_at'       => 'datetime',
        'expired_at'       => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** Returns true when the transaction is considered paid/settled. */
    public function isSettled(): bool
    {
        if ($this->transaction_status === self::STATUS_SETTLEMENT) {
            return true;
        }
        if ($this->transaction_status === self::STATUS_CAPTURE && $this->fraud_status === 'accept') {
            return true;
        }
        return false;
    }

    public function isFailed(): bool
    {
        return in_array($this->transaction_status, [
            self::STATUS_DENY,
            self::STATUS_CANCEL,
            self::STATUS_EXPIRE,
        ], true);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->transaction_status) {
            self::STATUS_SETTLEMENT, self::STATUS_CAPTURE                       => 'text-bg-green',
            self::STATUS_PENDING                                                 => 'text-bg-yellow',
            self::STATUS_DENY, self::STATUS_CANCEL, self::STATUS_EXPIRE         => 'text-bg-red',
            self::STATUS_REFUND                                                  => 'text-bg-orange',
            default                                                              => 'text-bg-secondary',
        };
    }

    public function paymentTypeLabel(): string
    {
        return match ($this->payment_type) {
            'credit_card'           => 'Kartu Kredit',
            'bank_transfer'         => 'Transfer Bank',
            'echannel'              => 'Mandiri Bill',
            'bca_klikbca'           => 'KlikBCA',
            'bca_klikpay'           => 'BCA KlikPay',
            'bri_epay'              => 'BRI e-Pay',
            'cimb_clicks'           => 'CIMB Clicks',
            'danamon_online'        => 'Danamon Online',
            'gopay'                 => 'GoPay',
            'shopeepay'             => 'ShopeePay',
            'qris'                  => 'QRIS',
            'akulaku'               => 'Akulaku',
            'kredivo'               => 'Kredivo',
            'alfamart'              => 'Alfamart',
            'indomaret'             => 'Indomaret',
            default                 => ucwords(str_replace('_', ' ', (string) $this->payment_type)),
        };
    }

    /**
     * Generate a unique order_id for this tenant.
     */
    public static function generateOrderId(int $tenantId): string
    {
        return 'MDTRANS-' . $tenantId . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
