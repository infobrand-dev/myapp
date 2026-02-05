<?php

namespace App\Modules\EmailMarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'status',
        'body_html',
        'started_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class, 'campaign_id');
    }

    public function getRecipientCountAttribute(): int
    {
        return $this->recipients->count();
    }

    public function metrics(): array
    {
        $total = max(1, $this->recipients->count());

        $counts = [
            'delivered' => $this->recipients->where('delivery_status', 'delivered')->count(),
            'opened' => $this->recipients->whereNotNull('opened_at')->count(),
            'bounced' => $this->recipients->where('delivery_status', 'bounced')->count(),
            'clicked' => $this->recipients->whereNotNull('clicked_at')->count(),
            'replied' => $this->recipients->whereNotNull('replied_at')->count(),
        ];

        return collect($counts)
            ->map(fn (int $count) => [
                'count' => $count,
                'percent' => round(($count / $total) * 100, 2),
            ])
            ->all();
    }
}
