<?php

namespace App\Modules\TaskManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_template_id',
        'title',
        'position',
    ];

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }
}
