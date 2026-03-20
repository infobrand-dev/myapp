<?php

namespace App\Modules\PointOfSale\Models;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosCart extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HELD = 'held';
    public const STATUS_CHECKING_OUT = 'checking_out';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'uuid',
        'status',
        'cashier_user_id',
        'pos_cash_session_id',
        'register_id',
        'contact_id',
        'customer_label',
        'currency_code',
        'notes',
        'item_count',
        'subtotal',
        'item_discount_total',
        'order_discount_total',
        'tax_total',
        'grand_total',
        'discount_snapshot',
        'meta',
        'held_at',
        'completed_at',
        'completed_sale_id',
    ];

    protected $casts = [
        'discount_snapshot' => 'array',
        'meta' => 'array',
        'held_at' => 'datetime',
        'completed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'item_discount_total' => 'decimal:2',
        'order_discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PosCartItem::class)->orderBy('line_no');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(PosCashSession::class, 'pos_cash_session_id');
    }
}
