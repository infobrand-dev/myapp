<?php

namespace App\Modules\Purchases\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseVoidLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'purchase_id',
        'status_before',
        'reason',
        'snapshot',
        'actor_id',
    ];

    protected $casts = [
        'snapshot' => 'array',
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
