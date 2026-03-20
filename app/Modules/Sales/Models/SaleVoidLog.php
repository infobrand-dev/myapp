<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleVoidLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'sale_id',
        'status_before',
        'reason',
        'snapshot',
        'actor_id',
    ];

    protected $casts = [
        'snapshot' => 'array',
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
