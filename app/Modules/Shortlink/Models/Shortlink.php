<?php

namespace App\Modules\Shortlink\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class Shortlink extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'destination_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function codes()
    {
        return $this->hasMany(ShortlinkCode::class);
    }

    public function primaryCode()
    {
        return $this->hasOne(ShortlinkCode::class)->where('is_primary', true);
    }

    public function clicks()
    {
        return $this->hasMany(ShortlinkClick::class);
    }

    public function getActiveCodeAttribute()
    {
        if ($this->relationLoaded('primaryCode') && $this->primaryCode) {
            return $this->primaryCode;
        }

        return $this->codes()
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
