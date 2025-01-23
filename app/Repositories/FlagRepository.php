<?php

namespace App\Repositories;

use App\Models\Flag;

readonly class FlagRepository
{
    public function create(array $data): Flag
    {
        return Flag::create($data);
    }

    public function find(int $id)
    {
        return Flag::find($id);
    }

    public function update(Flag $flag, array $data): Flag
    {
        $flag->fill($data);
        $flag->save();
        return $flag;
    }

    public function destroy(Flag $flag): void
    {
        $flag->delete();
    }
}