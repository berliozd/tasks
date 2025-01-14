<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository
{

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function find(int $id)
    {
        return Task::find($id);
    }

    public function update(Task $task, array $data): Task
    {
        $task->fill($data);
        $task->save();
        return $task;
    }

    public function destroy(Task $task): void
    {
        $task->delete();
    }
}