<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TasksProgression extends Model
{
    protected $table = 'tasks_progressions';
    protected $fillable = [
        'task_id',
        'created_at',
        'updated_date',
        'start_at',
        'end_at'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Task::class, (new Task())->getForeignKey());
    }
}
