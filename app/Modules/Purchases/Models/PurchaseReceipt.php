<?php

namespace App\Modules\Purchases\Models;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReceipt extends Model
{
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'tenant_id',
        'purchase_id',
        'receipt_number',
        'inventory_location_id',
        'fingerprint',
        'status',
        'receipt_date',
        'notes',
        'total_received_qty',
        'integration_snapshot',
        'meta',
        'received_by',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'datetime',
        'total_received_qty' => 'decimal:4',
        'integration_snapshot' => 'array',
        'meta' => 'array',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class)->orderBy('id');
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
