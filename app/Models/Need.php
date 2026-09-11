<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Need extends Model
{
    use HasFactory;

    /**
     * The pipeline every team gets the first time it fetches its stage
     * list with none yet (brand new teams) — see
     * NeedStageService::seedDefaults(). Not used for validation anymore;
     * stages are real per-team data now.
     */
    public const DEFAULT_SEED = [
        ['label' => 'Before Dev Pipeline', 'stages' => [
            ['label' => 'Discovery', 'color' => '#9ca3af'],
            ['label' => 'Documented', 'color' => '#818cf8'],
            ['label' => 'Jira ticket created', 'color' => '#60a5fa'],
            ['label' => 'Validated with business', 'color' => '#22d3ee'],
        ]],
        ['label' => 'In Dev Pipeline', 'stages' => [
            ['label' => 'Grooming', 'color' => '#c084fc'],
            ['label' => 'To do', 'color' => '#fbbf24'],
            ['label' => 'Dev', 'color' => '#fb923c'],
            ['label' => 'QA', 'color' => '#f472b6'],
        ]],
        ['label' => 'After Dev Pipeline', 'stages' => [
            ['label' => 'Coming soon', 'color' => '#2dd4bf'],
            ['label' => 'In production', 'color' => '#4ade80'],
        ]],
    ];

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'confluence_url',
        'jira_key',
        'jira_url',
        'business_owner',
        'need_stage_id',
        'position',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(NeedStage::class, 'need_stage_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(NeedActivity::class);
    }
}
