<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'title',
        'status',
        'pic',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
