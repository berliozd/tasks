<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use Illuminate\Support\Collection;

readonly class ProspectionSummaryService
{
    /**
     * Read-only counts for a team's prospection activity: how many directories
     * and prospects it has, how many prospects are marked won, and a
     * breakdown of logged actions by status.
     */
    public function getSummary(int $teamId): array
    {
        $directoryIds = Directory::where('team_id', $teamId)->pluck('id');

        $statusCounts = ProspectAction::whereHas(
            'prospect',
            fn ($query) => $query->whereIn('directory_id', $directoryIds)
        )
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'directories_count' => $directoryIds->count(),
            'prospects_count' => Prospect::whereIn('directory_id', $directoryIds)->count(),
            'won_count' => Prospect::whereIn('directory_id', $directoryIds)->where('won', true)->count(),
            'status_counts' => Collection::make(ProspectAction::STATUSES)
                ->mapWithKeys(fn (string $status) => [$status => (int) ($statusCounts[$status] ?? 0)])
                ->all(),
        ];
    }
}
