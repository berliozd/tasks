<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

readonly class TeamFeatureService
{
    public function getDisabled(Team $team): array
    {
        return $team->disabled_features ?? [];
    }

    public function update(User $user, Team $team, array $disabled): array
    {
        Gate::forUser($user)->authorize('update', $team);

        $disabled = array_values(array_intersect($disabled, Team::FEATURES));

        $team->disabled_features = $disabled;
        $team->save();

        return $disabled;
    }
}
