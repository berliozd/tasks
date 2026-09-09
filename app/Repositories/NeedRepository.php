<?php

namespace App\Repositories;

use App\Models\Need;

readonly class NeedRepository
{
    public function create(array $data): Need
    {
        return Need::create($data);
    }

    public function find(int $id): ?Need
    {
        return Need::find($id);
    }

    public function update(Need $need, array $data): Need
    {
        $need->fill($data);
        $need->save();
        return $need;
    }

    public function destroy(Need $need): void
    {
        $need->delete();
    }
}
