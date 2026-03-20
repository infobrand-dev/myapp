<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WATemplate extends Model
{
    use HasFactory;

    protected $table = 'wa_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'meta_name',
        'language',
        'category',
        'namespace',
        'meta_template_id',
        'body',
        'components',
        'variable_mappings',
        'status',
        'last_submitted_at',
        'last_submit_error',
    ];

    protected $casts = [
        'components' => 'array',
        'variable_mappings' => 'array',
        'last_submitted_at' => 'datetime',
    ];

    public function metaTemplateName(): string
    {
        return (string) ($this->meta_name ?: $this->name);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
