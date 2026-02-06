<?php

namespace App\Modules\EmailMarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailAttachmentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'filename',
        'html',
        'mime',
        'created_by',
    ];

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(EmailCampaign::class, 'email_attachment_campaign', 'template_id', 'campaign_id');
    }
}
