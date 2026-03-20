<?php

namespace App\Modules\Purchases\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseStatusHistory extends Model
{
    protected $fillable = [
        'tenant_id',
        'purchase_id',
        'from_status',
        'to_status',
        'event',
        'reason',
        'meta',
        'actor_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
