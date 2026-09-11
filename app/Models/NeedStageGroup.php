<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NeedStageGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'label',
        'position',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(NeedStage::class)->orderBy('position');
    }
}
