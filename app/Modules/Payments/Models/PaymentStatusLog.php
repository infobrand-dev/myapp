<?php

namespace App\Modules\Payments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentStatusLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'payment_id',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
