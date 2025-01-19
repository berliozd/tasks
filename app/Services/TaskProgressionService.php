<?php

namespace App\Services;

use App\Models\TasksProgression;
use App\Repositories\TaskProgressionRepository;

readonly class TaskProgressionService
{

    public function __construct(private TaskProgressionRepository $taskProgressionRepository)
    {
    }

    public function create(int $taskId)
    {
        return $this->taskProgressionRepository->create([
            'user_id' => auth()->user()->id,
            'task_id' => $taskId,
            'start_at' => now(),
        ]);
    }

    public function stop(int $taskId): ?TasksProgression
    {
        $activeTaskProgression = $this->getActiveTaskProgression($taskId);
        if ($activeTaskProgression) {
            $activeTaskProgression->end_at = now();
            $activeTaskProgression->save();
            return $activeTaskProgression;
        }
        return null;
    }

    public function getActiveTaskProgression(int $taskId)
    {
        $tasksProgressions = $this->taskProgressionRepository->getList($taskId);
        foreach ($tasksProgressions as $tasksProgression) {
            if ($tasksProgression->end_at === null) {
                return $tasksProgression;
            }
        }
        return null;
    }
}