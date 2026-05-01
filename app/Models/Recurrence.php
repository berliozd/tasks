<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recurrence extends Model
{
    protected $fillable = [
        'code',
        'label',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

