<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'company_name',
        'brand_name',
        'contact_name',
        'job_title',
        'phone',
        'email',
        'address',
        'deadline',
        'account_executive',
        'note',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) {
            return 0;
        }
        $done = $this->tasks()->where('status', 'done')->count();
        return (int) round(($done / $total) * 100);
    }
}
