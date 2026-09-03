<?php

namespace App\Repositories;

use App\Models\Prospect;
use Illuminate\Support\Collection;

readonly class ProspectRepository
{
    public function create(array $data): Prospect
    {
        return Prospect::create($data);
    }

    public function find(int $id): ?Prospect
    {
        return Prospect::find($id);
    }

    public function getList(int $directoryId): Collection
    {
        return Prospect::where('directory_id', $directoryId)->with('actions')->orderByDesc('created_at')->get();
    }

    public function getTreeList(int $directoryId): Collection
    {
        return Prospect::where('directory_id', $directoryId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function update(Prospect $prospect, array $data): Prospect
    {
        $prospect->fill($data);
        $prospect->save();
        return $prospect;
    }

    public function destroy(Prospect $prospect): void
    {
        $prospect->delete();
    }
}
