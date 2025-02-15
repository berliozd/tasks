<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Flag extends Model
{
    protected $fillable = [
        'name',
        'color',
        'created_at',
        'updated_at',
        'user_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, (new User)->getForeignKey());
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->withTimestamps();
    }
}
