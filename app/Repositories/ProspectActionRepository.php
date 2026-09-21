<?php

namespace App\Repositories;

use App\Models\ProspectAction;
use Illuminate\Support\Collection;

readonly class ProspectActionRepository
{
    public function create(array $data): ProspectAction
    {
        return ProspectAction::create($data);
    }

    public function find(int $id): ?ProspectAction
    {
        return ProspectAction::find($id);
    }

    public function getList(int $prospectId): Collection
    {
        return ProspectAction::where('prospect_id', $prospectId)
            ->with('emailTemplate:id,name')
            ->orderByDesc('scheduled_at')
            ->get();
    }

    /**
     * Actions currently queued for auto-send across a team, with the
     * product/directory/prospect they belong to, latest queue date first.
     * Fetches one extra row over $limit so the caller can tell whether more
     * are available without a separate count query.
     */
    public function getPlannedForTeam(int $teamId, int $limit): Collection
    {
        return ProspectAction::query()
            ->where('status', 'planned')
            ->whereHas('prospect.directory', fn ($query) => $query->where('team_id', $teamId))
            ->with([
                'prospect:id,name,email,directory_id',
                'prospect.directory:id,name,product_id',
                'prospect.directory.product:id,name',
            ])
            ->orderByDesc('scheduled_at')
            ->limit($limit + 1)
            ->get();
    }

    /**
     * The most recently carried-out actions across a team, latest first —
     * any status other than pending/planned, not just 'sent' (that only
     * covers email; logged actions like linkedin/call/meeting default to
     * 'done' and were previously invisible here). Same limit+1 convention
     * as getPlannedForTeam().
     */
    public function getLastSentForTeam(int $teamId, int $limit): Collection
    {
        return ProspectAction::query()
            ->whereNotIn('status', ['pending', 'planned'])
            ->whereHas('prospect.directory', fn ($query) => $query->where('team_id', $teamId))
            ->with([
                'prospect:id,name,email,directory_id',
                'prospect.directory:id,name,product_id',
                'prospect.directory.product:id,name',
            ])
            ->orderByDesc('updated_at')
            ->limit($limit + 1)
            ->get();
    }

    /**
     * Completed actions (not still pending/planned) within the given UTC
     * window, with their product preloaded — updated_at is when an action's
     * status last changed, which for a non-pending/planned action is
     * effectively when it was carried out. Returns full rows rather than
     * grouping by date here, since DATE() in SQL would bucket by the UTC
     * calendar day regardless of the user's own timezone; the caller
     * converts each timestamp to local time before bucketing.
     *
     * @return Collection<int, ProspectAction>
     */
    public function getCompletedActionsForTeam(int $teamId, \DateTimeInterface $from, \DateTimeInterface $until): Collection
    {
        return ProspectAction::query()
            ->whereNotIn('status', ['pending', 'planned'])
            ->whereHas('prospect.directory', fn ($query) => $query->where('team_id', $teamId))
            ->whereBetween('updated_at', [$from, $until])
            ->with('prospect.directory.product:id,name,color')
            ->get(['id', 'prospect_id', 'updated_at']);
    }

    public function update(ProspectAction $action, array $data): ProspectAction
    {
        $action->fill($data);
        $action->save();
        return $action;
    }

    public function destroy(ProspectAction $action): void
    {
        $action->delete();
    }
}
