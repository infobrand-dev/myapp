<?php

namespace App\Modules\Shortlink\Models;

use Illuminate\Database\Eloquent\Model;

class ShortlinkClick extends Model
{
    protected $fillable = [
        'shortlink_id',
        'shortlink_code_id',
        'code_used',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referer',
        'user_agent',
        'ip_address',
        'query',
    ];

    public function shortlink()
    {
        return $this->belongsTo(Shortlink::class);
    }

    public function code()
    {
        return $this->belongsTo(ShortlinkCode::class, 'shortlink_code_id');
    }
}
