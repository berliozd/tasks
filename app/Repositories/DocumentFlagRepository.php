<?php

namespace App\Repositories;

use App\Models\DocumentFlag;
use Illuminate\Support\Collection;

readonly class DocumentFlagRepository
{
    public function getList(int $teamId): Collection
    {
        return DocumentFlag::where('team_id', $teamId)->orderBy('name')->get();
    }

    public function findOrCreateByName(int $teamId, string $name): DocumentFlag
    {
        return DocumentFlag::firstOrCreate(
            ['team_id' => $teamId, 'name' => $name],
        );
    }
}
