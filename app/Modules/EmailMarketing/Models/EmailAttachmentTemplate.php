<?php

namespace App\Modules\EmailMarketing\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailAttachmentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'filename',
        'html',
        'mime',
        'created_by',
        'paper_size',
    ];

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(EmailCampaign::class, 'email_attachment_campaign', 'template_id', 'campaign_id')
            ->where('email_campaigns.tenant_id', TenantContext::currentId())
            ->wherePivot('tenant_id', TenantContext::currentId());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
