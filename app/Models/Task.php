<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, (new User)->getForeignKey());
    }

    public function progressions(): HasMany
    {
        return $this->hasMany(TasksProgression::class);
    }
}
