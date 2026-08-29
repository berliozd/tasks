<?php

namespace App\Repositories;

use App\Models\Directory;

readonly class DirectoryRepository
{
    public function create(array $data): Directory
    {
        return Directory::create($data);
    }

    public function find(int $id): ?Directory
    {
        return Directory::find($id);
    }

    public function update(Directory $directory, array $data): Directory
    {
        $directory->fill($data);
        $directory->save();
        return $directory;
    }

    public function destroy(Directory $directory): void
    {
        $directory->delete();
    }
}
