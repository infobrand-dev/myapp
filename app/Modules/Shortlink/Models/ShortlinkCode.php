<?php

namespace App\Modules\Shortlink\Models;

use Illuminate\Database\Eloquent\Model;

class ShortlinkCode extends Model
{
    protected $fillable = [
        'shortlink_id',
        'code',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
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
