<?php

namespace App\Modules\Shortlink\Models;

use App\Support\BooleanQuery;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class Shortlink extends Model
{
    use NormalizesPgsqlBooleanAttributes;

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
        return BooleanQuery::apply(
            $this->hasOne(ShortlinkCode::class),
            'is_primary'
        );
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
            ->tap(fn ($query) => BooleanQuery::apply($query, 'is_active'))
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
