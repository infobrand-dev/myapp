<?php

namespace App\Modules\PointOfSale\Models;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReceiptReprintLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'sale_id',
        'pos_cash_session_id',
        'reprint_sequence',
        'reason',
        'requested_by',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(PosCashSession::class, 'pos_cash_session_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
