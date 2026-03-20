<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleStatusHistory extends Model
{
    protected $fillable = [
        'tenant_id',
        'sale_id',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
