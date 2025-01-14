<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{

    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function __invoke(Request $request)
    {
        $todayTasks = $this->taskService->getTodayTasks();
        $lateTasks = $this->taskService->getLateTasks();
        $completedTodayTasks = $this->taskService->getCompletedTodayTasks();
        return Inertia::render(
            'Tasks/Tasks',
            [
                'tasks' =>$this->taskService->getAll()->toArray(),
                'todayTasks' => $todayTasks->toArray(),
                'lateTasks' => $lateTasks->toArray(),
                'completedTodayTasks' => $completedTodayTasks->toArray()
            ]
        );
    }
}
