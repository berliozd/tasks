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
        return ProspectAction::where('prospect_id', $prospectId)->orderByDesc('scheduled_at')->get();
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
