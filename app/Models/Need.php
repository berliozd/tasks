<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Need extends Model
{
    use HasFactory;

    public const STAGES = [
        'discovery', 'documented', 'jira_created', 'validated', 'grooming',
        'todo', 'dev', 'qa', 'soon', 'prod',
    ];

    public const STAGE_LABELS = [
        'discovery' => 'Discovery',
        'documented' => 'Documented',
        'jira_created' => 'Jira ticket created',
        'validated' => 'Validated with business',
        'grooming' => 'Grooming',
        'todo' => 'To do',
        'dev' => 'Dev',
        'qa' => 'QA',
        'soon' => 'Coming soon',
        'prod' => 'In production',
    ];

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'confluence_url',
        'jira_key',
        'jira_url',
        'business_owner',
        'stage',
        'position',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(NeedActivity::class);
    }
}
