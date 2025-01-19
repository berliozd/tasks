<?php

namespace App\Repositories;

use App\Models\TasksProgression;

readonly class TaskProgressionRepository
{

    public function create(array $data): TasksProgression
    {
        return TasksProgression::create($data);
    }

    public function find(int $id)
    {
        return TasksProgression::find($id);
    }
    public function getList(int $taskId)
    {
        return TasksProgression::where('task_id', $taskId)->get();
    }

    public function update(TasksProgression $tasksProgression, array $data): TasksProgression
    {
        $tasksProgression->fill($data);
        $tasksProgression->save();
        return $tasksProgression;
    }

    public function destroy(TasksProgression $task): void
    {
        $task->delete();
    }
}