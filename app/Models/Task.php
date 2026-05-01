<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'created_at',
        'updated_date',
        'completed_at',
        'user_id',
        'description',
        'scheduled_at',
        'start_progress_at',
        'recurrence_id',
        'parent_task_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, (new User)->getForeignKey());
    }

    public function progressions(): HasMany
    {
        return $this->hasMany(TasksProgression::class);
    }

    public function flags(): BelongsToMany
    {
        return $this->belongsToMany(Flag::class)
            ->withTimestamps();
    }

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }
}
