<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Billable;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;
    use Billable;

    /**
     * The features a team admin can individually disable for their team.
     */
    public const FEATURES = ['tasks', 'needs', 'prospection', 'documents'];

    /**
     * The subset of FEATURES that also require an active Pro subscription,
     * on top of not being manually disabled — see hasFeatureEnabled().
     */
    public const PAID_FEATURES = ['prospection', 'documents', 'needs'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = ['is_pro'];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'disabled_features' => 'array',
        ];
    }

    public function hasFeatureEnabled(string $feature): bool
    {
        if (in_array($feature, $this->disabled_features ?? [], true)) {
            return false;
        }

        if (in_array($feature, self::PAID_FEATURES, true) && !$this->subscribed()) {
            return false;
        }

        return true;
    }

    public function getIsProAttribute(): bool
    {
        return $this->subscribed();
    }
}
