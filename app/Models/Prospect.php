<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'directory_id',
        'name',
        'website',
        'email',
        'won',
        'is_excluded',
    ];

    protected $casts = [
        'won' => 'boolean',
        'is_excluded' => 'boolean',
    ];

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProspectAction::class);
    }
}
