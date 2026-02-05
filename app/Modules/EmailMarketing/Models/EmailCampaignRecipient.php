<?php

namespace App\Modules\EmailMarketing\Models;

use App\Modules\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'recipient_name',
        'recipient_email',
        'tracking_token',
        'delivery_status',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'replied_at',
        'bounced_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'replied_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
