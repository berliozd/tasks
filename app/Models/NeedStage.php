<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeedStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'need_stage_group_id',
        'label',
        'color',
        'position',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(NeedStageGroup::class, 'need_stage_group_id');
    }
}
