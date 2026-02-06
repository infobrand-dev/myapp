<?php

namespace App\Modules\EmailMarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'type',
        'filename',
        'path',
        'mime',
        'size',
        'template_html',
        'created_by',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }
}
