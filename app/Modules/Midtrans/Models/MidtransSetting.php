<?php

namespace App\Modules\Midtrans\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class MidtransSetting extends Model
{
    protected $table = 'midtrans_settings';

    /**
     * All payment method codes available via Midtrans Snap.
     * Admin activates these in the Midtrans Dashboard first, then mirrors selection here.
     */
    public const AVAILABLE_PAYMENT_METHODS = [
        'credit_card'     => 'Kartu Kredit / Debit',
        'bca_va'          => 'BCA Virtual Account',
        'bni_va'          => 'BNI Virtual Account',
        'bri_va'          => 'BRI Virtual Account',
        'permata_va'      => 'Permata Virtual Account',
        'other_va'        => 'Virtual Account Lainnya (Mandiri, Maybank, dll)',
        'gopay'           => 'GoPay',
        'shopeepay'       => 'ShopeePay',
        'qris'            => 'QRIS',
        'alfamart'        => 'Alfamart',
        'indomaret'       => 'Indomaret',
        'akulaku'         => 'Akulaku',
        'kredivo'         => 'Kredivo',
        'cimb_clicks'     => 'CIMB Clicks',
        'danamon_online'  => 'Danamon Online',
    ];

    protected $fillable = [
        'tenant_id',
        'environment',
        'server_key',
        'client_key',
        'merchant_id',
        'enabled_payments',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'enabled_payments' => 'array',
    ];

    /**
     * Encrypted fields — stored encrypted, retrieved decrypted.
     */
    protected $encrypted = ['server_key', 'client_key'];

    public function getServerKeyAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value; // fallback for non-encrypted legacy values
        }
    }

    public function setServerKeyAttribute(?string $value): void
    {
        $this->attributes['server_key'] = $value ? encrypt($value) : null;
    }

    public function getClientKeyAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function setClientKeyAttribute(?string $value): void
    {
        $this->attributes['client_key'] = $value ? encrypt($value) : null;
    }

    public static function forCurrentTenant(): ?self
    {
        return static::query()
            ->where('tenant_id', TenantContext::currentId())
            ->first();
    }

    public function getSnapBaseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    public function getApiBaseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }
}
