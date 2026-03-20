<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnStatusLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'sale_return_id',
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

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
