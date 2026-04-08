<?php

namespace App\Modules\Shortlink\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Model;

class ShortlinkCode extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'tenant_id',
        'shortlink_id',
        'code',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function shortlink()
    {
        return $this->belongsTo(Shortlink::class);
    }

    public function clicks()
    {
        return $this->hasMany(ShortlinkClick::class, 'shortlink_code_id');
    }
}
