<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'website_url',
        'brief',
        'from_label',
        'default_reply_to_email',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function directories(): HasMany
    {
        return $this->hasMany(Directory::class);
    }

    public function prospects(): HasManyThrough
    {
        return $this->hasManyThrough(Prospect::class, Directory::class);
    }
}
